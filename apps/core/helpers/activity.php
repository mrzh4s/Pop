<?php

/**
 * Activity Global Helper Functions
 * Provides convenient global functions for activity logging
 * Add this to your core/Activity.php file or create a separate helpers file
 */

// ============== ACTIVITY HELPER FUNCTIONS ==============

/**
 * Main activity helper function - handles multiple operations
 * 
 * Usage:
 * activity('user', ['message' => 'User logged in', 'pages' => 'dashboard'])
 * activity('project', ['system_id' => 'ABC123', 'flow' => 5, 'details' => 'Status updated'])
 * activity('get.user', ['limit' => 10])
 * activity('get.project', ['system_id' => 'ABC123'])
 */
if (!function_exists('activity')) {
    function activity($action, $data = []) {
        try {
            $instance = Activity::getInstance();
            
            switch ($action) {
                case 'user':
                case 'log.user':
                    return $instance->logUserActivity(
                        $data['user_id'] ??'',
                        $data['message'] ?? 'User activity',
                    );
                
                case 'project':
                case 'log.project':
                    return $instance->logProjectActivity(
                        $data['system_id'] ?? get_system_id(),
                        $data['flow'] ?? $data['current_flow'],
                        $data['details'] ?? 'Project activity',
                        $data['authority_id'] ?? null
                    );
                
                case 'get.user':
                case 'user.history':
                    return $instance->getUserActivity($data['limit'] ?? 1);
                
                case 'get.project':
                case 'project.history':
                    return $instance->getProjectActivity(
                        $data['system_id'] ?? get_system_id()
                    );
                
                case 'api':
                case 'log.api':
                    return Activity::logApiActivity($data);
                
                case 'api.update':
                    return Activity::updateApiActivity($data['id'], $data);
                
                default:
                    throw new InvalidArgumentException("Unknown activity action: {$action}");
            }
            
        } catch (Exception $e) {
            error_log("Activity helper error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Quick user activity logging
 * 
 * Usage: 
 * log_user_activity('User updated profile')
 * log_user_activity('File uploaded', 'documents')
 */
if (!function_exists('log_user_activity')) {
    function log_user_activity($message, $userId = null) {
        return activity('user', [
            'message' => $message,
            'user_id' => $userId ?? session('user.id'),
        ]);
    }
}

/**
 * Quick project activity logging
 * 
 * Usage:
 * log_project_activity('ABC123', 5, 'Status updated to pending')
 * log_project_activity('ABC123', 5, 'Authority approved', 'AUTH001')
 */
if (!function_exists('log_project_activity')) {
    function log_project_activity($systemId, $currentFlow, $details, $authorityId = null) {
        return activity('project', [
            'system_id' => $systemId,
            'flow' => $currentFlow,
            'details' => $details,
            'authority_id' => $authorityId
        ]);
    }
}

/**
 * Get user activity history
 * 
 * Usage:
 * $lastActivity = get_user_activity()
 * $recentActivities = get_user_activity(10)
 */
if (!function_exists('get_user_activity')) {
    function get_user_activity($limit = 1) {
        return activity('get.user', ['limit' => $limit]);
    }
}

/**
 * Get project activity history
 * 
 * Usage:
 * $projectHistory = get_project_activity('ABC123')
 */
if (!function_exists('get_project_activity')) {
    function get_project_activity($systemId = null) {
        return activity('get.project', [
            'system_id' => $systemId ?? get_system_id()
        ]);
    }
}



// ============== ACTIVITY CONTEXT HELPERS ==============

/**
 * Auto-log based on current context
 * 
 * Usage:
 * auto_activity('User accessed dashboard')
 * auto_activity('Project status updated', ['flow' => 5])
 */
if (!function_exists('auto_activity')) {
    function auto_activity($message, $context = []) {
        $systemId = get_system_id();
        
        if ($systemId && isset($context['flow'])) {
            // Project context
            return log_project_activity(
                $systemId,
                $context['flow'],
                $message,
                $context['authority_id'] ?? null
            );
        } else {
            // User context
            return log_user_activity($message);
        }
    }
}

/**
 * Batch activity logging
 * 
 * Usage:
 * batch_activity([
 *     ['type' => 'user', 'message' => 'Logged in'],
 *     ['type' => 'project', 'system_id' => 'ABC123', 'flow' => 5, 'details' => 'Updated']
 * ])
 */
if (!function_exists('batch_activity')) {
    function batch_activity($activities) {
        $results = [];
        
        foreach ($activities as $activity) {
            try {
                switch ($activity['type']) {
                    case 'user':
                        $results[] = log_user_activity(
                            $activity['message']
                        );
                        break;
                    
                    case 'project':
                        $results[] = log_project_activity(
                            $activity['system_id'],
                            $activity['flow'],
                            $activity['details'],
                            $activity['authority_id'] ?? null
                        );
                        break;
                }
            } catch (Exception $e) {
                $results[] = false;
                error_log("Batch activity error: " . $e->getMessage());
            }
        }
        
        return $results;
    }
}

// ============== UTILITY HELPERS ==============

/**
 * Get current system ID from global context
 */
if (!function_exists('get_system_id')) {
    function get_system_id() {
        return $GLOBALS['systemId'] 
            ?? $_GET['code'] 
            ?? $_POST['system_id'] 
            ?? session('current_system_id') 
            ?? null;
    }
}

/**
 * Get current request path for activity logging
 */
if (!function_exists('request_path')) {
    function request_path() {
        return $_SERVER['REQUEST_URI'] ?? 'unknown';
    }
}

/**
 * Session helper for activity context
 */
if (!function_exists('session')) {
    function session($key = null, $default = null) {
        if (!session_id()) {
            session_start();
        }
        
        if ($key === null) {
            return $_SESSION;
        }
        
        return $_SESSION[$key] ?? $default;
    }
}