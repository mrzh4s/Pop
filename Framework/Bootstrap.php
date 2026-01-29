<?php
/**
 * Pop Framework Bootstrap with Auto-Discovery
 * File: Framework/Bootstrap.php
 *
 * Features:
 * - Auto-discovers Framework classes with proper namespaces
 * - Auto-discovers helpers in Framework/Helpers/
 * - Maintains proper loading order and dependencies
 * - Uses PSR-4 autoloading for Features and other components
 */

namespace Framework;
use Exception;
use Framework\Database\Migration;
use Framework\View\Blade;
use Framework\View\Inertia;

/**
 * Bootstrap Class with Namespace-Aware Auto-Discovery
 */
class Bootstrap {
    private static $loadedHelpers = [];

    // Helper files load order (dependency-aware)
    private static $helperOrder = [
        'env',           // Environment helpers first
        'config',        // Configuration helpers
        'session',       // Session helpers
        'cookie',        // Cookie helpers
        'security',      // Security helpers (CSRF, etc)
        'validation',    // Validation helpers
        'connection',    // Database connection helpers
        'migration',     // Migration helpers
        'permission',    // Permission helpers
        'activity',      // Activity logging helpers
        'traffic',       // Traffic logging helpers
        'http',          // HTTP/Curl helpers
        'router',        // Routing helpers
        'view',          // View/Asset helpers
        'inertia',       // Inertia helpers
        'debug',         // Debug helpers
    ];

    /**
     * Main bootstrap method
     */
    public static function boot() {
        try {
            // 1. Load helpers (classes are autoloaded by Composer)
            self::loadHelpers();

            // 2. Auto-discover additional helpers
            self::autoDiscoverHelpers();

            // 3. Initialize framework
            self::initializeFramework();

            // 4. Run auto-migrations
            self::runAutoMigrations();

            // 5. Initialize view engine
            self::initializeViewEngine();

            // 6. Share Inertia data
            self::shareInertiaData();

            // Mark as loaded
            define('POP_FRAMEWORK_LOADED', true);

            // Log bootstrap status if debug mode is enabled
            $isDebug = (function_exists('env') && env('APP_DEBUG') === 'true');

            if ($isDebug) {
                self::logBootstrapStatus();
            }

        } catch (Exception $e) {
            self::handleBootstrapError($e);
        }
    }

    /**
     * Load helpers in dependency order
     */
    private static function loadHelpers() {
        $helpersDir = ROOT_PATH . '/Framework/Helpers';

        if (!is_dir($helpersDir)) {
            error_log("Pop Framework: Helpers directory not found: {$helpersDir}");
            return;
        }

        // Load helpers in dependency order
        foreach (self::$helperOrder as $helperName) {
            self::loadHelperFile($helpersDir, $helperName);
        }
    }

    /**
     * Auto-discover additional helpers not in load order
     */
    private static function autoDiscoverHelpers() {
        $helpersDir = ROOT_PATH . '/Framework/Helpers';

        if (!is_dir($helpersDir)) {
            return;
        }

        $helperFiles = glob($helpersDir . '/*.php');

        foreach ($helperFiles as $helperFile) {
            $helperName = basename($helperFile, '.php');

            // Skip if already loaded
            if (in_array($helperName, self::$loadedHelpers)) {
                continue;
            }

            self::loadHelperFileDirectly($helperFile, $helperName);
        }
    }

    /**
     * Load helper file by name
     */
    private static function loadHelperFile($helpersDir, $helperName) {
        $helperFile = $helpersDir . '/' . $helperName . '.php';

        if (file_exists($helperFile)) {
            self::loadHelperFileDirectly($helperFile, $helperName);
        }
        // Silently skip missing helpers - they may not all be present
    }

    /**
     * Load individual helper file with error handling
     */
    private static function loadHelperFileDirectly($helperFile, $helperName) {
        try {
            require_once $helperFile;
            self::$loadedHelpers[] = $helperName;
        } catch (Exception $e) {
            error_log("Pop Framework: Error loading helper {$helperName}: " . $e->getMessage());

            // Don't stop bootstrap for helper errors unless critical
            $criticalHelpers = ['config', 'session', 'env'];
            if (\in_array($helperName, $criticalHelpers, true)) {
                throw new Exception("Critical helper failed to load: {$helperName} - " . $e->getMessage());
            }
        }
    }

    /**
     * Initialize framework
     */
    private static function initializeFramework() {
        // Configuration handles core system initialization
        if (class_exists('Framework\Configuration')) {
            Configuration::getInstance();
        }
    }

    /**
     * Run automatic database migrations
     */
    private static function runAutoMigrations() {
        if (!class_exists('Framework\Database\Migration')) {
            return;
        }

        try {
            Migration::autoRun();
        } catch (Exception $e) {
            error_log("Pop Framework: Auto-migration error: " . $e->getMessage());
            // Don't stop bootstrap for migration errors
        }
    }

    /**
     * Initialize ViewEngine with framework context
     */
    private static function initializeViewEngine() {
        if (!class_exists('Framework\View\Blade')) {
            return;
        }

        try {
            $viewEngine = Blade::getInstance();

            // Share global data using helper functions
            $sharedData = [
                'app_name' => function_exists('config') ? config('app.name', 'Pop Framework') : 'Pop Framework',
                'app_version' => function_exists('config') ? config('app.version', '1.0.0') : '1.0.0',
                'app_env' => function_exists('env') ? env('APP_ENV', 'production') : 'production',
                'app_url' => function_exists('config') ? config('app.url', '') : '',
                'is_local' => function_exists('env') ? (env('APP_ENV') === 'local') : false,
                'is_debug' => function_exists('env') ? (env('APP_DEBUG') === 'true') : false,
            ];

            $viewEngine->share($sharedData);
        } catch (Exception $e) {
            error_log("Pop Framework: ViewEngine initialization error: " . $e->getMessage());
        }
    }

    /**
     * Share global data with Inertia
     */
    private static function shareInertiaData() {
        if (!class_exists('Framework\View\Inertia')) {
            return;
        }

        try {
            Inertia::share([
                'auth' => [
                    'user' => [
                        'name' => $_SESSION['user_name'] ?? null,
                        'email' => $_SESSION['user_email'] ?? null,
                        'authenticated' => $_SESSION['authenticated'] ?? false,
                    ],
                ],
                'flash' => [
                    'success' => $_SESSION['flash_success'] ?? null,
                    'error' => $_SESSION['flash_error'] ?? null,
                    'warning' => $_SESSION['flash_warning'] ?? null,
                    'info' => $_SESSION['flash_info'] ?? null,
                ],
                'errors' => $_SESSION['validation_errors'] ?? (object)[],
            ]);

            // Clear flash messages and validation errors after sharing
            unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_warning'], $_SESSION['flash_info'], $_SESSION['validation_errors']);
        } catch (Exception $e) {
            error_log("Pop Framework: Inertia data sharing error: " . $e->getMessage());
        }
    }

    /**
     * Log bootstrap status for debugging
     */
    private static function logBootstrapStatus() {
        error_log("Pop Framework: Bootstrap completed successfully");
        error_log("Pop Framework: Loaded helpers: " . implode(', ', self::$loadedHelpers));
    }

    /**
     * Handle bootstrap errors gracefully
     */
    private static function handleBootstrapError($e) {
        $errorMessage = "Pop Framework Bootstrap Error: " . $e->getMessage();

        $debug = (function_exists('env') && env('APP_DEBUG') === 'true');

        if ($debug) {
            // Show detailed error in debug mode
            echo "<div style='background:#f8d7da;color:#721c24;padding:20px;margin:10px;border-radius:5px;'>";
            echo "<h2>Pop Framework Bootstrap Error</h2>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
            echo "<p><strong>Loaded Helpers:</strong> " . implode(', ', self::$loadedHelpers) . "</p>";
            echo "<p><strong>Configuration Status:</strong> " . (class_exists('Framework\Configuration') ? 'Available' : 'Not Available') . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } else {
            // Log error and show user-friendly message
            error_log($errorMessage);
            echo "<h1>System Initialization Error</h1>";
            echo "<p>The application could not start properly. Please contact support.</p>";
        }

        exit(1);
    }

    /**
     * Get comprehensive bootstrap status
     */
    public static function getStatus() {
        return [
            'loaded' => \defined('POP_FRAMEWORK_LOADED'),
            'helpers_loaded' => self::$loadedHelpers,
            'counts' => [
                'helpers' => \count(self::$loadedHelpers),
            ],
            'configuration_ready' => class_exists('Framework\Configuration'),
            'critical_helpers_available' => [
                'config' => function_exists('config'),
                'session' => function_exists('session'),
                'env' => function_exists('env'),
            ],
            'environment' => function_exists('env') ? env('APP_ENV', 'unknown') : 'unknown',
            'debug_mode' => function_exists('env') ? (env('APP_DEBUG') === 'true') : false
        ];
    }

    /**
     * Check if specific helper is loaded
     */
    public static function hasHelper($helperName) {
        return \in_array($helperName, self::$loadedHelpers, true);
    }

    /**
     * Get loaded helpers list
     */
    public static function getLoadedHelpers() {
        return self::$loadedHelpers;
    }
}

// ============== GLOBAL HELPER FUNCTIONS FOR BOOTSTRAP ==============

if (!function_exists('framework_ready')) {
    /**
     * Check if Pop framework is ready
     */
    function framework_ready() {
        return \defined('POP_FRAMEWORK_LOADED');
    }
}

if (!function_exists('framework_status')) {
    /**
     * Get comprehensive bootstrap status
     */
    function framework_status() {
        return \Framework\Bootstrap::getStatus();
    }
}

if (!function_exists('framework_has_helper')) {
    /**
     * Check if specific helper is loaded
     */
    function framework_has_helper($helperName) {
        return \Framework\Bootstrap::hasHelper($helperName);
    }
}
