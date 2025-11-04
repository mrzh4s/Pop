<?php

/**
 * Session Helper Functions (Session class only)
 * File: session.php
 * 
 * Session-specific helper functions that work only with the Session class
 */


// ============== DATABASE SESSION STORAGE HELPERS ==============

if (!function_exists('session_store')) {
    /**
     * Store/Update current session data in database
     * Uses Session class method to sync session data to database
     */
    function session_store() {
        return Session::getInstance()->handleDatabaseSession();
    }
}

if (!function_exists('session_sync_to_db')) {
    /**
     * Force sync current session data to database
     * Alias for session_store() for clarity
     */
    function session_sync_to_db() {
        return session_store();
    }
}

if (!function_exists('session_exists_in_db')) {
    /**
     * Check if current session exists in database
     * Uses Session class to verify database record exists
     */
    function session_exists_in_db() {
        $sessionInfo = Session::getInstance()->getCurrentSessionInfo();
        return $sessionInfo !== null;
    }
}

if (!function_exists('session_load_from_db')) {
    /**
     * Load session data from database
     * Uses Session class to restore session state from database
     */
    function session_load_from_db($sessionId = null) {
        // If specific session ID provided, this would need a new Session class method
        // For now, we can only work with current session
        if ($sessionId && $sessionId !== session_id()) {
            return false; // Would need Session::loadSessionById() method
        }
        
        // Current session is automatically loaded when Session class initializes
        return session_exists_in_db();
    }
}

if (!function_exists('session_delete_from_db')) {
    /**
     * Delete session from database
     * Uses Session class terminateSession method
     */
    function session_delete_from_db($sessionId = null) {
        $sessionId = $sessionId ?? session_id();
        return Session::getInstance()->terminateSession($sessionId);
    }
}

if (!function_exists('session_update_activity_db')) {
    /**
     * Update session activity timestamp in database
     * This happens automatically when you set any session data
     */
    function session_update_activity_db() {
        // Update last activity by setting/getting a dummy value
        $currentTime = time();
        session_set('__activity_ping', $currentTime);
        session_remove('__activity_ping');
        return true;
    }
}

if (!function_exists('session_extend_expiry_db')) {
    /**
     * Extend session expiry time
     * Note: Would need a new Session class method for this functionality
     */
    function session_extend_expiry_db($additionalMinutes = 30, $sessionId = null) {
        // This would need Session::extendExpiry() method to be implemented
        // For now, updating activity extends the session naturally
        return session_update_activity_db();
    }
}

if (!function_exists('session_mark_inactive_db')) {
    /**
     * Mark session as inactive in database
     * Uses Session class markSessionInactive method (private - needs to be made public)
     */
    function session_mark_inactive_db($sessionId = null) {
        if ($sessionId && $sessionId !== session_id()) {
            // Would need Session::markSessionInactive($sessionId) public method
            return Session::getInstance()->terminateSession($sessionId);
        }
        
        // For current session, destroy it
        return Session::getInstance()->destroy();
    }
}

if (!function_exists('session_bulk_terminate')) {
    /**
     * Terminate multiple sessions
     * Uses Session class terminateOtherSessions method
     */
    function session_bulk_terminate($userId = null, $keepCurrent = true) {
        $userId = $userId ?? session_user_id();
        
        if (!$userId) {
            return false;
        }
        
        if ($keepCurrent) {
            return Session::getInstance()->terminateOtherSessions($userId);
        } else {
            // Would need Session::terminateAllUserSessions() method
            $sessions = session_get_user_sessions($userId);
            $result = true;
            foreach ($sessions as $session) {
                $result = $result && Session::getInstance()->terminateSession($session['id'], $userId);
            }
            return $result;
        }
    }
}

if (!function_exists('session_cleanup_expired_db')) {
    /**
     * Clean up expired sessions from database
     * Uses Session class static method
     */
    function session_cleanup_expired_db() {
        return Session::cleanExpiredSessions();
    }
}

if (!function_exists('session_find_user_sessions')) {
    /**
     * Find all sessions for a user
     * Uses Session class getUserSessions method
     */
    function session_find_user_sessions($userId = null) {
        $userId = $userId ?? session_user_id();
        
        if (!$userId) {
            return [];
        }
        
        return Session::getInstance()->getUserSessions($userId);
    }
}

if (!function_exists('session_find_active_sessions')) {
    /**
     * Find only active sessions for a user
     */
    function session_find_active_sessions($userId = null) {
        $allSessions = session_find_user_sessions($userId);
        return array_filter($allSessions, function($session) {
            return $session['is_active'];
        });
    }
}

if (!function_exists('session_find_trusted_sessions')) {
    /**
     * Find only trusted sessions for a user
     */
    function session_find_trusted_sessions($userId = null) {
        $allSessions = session_find_user_sessions($userId);
        return array_filter($allSessions, function($session) {
            return $session['is_trusted'];
        });
    }
}

if (!function_exists('session_get_stats_db')) {
    /**
     * Get session statistics
     * Uses Session class getSessionStats method
     */
    function session_get_stats_db($userId = null) {
        return Session::getInstance()->getSessionStats($userId);
    }
}

if (!function_exists('session_trust_current')) {
    /**
     * Mark current session/device as trusted
     * Uses Session class markAsTrusted method
     */
    function session_trust_current() {
        return Session::getInstance()->markAsTrusted();
    }
}

if (!function_exists('session_trust_device')) {
    /**
     * Mark specific session/device as trusted
     * Uses Session class markAsTrusted method
     */
    function session_trust_device($sessionId) {
        return Session::getInstance()->markAsTrusted($sessionId);
    }
}

if (!function_exists('session_get_current_info_db')) {
    /**
     * Get current session information from database
     * Uses Session class getCurrentSessionInfo method
     */
    function session_get_current_info_db() {
        return Session::getInstance()->getCurrentSessionInfo();
    }
}

if (!function_exists('session_is_current_trusted')) {
    /**
     * Check if current session is marked as trusted
     */
    function session_is_current_trusted() {
        $info = session_get_current_info_db();
        return $info ? (bool)$info['is_trusted'] : false;
    }
}

if (!function_exists('session_get_device_sessions')) {
    /**
     * Get sessions grouped by device type
     */
    function session_get_device_sessions($userId = null) {
        $sessions = session_find_user_sessions($userId);
        $grouped = [];
        
        foreach ($sessions as $session) {
            $deviceType = $session['device_type'];
            if (!isset($grouped[$deviceType])) {
                $grouped[$deviceType] = [];
            }
            $grouped[$deviceType][] = $session;
        }
        
        return $grouped;
    }
}

if (!function_exists('session_cleanup_user_sessions')) {
    /**
     * Clean up old sessions for a user (keep only N most recent active ones)
     * Note: This needs a new Session class method to be fully implemented
     */
    function session_cleanup_user_sessions($userId, $keepCount = 5) {
        $sessions = session_find_user_sessions($userId);
        
        // Sort by last_used_at descending
        usort($sessions, function($a, $b) {
            return strtotime($b['last_used_at']) - strtotime($a['last_used_at']);
        });
        
        // Keep only active sessions for counting
        $activeSessions = array_filter($sessions, function($s) {
            return $s['is_active'];
        });
        
        if (count($activeSessions) <= $keepCount) {
            return true; // Nothing to clean up
        }
        
        // Get sessions to terminate (beyond keepCount)
        $sessionsToTerminate = array_slice($activeSessions, $keepCount);
        $result = true;
        
        foreach ($sessionsToTerminate as $session) {
            $result = $result && Session::getInstance()->terminateSession($session['id'], $userId);
        }
        
        return $result;
    }
}

if (!function_exists('session_count_active')) {
    /**
     * Count active sessions for a user
     */
    function session_count_active($userId = null) {
        $sessions = session_find_active_sessions($userId);
        return count($sessions);
    }
}

if (!function_exists('session_count_trusted')) {
    /**
     * Count trusted sessions for a user
     */
    function session_count_trusted($userId = null) {
        $sessions = session_find_trusted_sessions($userId);
        return count($sessions);
    }
}

if (!function_exists('session_get_oldest_session')) {
    /**
     * Get the oldest session for a user
     */
    function session_get_oldest_session($userId = null) {
        $sessions = session_find_user_sessions($userId);
        
        if (empty($sessions)) {
            return null;
        }
        
        usort($sessions, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        return $sessions[0];
    }
}

if (!function_exists('session_get_newest_session')) {
    /**
     * Get the newest session for a user
     */
    function session_get_newest_session($userId = null) {
        $sessions = session_find_user_sessions($userId);
        
        if (empty($sessions)) {
            return null;
        }
        
        usort($sessions, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $sessions[0];
    }
}

if (!function_exists('session_terminate_by_device')) {
    /**
     * Terminate all sessions for a specific device type
     */
    function session_terminate_by_device($deviceType, $userId = null) {
        $sessions = session_find_user_sessions($userId);
        $result = true;
        
        foreach ($sessions as $session) {
            if ($session['device_type'] === $deviceType && $session['id'] !== session_id()) {
                $result = $result && Session::getInstance()->terminateSession($session['id'], $userId);
            }
        }
        
        return $result;
    }
}

if (!function_exists('session_terminate_untrusted')) {
    /**
     * Terminate all untrusted sessions for a user
     */
    function session_terminate_untrusted($userId = null) {
        $sessions = session_find_user_sessions($userId);
        $result = true;
        
        foreach ($sessions as $session) {
            if (!$session['is_trusted'] && $session['id'] !== session_id()) {
                $result = $result && Session::getInstance()->terminateSession($session['id'], $userId);
            }
        }
        
        return $result;
    }
}

if (!function_exists('session_backup_state')) {
    /**
     * Create a simple backup of current session state in session itself
     * Since we're avoiding direct SQL, store backup in session data
     */
    function session_backup_state($backupName = null) {
        $backupName = $backupName ?? 'backup_' . date('Y-m-d_H-i-s');
        $sessionData = session()->all();
        
        session_set("__backups.$backupName", [
            'data' => $sessionData,
            'created_at' => date('Y-m-d H:i:s'),
            'session_id' => session_id()
        ]);
        
        return $backupName;
    }
}

if (!function_exists('session_restore_state')) {
    /**
     * Restore session state from backup stored in session
     */
    function session_restore_state($backupName) {
        $backup = session("__backups.$backupName");
        
        if (!$backup || !isset($backup['data'])) {
            return false;
        }
        
        // Clear current session data (except backups)
        $backups = session('__backups');
        session_clear();
        
        // Restore backup data
        foreach ($backup['data'] as $key => $value) {
            if (strpos($key, '__backups') !== 0) { // Don't restore old backups
                session_set($key, $value);
            }
        }
        
        // Restore backups
        if ($backups) {
            session_set('__backups', $backups);
        }
        
        return true;
    }
}

if (!function_exists('session_list_backups')) {
    /**
     * List all session state backups
     */
    function session_list_backups() {
        $backups = session('__backups', []);
        $list = [];
        
        foreach ($backups as $name => $backup) {
            $list[$name] = [
                'name' => $name,
                'created_at' => $backup['created_at'],
                'session_id' => $backup['session_id'],
                'data_keys' => array_keys($backup['data'])
            ];
        }
        
        return $list;
    }
}

if (!function_exists('session_remove_backup')) {
    /**
     * Remove a specific backup
     */
    function session_remove_backup($backupName) {
        return session_remove("__backups.$backupName");
    }
}

if (!function_exists('session_clear_all_backups')) {
    /**
     * Clear all session backups
     */
    function session_clear_all_backups() {
        return session_remove('__backups');
    }
}

// ============== CORE SESSION HELPERS ==============

if (!function_exists('session_start')) {
    /**
     * Start session with optional configuration
     */
    function session_start($config = []) {
        return Session::getInstance()->start($config);
    }
}

if (!function_exists('session')) {
    /**
     * Session helper function with dot notation support
     */
    function session($key = null, $default = null) {
        $session = Session::getInstance();
        
        if ($key === null) {
            return $session->all();
        }
        
        return $session->get($key, $default);
    }
}

if (!function_exists('session_set')) {
    /**
     * Set session value with dot notation support
     */
    function session_set($key, $value) {
        return Session::getInstance()->set($key, $value);
    }
}

if (!function_exists('session_has')) {
    /**
     * Check if session has key
     */
    function session_has($key) {
        return Session::getInstance()->has($key);
    }
}

if (!function_exists('session_remove')) {
    /**
     * Remove session key
     */
    function session_remove($key) {
        return Session::getInstance()->remove($key);
    }
}

if (!function_exists('session_clear')) {
    /**
     * Clear all session data
     */
    function session_clear() {
        return Session::getInstance()->clear();
    }
}

if (!function_exists('session_destroy')) {
    /**
     * Destroy session completely
     */
    function session_destroy() {
        return Session::getInstance()->destroy();
    }
}

if (!function_exists('session_regenerate')) {
    /**
     * Regenerate session ID
     */
    function session_regenerate($deleteOld = true) {
        return Session::getInstance()->regenerate($deleteOld);
    }
}

// ============== FLASH MESSAGE HELPERS ==============

if (!function_exists('session_flash')) {
    /**
     * Set flash message
     */
    function session_flash($key, $value) {
        return Session::getInstance()->flash($key, $value);
    }
}

if (!function_exists('session_get_flash')) {
    /**
     * Get flash message
     */
    function session_get_flash($key, $default = null) {
        return Session::getInstance()->getFlash($key, $default);
    }
}

if (!function_exists('session_keep_flash')) {
    /**
     * Keep flash message for another request
     */
    function session_keep_flash($key) {
        return Session::getInstance()->keepFlash($key);
    }
}

// ============== SESSION INFO HELPERS ==============

if (!function_exists('session_id')) {
    /**
     * Get current session ID
     */
    function session_id() {
        return Session::getInstance()->getId();
    }
}

if (!function_exists('session_is_active')) {
    /**
     * Check if session is active
     */
    function session_is_active() {
        return Session::getInstance()->isActive();
    }
}

if (!function_exists('session_user_id')) {
    /**
     * Get current user ID from session
     */
    function session_user_id() {
        return Session::getInstance()->getUserId();
    }
}

if (!function_exists('session_set_user')) {
    /**
     * Set user ID for the session
     */
    function session_set_user($userId) {
        return Session::getInstance()->setUserId($userId);
    }
}

// ============== DATABASE SESSION HELPERS ==============

if (!function_exists('session_info')) {
    /**
     * Get current session information from database
     */
    function session_info() {
        return Session::getInstance()->getCurrentSessionInfo();
    }
}

if (!function_exists('session_stats')) {
    /**
     * Get session statistics
     */
    function session_stats($userId = null) {
        return Session::getInstance()->getSessionStats($userId);
    }
}

if (!function_exists('session_get_user_sessions')) {
    /**
     * Get all user sessions from database
     */
    function session_get_user_sessions($userId) {
        return Session::getInstance()->getUserSessions($userId);
    }
}

if (!function_exists('session_terminate')) {
    /**
     * Terminate specific session
     */
    function session_terminate($sessionId, $userId = null) {
        return Session::getInstance()->terminateSession($sessionId, $userId);
    }
}

if (!function_exists('session_terminate_others')) {
    /**
     * Terminate all other user sessions except current
     */
    function session_terminate_others($userId) {
        return Session::getInstance()->terminateOtherSessions($userId);
    }
}

if (!function_exists('session_trust_device')) {
    /**
     * Mark current session/device as trusted
     */
    function session_trust_device($sessionId = null) {
        return Session::getInstance()->markAsTrusted($sessionId);
    }
}

if (!function_exists('session_cleanup_expired')) {
    /**
     * Clean up expired sessions from database
     */
    function session_cleanup_expired() {
        return Session::cleanExpiredSessions();
    }
}

if (!function_exists('session_debug')) {
    /**
     * Get session debug information
     */
    function session_debug() {
        return Session::getInstance()->getDebugInfo();
    }
}

// ============== CONVENIENCE FLASH HELPERS ==============

if (!function_exists('flash_success')) {
    /**
     * Set success flash message
     */
    function flash_success($message) {
        return session_flash('success', $message);
    }
}

if (!function_exists('flash_error')) {
    /**
     * Set error flash message
     */
    function flash_error($message) {
        return session_flash('error', $message);
    }
}

if (!function_exists('flash_warning')) {
    /**
     * Set warning flash message
     */
    function flash_warning($message) {
        return session_flash('warning', $message);
    }
}

if (!function_exists('flash_info')) {
    /**
     * Set info flash message
     */
    function flash_info($message) {
        return session_flash('info', $message);
    }
}

if (!function_exists('get_flash_messages')) {
    /**
     * Get all flash messages
     */
    function get_flash_messages() {
        return [
            'success' => session_get_flash('success'),
            'error' => session_get_flash('error'),
            'warning' => session_get_flash('warning'),
            'info' => session_get_flash('info')
        ];
    }
}

if (!function_exists('has_flash_messages')) {
    /**
     * Check if there are any flash messages
     */
    function has_flash_messages() {
        return session_has('__flash.success') || 
               session_has('__flash.error') || 
               session_has('__flash.warning') || 
               session_has('__flash.info');
    }
}

// ============== SESSION UTILITY HELPERS ==============

if (!function_exists('session_config')) {
    /**
     * Set session configuration
     */
    function session_config($key, $value) {
        return Session::getInstance()->setConfig($key, $value);
    }
}

if (!function_exists('is_current_device_trusted')) {
    /**
     * Check if current device is trusted
     */
    function is_current_device_trusted() {
        $sessionInfo = session_info();
        return $sessionInfo ? $sessionInfo['is_trusted'] : false;
    }
}

if (!function_exists('get_current_device_info')) {
    /**
     * Get current device information
     */
    function get_current_device_info() {
        $sessionInfo = session_info();
        
        if (!$sessionInfo) {
            return null;
        }
        
        return [
            'device_type' => $sessionInfo['device_type'],
            'device_name' => $sessionInfo['device_name'],
            'platform' => $sessionInfo['platform'],
            'browser' => $sessionInfo['browser'],
            'ip_address' => $sessionInfo['ip_address'],
            'location' => trim(($sessionInfo['city'] ?? '') . ', ' . ($sessionInfo['country'] ?? ''), ', '),
            'is_trusted' => $sessionInfo['is_trusted'],
            'last_used' => $sessionInfo['last_used_at'],
            'created' => $sessionInfo['created_at']
        ];
    }
}

if (!function_exists('count_active_sessions')) {
    /**
     * Count active sessions for current user
     */
    function count_active_sessions($userId = null) {
        $userId = $userId ?? session_user_id();
        
        if (!$userId) {
            return 0;
        }
        
        $sessions = session_get_user_sessions($userId);
        return count(array_filter($sessions, fn($s) => $s['is_active']));
    }
}

if (!function_exists('get_user_devices')) {
    /**
     * Get user devices with formatted information
     */
    function get_user_devices($userId = null) {
        $userId = $userId ?? session_user_id();
        
        if (!$userId) {
            return [];
        }
        
        $sessions = session_get_user_sessions($userId);
        $currentSessionId = session_id();
        $devices = [];
        
        foreach ($sessions as $session) {
            $devices[] = [
                'session_id' => $session['id'],
                'device_name' => $session['device_name'],
                'device_type' => $session['device_type'],
                'platform' => $session['platform'],
                'browser' => $session['browser'],
                'ip_address' => $session['ip_address'],
                'location' => trim(($session['city'] ?? '') . ', ' . ($session['country'] ?? ''), ', '),
                'is_current' => ($session['id'] === $currentSessionId),
                'is_trusted' => $session['is_trusted'],
                'is_active' => $session['is_active'],
                'last_used' => $session['last_used_at'],
                'created' => $session['created_at']
            ];
        }
        
        return $devices;
    }
}

// ============== FORMAT HELPER FUNCTIONS ==============

if (!function_exists('format_session_time')) {
    /**
     * Format session time for display
     */
    function format_session_time($datetime) {
        if (!$datetime) {
            return 'Never';
        }
        
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
}

if (!function_exists('get_device_icon')) {
    /**
     * Get appropriate icon class for device type
     */
    function get_device_icon($deviceType) {
        switch (strtolower($deviceType)) {
            case 'mobile':
                return 'icon-mobile';
            case 'tablet':
                return 'icon-tablet';
            case 'desktop':
            default:
                return 'icon-desktop';
        }
    }
}