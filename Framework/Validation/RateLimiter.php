<?php

namespace Framework\Validation;

use Framework\Database\DB;
use Exception;

/**
 * RateLimiter - Rate limiting service
 *
 * Singleton service that provides request throttling with multiple backends.
 * Supports both database (persistent) and memory (non-persistent) backends.
 * Follows Pop Framework's singleton pattern.
 */
class RateLimiter
{
    /**
     * @var RateLimiter|null Singleton instance
     */
    private static ?RateLimiter $instance = null;

    /**
     * @var string Backend type ('database' or 'memory')
     */
    private string $backend = 'database';

    /**
     * @var array Memory cache for attempts (used with memory backend)
     */
    private array $cache = [];

    /**
     * @var array Configuration
     */
    private array $config = [];

    /**
     * Get singleton instance
     *
     * @return RateLimiter
     */
    public static function getInstance(): RateLimiter
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor - Singleton pattern
     */
    private function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Load rate limit configuration
     *
     * @return void
     */
    private function loadConfiguration(): void
    {
        $configPath = defined('ROOT_PATH') ? ROOT_PATH . '/Config/RateLimit.php' : __DIR__ . '/../../Config/RateLimit.php';

        if (file_exists($configPath)) {
            $this->config = require $configPath;
            $this->backend = $this->config['backend'] ?? 'database';
        } else {
            $this->config = [
                'backend' => 'database',
                'default' => [
                    'max_attempts' => 60,
                    'decay_minutes' => 1,
                ],
            ];
        }
    }

    /**
     * Attempt to perform an action
     * Returns true if allowed, false if too many attempts
     *
     * @param string $key Unique identifier for the rate limit
     * @param int $maxAttempts Maximum number of attempts
     * @param int $decayMinutes Time window in minutes
     * @return bool
     */
    public function attempt(string $key, int $maxAttempts, int $decayMinutes = 1): bool
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $this->hit($key, $decayMinutes);
        return true;
    }

    /**
     * Check if too many attempts have been made
     *
     * @param string $key Unique identifier
     * @param int $maxAttempts Maximum attempts allowed
     * @return bool
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * Increment the attempt counter
     *
     * @param string $key Unique identifier
     * @param int $decayMinutes Time window in minutes
     * @return int New attempt count
     */
    public function hit(string $key, int $decayMinutes = 1): int
    {
        if ($this->backend === 'database') {
            return $this->hitDatabase($key, $decayMinutes);
        }

        return $this->hitMemory($key, $decayMinutes);
    }

    /**
     * Get the number of attempts
     *
     * @param string $key Unique identifier
     * @return int
     */
    public function attempts(string $key): int
    {
        if ($this->backend === 'database') {
            return $this->getDatabaseAttempts($key);
        }

        return $this->getMemoryAttempts($key);
    }

    /**
     * Reset attempts for a key
     *
     * @param string $key Unique identifier
     * @return void
     */
    public function resetAttempts(string $key): void
    {
        if ($this->backend === 'database') {
            $this->resetDatabaseAttempts($key);
        } else {
            $this->resetMemoryAttempts($key);
        }
    }

    /**
     * Get remaining attempts
     *
     * @param string $key Unique identifier
     * @param int $maxAttempts Maximum attempts allowed
     * @return int
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = $this->attempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get seconds until rate limit resets
     *
     * @param string $key Unique identifier
     * @return int Seconds until reset
     */
    public function availableIn(string $key): int
    {
        if ($this->backend === 'database') {
            return $this->getDatabaseAvailableIn($key);
        }

        return $this->getMemoryAvailableIn($key);
    }

    /**
     * Clear all rate limits (useful for testing)
     *
     * @return void
     */
    public function clear(): void
    {
        if ($this->backend === 'database') {
            $this->clearDatabase();
        } else {
            $this->cache = [];
        }
    }

    /**
     * Throttle by IP address
     *
     * @param int $maxAttempts Maximum attempts
     * @param int $decayMinutes Time window
     * @return bool
     */
    public function forIp(int $maxAttempts, int $decayMinutes = 1): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'ip:' . $ip;

        return $this->attempt($key, $maxAttempts, $decayMinutes);
    }

    /**
     * Throttle by user ID
     *
     * @param int|string $userId User ID
     * @param int $maxAttempts Maximum attempts
     * @param int $decayMinutes Time window
     * @return bool
     */
    public function forUser(int|string $userId, int $maxAttempts, int $decayMinutes = 1): bool
    {
        $key = 'user:' . $userId;

        return $this->attempt($key, $maxAttempts, $decayMinutes);
    }

    // ===== DATABASE BACKEND METHODS =====

    /**
     * Hit counter in database
     *
     * @param string $key Unique identifier
     * @param int $decayMinutes Time window
     * @return int New attempt count
     */
    private function hitDatabase(string $key, int $decayMinutes): int
    {
        try {
            $conn = DB::connection();
            $expiresAt = date('Y-m-d H:i:s', time() + ($decayMinutes * 60));

            // Check if key exists
            $stmt = $conn->prepare("SELECT id, attempts FROM rate_limit_attempts WHERE key = ? AND expires_at > datetime('now')");
            $stmt->execute([$key]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                // Increment existing
                $newAttempts = $existing['attempts'] + 1;
                $stmt = $conn->prepare("UPDATE rate_limit_attempts SET attempts = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$newAttempts, $existing['id']]);
                return $newAttempts;
            } else {
                // Create new
                $stmt = $conn->prepare("INSERT INTO rate_limit_attempts (key, attempts, expires_at) VALUES (?, 1, ?)");
                $stmt->execute([$key, $expiresAt]);
                return 1;
            }
        } catch (Exception $e) {
            error_log("Rate limiter database error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get attempts from database
     *
     * @param string $key Unique identifier
     * @return int
     */
    private function getDatabaseAttempts(string $key): int
    {
        try {
            $conn = DB::connection();
            $stmt = $conn->prepare("SELECT attempts FROM rate_limit_attempts WHERE key = ? AND expires_at > datetime('now')");
            $stmt->execute([$key]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result ? (int) $result['attempts'] : 0;
        } catch (Exception $e) {
            error_log("Rate limiter database error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Reset database attempts
     *
     * @param string $key Unique identifier
     * @return void
     */
    private function resetDatabaseAttempts(string $key): void
    {
        try {
            $conn = DB::connection();
            $stmt = $conn->prepare("DELETE FROM rate_limit_attempts WHERE key = ?");
            $stmt->execute([$key]);
        } catch (Exception $e) {
            error_log("Rate limiter database error: " . $e->getMessage());
        }
    }

    /**
     * Get seconds until database rate limit resets
     *
     * @param string $key Unique identifier
     * @return int
     */
    private function getDatabaseAvailableIn(string $key): int
    {
        try {
            $conn = DB::connection();
            $stmt = $conn->prepare("SELECT expires_at FROM rate_limit_attempts WHERE key = ? AND expires_at > datetime('now')");
            $stmt->execute([$key]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                $expiresAt = strtotime($result['expires_at']);
                $now = time();
                return max(0, $expiresAt - $now);
            }

            return 0;
        } catch (Exception $e) {
            error_log("Rate limiter database error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear all database rate limits
     *
     * @return void
     */
    private function clearDatabase(): void
    {
        try {
            $conn = DB::connection();
            $stmt = $conn->prepare("DELETE FROM rate_limit_attempts");
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Rate limiter database error: " . $e->getMessage());
        }
    }

    // ===== MEMORY BACKEND METHODS =====

    /**
     * Hit counter in memory
     *
     * @param string $key Unique identifier
     * @param int $decayMinutes Time window
     * @return int New attempt count
     */
    private function hitMemory(string $key, int $decayMinutes): int
    {
        $this->cleanExpiredMemory();

        $expiresAt = time() + ($decayMinutes * 60);

        if (isset($this->cache[$key])) {
            $this->cache[$key]['attempts']++;
            $this->cache[$key]['expires_at'] = $expiresAt;
        } else {
            $this->cache[$key] = [
                'attempts' => 1,
                'expires_at' => $expiresAt,
            ];
        }

        return $this->cache[$key]['attempts'];
    }

    /**
     * Get attempts from memory
     *
     * @param string $key Unique identifier
     * @return int
     */
    private function getMemoryAttempts(string $key): int
    {
        $this->cleanExpiredMemory();

        if (isset($this->cache[$key]) && $this->cache[$key]['expires_at'] > time()) {
            return $this->cache[$key]['attempts'];
        }

        return 0;
    }

    /**
     * Reset memory attempts
     *
     * @param string $key Unique identifier
     * @return void
     */
    private function resetMemoryAttempts(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Get seconds until memory rate limit resets
     *
     * @param string $key Unique identifier
     * @return int
     */
    private function getMemoryAvailableIn(string $key): int
    {
        if (isset($this->cache[$key]) && $this->cache[$key]['expires_at'] > time()) {
            return $this->cache[$key]['expires_at'] - time();
        }

        return 0;
    }

    /**
     * Clean expired entries from memory cache
     *
     * @return void
     */
    private function cleanExpiredMemory(): void
    {
        $now = time();

        foreach ($this->cache as $key => $data) {
            if ($data['expires_at'] <= $now) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Set backend (for testing or runtime configuration)
     *
     * @param string $backend 'database' or 'memory'
     * @return void
     */
    public function setBackend(string $backend): void
    {
        if (in_array($backend, ['database', 'memory'])) {
            $this->backend = $backend;
        }
    }

    /**
     * Get current backend
     *
     * @return string
     */
    public function getBackend(): string
    {
        return $this->backend;
    }
}
