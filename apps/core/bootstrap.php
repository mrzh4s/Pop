<?php
/**
 * Enhanced APP Bootstrap with Services Auto-Discovery
 * File: core/bootstrap.php
 * 
 * Features:
 * - Auto-discovers core classes in core/
 * - Auto-discovers service classes in services/
 * - Auto-discovers helpers in core/helpers/
 * - Auto-discovers helpers in services/helpers/
 * - Maintains proper loading order and dependencies
 */

// Start error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent multiple loads
if (defined('APP_CORE_LOADED')) {
    return;
}

// Define ROOT_PATH if not already defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if(!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

/**
 * Enhanced Bootstrap Class with Full Auto-Discovery
 */
class Bootstrap {
    private static $loadedClasses = [];
    private static $loadedServices = [];
    private static $loadedHelpers = [];
    
    // Core class load order (dependency-aware)
    private static $coreLoadOrder = [
        // Core infrastructure
        'Environment',      // Environment variables
        'Configuration',    // Configuration management
        'Session',         // Session management
        'Cookie',          // Cookie management
        'Security',        // CSRF and security

        // Database and connections
        'Connection',       // Database connections
        'Migration',       // Database migrations

        // Authorization and logging
        'Permission',      // Permission system
        'Activity',        // Activity logging
        'Traffic',         // API traffic logging

        // Application components
        'ViewEngine',      // View rendering
        'Router',          // Routing system
        'Curl',            // HTTP client
    ];
    
    // Service load order (after core classes)
    private static $serviceLoadOrder = [
        // Add more services as needed
        'GravityFormService'
    ];
    
    // Helper files load order (dependency-aware)
    private static $coreHelperOrder = [
        'env',           // Environment helpers first
        'config',        // Configuration helpers
        'session',       // Session helpers
        'cookie',        // Cookie helpers
        'security',      // Security helpers (CSRF, etc)
        'connection',    // Database connection helpers
        'migration',     // Migration helpers
        'permission',    // Permission helpers
        'activity',      // Activity logging helpers
        'traffic',       // Traffic logging helpers
        'http',          // HTTP/Curl helpers
        'router',        // Routing helpers
        'view',          // View/Asset helpers
        'debug',         // Debug helpers
    ];
    
    private static $serviceHelperOrder = [
        'gravityform'
    ];
    
    /**
     * Main bootstrap method
     */
    public static function boot() {
        try {
            // 1. Load core classes first (dependency order)
            self::loadCoreClasses();
            
            // 2. Load service classes
            self::loadServiceClasses();
            
            // 3. Auto-discover additional services
            self::autoDiscoverServices();
            
            // 4. Load core helpers
            self::loadCoreHelpers();
            
            // 5. Load service helpers
            self::loadServiceHelpers();
            
            // 6. Auto-discover additional helpers
            self::autoDiscoverAllHelpers();
            
            // 7. Initialize framework
            self::initializeFramework();

            // 8. Run auto-migrations
            self::runAutoMigrations();

            // 9. Initialize view engine
            self::initializeViewEngine();

            // Mark as loaded
            define('APP_CORE_LOADED', true);
            
            if (defined('APP_DEBUG')) {
                self::logBootstrapStatus();
            }
            
        } catch (Exception $e) {
            self::handleBootstrapError($e);
        }
    }
    
    /**
     * Load core classes in dependency order
     */
    private static function loadCoreClasses() {
        foreach (self::$coreLoadOrder as $className) {
            self::loadCoreClass($className);
        }
    }
    
    /**
     * Load a single core class
     */
    private static function loadCoreClass($className) {
        $file = ROOT_PATH . "/core/{$className}.php";
        
        if (file_exists($file)) {
            require_once $file;
            self::$loadedClasses[] = $className;
            
            if (defined('APP_DEBUG')) {
                error_log("APP: Loaded core class {$className}");
            }
        } else {
            // Some classes are optional
            $optional = [
                'ViewEngine', 'Router'
            ];
            
            if (in_array($className, $optional)) {
                if (defined('APP_DEBUG')) {
                    error_log("APP: Optional core class {$className} not found - skipping");
                }
            } else {
                throw new Exception("Required core class not found: {$file}");
            }
        }
    }
    
    /**
     * Load service classes in order
     */
    private static function loadServiceClasses() {
        foreach (self::$serviceLoadOrder as $serviceName) {
            self::loadServiceClass($serviceName);
        }
    }
    
    /**
     * Load a single service class
     */
    private static function loadServiceClass($serviceName) {
        $file = ROOT_PATH . "/services/{$serviceName}.php";
        
        if (file_exists($file)) {
            require_once $file;
            self::$loadedServices[] = $serviceName;
            
            if (defined('APP_DEBUG')) {
                error_log("APP: Loaded service {$serviceName}");
            }
        } else {
            if (defined('APP_DEBUG')) {
                error_log("APP: Service {$serviceName} not found - skipping");
            }
        }
    }
    
    /**
     * Auto-discover additional services not in load order
     */
    private static function autoDiscoverServices() {
        $servicesDir = ROOT_PATH . '/services';
        
        if (!is_dir($servicesDir)) {
            if (defined('APP_DEBUG')) {
                error_log("APP: Services directory not found: {$servicesDir}");
            }
            return;
        }
        
        $serviceFiles = glob($servicesDir . '/*.php');
        
        foreach ($serviceFiles as $serviceFile) {
            $serviceName = basename($serviceFile, '.php');
            
            // Skip if already loaded
            if (in_array($serviceName, self::$loadedServices)) {
                continue;
            }
            
            require_once $serviceFile;
            self::$loadedServices[] = $serviceName;
            
            if (defined('APP_DEBUG')) {
                error_log("APP: Auto-discovered service {$serviceName}");
            }
        }
    }
    
    /**
     * Load core helpers in dependency order
     */
    private static function loadCoreHelpers() {
        $coreHelpersDir = ROOT_PATH . '/core/helpers';
        
        if (!is_dir($coreHelpersDir)) {
            if (defined('APP_DEBUG')) {
                error_log("APP: Core helpers directory not found: {$coreHelpersDir}");
            }
            return;
        }
        
        // Load core helpers in dependency order
        foreach (self::$coreHelperOrder as $helperName) {
            self::loadHelperFile($coreHelpersDir, $helperName, 'core');
        }
    }
    
    /**
     * Load service helpers in dependency order
     */
    private static function loadServiceHelpers() {
        $serviceHelpersDir = ROOT_PATH . '/services/helpers';
        
        if (!is_dir($serviceHelpersDir)) {
            if (defined('APP_DEBUG')) {
                error_log("APP: Service helpers directory not found: {$serviceHelpersDir}");
            }
            return;
        }
        
        // Load service helpers in dependency order
        foreach (self::$serviceHelperOrder as $helperName) {
            self::loadHelperFile($serviceHelpersDir, $helperName, 'service');
        }
    }
    
    /**
     * Auto-discover all remaining helpers from both directories
     */
    private static function autoDiscoverAllHelpers() {
        // Auto-discover remaining core helpers
        self::autoDiscoverHelpersInDirectory(ROOT_PATH . '/core/helpers', 'core');
        
        // Auto-discover remaining service helpers
        self::autoDiscoverHelpersInDirectory(ROOT_PATH . '/services/helpers', 'service');
    }
    
    /**
     * Auto-discover helpers in a specific directory
     */
    private static function autoDiscoverHelpersInDirectory($helpersDir, $type) {
        if (!is_dir($helpersDir)) {
            return;
        }
        
        $helperFiles = glob($helpersDir . '/*.php');
        
        foreach ($helperFiles as $helperFile) {
            $helperName = basename($helperFile, '.php');
            $helperKey = "{$type}:{$helperName}";
            
            // Skip if already loaded
            if (in_array($helperKey, self::$loadedHelpers)) {
                continue;
            }
            
            self::loadHelperFileDirectly($helperFile, $helperName, $type);
        }
    }
    
    /**
     * Load helper file by name and type
     */
    private static function loadHelperFile($helpersDir, $helperName, $type) {
        $helperFile = $helpersDir . '/' . $helperName . '.php';
        
        if (file_exists($helperFile)) {
            self::loadHelperFileDirectly($helperFile, $helperName, $type);
        } else {
            if (defined('APP_DEBUG')) {
                error_log("APP: {$type} helper {$helperName} not found: {$helperFile}");
            }
        }
    }
    
    /**
     * Load individual helper file with error handling
     */
    private static function loadHelperFileDirectly($helperFile, $helperName, $type) {
        try {
            require_once $helperFile;
            $helperKey = "{$type}:{$helperName}";
            self::$loadedHelpers[] = $helperKey;
            
            if (defined('APP_DEBUG')) {
                error_log("APP: Loaded {$type} helper {$helperName}");
            }
        } catch (Exception $e) {
            error_log("APP: Error loading {$type} helper {$helperName}: " . $e->getMessage());
            
            // Don't stop bootstrap for helper errors unless critical
            $criticalHelpers = ['config', 'session', 'env'];
            if (in_array($helperName, $criticalHelpers)) {
                throw new Exception("Critical {$type} helper failed to load: {$helperName} - " . $e->getMessage());
            }
        }
    }
    
    /**
     * Initialize framework
     */
    private static function initializeFramework() {
        // Configuration handles core system initialization
        $config = Configuration::getInstance();

        if (defined('APP_DEBUG')) {
            error_log("APP: Framework initialized via Configuration class");
        }
    }

    /**
     * Run automatic database migrations
     */
    private static function runAutoMigrations() {
        if (!class_exists('Migration')) {
            if (defined('APP_DEBUG')) {
                error_log("APP: Migration class not available - skipping auto-migrations");
            }
            return;
        }

        try {
            Migration::autoRun();

            if (defined('APP_DEBUG')) {
                error_log("APP: Auto-migration check completed");
            }
        } catch (Exception $e) {
            error_log("APP: Auto-migration error: " . $e->getMessage());
            // Don't stop bootstrap for migration errors
        }
    }

    /**
     * Initialize ViewEngine with framework context
     */
    private static function initializeViewEngine() {
        if (!class_exists('ViewEngine')) {
            return;
        }
        
        try {
            $viewEngine = ViewEngine::getInstance();
            
            // Share global data using helper functions
            $sharedData = [
                'app_name' => function_exists('app_name') ? app_name() : 'APP',
                'app_version' => function_exists('app_version') ? app_version() : '1.0.0',
                'app_env' => function_exists('app_env') ? app_env() : 'production',
                'app_url' => function_exists('app_url') ? app_url() : '',
                'is_local' => function_exists('is_local') ? is_local() : false,
                'is_debug' => function_exists('app_debug') ? app_debug() : false,
            ];
            
            $viewEngine->share($sharedData);
            
            if (defined('APP_DEBUG')) {
                error_log("APP: ViewEngine initialized with framework data");
            }
            
        } catch (Exception $e) {
            error_log("APP: ViewEngine initialization error: " . $e->getMessage());
        }
    }
    
    /**
     * Log bootstrap status for debugging
     */
    private static function logBootstrapStatus() {
        error_log("APP: Bootstrap completed successfully");
        error_log("APP: Loaded core classes: " . implode(', ', self::$loadedClasses));
        error_log("APP: Loaded services: " . implode(', ', self::$loadedServices));
        error_log("APP: Loaded helpers: " . implode(', ', self::$loadedHelpers));
    }
    
    /**
     * Handle bootstrap errors gracefully
     */
    private static function handleBootstrapError($e) {
        $errorMessage = "APP Bootstrap Error: " . $e->getMessage();
        
        $debug = defined('APP_DEBUG') && APP_DEBUG;
        
        if ($debug) {
            // Show detailed error in debug mode
            echo "<div style='background:#f8d7da;color:#721c24;padding:20px;margin:10px;border-radius:5px;'>";
            echo "<h2>APP Framework Bootstrap Error</h2>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
            echo "<p><strong>Loaded Core Classes:</strong> " . implode(', ', self::$loadedClasses) . "</p>";
            echo "<p><strong>Loaded Services:</strong> " . implode(', ', self::$loadedServices) . "</p>";
            echo "<p><strong>Loaded Helpers:</strong> " . implode(', ', self::$loadedHelpers) . "</p>";
            echo "<p><strong>Configuration Status:</strong> " . (class_exists('Configuration') ? 'Available' : 'Not Available') . "</p>";
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
            'loaded' => defined('APP_CORE_LOADED'),
            'core_classes_loaded' => self::$loadedClasses,
            'services_loaded' => self::$loadedServices,
            'helpers_loaded' => self::$loadedHelpers,
            'counts' => [
                'core_classes' => count(self::$loadedClasses),
                'services' => count(self::$loadedServices),
                'helpers' => count(self::$loadedHelpers),
                'expected_core' => count(self::$coreLoadOrder),
                'expected_services' => count(self::$serviceLoadOrder)
            ],
            'configuration_ready' => class_exists('Configuration'),
            'critical_helpers_available' => [
                'config' => function_exists('config'),
                'session' => function_exists('session'),
                'env' => function_exists('env'),
                'app' => function_exists('app'),
            ],
            'service_helpers_available' => [
                'user' => function_exists('current_user'),
                'notification' => function_exists('send_email'),
                'activity' => function_exists('log_user_activity'),
            ],
            'environment' => function_exists('app_env') ? app_env() : 'unknown',
            'debug_mode' => function_exists('app_debug') ? app_debug() : false
        ];
    }
    
    /**
     * Check if specific helper is loaded
     */
    public static function hasHelper($helperName, $type = null) {
        if ($type) {
            return in_array("{$type}:{$helperName}", self::$loadedHelpers);
        }
        
        // Check both core and service helpers
        return in_array("core:{$helperName}", self::$loadedHelpers) || 
               in_array("service:{$helperName}", self::$loadedHelpers);
    }
    
    /**
     * Get loaded helpers grouped by type
     */
    public static function getLoadedHelpers() {
        $grouped = ['core' => [], 'service' => []];
        
        foreach (self::$loadedHelpers as $helper) {
            list($type, $name) = explode(':', $helper, 2);
            $grouped[$type][] = $name;
        }
        
        return $grouped;
    }
    
    /**
     * Get loaded services list
     */
    public static function getLoadedServices() {
        return self::$loadedServices;
    }
    
    /**
     * Manually load a service (useful for dynamic loading)
     */
    public static function loadService($serviceName) {
        if (in_array($serviceName, self::$loadedServices)) {
            return true; // Already loaded
        }
        
        self::loadServiceClass($serviceName);
        return in_array($serviceName, self::$loadedServices);
    }
}

// Execute bootstrap
Bootstrap::boot();

// ============== GLOBAL HELPER FUNCTIONS FOR BOOTSTRAP ==============

if (!function_exists('APP_ready')) {
    /**
     * Check if APP framework is ready
     */
    function APP_ready() {
        return defined('APP_CORE_LOADED');
    }
}

if (!function_exists('APP_status')) {
    /**
     * Get comprehensive bootstrap status
     */
    function APP_status() {
        return Bootstrap::getStatus();
    }
}

if (!function_exists('APP_has_helper')) {
    /**
     * Check if specific helper is loaded
     */
    function APP_has_helper($helperName, $type = null) {
        return Bootstrap::hasHelper($helperName, $type);
    }
}

if (!function_exists('APP_helpers')) {
    /**
     * Get loaded helpers grouped by type
     */
    function APP_helpers() {
        return Bootstrap::getLoadedHelpers();
    }
}

if (!function_exists('APP_services')) {
    /**
     * Get loaded services
     */
    function APP_services() {
        return Bootstrap::getLoadedServices();
    }
}

if (!function_exists('APP_load_service')) {
    /**
     * Dynamically load a service
     */
    function APP_load_service($serviceName) {
        return Bootstrap::loadService($serviceName);
    }
}