<?php

/**
 * Pop Framework - Application Entry Point
 * File: public/index.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define global root path (get root path)
define('ROOT_PATH', dirname(__DIR__,3 ));

// Set include path using ROOT_PATH
set_include_path(ROOT_PATH);

// Load Composer autoloader
require_once ROOT_PATH . '/vendor/autoload.php';

// Bootstrap the framework
Framework\Bootstrap::boot();

// Import Router class
use Framework\Http\Router;

// Auto-discovery enabled:
// - Middleware from Infrastructure/Http/Middleware and Features/*/Middleware
// - Routes from Infrastructure/Http/Routes and Features/*/Routes

// Execute router
Router::route();
