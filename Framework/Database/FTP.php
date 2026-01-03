<?php

/**
 * Enhanced Database Factory with Dynamic Multi-Connection Support
 * File: apps/core/Connection.php
 *
 * FEATURES:
 * - Auto-discovers connections from config/Database.php
 * - Support for unlimited named connections
 * - Multiple database drivers: SQLite, PostgreSQL, MySQL, SQL Server
 * - Connection pooling with health checks
 * - Retry logic with exponential backoff
 * - Backward compatible with legacy code
 */
namespace Framework\Database;
use Framework\Database\Factories\FTPConnection;
// ============== FTP HELPER CLASS ==============
class FTP
{
    public static function connection()
    {
        return (new FTPConnection())->createConnection();
    }

    public static function config()
    {
        return (new FTPConnection())->getConfig();
    }
}