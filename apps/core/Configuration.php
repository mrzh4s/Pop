<?php
/**
 * Configuration Manager (Enhanced and Fixed)
 * File: apps/core/Configuration.php
 * 
 * Handles application configuration with dot notation support
 * Enhanced with app helper functions to replace System.php
 * Fixed: Removed duplicate env() function to avoid conflicts
 */

class Configuration {
    private static $instance = null;
    private $config = [];
    private $loaded = false;
    
    // Component references to replace System.php functionality
    private $environment;
    private $session;
    private $permission;
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize configuration and components
     */
    public function __construct() {
        $this->initializeComponents();
    }
    
    /**
     * Initialize core components (replaces System.php functionality)
     */
    private function initializeComponents() {
        // Load Environment first
        if (class_exists('Environment')) {
            $this->environment = Environment::getInstance();
        }
        
        // Initialize Session if available
        if (class_exists('Session')) {
            $this->session = Session::getInstance();
        }
        
        // Initialize Permission if available  
        if (class_exists('Permission')) {
            $this->permission = Permission::getInstance();
        }
    }
    
    /**
     * Load configuration from environment variables
     */
    public function load() {
        if ($this->loaded) {
            return $this->config;
        }
        
        // Get environment variables
        $env = $this->environment ?: Environment::getInstance();
        $envVars = $env->all();
        
        // Auto-discover configuration based on prefixes
        $this->autoDiscoverFromEnvironment($envVars);
        
        // Set up predefined configuration objects
        $this->setupPredefinedConfig();
        
        $this->loaded = true;
        return $this->config;
    }
    
    /**
     * Auto-discover configuration from environment variables
     */
    private function autoDiscoverFromEnvironment($envVars) {
        $groups = [];
        
        foreach ($envVars as $key => $value) {
            // Split by underscore to determine grouping
            $parts = explode('_', $key);
            
            if (count($parts) >= 2) {
                $prefix = strtolower($parts[0]);
                $configKey = strtolower(implode('_', array_slice($parts, 1)));
                
                if (!isset($groups[$prefix])) {
                    $groups[$prefix] = [];
                }
                
                $groups[$prefix][$configKey] = $value;
            }
        }
        
        $this->config = $groups;
    }
    
    /**
     * Set up predefined configuration for backward compatibility
     */
    private function setupPredefinedConfig() {
        $env = $this->environment ?: Environment::getInstance();
        
        // App configuration
        $this->config['app'] = array_merge($this->config['app'] ?? [], [
            'name' => $env->get('APP_NAME', 'APP'),
            'title' => $env->get('APP_TITLE', $env->get('APP_NAME', 'APP')),
            'version' => $env->get('APP_VERSION', '1.0.0'),
            'environment' => $env->get('APP_ENV', $env->get('APP_STATE', 'production')),
            'debug' => $env->get('APP_DEBUG', false),
            'url' => $this->normalizeUrl($env->get('APP_URL', 'http://localhost')),
            'timezone' => $env->get('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),
            'company' => $env->get('APP_COMPANY', ''),
            'tenant' => $env->get('APP_TENANT', ''),
            'secret_key' => $env->get('APP_SECRET_KEY', $env->get('SECRET_KEY', ''))
        ]);
        
        // Database configuration
        $this->config['db'] = array_merge($this->config['db'] ?? [], [
            'host' => $env->get('DB_HOST', 'localhost'),
            'port' => $env->get('DB_PORT', '5432'),
            'database' => $env->get('DB_DATABASE', 'app'),
            'username' => $env->get('DB_USERNAME', 'postgres'),
            'password' => $env->get('DB_PASSWORD', ''),
            'driver' => $env->get('DB_DRIVER', 'pgsql'),
            'charset' => $env->get('DB_CHARSET', 'utf8'),
            'prefix' => $env->get('DB_PREFIX', '')
        ]);
        
        // FTP configuration
        $this->config['ftp'] = array_merge($this->config['ftp'] ?? [], [
            'host' => $env->get('FTP_HOST', ''),
            'port' => $env->get('FTP_PORT', '21'),
            'username' => $env->get('FTP_USERNAME', ''),
            'password' => $env->get('FTP_PASSWORD', ''),
            'path' => $env->get('FTP_PATH', '')
        ]);
        
        // Telegram configuration
        $this->config['telegram'] = array_merge($this->config['telegram'] ?? [], [
            'bot_token' => $env->get('TELEGRAM_BOT_TOKEN', ''),
            'bot_name' => $env->get('TELEGRAM_BOT_NAME', ''),
            'chat_id' => $env->get('TELEGRAM_CHAT_ID', '')
        ]);
        
        // GeoServer configuration
        $this->config['geoserver'] = array_merge($this->config['geoserver'] ?? [], [
            'layer' => $env->get('GEOSERVER_LAYER', ''),
            'url' => $env->get('GEOSERVER_URL', ''),
            'username' => $env->get('GEOSERVER_USERNAME', ''),
            'password' => $env->get('GEOSERVER_PASSWORD', '')
        ]);
        
        // Client configuration
        $this->config['client'] = array_merge($this->config['client'] ?? [], [
            'url' => $this->normalizeUrl($env->get('CLIENT_URL', '')),
            'secret_key' => $env->get('CLIENT_SECRET_KEY', $env->get('SECRET_KEY', ''))
        ]);
    }
    
    /**
     * Normalize URL format
     */
    private function normalizeUrl($url) {
        if (empty($url)) {
            return '';
        }
        
        // Ensure URL has protocol
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        
        return rtrim($url, '/');
    }
    
    /**
     * Get config value using dot notation
     */
    public function get($key, $default = null) {
        if (!$this->loaded) {
            $this->load();
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Set config value using dot notation
     */
    public function set($key, $value) {
        if (!$this->loaded) {
            $this->load();
        }
        
        $keys = explode('.', $key);
        $config = &$this->config;
        
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }
        
        $config[array_shift($keys)] = $value;
    }
    
    /**
     * Check if config key exists
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Get all configuration
     */
    public function all() {
        if (!$this->loaded) {
            $this->load();
        }
        return $this->config;
    }
    
    /**
     * Get config group
     */
    public function group($group) {
        return $this->get($group, []);
    }
    
    /**
     * Create configuration objects for backward compatibility
     */
    public function createObjects() {
        if (!$this->loaded) {
            $this->load();
        }
        
        return (object) [
            'App' => (object) $this->group('app'),
            'Database' => (object) $this->group('db'),
            'FTP' => (object) $this->group('ftp'),
            'Telegram' => (object) $this->group('telegram'),
            'GeoServer' => (object) $this->group('geoserver'),
            'Client' => (object) $this->group('client')
        ];
    }
    
    // ============== COMPONENT ACCESS METHODS (replaces System.php) ==============
    
    /**
     * Get environment component
     */
    public function getEnvironment() {
        return $this->environment;
    }
    
    /**
     * Get session component
     */
    public function getSession() {
        return $this->session;
    }
    
    /**
     * Get permission component
     */
    public function getPermission() {
        return $this->permission;
    }
    
    // ============== APP HELPER METHODS (replaces System.php) ==============
    
    /**
     * Get environment variable (proxy to Environment)
     * This is a CLASS METHOD, not a global function
     */
    public function env($key, $default = null) {
        return $this->environment ? $this->environment->get($key, $default) : ($_ENV[$key] ?? $default);
    }
    
    /**
     * Check permission (proxy to Permission)
     */
    public function can($permission, $value = null, $attribute = null, $attributeValue = null) {
        return $this->permission ? $this->permission->can($permission, $value, $attribute, $attributeValue) : false;
    }
    
    /**
     * Get session value (proxy to Session)
     */
    public function session($key = null, $default = null) {
        if (!$this->session) return $default;
        
        if ($key === null) {
            return $this->session->all();
        }
        return $this->session->get($key, $default);
    }
    
    /**
     * Set session value (proxy to Session)
     */
    public function setSession($key, $value) {
        return $this->session ? $this->session->set($key, $value) : false;
    }
    
    /**
     * Get debug information
     */
    public function getDebugInfo() {
        return [
            'loaded' => $this->loaded,
            'groups' => array_keys($this->config),
            'app_name' => $this->get('app.name'),
            'app_environment' => $this->get('app.environment'),
            'app_debug' => $this->get('app.debug'),
            'app_url' => $this->get('app.url'),
            'components' => [
                'environment' => $this->environment ? 'loaded' : 'not loaded',
                'session' => $this->session ? 'loaded' : 'not loaded', 
                'permission' => $this->permission ? 'loaded' : 'not loaded'
            ]
        ];
    }
}

