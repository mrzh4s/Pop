<?php
/**
 * Main routes.php - Entry point for all APP routes
 * Loads all route modules organized by functionality
 */

require_once 'core/Router.php';

$router = new router();

// Register middleware
$router->middleware('auth', 'authMiddleware');
$router->middleware('public', 'publicMiddleware');
$router->middleware('guest', 'guestMiddleware');    
$router->middleware('admin', 'adminMiddleware');
$router->middleware('csrf', 'csrfMiddleware');



// ============== LOAD ROUTE MODULES ==============

// Load all Web routes
require_once 'routes/web.php';
// Load all API routes
require_once 'routes/api.php';

// ============== EXECUTE ROUTER ==============
$router->route();

