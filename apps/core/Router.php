<?php
/**
 * Enhanced Router System for APP with POST Data Handling
 * File: core/router.php
 * 
 * Features:
 * - Named routes with route() helper
 * - URL generation with parameters
 * - JSON and form data handling for POST requests
 * - Request body parsing
 * - Content-Type detection
 * - File upload support
 * - Laravel-like syntax
 */

class Router {
    private $routes = [];
    private $namedRoutes = [];
    private $middleware = [];
    private $baseViewPath = 'views/';
    private $baseApiPath = 'api/v1/';
    
    // Store parsed request data
    private $requestData = [];
    private $requestFiles = [];
    private $requestHeaders = [];
    private $contentType = '';
    
    // Parameter patterns for easy validation
    private $patterns = [
        'id' => '[A-Z0-9]{8}',           // System ID: ABC12345
        'uuid' => '[a-zA-Z0-9-]{36}',    // UUID: 550e8400-e29b-41d4-a716-446655440000
        'string' => '[a-zA-Z]+',         // Letters only: signin, dashboard
        'alpha' => '[a-zA-Z]+',          // Same as string
        'alphanum' => '[a-zA-Z0-9]+',    // Letters and numbers
        'slug' => '[a-zA-Z0-9-_]+',      // URL-friendly: my-page, user_profile
        'number' => '[0-9]+',            // Numbers only: 123, 456
        'year' => '[0-9]{4}',            // Year: 2024
        'month' => '[0-9]{1,2}',         // Month: 1, 12
        'day' => '[0-9]{1,2}',           // Day: 1, 31
        'code' => '[A-Z0-9]{6}',         // 6-char code: ABC123
        'token' => '[a-zA-Z0-9]{16}',    // 16-char token
        'phone' => '[0-9-+]+',           // Phone: +60123456789
        'any' => '.*'                    // Any characters
    ];
    
    /**
     * Constructor - Initialize request parsing
     */
    public function __construct() {
        $this->parseRequest();
    }
    
    
    /**
     * Parse incoming request data
     */
    private function parseRequest() {
        // Get headers
        $this->requestHeaders = getallheaders() ?: [];
        
        // Determine content type
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $this->contentType = strtolower(trim(explode(';', $this->contentType)[0]));
        
        // Parse request data based on method and content type
        $method = $this->getMethod();
        
        if ($method === 'GET') {
            $this->requestData = $_GET;
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->parseRequestBody();
        }
        
        // Handle file uploads
        if (!empty($_FILES)) {
            $this->requestFiles = $_FILES;
        }
    }
    
    /**
     * Parse request body for POST/PUT/PATCH/DELETE
     */
    private function parseRequestBody() {
        $rawInput = file_get_contents('php://input');

            error_log("Content-Type: " . $this->contentType);
            error_log("Raw Input: " . $rawInput);
            error_log("POST data: " . print_r($_POST, true));
        
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
                // Try to detect JSON if content type is not set
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
    private function parseJsonData($rawInput) {
        if (!empty($rawInput)) {
            $decoded = json_decode($rawInput, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->requestData = $decoded;
            } else {
                // Log JSON parsing error
                error_log("JSON parsing error: " . json_last_error_msg());
                $this->requestData = [];
            }
        }
    }
    
    /**
     * Parse form data
     */
    private function parseFormData() {
        $this->requestData = $_POST;
    }
    
    /**
     * Parse multipart form data
     */
    private function parseMultipartData() {
        $this->requestData = $_POST;
        // Files are already in $_FILES
    }
    
    /**
     * Check if string is valid JSON
     */
    private function isJsonString($string) {
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
    public function getRequestData($key = null, $default = null) {
        if ($key === null) {
            return $this->requestData;
        }
        
        return $this->requestData[$key] ?? $default;
    }
    
    /**
     * Get uploaded files
     */
    public function getFiles($key = null) {
        if ($key === null) {
            return $this->requestFiles;
        }
        
        return $this->requestFiles[$key] ?? null;
    }
    
    /**
     * Get request headers
     */
    public function getHeaders($key = null) {
        if ($key === null) {
            return $this->requestHeaders;
        }
        
        // Case-insensitive header lookup
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
    public function getContentType() {
        return $this->contentType;
    }
    
    /**
     * Check if request is JSON
     */
    public function isJson() {
        return $this->contentType === 'application/json';
    }
    
    /**
     * Check if request has files
     */
    public function hasFiles() {
        return !empty($this->requestFiles);
    }
    
    /**
     * Add a GET route with optional name
     */
    public function get($pattern, $handler, $middleware = [], $name = null) {
        return $this->addRoute('GET', $pattern, $handler, $middleware, $name);
    }
    
    /**
     * Add a POST route with optional name
     */
    public function post($pattern, $handler, $middleware = [], $name = null) {
        return $this->addRoute('POST', $pattern, $handler, $middleware, $name);
    }
    
    /**
     * Add a PUT route with optional name
     */
    public function put($pattern, $handler, $middleware = [], $name = null) {
        return $this->addRoute('PUT', $pattern, $handler, $middleware, $name);
    }
    
    /**
     * Add a DELETE route with optional name
     */
    public function delete($pattern, $handler, $middleware = [], $name = null) {
        return $this->addRoute('DELETE', $pattern, $handler, $name);
    }
    
    /**
     * Add route for multiple methods
     */
    public function match($methods, $pattern, $handler, $middleware = [], $name = null) {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $pattern, $handler, $middleware, $name);
        }
        return $this;
    }
    
    /**
     * Add named route (fluent interface)
     */
    public function name($name) {
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
    private function addRoute($method, $pattern, $handler, $middleware = [], $name = null) {
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
        
        // Store named route
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
        
        return $this;
    }
    
    /**
     * Convert route pattern to regex with enhanced parameter support
     */
    private function convertPattern($pattern) {
        // Handle typed parameters: {id:string}, {sid:id}, {page:number}
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+):([a-zA-Z0-9_]+)\}/', function($matches) {
            $paramName = $matches[1];
            $type = $matches[2];
            
            if (isset($this->patterns[$type])) {
                return '(' . $this->patterns[$type] . ')';
            }
            
            // Custom length: {code:6} = 6 characters
            if (is_numeric($type)) {
                return '([a-zA-Z0-9]{' . $type . '})';
            }
            
            // Default to any
            return '([a-zA-Z0-9_-]+)';
        }, $pattern);
        
        // Handle simple parameters: {action}, {id}
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $pattern);
        
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);
        
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Extract parameter names from pattern
     */
    private function extractParameters($pattern) {
        preg_match_all('/\{([a-zA-Z0-9_]+)(?::([a-zA-Z0-9_]+))?\}/', $pattern, $matches);
        return $matches[1]; // Return parameter names
    }
    
    /**
     * Generate URL for named route
     */
    public function url($name, $parameters = []) {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Route '$name' not found");
        }
        
        $route = $this->namedRoutes[$name];
        $pattern = $route['original_pattern'];
        $url = $pattern;
        
        // Replace parameters in the pattern
        foreach ($parameters as $key => $value) {
            // Replace both {key} and {key:type} formats
            $url = preg_replace('/\{' . $key . '(?::[a-zA-Z0-9_]+)?\}/', $value, $url);
        }
        
        // Check if all parameters were replaced
        if (preg_match('/\{[^}]+\}/', $url)) {
            throw new Exception("Missing parameters for route '$name'");
        }
        
        return $url;
    }
    
    /**
     * Check if named route exists
     */
    public function hasRoute($name) {
        return isset($this->namedRoutes[$name]);
    }
    
    /**
     * Get all named routes
     */
    public function getNamedRoutes() {
        return $this->namedRoutes;
    }
    
    /**
     * Add middleware
     */
    public function middleware($name, $callback) {
        $this->middleware[$name] = $callback;
        return $this;
    }
    
    /**
     * Route the current request
     */
    public function route() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestUri = rtrim($requestUri, '/');
        
        // Handle root path
        if (empty($requestUri)) {
            $requestUri = '/';
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                if (preg_match($route['pattern'], $requestUri, $matches)) {
                    // Remove full match from parameters
                    array_shift($matches);
                    
                    // Create named parameters array
                    $params = [];
                    foreach ($route['parameters'] as $index => $paramName) {
                        if (isset($matches[$index])) {
                            $params[$paramName] = $matches[$index];
                        }
                    }
                    
                    // Merge URL parameters with request data
                    $allParams = array_merge($params, $this->requestData);
                    
                    // Run middleware
                    foreach ($route['middleware'] as $middlewareName) {
                        if (isset($this->middleware[$middlewareName])) {
                            $result = call_user_func($this->middleware[$middlewareName]);
                            if ($result === false) {
                                return;
                            }
                        }
                    }
                    
                    // Execute handler with all data available
                    return $this->executeHandler($route['handler'], $matches, $allParams);
                }
            }
        }
        
        // No route found - 404
        $this->handle404();
    }
    
    /**
     * Execute route handler with enhanced parameter passing
     */
    private function executeHandler($handler, $params = [], $namedParams = []) {
        try {
            if (is_string($handler)) {
                // Handle view shorthand: "auth.signin"
                if (strpos($handler, '.') !== false && strpos($handler, ':') === false) {
                    return $this->renderViewShorthand($handler, $namedParams);
                }
                
                // Handle view files: "view:auth.signin"
                if (strpos($handler, 'view:') === 0) {
                    $viewFile = substr($handler, 5);
                    return $this->renderViewShorthand($viewFile, $namedParams);
                }
                
                // Handle API endpoints: "api:authentication/login"
                if (strpos($handler, 'api:') === 0) {
                    $apiFile = substr($handler, 4);
                    return $this->executeApi($apiFile, $namedParams);
                }
            }
            
            if (is_callable($handler)) {
                // Execute callback with enhanced parameter passing
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
     * Render view using dot notation: "auth.signin" -> views/auth/signin.php
     */
    private function renderViewShorthand($viewFile, $params = []) {
        try {
            // Use ViewEngine if available
            if (function_exists('view')) {
                $content = view($viewFile, $params);
                echo $content;
                return;
            }
            
            // Fallback to direct rendering
            return $this->renderView($viewFile, $params);
            
        } catch (Exception $e) {
            $this->handleViewError($e, $viewFile, $params);
        }
    }
    
    /**
     * Render view file (enhanced version)
     */
    private function renderView($viewFile, $params = []) {
        try {
            // Convert dot notation to path
            $viewPath = str_replace('.', '/', $viewFile);
            $fullPath = ROOT_PATH . '/' . $this->baseViewPath . $viewPath . '.php';
            
            if (!file_exists($fullPath)) {
                throw new Exception("View file not found: {$fullPath}");
            }
            
            // Set GET parameters for backward compatibility
            foreach ($params as $key => $value) {
                $_GET[$key] = $value;
            }
            
            // Extract parameters as variables
            extract($params);
            
            // Make helper functions available
            if (function_exists('route')) {
                $route = 'route';
            }
            if (function_exists('asset')) {
                $asset = 'asset';
            }
            
            include $fullPath;
            
        } catch (Exception $e) {
            $this->handleViewError($e, $viewFile, $params);
        }
    }

    /**
     * Handle router errors
     */
    private function handleRouterError($e) {
        if (defined('APP_DEBUG')) {
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
    private function handleViewError($e, $viewFile, $params = []) {
        if (defined('APP_DEBUG')) {
            echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;margin:10px;border-radius:4px;'>";
            echo "<h3>Router View Error</h3>";
            echo "<p><strong>View:</strong> " . htmlspecialchars($viewFile) . "</p>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
            
            if (!empty($params)) {
                echo "<p><strong>Parameters:</strong></p>";
                echo "<pre style='background:#fff;padding:10px;border-radius:3px;'>" . print_r($params, true) . "</pre>";
            }
            
            // Show ViewEngine debug info if available
            if (class_exists('ViewEngine')) {
                try {
                    $engine = ViewEngine::getInstance();
                    echo "<p><strong>ViewEngine Debug Info:</strong></p>";
                    echo "<pre style='background:#fff;padding:10px;border-radius:3px;'>" . print_r($engine->getDebugInfo(), true) . "</pre>";
                } catch (Exception $debugException) {
                    echo "<p><strong>ViewEngine Debug:</strong> Could not get debug info - " . $debugException->getMessage() . "</p>";
                }
            }
            
            echo "<p><strong>Stack Trace:</strong></p>";
            echo "<pre style='background:#fff;padding:10px;border-radius:3px;font-size:12px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } else {
            error_log("Router View Error ({$viewFile}): " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            $this->handle404();
        }
    }

    /**
     * Enhanced 404 error handler
     */
    private function handle404() {
        http_response_code(404);
        
        // Check if this is an API request
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
        
        // Try to render a custom 404 view
        $error404Path = ROOT_PATH . '/' . $this->baseViewPath . 'errors/404.php';
        if (file_exists($error404Path)) {
            include $error404Path;
            return;
        }
        
        // Default 404 response
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
                    a:hover { text-decoration: underline; }
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
    private function handle500() {
        http_response_code(500);
        
        // Check if this is an API request
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
        
        // Try to render a custom 500 view
        $error500Path = ROOT_PATH . '/' . $this->baseViewPath . 'errors/500.php';
        if (file_exists($error500Path)) {
            include $error500Path;
            return;
        }
        
        // Default 500 response
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
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <h1>500 - Internal Server Error</h1>
            <p>Something went wrong on our end. Please try again later.</p>
            <a href='/'>Return to Home</a>
        </body>
        </html>";
    }
    
    /**
     * Execute API endpoint with all request data
     */
    private function executeApi($apiFile, $params = []) {
        $apiPath = ROOT_PATH . '/' . $this->baseApiPath . $apiFile . '.php';
        
        if (!file_exists($apiPath)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'Client Error',
                'message' => 'API endpoint not found',
                'endpoint' => $apiFile,
                'timestamp' => date('Y-m-d H:i:s'),
                'server_time' => time()
            ], JSON_PRETTY_PRINT);
            return;
        }
        
        // Set GET parameters for backward compatibility
        foreach ($params as $key => $value) {
            $_GET[$key] = $value;
        }
        
        // Make router instance available to API endpoints
        $GLOBALS['router'] = $this;
        
        include $apiPath;
    }
    
    /**
     * Check if current request is API
     */
    public function isApiRequest() {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
    }
    
    /**
     * Add parameter validation rules
     */
    public function addPattern($name, $pattern) {
        $this->patterns[$name] = $pattern;
        return $this;
    }
    
    /**
     * Get all registered patterns
     */
    public function getPatterns() {
        return $this->patterns;
    }

    public function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
}

// ============== GLOBAL ROUTER INSTANCE ==============
$GLOBALS['router'] = null;

function getRouter() {
    return $GLOBALS['router'];
}

function setRouter($router) {
    $GLOBALS['router'] = $router;
}
