<?php
/**
 * FTP Configuration
 * File: apps/config/Ftp.php
 *
 * Features:
 * - Dynamic FTP connection discovery from environment variables
 * - Support for multiple FTP servers
 * - Pattern: {NAME}_FTP_HOST, {NAME}_FTP_PORT, etc.
 *
 * Environment Variable Patterns:
 * - FTP_HOST, FTP_PORT, etc. → 'default' connection
 * - BACKUP_FTP_HOST, BACKUP_FTP_PORT, etc. → 'backup' connection
 * - CDN_FTP_HOST, etc. → 'cdn' connection
 *
 * Usage in code:
 * FTP::connection('default')
 * FTP::connection('backup')
 * FTP::connection('cdn')
 */

/**
 * Auto-discover FTP connections from environment variables
 * Scans for patterns like {NAME}_FTP_HOST, {NAME}_FTP_PORT, etc.
 */
function discoverFtpConnections() {
    $connections = [];
    $envVars = $_ENV + $_SERVER; // Merge both sources

    // Find all unique connection names (e.g., BACKUP, CDN, MEDIA)
    $connectionNames = [];
    foreach ($envVars as $key => $value) {
        if (preg_match('/^([A-Z_]+)?FTP_(HOST|USERNAME)$/', $key, $matches)) {
            // Handle both "FTP_HOST" (default) and "BACKUP_FTP_HOST" (named)
            $prefix = $matches[1] ? rtrim($matches[1], '_') : 'DEFAULT';
            $connectionNames[$prefix] = true;
        }
    }

    // Build configuration for each discovered connection
    foreach (array_keys($connectionNames) as $name) {
        // For DEFAULT, use "FTP_" prefix, otherwise use "{NAME}_FTP_" prefix
        $prefix = ($name === 'DEFAULT') ? 'FTP_' : $name . '_FTP_';
        $connName = strtolower($name === 'DEFAULT' ? 'default' : $name);

        // Skip if no host is defined
        if (!env($prefix . 'HOST')) {
            continue;
        }

        $config = [
            'host' => env($prefix . 'HOST'),
            'port' => env($prefix . 'PORT', 21),
            'username' => env($prefix . 'USERNAME', 'anonymous'),
            'password' => env($prefix . 'PASSWORD', ''),
            'path' => env($prefix . 'PATH', '/'),
            'passive' => env($prefix . 'PASSIVE', true),
            'ssl' => env($prefix . 'SSL', false),
            'timeout' => env($prefix . 'TIMEOUT', 90),
        ];

        $connections[$connName] = $config;
    }

    return $connections;
}

return [
    /**
     * Default FTP connection name
     * This will be used when you call FTP::connection() without arguments
     */
    'default' => env('FTP_DEFAULT', 'default'),

    /**
     * FTP Connections
     * Auto-discovered from environment variables
     */
    'connections' => discoverFtpConnections(),

    /**
     * Global FTP Settings
     */
    'global' => [
        'max_retries' => 3,
        'retry_delay' => 1, // seconds
        'verify_ssl' => false,
    ],
];
