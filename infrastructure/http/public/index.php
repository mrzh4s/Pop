<?php
/**
 * Pop Framework - Minimal PHP Template
 * Entry Point
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));

// Load Composer autoloader if exists
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

loadEnv(ROOT_PATH . '/.env');

// Simple routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Home route
if ($uri === '/' || $uri === '/index.php') {
    require ROOT_PATH . '/infrastructure/view/welcome.php';
    exit;
}

// API example route
if ($uri === '/api/hello' && $method === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Hello from Pop Framework!', 'version' => '1.0.0']);
    exit;
}

// 404
http_response_code(404);
echo '<h1>404 - Page Not Found</h1>';
