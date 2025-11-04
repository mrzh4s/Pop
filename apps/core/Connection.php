<?php

/**
 * Enhanced Database Factory with Multi-Connection Support
 * File: apps/core/DBFactory.php
 * 
 * CHANGES:
 * - Added support for named connections (main, source, dest)
 * - Keeps backward compatibility with existing DB::connection()
 * - Added SQLite support alongside PostgreSQL
 */

class DBConnectionFactory
{
    private static $instance = null;
    private $connections = [];
    private $connectionAttempts = [];
    private $lastConnectionTime = [];
    private $connectionStats = [];

    public function __construct()
    {
        // Initialize empty connections array
        $this->connections = [
            'main' => null,
            'source' => null,
            'dest' => null
        ];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get connection by name (main, source, dest)
     * Defaults to 'main' for backward compatibility
     */
    public function getConnection($name = 'main')
    {
        // Validate connection name
        $validConnections = ['main', 'source', 'dest'];
        if (!in_array($name, $validConnections)) {
            throw new Exception("Invalid connection name: {$name}. Valid options: " . implode(', ', $validConnections));
        }

        // Return existing connection if alive
        if ($this->connections[$name] && $this->isConnectionAlive($this->connections[$name], $name)) {
            return $this->connections[$name];
        }

        // Create new connection
        return $this->createConnection($name);
    }

    /**
     * Create connection based on type
     */
    private function createConnection($name)
    {
        switch ($name) {
            case 'main':
                return $this->createSQLiteConnection();
            case 'source':
                return $this->createPostgreSQLConnection('source');
            case 'dest':
                return $this->createPostgreSQLConnection('dest');
            default:
                throw new Exception("Unknown connection type: {$name}");
        }
    }

    /**
     * Create PostgreSQL connection with retry logic
     */
    private function createPostgreSQLConnection($configGroup)
    {
        $config = Configuration::getInstance();

        // Get config from the new system
        $host = $config->get("{$configGroup}.host");
        $port = $config->get("{$configGroup}.port", 5432);
        $database = $config->get("{$configGroup}.database");
        $username = $config->get("{$configGroup}.username");
        $password = $config->get("{$configGroup}.password");

        if (!$host || !$database) {
            throw new Exception("Invalid database configuration for {$configGroup}");
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

        $maxRetries = 3;
        $baseTimeout = 10;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $start = microtime(true);
            $timeout = $baseTimeout + ($attempt * 5);

            try {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_TIMEOUT => $timeout,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ];

                $pdo = new PDO($dsn, $username, $password, $options);

                // Set charset AFTER connection
                $pdo->exec("SET client_encoding = 'UTF8'");

                // PostgreSQL session optimization
                $pdo->exec("SET statement_timeout = '300s'");
                $pdo->exec("SET lock_timeout = '30s'");
                $pdo->exec("SET idle_in_transaction_session_timeout = '300s'");
                $pdo->exec("SET timezone = 'UTC'");

                // Test connection
                $pdo->query("SELECT 1");

                $connectionTime = (microtime(true) - $start) * 1000;
                $this->lastConnectionTime[$configGroup] = $connectionTime;

                if (!isset($this->connectionAttempts[$configGroup])) {
                    $this->connectionAttempts[$configGroup] = 0;
                }
                $this->connectionAttempts[$configGroup]++;

                $this->logConnectionStats($configGroup, $attempt, $connectionTime, true);

                if ($connectionTime > 200 && app_debug()) {
                    error_log("SLOW DB CONNECTION ({$configGroup}): {$connectionTime}ms on attempt $attempt");
                }

                $this->connections[$configGroup] = $pdo;

                return $pdo;
            } catch (PDOException $e) {
                $failedTime = (microtime(true) - $start) * 1000;
                $this->logConnectionStats($configGroup, $attempt, $failedTime, false, $e->getMessage());

                if (app_debug()) {
                    error_log("PostgreSQL connection ({$configGroup}) attempt $attempt failed: " . $e->getMessage());
                }

                if ($attempt < $maxRetries) {
                    $waitTime = pow(2, $attempt) * 100000;
                    usleep($waitTime);
                } else {
                    throw new Exception("PostgreSQL connection ({$configGroup}) failed after {$maxRetries} attempts: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Create SQLite connection
     */
    private function createSQLiteConnection()
    {
        $config = Configuration::getInstance();
        $dbPath = $config->get('app.db', 'database/app.db');

        // Convert relative path to absolute
        if (strpos($dbPath, '/') !== 0) {
            $dbPath = ROOT_PATH . '/' . ltrim($dbPath, '/');
        }

        // Create directory if not exists
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("Failed to create database directory: {$dir}");
            }
        }

        // Check if directory is writable
        if (!is_writable($dir)) {
            throw new Exception("Database directory is not writable: {$dir}. Current permissions: " . substr(sprintf('%o', fileperms($dir)), -4));
        }

        // If database file exists, check if it's writable
        if (file_exists($dbPath) && !is_writable($dbPath)) {
            throw new Exception("Database file exists but is not writable: {$dbPath}");
        }

        $dsn = "sqlite:{$dbPath}";

        try {
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            // Enable foreign keys
            $pdo->exec("PRAGMA foreign_keys = ON");

            // Performance optimizations
            $pdo->exec("PRAGMA journal_mode = WAL");
            $pdo->exec("PRAGMA synchronous = NORMAL");
            $pdo->exec("PRAGMA cache_size = 10000");

            // Test connection
            $pdo->query("SELECT 1");

            if (app_debug()) {
                error_log("SQLite connected: {$dbPath}");
            }

            $this->connections['main'] = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            error_log("SQLite connection failed: " . $e->getMessage());
            error_log("Database path: {$dbPath}");
            error_log("Directory exists: " . (is_dir($dir) ? 'yes' : 'no'));
            error_log("Directory writable: " . (is_writable($dir) ? 'yes' : 'no'));
            throw new Exception("Failed to connect to SQLite database: " . $e->getMessage() . " (Path: {$dbPath})");
        }
    }

    /**
     * Check if connection is alive
     */
    private function isConnectionAlive($pdo, $name = 'unknown')
    {
        if (!$pdo) {
            return false;
        }

        try {
            $start = microtime(true);
            $pdo->query("SELECT 1");
            $pingTime = (microtime(true) - $start) * 1000;

            if ($pingTime > 1000) {
                if (app_debug()) {
                    error_log("Connection ({$name}) ping too slow: {$pingTime}ms - reconnecting");
                }
                return false;
            }

            return true;
        } catch (Exception $e) {
            if (app_debug()) {
                error_log("Connection ({$name}) health check failed: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Log connection statistics
     */
    private function logConnectionStats($type, $attempt, $time, $success, $error = null)
    {
        if (!isset($this->connectionStats[$type])) {
            $this->connectionStats[$type] = [];
        }

        $this->connectionStats[$type][] = [
            'attempt' => $attempt,
            'time' => round($time, 2),
            'success' => $success,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get connection statistics for a specific connection or all
     */
    public function getConnectionStats($name = null)
    {
        if ($name) {
            $configGroup = $this->getConfigGroupFromName($name);
            return $this->connectionStats[$configGroup] ?? [];
        }
        return $this->connectionStats;
    }

    /**
     * Map connection name to config group
     */
    private function getConfigGroupFromName($name)
    {
        $map = [
            'main' => 'db',
            'source' => 'source',
            'dest' => 'dest',
        ];
        return $map[$name] ?? 'db';
    }

    /**
     * Test all connections
     */
    public function testAllConnections()
    {
        $results = [];

        foreach (['main', 'source', 'dest'] as $name) {
            try {
                $conn = $this->getConnection($name);
                $conn->query("SELECT 1");
                $results[$name] = [
                    'status' => 'connected',
                    'error' => null
                ];
            } catch (Exception $e) {
                $results[$name] = [
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Reset specific connection
     */
    public function resetConnection($name = 'main')
    {
        if (isset($this->connections[$name])) {
            $this->connections[$name] = null;
        }
    }

    /**
     * Reset all connections
     */
    public function resetAllConnections()
    {
        foreach (array_keys($this->connections) as $name) {
            $this->connections[$name] = null;
        }
    }
}

// ============== FTP CLASS (unchanged) ==============
class FTPConnectionFactory
{
    private $ftp;
    private $connect;

    public function __construct()
    {
        $this->ftp = null;
    }

    public function createConnection()
    {
        if (!$this->ftp) {
            try {
                $host = ftp_config('host');
                $port = ftp_config('port');
                $username = ftp_config('username');
                $password = ftp_config('password');

                $this->connect = ftp_connect($host, $port);

                if (!$this->connect) {
                    throw new Exception("FTP connection failed to {$host}:{$port}");
                }

                $this->ftp = ftp_login($this->connect, $username, $password);

                if (!$this->ftp) {
                    throw new Exception("FTP login failed for user: {$username}");
                }

                ftp_pasv($this->connect, true);

                if (app_debug()) {
                    error_log("FTP connected successfully to {$host}");
                }
            } catch (Exception $e) {
                $errorMsg = "FTP connection initialization failed: " . $e->getMessage();

                if (app_debug()) {
                    error_log($errorMsg);
                }

                throw new Exception($errorMsg);
            }
        }

        return $this->connect;
    }

    public function getConfig()
    {
        return [
            'host' => ftp_config('host'),
            'port' => ftp_config('port'),
            'username' => ftp_config('username'),
            'path' => ftp_config('path'),
            'environment' => app_env()
        ];
    }
}

// ============== ENHANCED DB HELPER CLASS ==============
class DB
{
    /**
     * Get main database connection (backward compatibility)
     */

    public static function connection($name)
    {
        if (!empty($name)) {
            return DBConnectionFactory::getInstance()->getConnection($name);
        }

        return throw new Exception("Connection name cannot be empty.");
    }

    /**
     * Execute query on specific connection
     */
    public static function query($query, $params = [], $connection = 'main')
    {
        try{
            $pdo = self::connection($connection);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
            
        } catch (Exception $e) {
            error_log("DB query error on connection ({$connection}): " . $e->getMessage());
            throw $e;
        }

    }

    /**
     * Get connection statistics
     */
    public static function stats($name = null)
    {
        return DBConnectionFactory::getInstance()->getConnectionStats($name);
    }

    /**
     * Get health status
     */
    public static function health()
    {
        return DBConnectionFactory::getInstance()->testAllConnections();
    }

    /**
     * Get configuration for specific connection
     */
    public static function config($name = 'main')
    {
        switch ($name) {
            case 'main':
                return [
                    'path' => sqlite_config(),
                    'driver' => 'sqlite'
                ];
            case 'source':
                return [
                    'host' => source_db_config('db_host'),
                    'database' => source_db_config('db_database'),
                    'driver' => 'pgsql'
                ];
            case 'dest':
                return [
                    'host' => dest_db_config('db_host'),
                    'database' => dest_db_config('db_database'),
                    'driver' => 'pgsql'
                ];
            default:
                return [];
        }
    }

    /**
     * Reset specific connection
     */
    public static function reset($name = 'main')
    {
        return DBConnectionFactory::getInstance()->resetConnection($name);
    }

    /**
     * Reset all connections
     */
    public static function resetAll()
    {
        return DBConnectionFactory::getInstance()->resetAllConnections();
    }
}

// ============== FTP HELPER CLASS (unchanged) ==============
class FTP
{
    public static function connection()
    {
        return (new FTPConnectionFactory())->createConnection();
    }

    public static function config()
    {
        return (new FTPConnectionFactory())->getConfig();
    }
}
