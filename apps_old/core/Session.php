<?php
/**
 * Enhanced Session Manager with Database Integration
 * File: apps/core/Session.php
 * 
 * Handles session management with dot notation, security features, and database tracking
 */

class Session {
    private static $instance = null;
    private $isActive = false;
    private $config = [];
    private $dbSessionId = null;
    private $userId = null;
    
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
     * Constructor - Initialize session configuration
     */
    public function __construct() {
        $this->config = [
            'name' => 'APP_SESSION',
            'lifetime' => 7200, // 2 hours
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
            'check_ip' => true,
            'track_device' => true
        ];
    }
    
    /**
     * Start session with security configuration
     */
    public function start($config = []) {
        if ($this->isActive()) {
            return session_id();
        }
        
        // Merge custom config
        $this->config = array_merge($this->config, $config);
        
        // Set secure session configuration
        $this->configureSession();
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            $this->isActive = true;
            
            // Initialize session security
            $this->initializeSecurity();
            
            // Create or update database session record
            $this->handleDatabaseSession();
        }
        
        return session_id();
    }
    
    /**
     * Configure session settings for security
     */
    private function configureSession() {
        // Set session name
        session_name($this->config['name']);
        
        // Set session parameters
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['httponly'],
            'samesite' => $this->config['samesite']
        ]);
        
        // Additional PHP settings
        ini_set('session.cookie_httponly', $this->config['httponly']);
        ini_set('session.cookie_secure', $this->config['secure']);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', $this->config['samesite']);
        
        // Prevent session fixation
        ini_set('session.use_strict_mode', 1);
        
        // Set session cache settings
        session_cache_limiter('nocache');
        session_cache_expire($this->config['lifetime'] / 60);
    }
    
    /**
     * Initialize session security measures
     */
    private function initializeSecurity() {
        // Set creation time
        if (!isset($_SESSION['__created'])) {
            $_SESSION['__created'] = time();
        }
        
        // Set last activity time
        $_SESSION['__last_activity'] = time();
        
        // Set user agent fingerprint
        if (!isset($_SESSION['__user_agent'])) {
            $_SESSION['__user_agent'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
        }
        
        // Set IP address fingerprint  
        if (!isset($_SESSION['__ip_address'])) {
            $_SESSION['__ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['__last_regeneration'])) {
            $_SESSION['__last_regeneration'] = time();
        } elseif (time() - $_SESSION['__last_regeneration'] > 1800) { // 30 minutes
            $this->regenerate();
        }
        
        // Check for session hijacking
        $this->validateSecurity();
    }
    
    /**
     * Handle database session creation/updating
     */
    public function handleDatabaseSession() {

        try {
            $conn = db();
            $sessionId = session_id();
            $deviceInfo = $this->getDeviceInfo();
            $locationInfo = $this->getLocationInfo();
            
            // Check if session already exists in database
            $stmt = $conn->prepare("SELECT session_id, user_id FROM auth.sessions WHERE session_id = :session_id");
            $stmt->execute([':session_id' => $sessionId]);
            $existingSession = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingSession) {
                // Update existing session
                $this->updateDatabaseSession($conn, $sessionId, $deviceInfo, $locationInfo);
                $this->dbSessionId = $existingSession['session_id'];
                $this->userId = $existingSession['user_id'];
            } else {
                // Create new session record
                $this->createDatabaseSession($conn, $sessionId, $deviceInfo, $locationInfo);
            }
            
        } catch (Exception $e) {
            var_dump("Session database error: " . $e->getMessage());
        }
    }
    
    /**
     * Create new database session record
     */
    private function createDatabaseSession($conn, $sessionId, $deviceInfo, $locationInfo) {
        $stmt = $conn->prepare("
            INSERT INTO auth.sessions (
                session_id, user_id, ip_address, user_agent, payload, last_activity, 
                expires_at, device_type, device_name, platform, browser, 
                city, country, is_current, last_used_at, created_at, updated_at
            ) VALUES (
                :session_id, :user_id, :ip_address, :user_agent, :payload, :last_activity,
                :expires_at, :device_type, :device_name, :platform, :browser,
                :city, :country, :is_current, :last_used_at, NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            ':session_id' => $sessionId,
            ':user_id' => $this->userId,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ':payload' => $this->serializeSessionData(),
            ':last_activity' => time(),
            ':expires_at' => date('Y-m-d H:i:s', time() + $this->config['lifetime']),
            ':device_type' => $deviceInfo['type'],
            ':device_name' => $deviceInfo['name'],
            ':platform' => $deviceInfo['platform'],
            ':browser' => $deviceInfo['browser'],
            ':city' => $locationInfo['city'],
            ':country' => $locationInfo['country'],
            ':is_current' => true,
            ':last_used_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->dbSessionId = $sessionId;
    }
    
    /**
     * Update existing database session record
     */
    private function updateDatabaseSession($conn, $sessionId, $deviceInfo, $locationInfo) {
        $stmt = $conn->prepare("
            UPDATE auth.sessions SET
                payload = :payload,
                last_activity = :last_activity,
                expires_at = :expires_at,
                device_type = :device_type,
                device_name = :device_name,
                platform = :platform,
                browser = :browser,
                city = :city,
                country = :country,
                is_current = :is_current,
                last_used_at = :last_used_at,
                updated_at = NOW()
            WHERE session_id = :session_id
        ");
        
        $stmt->execute([
            ':session_id' => $sessionId,
            ':payload' => $this->serializeSessionData(),
            ':last_activity' => time(),
            ':expires_at' => date('Y-m-d H:i:s', time() + $this->config['lifetime']),
            ':device_type' => $deviceInfo['type'],
            ':device_name' => $deviceInfo['name'],
            ':platform' => $deviceInfo['platform'],
            ':browser' => $deviceInfo['browser'],
            ':city' => $locationInfo['city'],
            ':country' => $locationInfo['country'],
            ':is_current' => true,
            ':last_used_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Set user ID for the session
     */
    public function setUserId($userId) {
        $this->userId = $userId;
        $_SESSION['__user_id'] = $userId;
        
        // Update database session with user ID
        if (function_exists('db') && $this->dbSessionId) {
            try {
                $conn = db();
                $stmt = $conn->prepare("UPDATE auth.sessions SET user_id = :user_id WHERE session_id = :session_id");
                $stmt->execute([
                    ':user_id' => $userId,
                    ':session_id' => session_id()
                ]);
            } catch (Exception $e) {
                error_log("Session user ID update error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Get current user ID from session
     */
    public function getUserId() {
        return $this->userId ?? $_SESSION['__user_id'] ?? null;
    }
    
    /**
     * Get device information from user agent
     */
    private function getDeviceInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Simple device detection
        $deviceType = 'desktop';
        $deviceName = 'Unknown Device';
        $platform = 'Unknown OS';
        $browser = 'Unknown Browser';
        
        // Detect mobile devices
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $deviceType = 'mobile';
            
            if (preg_match('/iPhone/', $userAgent)) {
                $deviceName = 'iPhone';
                $platform = 'iOS';
            } elseif (preg_match('/iPad/', $userAgent)) {
                $deviceName = 'iPad';
                $platform = 'iOS';
            } elseif (preg_match('/Android/', $userAgent)) {
                $deviceName = 'Android Device';
                $platform = 'Android';
            }
        } elseif (preg_match('/Tablet/', $userAgent)) {
            $deviceType = 'tablet';
        }
        
        // Detect platform
        if (preg_match('/Windows NT/', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac OS X/', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $platform = 'Linux';
        }
        
        // Detect browser
        if (preg_match('/Chrome/', $userAgent) && !preg_match('/Edg/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edg/', $userAgent)) {
            $browser = 'Edge';
        }
        
        return [
            'type' => $deviceType,
            'name' => $deviceName,
            'platform' => $platform,
            'browser' => $browser
        ];
    }
    
    /**
     * Get location information (basic implementation)
     */
    private function getLocationInfo() {

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if($ip === '127.0.0.1') {
            return [
                'city' => 'localhost',
                'country' => 'localhost'
            ];
        }

        $accessKey = config('localhost.api.key', 'd569fd0625f7463290ebc6f9d3d0c870');
        $url = config('location.api.url', 'https://api.ipgeolocation.io/v2/ipgeo') . "?apiKey={$accessKey}&ip={$ip}";
        
        $response = @file_get_contents($url);
        
        if ($response !== false) {
            return $response;
        } else {
            error_log("Failed to fetch IP info for {$ip}");
            return json_encode(['error' => 'API call failed']);
        }
    }
    
    /**
     * Serialize session data for database storage
     */
    private function serializeSessionData() {
        $data = $_SESSION;
        
        // Remove sensitive data from payload
        unset($data['__user_agent'], $data['__ip_address'], $data['__user_id']);
        
        return json_encode($data);
    }
    
    /**
     * Validate session security
     */
    private function validateSecurity() {
        $currentUserAgent = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
        $currentIpAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Check user agent
        if (isset($_SESSION['__user_agent']) && $_SESSION['__user_agent'] !== $currentUserAgent) {
            $this->destroy();
            throw new Exception('Session security violation: User agent mismatch');
        }
        
        // Check IP address (optional - can be disabled for mobile users)
        if ($this->config['check_ip'] && 
            isset($_SESSION['__ip_address']) && 
            $_SESSION['__ip_address'] !== $currentIpAddress) {
            $this->destroy();
            throw new Exception('Session security violation: IP address mismatch');
        }
        
        // Check session age
        if (isset($_SESSION['__created']) && 
            time() - $_SESSION['__created'] > $this->config['lifetime']) {
            $this->destroy();
            throw new Exception('Session expired');
        }
        
        // Check activity timeout
        if (isset($_SESSION['__last_activity']) && 
            time() - $_SESSION['__last_activity'] > 1800) { // 30 minutes inactivity
            $this->destroy();
            throw new Exception('Session timeout due to inactivity');
        }
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerate($deleteOldSession = true) {
        if ($this->isActive()) {
            $oldSessionId = session_id();
            session_regenerate_id($deleteOldSession);
            $newSessionId = session_id();
            $_SESSION['__last_regeneration'] = time();
            
            // Update database record with new session ID
            $this->updateSessionIdInDatabase($oldSessionId, $newSessionId);
        }
        return session_id();
    }
    
    /**
     * Update session ID in database
     */
    private function updateSessionIdInDatabase($oldSessionId, $newSessionId) {
        
        try {
            $conn = db();
            $stmt = $conn->prepare("UPDATE auth.sessions SET session_id = :new_id WHERE session_id = :old_id");
            $stmt->execute([
                ':new_id' => $newSessionId,
                ':old_id' => $oldSessionId
            ]);
            $this->dbSessionId = $newSessionId;
        } catch (Exception $e) {
            error_log("Session ID update error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if session is active
     */
    public function isActive() {
        return $this->isActive || session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Get session value using dot notation
     */
    public function get($key, $default = null) {
        if (!$this->isActive()) {
            $this->start();
        }
        
        if (strpos($key, '.') !== false) {
            return $this->getDotNotation($key, $default);
        }
        
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Get session value using dot notation
     */
    private function getDotNotation($key, $default) {
        $keys = explode('.', $key);
        $value = $_SESSION;
        
        foreach ($keys as $segment) {
            if (isset($value[$segment])) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Set session value using dot notation
     */
    public function set($key, $value) {
        if (!$this->isActive()) {
            $this->start();
        }
        
        if (strpos($key, '.') !== false) {
            $this->setDotNotation($key, $value);
        } else {
            $_SESSION[$key] = $value;
        }
        
        // Update last activity
        $_SESSION['__last_activity'] = time();
        
        // Update database session payload
        $this->updateSessionPayload();
    }
    
    /**
     * Set session value using dot notation
     */
    private function setDotNotation($key, $value) {
        $keys = explode('.', $key);
        $session = &$_SESSION;
        
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($session[$segment]) || !is_array($session[$segment])) {
                $session[$segment] = [];
            }
            $session = &$session[$segment];
        }
        
        $session[array_shift($keys)] = $value;
    }
    
    /**
     * Update session payload in database
     */
    private function updateSessionPayload() {
        if (!$this->dbSessionId) return;
        
        try {
            $conn = db();
            $stmt = $conn->prepare("
                UPDATE auth.sessions 
                SET payload = :payload, last_activity = :last_activity, last_used_at = :last_used_at, updated_at = NOW()
                WHERE session_id = :session_id
            ");
            $stmt->execute([
                ':payload' => $this->serializeSessionData(),
                ':last_activity' => time(),
                ':last_used_at' => date('Y-m-d H:i:s'),
                ':session_id' => session_id()
            ]);
        } catch (Exception $e) {
            error_log("Session payload update error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if session has key
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Remove session key using dot notation
     */
    public function remove($key) {
        if (!$this->isActive()) {
            return false;
        }
        
        if (strpos($key, '.') !== false) {
            $this->removeDotNotation($key);
        } else {
            unset($_SESSION[$key]);
        }
        
        // Update database session payload
        $this->updateSessionPayload();
        
        return true;
    }
    
    /**
     * Remove session value using dot notation
     */
    private function removeDotNotation($key) {
        $keys = explode('.', $key);
        $session = &$_SESSION;
        
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($session[$segment])) {
                return;
            }
            $session = &$session[$segment];
        }
        
        unset($session[array_shift($keys)]);
    }
    
    /**
     * Get all session data (excluding security keys)
     */
    public function all() {
        if (!$this->isActive()) {
            $this->start();
        }
        
        $data = $_SESSION;
        
        // Remove security keys from output
        $securityKeys = ['__created', '__last_activity', '__user_agent', '__ip_address', '__last_regeneration', '__user_id'];
        foreach ($securityKeys as $securityKey) {
            unset($data[$securityKey]);
        }
        
        return $data;
    }
    
    /**
     * Clear all session data
     */
    public function clear() {
        if ($this->isActive()) {
            $_SESSION = [];
            // Re-initialize security after clearing
            $this->initializeSecurity();
            // Update database payload
            $this->updateSessionPayload();
        }
    }
    
    /**
     * Destroy session completely
     */
    public function destroy() {
        if ($this->isActive()) {
            // Mark session as inactive in database
            $this->markSessionInactive();
            
            // Clear session data
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destroy session
            session_destroy();
            $this->isActive = false;
            $this->dbSessionId = null;
            $this->userId = null;
        }
    }
    
    /**
     * Mark session as inactive in database
     */
    private function markSessionInactive() {
        if (!$this->dbSessionId) return;
        
        try {
            $conn = db();
            $stmt = $conn->prepare("UPDATE auth.sessions SET is_current = false, updated_at = NOW() WHERE session_id = :session_id");
            $stmt->execute([':session_id' => session_id()]);
        } catch (Exception $e) {
            error_log("Session inactive marking error: " . $e->getMessage());
        }
    }
    
    /**
     * Flash data for one request
     */
    public function flash($key, $value) {
        $this->set("__flash.$key", $value);
    }
    
    /**
     * Get flash data
     */
    public function getFlash($key, $default = null) {
        $value = $this->get("__flash.$key", $default);
        $this->remove("__flash.$key");
        return $value;
    }
    
    /**
     * Keep flash data for another request
     */
    public function keepFlash($key) {
        $value = $this->get("__flash.$key");
        if ($value !== null) {
            $this->flash($key, $value);
        }
    }
    
    /**
     * Get session ID
     */
    public function getId() {
        return session_id();
    }
    
    /**
     * Set session configuration
     */
    public function setConfig($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * Get all user sessions from database
     */
    public function getUserSessions($userId) {
        
        try {
            $conn = db();
            $stmt = $conn->prepare("
                SELECT session_id, ip_address, user_agent, device_type, device_name, 
                       platform, browser, city, country, is_current, is_trusted,
                       last_used_at, created_at, 
                       CASE WHEN expires_at > NOW() THEN true ELSE false END as is_active
                FROM auth.sessions 
                WHERE user_id = :user_id 
                ORDER BY last_used_at DESC
            ");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user sessions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get current session info from database
     */
    public function getCurrentSessionInfo() {
        if (!$this->dbSessionId) {
            return null;
        }
        
        try {
            $conn = db();
            $stmt = $conn->prepare("
                SELECT session_id, user_id, ip_address, user_agent, device_type, device_name,
                       platform, browser, city, country, is_current, is_trusted,
                       last_used_at, created_at, expires_at
                FROM auth.sessions 
                WHERE session_id = :session_id
            ");
            $stmt->execute([':session_id' => session_id()]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get current session info error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Terminate specific session
     */
    public function terminateSession($sessionId, $userId = null) {

        try {
            $conn = db();
            $sql = "DELETE FROM auth.sessions WHERE session_id = :session_id";
            $params = [':session_id' => $sessionId];
            
            // Add user restriction if provided
            if ($userId) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Terminate session error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Terminate all other user sessions except current
     */
    public function terminateOtherSessions($userId) {

        try {
            $conn = db();
            $stmt = $conn->prepare("DELETE FROM auth.sessions WHERE user_id = :user_id AND session_id != :current_session_id");
            return $stmt->execute([
                ':user_id' => $userId,
                ':current_session_id' => session_id()
            ]);
        } catch (Exception $e) {
            error_log("Terminate other sessions error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean expired sessions from database
     */
    public static function cleanExpiredSessions() {
        if (!function_exists('db')) {
            return false;
        }
        
        try {
            $conn = db();
            $stmt = $conn->prepare("DELETE FROM auth.sessions WHERE expires_at < NOW()");
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Clean expired sessions error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark session as trusted device
     */
    public function markAsTrusted($sessionId = null) {
        
        $sessionId = $sessionId ?? session_id();
        
        try {
            $conn = db();
            $stmt = $conn->prepare("UPDATE auth.sessions SET is_trusted = true WHERE session_id = :session_id");
            return $stmt->execute([':session_id' => $sessionId]);
        } catch (Exception $e) {
            error_log("Mark trusted session error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get session statistics
     */
    public function getSessionStats($userId = null) {
        if (!function_exists('db')) {
            return [];
        }
        
        try {
            $conn = db();
            
            if ($userId) {
                // Stats for specific user
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as total_sessions,
                        COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_sessions,
                        COUNT(CASE WHEN is_trusted = true THEN 1 END) as trusted_sessions,
                        COUNT(DISTINCT device_type) as unique_devices,
                        COUNT(DISTINCT ip_address) as unique_ips
                    FROM auth.sessions 
                    WHERE user_id = :user_id
                ");
                $stmt->execute([':user_id' => $userId]);
            } else {
                // Global stats
                $stmt = $conn->query("
                    SELECT 
                        COUNT(*) as total_sessions,
                        COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_sessions,
                        COUNT(CASE WHEN is_trusted = true THEN 1 END) as trusted_sessions,
                        COUNT(DISTINCT user_id) as unique_users,
                        COUNT(DISTINCT device_type) as unique_devices,
                        COUNT(DISTINCT ip_address) as unique_ips
                    FROM auth.sessions
                ");
            }
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get session stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get session information for debugging
     */
    public function getDebugInfo() {
        $sessionInfo = $this->getCurrentSessionInfo();
        
        return [
            'active' => $this->isActive(),
            'id' => session_id() ? substr(session_id(), 0, 8) . '...' : 'none',
            'name' => session_name(),
            'user_id' => $this->getUserId(),
            'created' => $_SESSION['__created'] ?? null,
            'last_activity' => $_SESSION['__last_activity'] ?? null,
            'db_session_id' => $this->dbSessionId,
            'config' => $this->config,
            'data_keys' => array_keys($this->all()),
            'db_info' => $sessionInfo
        ];
    }
}