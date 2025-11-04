<?php


// ============== ENHANCED HELPER FUNCTIONS ==============

/**
 * Get request data (works with JSON and form data)
 */
function request($key = null, $default = null) {
    $router = getRouter();
    if (!$router) {
        // Fallback to $_REQUEST
        if ($key === null) {
            return $_REQUEST;
        }
        return $_REQUEST[$key] ?? $default;
    }
    
    return $router->getRequestData($key, $default);
}

/**
 * Get JSON data from request
 */
function json_input($key = null, $default = null) {
    $router = getRouter();
    if (!$router || !$router->isJson()) {
        return $default;
    }
    
    return $router->getRequestData($key, $default);
}

/**
 * Get uploaded files
 */
function request_files($key = null) {
    $router = getRouter();
    if (!$router) {
        if ($key === null) {
            return $_FILES;
        }
        return $_FILES[$key] ?? null;
    }
    
    return $router->getFiles($key);
}

/**
 * Get request headers
 */
function request_header($key = null) {
    $router = getRouter();
    if (!$router) {
        return null;
    }
    
    return $router->getHeaders($key);
}

function request_method() {
    $router = getRouter();
    if (!$router) {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    return $router->getMethod();
}

/**
 * Check if request is JSON
 */
function is_json_request() {
    $router = getRouter();
    return $router ? $router->isJson() : false;
}

/**
 * Generate URL for named route
 */
function route($name, $parameters = []) {
    $router = getRouter();
    if (!$router) {
        throw new Exception("Router not initialized");
    }
    
    return $router->url($name, $parameters);
}

/**
 * Generate URL with app base URL
 */
function route_url($name, $parameters = []) {
    $routeUrl = route($name, $parameters);
    $baseUrl = app('app.url', '');
    
    if (substr($baseUrl, -1) === '/') {
        $baseUrl = substr($baseUrl, 0, -1);
    }
    
    // If route starts with /, prepend base URL
    if (strpos($routeUrl, '/') === 0) {
        return $baseUrl . $routeUrl;
    }
    
    return $baseUrl . '/' . ltrim($routeUrl, '/');
}

/**
 * Check if route exists
 */
function route_exists($name) {
    $router = getRouter();
    return $router ? $router->hasRoute($name) : false;
}

/**
 * Redirect to named route
 */
function redirect($name, $parameters = [], $statusCode = 302) {
    $url = route($name, $parameters);
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Main view function - Simplified error handling
 */
if (!function_exists('view')) {
    function view($view, $data = []) {
        if (class_exists('ViewEngine')) {
            try {
                $engine = ViewEngine::getInstance();
                return $engine->render($view, $data);
            } catch (Exception $e) {
                // For debugging - show the actual error
                if (defined('APP_DEBUG')) {
                    return "ViewEngine Error: " . $e->getMessage();
                }
                // In production, fall back to basic rendering
                return view_fallback($view, $data);
            }
        }
        
        return view_fallback($view, $data);
    }
}

/**
 * Fallback view function
 */
if (!function_exists('view_fallback')) {
    function view_fallback($view, $data = []) {
        try {
            $viewPath = str_replace('.', '/', $view);
            $fullPath = ROOT_PATH . '/views/' . $viewPath . '.php';
            
            if (!file_exists($fullPath)) {
                return "<!-- View not found: $fullPath -->";
            }
            
            extract($data);
            ob_start();
            include $fullPath;
            return ob_get_clean();
            
        } catch (Exception $e) {
            return "<!-- Fallback view error: " . $e->getMessage() . " -->";
        }
    }
}

/**
 * API helper function (updated to use dot notation)
 */
function api($endpoint, $data = []) {
    // Convert dot notation to path: "authentication.login" -> "authentication/login"
    $apiPath = str_replace('.', '/', $endpoint);
    $fullPath = ROOT_PATH . '/api/v1/' . $apiPath . '.php';
    
    if (!file_exists($fullPath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'Client Error',
            'message' => 'API endpoint not found',
            'endpoint' => $endpoint,
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], JSON_PRETTY_PRINT);
        return false;
    }

    // Set GET parameters for backward compatibility
    foreach ($data as $key => $value) {
        $_GET[$key] = $value;
    }
    
    include $fullPath;
    return true;
}

function getHttpStatusName($code) {
    if ($code >= 100 && $code < 200) {
        return "Informational";
    } elseif ($code >= 200 && $code < 300) {
        return "Success";
    } elseif ($code >= 300 && $code < 400) {
        return "Redirect";
    } elseif ($code >= 400 && $code < 500) {
        return "Client Error";
    } elseif ($code >= 500 && $code < 600) {
        return "Server Error";
    }
    return "Unknown";
}

/**
 * Enhanced JSON response helper
 */
function json($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');

    // Extract message (if exists) and remove from data
    $message = '';
    if (is_array($data) && isset($data['message'])) {
        $message = $data['message'];
        unset($data['message']);
    }

    // Base response
    $response = [
        'status' => getHttpStatusName($statusCode),
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'server_time' => time(),
    ];

    if(isset($data['data'])) {
        $response['data'] = $data['data'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Alternative JSON response helper (same as json but different name for compatibility)
 */
function json_response($data, $statusCode = 200) {
    return json($data, $statusCode);
}

/**
 * Validate parameter using patterns
 */
function validate($value, $pattern) {
    $router = getRouter();
    
    $patterns = $router ? $router->getPatterns() : [
        'id' => '[A-Z0-9]{8}',
        'string' => '[a-zA-Z]+',
        'number' => '[0-9]+',
        'alphanum' => '[a-zA-Z0-9]+'
    ];
    
    if (isset($patterns[$pattern])) {
        return preg_match('/^' . $patterns[$pattern] . '$/', $value);
    }
    
    return preg_match('/^' . $pattern . '$/', $value);
}

/**
 * Authentication Middleware (updated - no core loading)
 */
function authMiddleware() {    
    if (!session_has("user.id") && !is_cookie_authenticated()) {
        
        session_set('security.intended_url', '/');
        
        // Check if this is an API request
        $router = getRouter();
        if ($router && $router->isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            json([
                'message' => 'Authentication required',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_time' => time()
            ]);
            return false; // Stop execution
        } else {
            redirect("auth.signin");
            return false; // Stop execution
        }
    }
    
    // Set global variables
    if (isset($_GET['code'])) {
        $GLOBALS['code'] = $_GET['code'];
    }
    
    if (session_has("user.id")) {
        $GLOBALS['id'] = session("user.id");
    }
    
    if (session_has("user.role")) {
        $GLOBALS['role'] = session("user.role");
    }

    if(session_has("user.group")) {
        $GLOBALS["group"] = session("user.group");
    }
    
    // Core files already loaded by bootstrap
    return true;
}

function guestMiddleware() {
    // Check if user is authenticated
    if (session_has("user.id") || is_cookie_authenticated()) {
        // Check if there's an intended URL to redirect to after login
        $intendedUrl = session('intended_url');
        if ($intendedUrl) {
            session_remove('intended_url');
            redirect($intendedUrl);
        } else {
            redirect('dashboard'); // Default redirect for authenticated users
        }
        return false; // Stop route execution
    }
    
    return true; // Continue to route handler (user is not authenticated)
}


/**
 * Public routes middleware (updated)
 */
function publicMiddleware() {

    return true;
}

/**
 * Admin middleware - Only allows admin users
 */
function adminMiddleware() {
    // First check if user is authenticated
    if (!authMiddleware()) {
        return false; // Will redirect to login
    }
    
    // Check if user has admin privileges
    $userRole = session('user.role');
    if ($userRole !== 'admin' && $userRole !== 'superadmin') {
        // Redirect to dashboard or show 403 error
        redirect('dashboard');
        return false;
    }
    
    return true;
}

/**
 * CSRF Protection middleware (optional but recommended)
 */
function csrfMiddleware() {
    $router = getRouter();
    
    if (in_array($router->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        $token = $router->getRequestData('_token');
        $sessionToken = session('security.csrf_token');
        
        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            http_response_code(419);
            if ($router->isApiRequest()) {
                header('Content-Type: application/json');
                json([
                    'status' => 'Token Mismatch',
                    'message' => 'CSRF token validation failed',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                redirect('auth.signin', 'Session expired. Please login again.');
            }
            return false;
        }
    }
    
    return true;
}