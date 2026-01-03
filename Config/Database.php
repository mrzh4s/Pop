<?php
/**
 * Database Configuration
 * File: apps/config/Database.php
 *
 * Features:
 * - Dynamic database discovery from environment variables
 * - Support for SQLite, PostgreSQL, MySQL, SQL Server
 * - Auto-discovery by DBConnectionFactory
 * - Pattern: {NAME}_DB_HOST, {NAME}_DB_PORT, etc.
 *
 * Environment Variable Patterns:
 * - MAIN_DB_HOST, MAIN_DB_PORT, etc. → 'main' connection
 * - SOURCE_DB_HOST, SOURCE_DB_PORT, etc. → 'source' connection
 * - ANALYTICS_DB_HOST, etc. → 'analytics' connection
 *
 * Usage in code:
 * DB::connection('main')
 * DB::connection('source')
 * DB::connection('analytics')
 */

/**
 * Auto-discover database connections from environment variables
 * Scans for patterns like {NAME}_DB_HOST, {NAME}_DB_PORT, etc.
 */
function discoverDatabaseConnections() {
    $connections = [];
    $envVars = $_ENV + $_SERVER; // Merge both sources

    // Find all unique connection names (e.g., MAIN, SOURCE, ANALYTICS)
    $connectionNames = [];
    foreach ($envVars as $key => $value) {
        if (preg_match('/^([A-Z_]+)_DB_(HOST|DATABASE|DRIVER)$/', $key, $matches)) {
            $connectionNames[$matches[1]] = true;
        }
    }

    // Build configuration for each discovered connection
    foreach (array_keys($connectionNames) as $name) {
        $prefix = $name . '_DB_';
        $connName = strtolower($name);

        // Determine driver (default to pgsql if not specified)
        $driver = env($prefix . 'DRIVER', 'pgsql');

        // Common configuration
        $config = [
            'driver' => $driver,
        ];

        // Driver-specific configuration
        if ($driver === 'sqlite') {
            $config['database'] = env($prefix . 'DATABASE', 'database/' . $connName . '.db');
        } else {
            // PostgreSQL, MySQL, SQL Server
            $config['host'] = env($prefix . 'HOST', 'localhost');
            $config['port'] = env($prefix . 'PORT', $driver === 'pgsql' ? 5432 : ($driver === 'mysql' ? 3306 : 1433));
            $config['database'] = env($prefix . 'DATABASE', $connName);
            $config['username'] = env($prefix . 'USERNAME', 'postgres');
            $config['password'] = env($prefix . 'PASSWORD', '');
            $config['charset'] = env($prefix . 'CHARSET', 'utf8');
            $config['prefix'] = env($prefix . 'PREFIX', '');
            $config['schema'] = env($prefix . 'SCHEMA', 'public');

            // Additional options
            if ($timeout = env($prefix . 'TIMEOUT')) {
                $config['options']['statement_timeout'] = $timeout;
            }
        }

        $connections[$connName] = $config;
    }

    // Add APP_DB if defined (default SQLite database)
    if (env('APP_DB')) {
        $connections['app'] = [
            'driver' => 'sqlite',
            'database' => env('APP_DB'),
        ];
    }

    return $connections;
}

return [
    /**
     * Default database connection name
     * This will be used when you call DB::connection() without arguments
     */
    'default' => env('DB_DEFAULT', 'main'),

    /**
     * Database Connections
     * Auto-discovered from environment variables
     */
    'connections' => discoverDatabaseConnections(),

    /**
     * Connection Pool Settings
     */
    'pool' => [
        'max_retries' => 3,
        'base_timeout' => 10,  // seconds
        'ping_timeout' => 1000, // milliseconds
        'enable_health_check' => true,
    ],

    /**
     * Global PDO Options
     * These apply to all connections unless overridden
     */
    'pdo_options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ],
];