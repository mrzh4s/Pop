<?php
/**
 * Router Helper Functions
 * File: core/helpers/router.php
 * 
 * Updated for new page-based architecture
 * All helpers work with the new Router class
 */

// ============== REQUEST HELPERS ==============

/**
 * Get request data (works with JSON and form data)
 * 
 * Usage:
 * request()              // Get all request data
 * request('username')    // Get specific field
 * request('email', 'default@example.com') // With default value
 */

use Framework\Http\Router;

function request($key = null, $default = null) {
    $router = Router::getRouter();
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
 * Only returns data if request content-type is application/json
 * 
 * Usage:
 * json_input()           // Get all JSON data
 * json_input('name')     // Get specific field
 */
function json_input($key = null, $default = null) {
    $router = Router::getRouter();
    if (!$router || !$router->isJson()) {
        return $default;
    }
    
    return $router->getRequestData($key, $default);
}

/**
 * Get uploaded files
 * 
 * Usage:
 * request_files()           // Get all files
 * request_files('avatar')   // Get specific file
 */
function request_files($key = null) {
    $router = Router::getRouter();
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
 * 
 * Usage:
 * request_header()                // Get all headers
 * request_header('Authorization') // Get specific header
 */
function request_header($key = null) {
    $router = Router::getRouter();
    if (!$router) {
        return null;
    }
    
    return $router->getHeaders($key);
}

/**
 * Get request method (GET, POST, PUT, DELETE, etc.)
 * 
 * Usage:
 * if (request_method() === 'POST') { ... }
 */
function request_method() {
    $router = Router::getRouter();
    if (!$router) {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    return $router->getMethod();
}

/**
 * Check if request is JSON
 * 
 * Usage:
 * if (is_json_request()) { ... }
 */
function is_json_request() {
    $router = Router::getRouter();
    return $router ? $router->isJson() : false;
}

/**
 * Check if request is API request (URL starts with /api/)
 * 
 * Usage:
 * if (is_api_request()) { ... }
 */
function is_api_request() {
    $router = Router::getRouter();
    return $router ? $router->isApiRequest() : false;
}

/**
 * Check if request has files
 * 
 * Usage:
 * if (request_has_files()) { ... }
 */
function request_has_files() {
    $router = Router::getRouter();
    return $router ? $router->hasFiles() : !empty($_FILES);
}


// ============== ROUTING HELPERS ==============

/**
 * Generate URL for named route
 * 
 * Usage:
 * route('home')                              // /home
 * route('applications.view', ['id' => '123']) // /applications/view/123
 * route('users.posts.show', ['userId' => '1', 'postId' => '5']) // /users/1/posts/5
 */
function route($name, $parameters = []) {
    return Router::url($name, $parameters);
}

/**
 * Generate full URL with app base URL
 * 
 * Usage:
 * route_url('home')                    // https://example.com/home
 * route_url('profile', ['id' => '123']) // https://example.com/profile/123
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
 * Check if named route exists
 * 
 * Usage:
 * if (route_exists('profile')) { ... }
 */
function route_exists($name) {
    $router = Router::getRouter();
    return $router ? $router->hasRoute($name) : false;
}

/**
 * Redirect to named route
 * 
 * Usage:
 * redirect('home')                           // Redirect to /home
 * redirect('profile', ['id' => '123'])       // Redirect to /profile/123
 * redirect('login', [], 301)                 // Permanent redirect
 */
function redirect($name, $parameters = [], $statusCode = 302) {
    $url = route($name, $parameters);
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Redirect to URL (not named route)
 * 
 * Usage:
 * redirect_to('/home')
 * redirect_to('https://example.com')
 */
function redirect_to($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Redirect back to previous page
 * 
 * Usage:
 * redirect_back()
 * redirect_back('/home')  // Fallback if no referrer
 */
function redirect_back($fallback = '/') {
    $referrer = $_SERVER['HTTP_REFERER'] ?? $fallback;
    header("Location: $referrer", true, 302);
    exit;
}


// ============== RESPONSE HELPERS ==============

/**
 * Get HTTP status name from code
 * 
 * Usage:
 * getHttpStatusName(200)  // "Success"
 * getHttpStatusName(404)  // "Client Error"
 */
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
 * Return JSON response
 * 
 * Usage:
 * json(['message' => 'Success', 'data' => $data])
 * json(['message' => 'Not found'], 404)
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

    if (isset($data['data'])) {
        $response['data'] = $data['data'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Alternative JSON response helper
 * Same as json() but different name for compatibility
 */
function json_response($data, $statusCode = 200) {
    return json($data, $statusCode);
}


// ============== VALIDATION HELPERS ==============

/**
 * Validate parameter using patterns
 * 
 * Usage:
 * validate('ABC12345', 'id')        // Check if matches ID pattern
 * validate('test@email.com', 'email')
 * validate('2025', 'year')
 */
function validate($value, $pattern) {
    $router = Router::getRouter();
    
    $patterns = $router ? $router->getPatterns() : [
        'id' => '[A-Z0-9]{8}',
        'uuid' => '[a-zA-Z0-9-]{36}',
        'string' => '[a-zA-Z]+',
        'number' => '[0-9]+',
        'alphanum' => '[a-zA-Z0-9]+',
        'year' => '[0-9]{4}',
    ];
    
    if (isset($patterns[$pattern])) {
        return preg_match('/^' . $patterns[$pattern] . '$/', $value);
    }
    
    return preg_match('/^' . $pattern . '$/', $value);
}

/**
 * Validate required fields in request
 * Returns array of missing fields or empty array if all present
 * 
 * Usage:
 * $missing = validate_required(['username', 'password']);
 * if (!empty($missing)) {
 *     json(['message' => 'Missing: ' . implode(', ', $missing)], 400);
 * }
 */
function validate_required($fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (empty(request($field))) {
            $missing[] = $field;
        }
    }
    return $missing;
}


// ============== MIDDLEWARE FUNCTIONS ==============

/**
 * Authentication Middleware
 * Checks if user is authenticated
 */
function authMiddleware() {    
    if (!session("authenticated")) {
        // Save intended URL for redirect after login
        session_set('security.intended_url', $_SERVER['REQUEST_URI']);
        
        // Check if this is an API request
        $router = Router::getRouter();
        if ($router && $router->isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            json([
                'message' => 'Authentication required',
            ], 401);
            return false;
        } else {
            redirect('auth.signin');
            return false;
        }
    }
    
    // User is authenticated - continue
    return true;
}

/**
 * Guest Middleware
 * Only allows unauthenticated users (for login/register pages)
 */
function guestMiddleware() {
    if (session("authenticated")) {
        redirect('home');
        return false;
    }
    
    return true;
}

/**
 * Public Middleware
 * Allows all users (authenticated or not)
 */
function publicMiddleware() {
    return true;
}

/**
 * Admin Middleware
 * Only allows admin users
 */
function adminMiddleware() {
    // First check authentication
    if (!authMiddleware()) {
        return false;
    }
    
    // Check if user is admin
    if (session('user.role') !== 'admin') {
        $router = Router::getRouter();
        if ($router && $router->isApiRequest()) {
            json(['message' => 'Admin access required'], 403);
        } else {
            redirect_to('/error/403');
        }
        return false;
    }
    
    return true;
}


// ============== URL HELPERS ==============

/**
 * Get current URL
 * 
 * Usage:
 * $currentUrl = current_url()  // https://example.com/applications/view/123
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

/**
 * Get current path (without domain)
 * 
 * Usage:
 * $path = current_path()  // /applications/view/123
 */
function current_path() {
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}

/**
 * Check if current route matches
 * 
 * Usage:
 * if (is_route('home')) { ... }
 * if (is_route('applications.*')) { ... }  // Wildcard
 */
function is_route($routeName) {
    $currentPath = current_path();
    
    try {
        $routePath = route($routeName);
        
        // Exact match
        if ($currentPath === $routePath) {
            return true;
        }
        
        // Wildcard match (e.g., 'applications.*')
        if (strpos($routeName, '*') !== false) {
            $pattern = str_replace('*', '.*', $routeName);
            $pattern = str_replace('.', '\\.', $pattern);
            
            // Try to match against all routes
            $router = Router::getRouter();
            if ($router) {
                foreach ($router->getNamedRoutes() as $name => $route) {
                    if (preg_match('/^' . $pattern . '$/', $name)) {
                        $path = route($name);
                        if ($currentPath === $path) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Add active class if current route matches
 * Useful for navigation menus
 * 
 * Usage:
 * <a href="<?= route('home') ?>" class="<?= active_if('home') ?>">Home</a>
 * <a href="<?= route('profile') ?>" class="nav-link <?= active_if('profile', 'active') ?>">Profile</a>
 */
function active_if($routeName, $activeClass = 'active') {
    return is_route($routeName) ? $activeClass : '';
}


// ============== DEBUGGING HELPERS ==============

/**
 * Dump request data and die (for debugging)
 * 
 * Usage:
 * dd_request()  // Shows all request data
 */
function dd_request() {
    echo '<pre>';
    echo '<h3>Request Method: ' . request_method() . '</h3>';
    echo '<h3>Request Data:</h3>';
    print_r(request());
    echo '<h3>Files:</h3>';
    print_r(request_files());
    echo '<h3>Headers:</h3>';
    print_r(request_header());
    echo '</pre>';
    die();
}

/**
 * Dump all routes (for debugging)
 * 
 * Usage:
 * dd_routes()  // Shows all registered routes
 */
function dd_routes() {
    $router = Router::getRouter();
    if (!$router) {
        die('Router not initialized');
    }
    
    echo '<pre>';
    echo '<h3>Named Routes:</h3>';
    print_r($router->getNamedRoutes());
    echo '</pre>';
    die();
}