<?php

/**
 * PHPUnit Test Bootstrap
 *
 * Initializes the testing environment for the Pop Framework
 */

// Define root path
define('ROOT_PATH', dirname(__DIR__));

// Load Composer autoloader
require_once ROOT_PATH . '/vendor/autoload.php';

// Set testing environment
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');

// Load environment variables from .env.testing if it exists
$envTestingFile = ROOT_PATH . '/.env.testing';
if (file_exists($envTestingFile)) {
    $lines = file($envTestingFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        putenv($line);
    }
}

// Initialize framework (load helpers)
if (file_exists(ROOT_PATH . '/Framework/Bootstrap.php')) {
    // Load helpers only (not full bootstrap for tests)
    $helpersDir = ROOT_PATH . '/Framework/Helpers';

    $helperOrder = [
        'env',
        'config',
        'session',
        'cookie',
        'security',
        'validation',
        'connection',
        'migration',
        'permission',
        'activity',
        'traffic',
        'http',
        'router',
        'view',
        'inertia',
        'debug',
    ];

    foreach ($helperOrder as $helperName) {
        $helperFile = $helpersDir . '/' . $helperName . '.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }
}

// Create test database directory if it doesn't exist
$testDbDir = ROOT_PATH . '/database/test';
if (!is_dir($testDbDir)) {
    mkdir($testDbDir, 0755, true);
}

echo "PHPUnit Test Bootstrap Complete\n";
echo "Root Path: " . ROOT_PATH . "\n";
echo "Environment: testing\n\n";
