<?php

/**
 * Enhanced Router System with Smart Page Detection
 * File: core/Router.php
 * 
 * Features:
 * - Named routes with route() helper
 * - Smart page auto-detection
 * - JSON and form data handling
 * - File upload support
 * - No prefix needed
 * - Auto-finds pages in nested folders
 * - Performance-optimized with caching
 */

class Router
{
    private $routes = [];
    private $namedRoutes = [];
    private $middleware = [];
    private $baseTemplatesPath = '/templates/';
    private $basePagesPath = '/pages/';

    // Store parsed request data
    private $requestData = [];
    private $requestFiles = [];
    private $requestHeaders = [];
    private $contentType = '';

    // Performance cache
    private $pageCache = [];

    // Parameter patterns for validation
    private $patterns = [
        'id' => '[A-Z0-9]{8}',
        'uuid' => '[a-zA-Z0-9-]{36}',
        'string' => '[a-zA-Z]+',
        'alpha' => '[a-zA-Z]+',
        'alphanum' => '[a-zA-Z0-9]+',
        'slug' => '[a-zA-Z0-9-_]+',
        'number' => '[0-9]+',
        'year' => '[0-9]{4}',
        'month' => '[0-9]{1,2}',
        'day' => '[0-9]{1,2}',
        'code' => '[A-Z0-9]{6}',
        'token' => '[a-zA-Z0-9]{16}',
        'phone' => '[0-9-+]+',
        'any' => '.*'
    ];

    /**
     * Constructor - Initialize request parsing
     */
    public function __construct()
    {
        $this->parseRequest();
    }

    /**
     * Parse incoming request data
     */
    private function parseRequest()
    {
        $this->requestHeaders = getallheaders() ?: [];
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $this->contentType = strtolower(trim(explode(';', $this->contentType)[0]));

        $method = $this->getMethod();

        if ($method === 'GET') {
            $this->requestData = $_GET;
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->parseRequestBody();
        }

        if (!empty($_FILES)) {
            $this->requestFiles = $_FILES;
        }
    }

    /**
     * Parse request body for POST/PUT/PATCH/DELETE
     */
    private function parseRequestBody()
    {
        $rawInput = file_get_contents('php://input');

        switch ($this->contentType) {
            case 'application/json':
                $this->parseJsonData($rawInput);
                break;

            case 'application/x-www-form-urlencoded':
                $this->parseFormData();
                break;

            case 'multipart/form-data':
                $this->parseMultipartData();
                break;

            default:
                if ($this->isJsonString($rawInput)) {
                    $this->parseJsonData($rawInput);
                } else {
                    $this->parseFormData();
                }
                break;
        }
    }

    /**
     * Parse JSON data
     */
    private function parseJsonData($rawInput)
    {
        if (!empty($rawInput)) {
            $decoded = json_decode($rawInput, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->requestData = $decoded;
            } else {
                error_log("JSON parsing error: " . json_last_error_msg());
                $this->requestData = [];
            }
        }
    }

    /**
     * Parse form data
     */
    private function parseFormData()
    {
        $this->requestData = $_POST;
    }

    /**
     * Parse multipart form data
     */
    private function parseMultipartData()
    {
        $this->requestData = $_POST;
    }

    /**
     * Check if string is valid JSON
     */
    private function isJsonString($string)
    {
        if (empty($string)) return false;

        $trimmed = trim($string);
        return (
            (substr($trimmed, 0, 1) === '{' && substr($trimmed, -1) === '}') ||
            (substr($trimmed, 0, 1) === '[' && substr($trimmed, -1) === ']')
        );
    }

    /**
     * Get request data
     */
    public function getRequestData($key = null, $default = null)
    {
        if ($key === null) {
            return $this->requestData;
        }

        return $this->requestData[$key] ?? $default;
    }

    /**
     * Get uploaded files
     */
    public function getFiles($key = null)
    {
        if ($key === null) {
            return $this->requestFiles;
        }

        return $this->requestFiles[$key] ?? null;
    }

    /**
     * Get request headers
     */
    public function getHeaders($key = null)
    {
        if ($key === null) {
            return $this->requestHeaders;
        }

        foreach ($this->requestHeaders as $name => $value) {
            if (strtolower($name) === strtolower($key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get content type
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Check if request is JSON
     */
    public function isJson()
    {
        return $this->contentType === 'application/json';
    }

    /**
     * Check if request has files
     */
    public function hasFiles()
    {
        return !empty($this->requestFiles);
    }

    /**
     * Add a GET route
     */
    public function get($pattern, $handler, $middleware = [], $name = null)
    {
        return $this->addRoute('GET', $pattern, $handler, $middleware, $name);
    }

    /**
     * Add a POST route
     */
    public function post($pattern, $handler, $middleware = [], $name = null)
    {
        return $this->addRoute('POST', $pattern, $handler, $middleware, $name);
    }

    /**
     * Add a PUT route
     */
    public function put($pattern, $handler, $middleware = [], $name = null)
    {
        return $this->addRoute('PUT', $pattern, $handler, $middleware, $name);
    }

    /**
     * Add a DELETE route
     */
    public function delete($pattern, $handler, $middleware = [], $name = null)
    {
        return $this->addRoute('DELETE', $pattern, $handler, $middleware, $name);
    }

    /**
     * Add route for multiple methods
     */
    public function match($methods, $pattern, $handler, $middleware = [], $name = null)
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $pattern, $handler, $middleware, $name);
        }
        return $this;
    }

    /**
     * Add named route (fluent interface)
     */
    public function name($name)
    {
        if (!empty($this->routes)) {
            $lastIndex = count($this->routes) - 1;
            $this->routes[$lastIndex]['name'] = $name;
            $this->namedRoutes[$name] = $this->routes[$lastIndex];
        }
        return $this;
    }

    /**
     * Add route with method and optional name
     */
    private function addRoute($method, $pattern, $handler, $middleware = [], $name = null)
    {
        $route = [
            'method' => $method,
            'pattern' => $this->convertPattern($pattern),
            'original_pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
            'parameters' => $this->extractParameters($pattern),
            'name' => $name
        ];

        $this->routes[] = $route;

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        return $this;
    }

    /**
     * Convert route pattern to regex
     */
    private function convertPattern($pattern)
    {
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+):([a-zA-Z0-9_]+)\}/', function ($matches) {
            $type = $matches[2];

            if (isset($this->patterns[$type])) {
                return '(' . $this->patterns[$type] . ')';
            }

            if (is_numeric($type)) {
                return '([a-zA-Z0-9]{' . $type . '})';
            }

            return '([a-zA-Z0-9_-]+)';
        }, $pattern);

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $pattern);
        $pattern = str_replace('/', '\/', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Extract parameter names from pattern
     */
    private function extractParameters($pattern)
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)(?::([a-zA-Z0-9_]+))?\}/', $pattern, $matches);
        return $matches[1];
    }

    /**
     * Generate URL for named route
     */
    public function url($name, $parameters = [])
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Route '$name' not found");
        }

        $route = $this->namedRoutes[$name];
        $pattern = $route['original_pattern'];
        $url = $pattern;

        foreach ($parameters as $key => $value) {
            $url = preg_replace('/\{' . $key . '(?::[a-zA-Z0-9_]+)?\}/', $value, $url);
        }

        if (preg_match('/\{[^}]+\}/', $url)) {
            throw new Exception("Missing parameters for route '$name'");
        }

        return $url;
    }

    /**
     * Check if named route exists
     */
    public function hasRoute($name)
    {
        return isset($this->namedRoutes[$name]);
    }

    /**
     * Get all named routes
     */
    public function getNamedRoutes()
    {
        return $this->namedRoutes;
    }

    /**
     * Add middleware
     */
    public function middleware($name, $callback)
    {
        $this->middleware[$name] = $callback;
        return $this;
    }

    /**
     * Route the current request
     */
    public function route()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestUri = rtrim($requestUri, '/');

        if (empty($requestUri)) {
            $requestUri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                if (preg_match($route['pattern'], $requestUri, $matches)) {
                    array_shift($matches);

                    $params = [];
                    foreach ($route['parameters'] as $index => $paramName) {
                        if (isset($matches[$index])) {
                            $params[$paramName] = $matches[$index];
                        }
                    }

                    $allParams = array_merge($params, $this->requestData);

                    foreach ($route['middleware'] as $middlewareName) {
                        if (isset($this->middleware[$middlewareName])) {
                            $result = call_user_func($this->middleware[$middlewareName]);
                            if ($result === false) {
                                return;
                            }
                        }
                    }

                    return $this->executeHandler($route['handler'], $matches, $allParams);
                }
            }
        }

        $this->handle404();
    }

    /**
     * Execute route handler with smart auto-detection
     */
    private function executeHandler($handler, $params = [], $namedParams = [])
    {
        try {
            if (is_string($handler)) {
                
                // 1. Check for Class@method syntax (pages/)
                if (strpos($handler, '@') !== false) {
                    return $this->executeControllerOrPage($handler, $namedParams);
                }
                
                // 2. Check for view shorthand: "auth.signin"
                if (strpos($handler, '.') !== false) {
                    return $this->renderViewShorthand($handler, $namedParams);
                }
            }
            
            if (is_callable($handler)) {
                $result = call_user_func_array($handler, array_merge($params, [$namedParams]));
                
                if (is_string($result)) {
                    echo $result;
                    return;
                }
                
                return $result;
            }
            
            throw new Exception("Invalid handler: " . print_r($handler, true));
            
        } catch (Exception $e) {
            $this->handleRouterError($e);
        }
    }

    /**
     * Smart controller/page executor - auto-detects location
     */
    private function executeControllerOrPage($handler, $params = [])
    {
        try {
            list($className, $method) = explode('@', $handler);
            
            // Find page file in pages/ folder
            $pagePath = $this->findPageFile($className);
            
            if ($pagePath) {
                return $this->executeFile($pagePath, $className, $method, $params);
            }
            
            throw new Exception("Page not found: {$className}");
            
        } catch (Exception $e) {
            $this->handleRouterError($e);
        }
    }

    /**
     * Find page file - supports nested structure with caching
     */
    private function findPageFile($pageName)
    {
        // Check cache first
        if (isset($this->pageCache[$pageName])) {
            return $this->pageCache[$pageName];
        }
        
        $possiblePaths = [];
        
        // If contains slash, use direct path
        if (strpos($pageName, '/') !== false) {
            $possiblePaths[] = ROOT_PATH . $this->basePagesPath . $pageName . '.php';
        } else {
            // Common locations (fast check first)
            $possiblePaths[] = ROOT_PATH . $this->basePagesPath . $pageName . '.php';
            $possiblePaths[] = ROOT_PATH . $this->basePagesPath . 'auth/' . $pageName . '.php';
            $possiblePaths[] = ROOT_PATH . $this->basePagesPath . 'home/' . $pageName . '.php';
            $possiblePaths[] = ROOT_PATH . $this->basePagesPath . 'applications/' . $pageName . '.php';
            $possiblePaths[] = ROOT_PATH . $this->basePagesPath . 'profile/' . $pageName . '.php';
            $possiblePaths[] = ROOT_PATH . $this->basePagesPath . 'settings/' . $pageName . '.php';
        }
        
        // Check common paths first
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $this->pageCache[$pageName] = $path;
                return $path;
            }
        }
        
        // If not found in common paths, search recursively
        $foundPath = $this->searchPageRecursively($pageName);
        
        if ($foundPath) {
            $this->pageCache[$pageName] = $foundPath;
            return $foundPath;
        }
        
        return null;
    }

    /**
     * Recursively search for page file
     */
    private function searchPageRecursively($pageName, $dir = null)
    {
        if ($dir === null) {
            $dir = ROOT_PATH . $this->basePagesPath;
        }
        
        if (!is_dir($dir)) {
            return null;
        }
        
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'BasePage.php') {
                continue;
            }
            
            $filePath = $dir . '/' . $file;
            
            // If it's the file we're looking for
            if (is_file($filePath) && $file === $pageName . '.php') {
                return $filePath;
            }
            
            // If it's a directory, search recursively
            if (is_dir($filePath)) {
                $result = $this->searchPageRecursively($pageName, $filePath);
                if ($result) {
                    return $result;
                }
            }
        }
        
        return null;
    }

    /**
     * Execute file (controller or page)
     */
    private function executeFile($filePath, $className, $method, $params)
    {
        require_once $filePath;
        
        // Extract actual class name (remove path if present)
        $parts = explode('/', $className);
        $actualClassName = end($parts);
        
        if (!class_exists($actualClassName)) {
            throw new Exception("Class not found: {$actualClassName} in {$filePath}");
        }
        
        $instance = new $actualClassName();
        
        if (!method_exists($instance, $method)) {
            throw new Exception("Method {$method} not found in {$actualClassName}");
        }
        
        // Call method
        $result = call_user_func_array([$instance, $method], [$params]);
        
        // Handle result
        if (is_string($result)) {
            echo $result;
            return;
        }
        
        return $result;
    }

    /**
     * Render view using dot notation
     */
    private function renderViewShorthand($viewFile, $params = [])
    {
        try {
            if (function_exists('view')) {
                $content = view($viewFile, $params);
                echo $content;
                return;
            }

            return $this->renderView($viewFile, $params);
        } catch (Exception $e) {
            $this->handleViewError($e, $viewFile, $params);
        }
    }

    /**
     * Render view file
     */
    private function renderView($viewFile, $params = [])
    {
        try {
            $viewPath = str_replace('.', '/', $viewFile);
            $fullPath = ROOT_PATH . $this->baseTemplatesPath . $viewPath . '.php';

            if (!file_exists($fullPath)) {
                throw new Exception("View file not found: {$fullPath}");
            }

            foreach ($params as $key => $value) {
                $_GET[$key] = $value;
            }

            extract($params);

            include $fullPath;
        } catch (Exception $e) {
            $this->handleViewError($e, $viewFile, $params);
        }
    }

    /**
     * Handle router errors
     */
    private function handleRouterError($e)
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<h1>Router Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            error_log("Router Error: " . $e->getMessage());
            $this->handle500();
        }
    }

    /**
     * Handle view errors
     */
    private function handleViewError($e, $viewFile, $params = [])
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;margin:10px;border-radius:4px;'>";
            echo "<h3>Router View Error</h3>";
            echo "<p><strong>View:</strong> " . htmlspecialchars($viewFile) . "</p>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";

            if (!empty($params)) {
                echo "<p><strong>Parameters:</strong></p>";
                echo "<pre style='background:#fff;padding:10px;border-radius:3px;'>" . print_r($params, true) . "</pre>";
            }

            echo "<p><strong>Stack Trace:</strong></p>";
            echo "<pre style='background:#fff;padding:10px;border-radius:3px;font-size:12px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } else {
            error_log("Router View Error ({$viewFile}): " . $e->getMessage());
            $this->handle404();
        }
    }

    /**
     * Handle 404 errors
     */
    private function handle404()
    {
        http_response_code(404);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'Client Error',
                'message' => 'Endpoint not found',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_time' => time()
            ], JSON_PRETTY_PRINT);
            return;
        }

        $error404Path = ROOT_PATH . $this->baseTemplatesPath . 'error/404.php';
        if (file_exists($error404Path)) {
            include $error404Path;
            return;
        }

        echo "<!DOCTYPE html>
            <html>
            <head>
                <title>404 - Page Not Found</title>
                <meta charset='utf-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1'>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                    h1 { color: #dc3545; }
                    p { color: #6c757d; }
                    a { color: #007bff; text-decoration: none; }
                </style>
            </head>
            <body>
                <h1>404 - Page Not Found</h1>
                <p>The requested page could not be found.</p>
                <a href='/'>Return to Home</a>
            </body>
            </html>";
    }

    /**
     * Handle 500 errors
     */
    private function handle500()
    {
        http_response_code(500);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'Server Error',
                'message' => 'Internal server error',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_time' => time()
            ], JSON_PRETTY_PRINT);
            return;
        }

        $error500Path = ROOT_PATH . $this->baseTemplatesPath . 'error/500.php';
        if (file_exists($error500Path)) {
            include $error500Path;
            return;
        }

        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>500 - Internal Server Error</title>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                h1 { color: #dc3545; }
                p { color: #6c757d; }
                a { color: #007bff; text-decoration: none; }
            </style>
        </head>
        <body>
            <h1>500 - Internal Server Error</h1>
            <p>Something went wrong. Please try again later.</p>
            <a href='/'>Return to Home</a>
        </body>
        </html>";
    }

    /**
     * Check if current request is API
     */
    public function isApiRequest()
    {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }

    /**
     * Add parameter validation pattern
     */
    public function addPattern($name, $pattern)
    {
        $this->patterns[$name] = $pattern;
        return $this;
    }

    /**
     * Get all registered patterns
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    /**
     * Get request method
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
}

// ============== GLOBAL ROUTER INSTANCE ==============
$GLOBALS['router'] = null;

function getRouter()
{
    return $GLOBALS['router'];
}

function setRouter($router)
{
    $GLOBALS['router'] = $router;
}