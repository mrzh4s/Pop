<?php
/**
 * Database Connection Helper Functions
 * File: apps/core/helpers/connection.php
 * 
 */

// ============== MAIN DATABASE HELPERS ==============

/**
 * Get database connection
 * Now supports named connections: db('main'), db('source'), db('dest'), db('sqlite')
 * Defaults to 'main' for backward compatibility
 */
if (!function_exists('db')) {
    function db($name = 'main') {
        return DB::connection($name);
    }
}

/**
 * Get database query builder
 */
if (!function_exists('db_query')) {
    function db_query($connection, $query, $params = []) {
        $connection = $connection ?? 'main';
        return DB::query($query, $params, $connection);

    }
}


/**
 * Get database health status
 */
if (!function_exists('db_health')) {
    function db_health() {
        return DB::health();
    }
}

/**
 * Get database statistics
 */
if (!function_exists('db_stats')) {
    function db_stats($name = null) {
        return DB::stats($name);
    }
}


// ============== FTP HELPERS (unchanged) ==============

if (!function_exists('ftp')) {
    function ftp() {
        return FTP::connection();
    }
}

if (!function_exists('ftp_config_info')) {
    function ftp_config_info() {
        return FTP::config();
    }
}