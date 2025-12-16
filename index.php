<?php

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define global root path
define('ROOT_PATH', realpath($_SERVER['DOCUMENT_ROOT']));


// Set include path using ROOT_PATH
set_include_path(ROOT_PATH);

require_once ROOT_PATH . '/core/bootstrap.php';
/**
 * Main routes.php - Entry point for all KITER routes
 * Loads all route modules organized by functionality
 */

require_once 'core/Router.php';

$router = new router();

// Register middleware
$router->middleware('auth', 'authMiddleware');
$router->middleware('public', 'publicMiddleware');
$router->middleware('guest', 'guestMiddleware');    



// ============== LOAD ROUTE MODULES ==============

// Load all Web routes
require_once 'routes/web.php';
// Load all API routes
require_once 'routes/api.php';

// ============== EXECUTE ROUTER ==============
$router->route();

?>