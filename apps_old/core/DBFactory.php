<?php
/**
 * PostgreSQL-Optimized Database Connection Factory (CORRECTED DSN)
 * File: core/DBFactory.php
 */

class DBConnectionFactory {
    private $db;
    private static $instance = null;
    private $connectionAttempts = 0;
    private $lastConnectionTime = 0;
    private $connectionStats = [];

    public function __construct() {
        $this->db = null;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function createConnection() {
        if ($this->db && $this->isConnectionAlive()) {
            return $this->db;
        }
        return $this->createConnectionWithRetry();
    }

    /**
     * FIXED: Correct PostgreSQL DSN format
     * PostgreSQL does NOT support 'charset' in DSN - that's MySQL only!
     */
    private function buildDSN() {
        $driver = 'pgsql';
        $host = db_config('host');
        $port = db_config('port') ?? 5432;
        $database = db_config('database');
        
        // PostgreSQL DSN format - NO charset parameter!
        return "{$driver}:host={$host};port={$port};dbname={$database}";
    }

    private function createConnectionWithRetry() {
        $DSN = $this->buildDSN();
        $username = db_config('username');
        $password = db_config('password');
        $maxRetries = 3;
        $baseTimeout = 10;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $start = microtime(true);
            $timeout = $baseTimeout + ($attempt * 5);
            
            try {
                // PostgreSQL-specific PDO options
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_TIMEOUT => $timeout,
                    
                    // CAREFUL: Persistent connections can cause issues in PostgreSQL
                    // Only enable if your PostgreSQL is configured for connection pooling
                    PDO::ATTR_PERSISTENT => false, // Changed to false for safety
                    
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false, // Keep proper data types
                ];

                $this->db = new PDO($DSN, $username, $password, $options);
                
                // FIXED: Set charset AFTER connection, not in DSN
                $charset = 'UTF8';
                $this->db->exec("SET client_encoding = '{$charset}'");
                
                // PostgreSQL session optimization
                $this->db->exec("SET statement_timeout = '300s'");
                $this->db->exec("SET lock_timeout = '30s'");
                $this->db->exec("SET idle_in_transaction_session_timeout = '300s'");
                
                // Optional: Set timezone
                $timezone = 'UTC';
                $this->db->exec("SET timezone = '{$timezone}'");
                
                // TCP keepalive settings (wrap in try-catch as they're system-dependent)
                try {
                    $this->db->exec("SET tcp_keepalives_idle = 300");
                    $this->db->exec("SET tcp_keepalives_interval = 30");
                    $this->db->exec("SET tcp_keepalives_count = 3");
                } catch (PDOException $keepaliveException) {
                    // Ignore keepalive errors - not all systems support them
                    if (app_debug()) {
                        error_log("TCP keepalive settings not supported: " . $keepaliveException->getMessage());
                    }
                }
                
                // Test connection
                $testStart = microtime(true);
                $stmt = $this->db->query("SELECT 1 as test");
                $testTime = (microtime(true) - $testStart) * 1000;
                
                $connectionTime = (microtime(true) - $start) * 1000;
                $this->lastConnectionTime = $connectionTime;
                $this->connectionAttempts++;
                
                $this->logConnectionStats($attempt, $connectionTime, $testTime, true);
                
                if ($connectionTime > 200 && app_debug()) {
                    error_log("SLOW DB CONNECTION: {$connectionTime}ms on attempt $attempt (query test: {$testTime}ms)");
                }
                
                return $this->db;
                
            } catch (PDOException $e) {
                $failedTime = (microtime(true) - $start) * 1000;
                $this->logConnectionStats($attempt, $failedTime, 0, false, $e->getMessage());
                
                if (app_debug() || !is_local()) {
                    error_log("PostgreSQL connection attempt $attempt failed after {$failedTime}ms: " . $e->getMessage());
                }
                
                if ($attempt < $maxRetries) {
                    $waitTime = pow(2, $attempt) * 100000; // 200ms, 400ms, 800ms
                    usleep($waitTime);
                } else {
                    $totalTime = array_sum(array_column($this->connectionStats, 'time'));
                    $errorMsg = "PostgreSQL connection failed after $maxRetries attempts. Total time: {$totalTime}ms";
                    
                    if (app_debug()) {
                        error_log($errorMsg . " - " . $e->getMessage());
                    }
                    
                    throw new Exception($errorMsg);
                }
            }
        }
    }

    private function isConnectionAlive() {
        if (!$this->db) {
            return false;
        }

        try {
            $start = microtime(true);
            $stmt = $this->db->query("SELECT 1");
            $pingTime = (microtime(true) - $start) * 1000;
            
            if ($pingTime > 1000) {
                if (app_debug()) {
                    error_log("Connection ping too slow: {$pingTime}ms - reconnecting");
                }
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            if (app_debug()) {
                error_log("PostgreSQL connection health check failed: " . $e->getMessage());
            }
            return false;
        }
    }

    public function executeQuery($query, $params = []) {
        $maxRetries = 2;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $db = $this->createConnection();
                $start = microtime(true);
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                
                $queryTime = (microtime(true) - $start) * 1000;
                
                if ($queryTime > 500 && app_debug()) {
                    error_log("SLOW QUERY ({$queryTime}ms): " . substr($query, 0, 100) . "...");
                }
                
                return $stmt;
                
            } catch (Exception $e) {
                if (app_debug()) {
                    error_log("Query attempt $attempt failed: " . $e->getMessage());
                }
                
                if ($attempt < $maxRetries) {
                    $this->db = null;
                    usleep(200000);
                } else {
                    throw $e;
                }
            }
        }
    }

    private function logConnectionStats($attempt, $connectionTime, $testTime, $success, $error = null) {
        $this->connectionStats[] = [
            'timestamp' => time(),
            'attempt' => $attempt,
            'time' => $connectionTime,
            'test_time' => $testTime,
            'success' => $success,
            'error' => $error,
            'environment' => app_env(),
            'tenant' => app_tenant()
        ];

        if (count($this->connectionStats) > 50) {
            array_shift($this->connectionStats);
        }
    }

    public function getConnectionStats() {
        $total = count($this->connectionStats);
        $successful = count(array_filter($this->connectionStats, function($stat) {
            return $stat['success'];
        }));
        
        $avgTime = $total > 0 ? array_sum(array_column($this->connectionStats, 'time')) / $total : 0;
        
        return [
            'total_attempts' => $total,
            'successful' => $successful,
            'success_rate' => $total > 0 ? ($successful / $total) * 100 : 0,
            'avg_connection_time' => round($avgTime, 2),
            'last_connection_time' => $this->lastConnectionTime,
            'database_info' => [
                'host' => db_config('host'),
                'database' => db_config('database'),
                'driver' => db_config('driver'),
                'version' => $this->getPostgreSQLVersion()
            ],
            'environment' => app_env(),
            'recent_stats' => array_slice($this->connectionStats, -10)
        ];
    }

    /**
     * Get PostgreSQL version for monitoring
     */
    private function getPostgreSQLVersion() {
        try {
            if ($this->db) {
                $stmt = $this->db->query("SELECT version()");
                $result = $stmt->fetch();
                return $result->version ?? 'Unknown';
            }
        } catch (Exception $e) {
            return 'Unable to determine version';
        }
        return 'Not connected';
    }

    public function resetConnection() {
        $this->db = null;
    }

    public function getHealthStatus() {
        $stats = $this->getConnectionStats();
        
        if ($stats['success_rate'] < 70) {
            return 'critical';
        } elseif ($stats['avg_connection_time'] > 500) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }

    public function getConfig() {
        return [
            'host' => db_config('host'),
            'port' => db_config('port'),
            'database' => db_config('database'),
            'username' => db_config('username'),
            'driver' => 'pgsql',
            'charset' => 'UTF8',
            'timezone' => 'UTC',
            'environment' => app_env(),
            'tenant' => app_tenant()
        ];
    }
}

// Your existing FTP and helper classes remain the same
class FTPConnectionFactory {
    private $ftp;
    private $connect;

    public function __construct() {
        $this->ftp = null;
    }

    public function createConnection() {
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
    
    public function getConfig() {
        return [
            'host' => ftp_config('host'),
            'port' => ftp_config('port'),
            'username' => ftp_config('username'),
            'path' => ftp_config('path'),
            'environment' => app_env()
        ];
    }
}

class DB {
    public static function connection() {
        return DBConnectionFactory::getInstance()->createConnection();
    }
    
    public static function query($query, $params = []) {
        return DBConnectionFactory::getInstance()->executeQuery($query, $params);
    }
    
    public static function stats() {
        return DBConnectionFactory::getInstance()->getConnectionStats();
    }
    
    public static function health() {
        return DBConnectionFactory::getInstance()->getHealthStatus();
    }
    
    public static function config() {
        return DBConnectionFactory::getInstance()->getConfig();
    }
    
    public static function reset() {
        return DBConnectionFactory::getInstance()->resetConnection();
    }
}

class FTP {
    public static function connection() {
        return (new FTPConnectionFactory())->createConnection();
    }
    
    public static function config() {
        return (new FTPConnectionFactory())->getConfig();
    }
}
