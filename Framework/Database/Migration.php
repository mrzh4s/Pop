<?php
/**
 * Database Migration Manager
 * File: apps/core/Migration.php
 *
 * Automatically runs database migrations on first start
 * Tracks which migrations have been executed
 */
namespace Framework\Database;

use Framework\Database\DB;
use Exception;
use PDO;

class Migration {
    private static $instance = null;
    private $db;
    private $migrationsPath;
    private $migrationsTable = 'migrations';

    private function __construct() {
        // Use default connection (main/PostgreSQL) instead of hardcoded 'app'
        $this->db = DB::connection(); // This will use DB_DEFAULT from .env
        $this->migrationsPath = ROOT_PATH . '/Infrastructure/Persistence/Migrations';
        $this->ensureMigrationsTable();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create migrations tracking table if it doesn't exist
     * Supports both PostgreSQL and SQLite
     */
    private function ensureMigrationsTable() {
        try {
            // Detect database driver
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'pgsql') {
                // PostgreSQL syntax
                $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                    id SERIAL PRIMARY KEY,
                    migration VARCHAR(255) UNIQUE NOT NULL,
                    batch INTEGER NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
            } else {
                // SQLite syntax
                $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration VARCHAR(255) UNIQUE NOT NULL,
                    batch INTEGER NOT NULL,
                    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )";
            }

            $this->db->exec($sql);
        } catch (Exception $e) {
            error_log("Failed to create migrations table: " . $e->getMessage());
        }
    }

    /**
     * Run all pending migrations
     */
    public function run() {
        try {
            $pendingMigrations = $this->getPendingMigrations();

            if (empty($pendingMigrations)) {
                if (app_debug()) {
                    error_log("No pending migrations to run");
                }
                return [
                    'success' => true,
                    'migrated' => [],
                    'message' => 'No pending migrations'
                ];
            }

            $batch = $this->getNextBatchNumber();
            $migrated = [];
            $errors = [];

            foreach ($pendingMigrations as $migration) {
                try {
                    $this->runMigration($migration, $batch);
                    $migrated[] = $migration;

                    if (app_debug()) {
                        error_log("Migrated: {$migration}");
                    }
                } catch (Exception $e) {
                    $errors[] = [
                        'migration' => $migration,
                        'error' => $e->getMessage()
                    ];
                    error_log("Migration failed [{$migration}]: " . $e->getMessage());
                }
            }

            return [
                'success' => empty($errors),
                'migrated' => $migrated,
                'errors' => $errors,
                'message' => count($migrated) . ' migrations executed'
            ];

        } catch (Exception $e) {
            error_log("Migration run error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Run a single migration file
     */
    private function runMigration($migration, $batch) {
        $filePath = $this->migrationsPath . '/' . $migration;

        if (!file_exists($filePath)) {
            throw new Exception("Migration file not found: {$filePath}");
        }

        // Read SQL file
        $sql = file_get_contents($filePath);

        if ($sql === false) {
            throw new Exception("Failed to read migration file: {$migration}");
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Execute SQL
            $this->db->exec($sql);

            // Record migration
            $stmt = $this->db->prepare("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)");
            $stmt->execute([$migration, $batch]);

            // Commit transaction
            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get list of pending migrations
     */
    private function getPendingMigrations() {
        // Get all migration files
        $allMigrations = $this->getAllMigrationFiles();

        // Get executed migrations
        $executedMigrations = $this->getExecutedMigrations();

        // Return difference
        return array_diff($allMigrations, $executedMigrations);
    }

    /**
     * Get all migration files from directory
     */
    private function getAllMigrationFiles() {
        if (!is_dir($this->migrationsPath)) {
            if (app_debug()) {
                error_log("Migrations directory not found: {$this->migrationsPath}");
            }
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^\d{3}_.*\.sql$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Get list of executed migrations
     */
    private function getExecutedMigrations() {
        try {
            $stmt = $this->db->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id");
            if ($stmt === false) {
                return [];
            }
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $results ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get next batch number
     */
    private function getNextBatchNumber() {
        try {
            $stmt = $this->db->query("SELECT MAX(batch) FROM {$this->migrationsTable}");
            if ($stmt === false) {
                return 1;
            }
            $maxBatch = $stmt->fetchColumn();
            return ($maxBatch ?? 0) + 1;
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Check if migrations are pending
     */
    public function hasPending() {
        $pending = $this->getPendingMigrations();
        return !empty($pending);
    }

    /**
     * Get migration status
     */
    public function status() {
        try {
            $allMigrations = $this->getAllMigrationFiles();
            $executedMigrations = $this->getExecutedMigrations();
            $pendingMigrations = array_diff($allMigrations, $executedMigrations);

            $status = [];

            foreach ($allMigrations as $migration) {
                $status[] = [
                    'migration' => $migration,
                    'status' => in_array($migration, $executedMigrations) ? 'executed' : 'pending'
                ];
            }

            return [
                'total' => count($allMigrations),
                'executed' => count($executedMigrations),
                'pending' => count($pendingMigrations),
                'migrations' => $status
            ];

        } catch (Exception $e) {
            error_log("Migration status error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Rollback last batch of migrations
     */
    public function rollback() {
        try {
            $lastBatch = $this->getLastBatch();

            if (!$lastBatch) {
                return [
                    'success' => true,
                    'message' => 'Nothing to rollback'
                ];
            }

            $migrations = $this->getMigrationsByBatch($lastBatch);
            $rolledBack = [];

            foreach (array_reverse($migrations) as $migration) {
                try {
                    // For SQLite, we can't easily rollback schema changes
                    // This would require down migrations which we haven't implemented
                    // For now, just remove from tracking
                    $stmt = $this->db->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
                    $stmt->execute([$migration]);

                    $rolledBack[] = $migration;
                    error_log("Rolled back (tracking only): {$migration}");
                } catch (Exception $e) {
                    error_log("Rollback failed [{$migration}]: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'rolled_back' => $rolledBack,
                'message' => count($rolledBack) . ' migrations rolled back (tracking only)'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get last batch number
     */
    private function getLastBatch() {
        try {
            $stmt = $this->db->query("SELECT MAX(batch) FROM {$this->migrationsTable}");
            if ($stmt === false) {
                return null;
            }
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get migrations by batch number
     */
    private function getMigrationsByBatch($batch) {
        try {
            $stmt = $this->db->prepare("SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id");
            $stmt->execute([$batch]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Reset all migrations (dangerous!)
     */
    public function reset() {
        try {
            $this->db->exec("DROP TABLE IF EXISTS {$this->migrationsTable}");
            $this->ensureMigrationsTable();

            return [
                'success' => true,
                'message' => 'Migration tracking reset. Run migrations again.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Auto-run migrations on app startup (if enabled)
     */
    public static function autoRun() {
        try {
            $autoMigrate = env('AUTO_MIGRATE', true);

            if (!$autoMigrate) {
                return;
            }

            // Try to get instance - may fail if DB driver not available
            try {
                $migration = self::getInstance();
            } catch (Exception $e) {
                error_log("Auto-migration error: " . $e->getMessage());
                return;
            } catch (\Throwable $e) {
                error_log("Auto-migration error: " . $e->getMessage());
                return;
            }

            if ($migration->hasPending()) {
                if (app_debug()) {
                    error_log("Auto-running pending migrations...");
                }

                $result = $migration->run();

                if ($result['success']) {
                    error_log("Auto-migration completed: {$result['message']}");
                } else {
                    error_log("Auto-migration failed");
                }
            }

        } catch (Exception $e) {
            error_log("Auto-migration error: " . $e->getMessage());
        } catch (\Throwable $e) {
            error_log("Auto-migration error: " . $e->getMessage());
        }
    }
}
