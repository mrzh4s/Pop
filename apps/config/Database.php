<?php
/**
 * Database Configuration
 * File: apps/config/Database.php
 *
 * Features:
 * - Define unlimited database connections
 * - Support for SQLite, PostgreSQL, MySQL, SQL Server
 * - Auto-discovery by DBConnectionFactory
 * - Environment variable support
 *
 * Usage in code:
 * DB::connection('mysql_main')
 * DB::connection('analytics')
 * DB::connection('cache')
 */

return [
    /**
     * Default database connection name
     * This will be used when you call DB::connection() without arguments
     */
    'default' => env('DB_DEFAULT', 'main'),

    /**
     * Database Connections
     *
     * You can define as many connections as you need.
     * Each connection requires a 'driver' and driver-specific configuration.
     *
     * Supported drivers: sqlite, pgsql, mysql, sqlsrv
     */
    'connections' => [

        /**
         * Main Database (SQLite)
         * Default application database
         */
        'main' => [
            'driver' => 'sqlite',
            'database' => env('APP_DB', 'database/app.db'),
            'prefix' => '',
            'foreign_keys' => true,

            // SQLite-specific optimizations
            'options' => [
                'journal_mode' => 'WAL',
                'synchronous' => 'NORMAL',
                'cache_size' => 10000,
                'temp_store' => 'MEMORY',
            ]
        ],

        /**
         * Source Database (PostgreSQL)
         * External data source
         */
        'source' => [
            'driver' => 'pgsql',
            'host' => env('SOURCE_DB_HOST', 'localhost'),
            'port' => env('SOURCE_DB_PORT', 5432),
            'database' => env('SOURCE_DB_DATABASE', 'source_db'),
            'username' => env('SOURCE_DB_USERNAME', 'postgres'),
            'password' => env('SOURCE_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',

            // PostgreSQL-specific settings
            'options' => [
                'statement_timeout' => '300s',
                'lock_timeout' => '30s',
                'idle_in_transaction_session_timeout' => '300s',
                'timezone' => 'UTC',
            ]
        ],

        /**
         * Destination Database (PostgreSQL)
         * Migration destination or backup
         */
        'dest' => [
            'driver' => 'pgsql',
            'host' => env('DEST_DB_HOST', 'localhost'),
            'port' => env('DEST_DB_PORT', 5432),
            'database' => env('DEST_DB_DATABASE', 'dest_db'),
            'username' => env('DEST_DB_USERNAME', 'postgres'),
            'password' => env('DEST_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',

            'options' => [
                'statement_timeout' => '300s',
                'lock_timeout' => '30s',
                'timezone' => 'UTC',
            ]
        ],

    ],

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