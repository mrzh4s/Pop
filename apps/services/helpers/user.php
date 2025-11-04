<?php
// ============== GLOBAL HELPER FUNCTIONS ==============

/**
 * Get UserService instance
 */
if (!function_exists('user_service')) {
    function user_service() {
        return UserService::getInstance();
    }
}

/**
 * Create a new user
 */
if (!function_exists('create_user')) {
    function create_user($userData) {
        return user_service()->createUser($userData);
    }
}

/**
 * Get user by ID
 */
if (!function_exists('get_user')) {
    function get_user($userId, $options = null) {
        return user_service()->getUserById($userId);
    }
}

/**
 * Get user by email
 */
if (!function_exists('get_user_by_email')) {
    function get_user_by_email($email) {
        return user_service()->getUserByEmail($email);
    }
}

/**
 * Get users with pagination and filtering
 */
if (!function_exists('get_users')) {
    function get_users($options = []) {
        return user_service()->getUsers($options);
    }
}

/**
 * Update user
 */
if (!function_exists('update_user')) {
    function update_user($userId, $userData) {
        return user_service()->updateUser($userId, $userData);
    }
}

/**
 * Delete (deactivate) user
 */
if (!function_exists('delete_user')) {
    function delete_user($userId) {
        return user_service()->deleteUser($userId);
    }
}

/**
 * Authenticate user
 */
if (!function_exists('auth_user')) {
    function auth_user($email, $password) {
        return user_service()->authenticateUser($email, $password);
    }
}

/**
 * Create verification code
 */
if (!function_exists('create_verification')) {
    function create_verification($userId) {
        return user_service()->createVerificationCode($userId);
    }
}

/**
 * Verify code
 */
if (!function_exists('verify_code')) {
    function verify_code($userId, $code) {
        return user_service()->verifyCode($userId, $code);
    }
}

/**
 * Get current authenticated user
 */
if (!function_exists('current_user')) {
    function current_user($attribute = null) {
        $userId = session('user.id');
        
        if (!$userId) {
            return null;
        }
        
        static $cachedUser = null;
        static $cachedUserId = null;
        
        // Cache user for current request
        if ($cachedUserId !== $userId) {
            $cachedUser = get_user($userId);
            $cachedUserId = $userId;
        }
        
        if (!$cachedUser) {
            return null;
        }
        
        // Return specific attribute if requested
        if ($attribute) {
            return $cachedUser[$attribute] ?? null;
        }
        
        return $cachedUser;
    }
}

/**
 * Check if user is authenticated
 */
if (!function_exists('is_authenticated')) {
    function is_authenticated() {
        return !empty(session('user.id'));
    }
}

/**
 * Check if current user has role
 */
if (!function_exists('user_has_role')) {
    function user_has_role($roleName) {
        $user = current_user();
        
        if (!$user || empty($user['roles'])) {
            return false;
        }
        
        foreach ($user['roles'] as $role) {
            if ($role['name'] === $roleName) {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * Check if current user has any of the specified roles
 */
if (!function_exists('user_has_any_role')) {
    function user_has_any_role($roles) {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        foreach ($roles as $role) {
            if (user_has_role($role)) {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * Check if current user has all specified roles
 */
if (!function_exists('user_has_all_roles')) {
    function user_has_all_roles($roles) {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        foreach ($roles as $role) {
            if (!user_has_role($role)) {
                return false;
            }
        }
        
        return true;
    }
}

/**
 * Check if current user is in group
 */
if (!function_exists('user_in_group')) {
    function user_in_group($groupName) {
        $user = current_user();
        
        if (!$user || empty($user['groups'])) {
            return false;
        }
        
        foreach ($user['groups'] as $group) {
            if ($group['name'] === $groupName) {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * Get user avatar URL
 */
if (!function_exists('user_avatar')) {
    function user_avatar($userId = null) {
        if ($userId) {
            $user = get_user($userId);
        } else {
            $user = current_user();
        }
        
        if (!$user) {
            return '/assets/images/default-avatar.png';
        }
        
        return $user['profile_picture'] ?? '/assets/images/default-avatar.png';
    }
}

/**
 * Format user display name
 */
if (!function_exists('user_display_name')) {
    function user_display_name($userId = null) {
        if ($userId) {
            $user = get_user($userId);
        } else {
            $user = current_user();
        }
        
        if (!$user) {
            return 'Guest';
        }
        
        if (!empty($user['first_name']) && !empty($user['last_name'])) {
            return $user['first_name'] . ' ' . $user['last_name'];
        }
        
        if (!empty($user['first_name'])) {
            return $user['first_name'];
        }
        
        return $user['name'] ?? 'User';
    }
}

/**
 * Get user roles as array of role names
 */
if (!function_exists('user_roles')) {
    function user_roles($userId = null) {
        if ($userId) {
            $user = get_user($userId);
        } else {
            $user = current_user();
        }
        
        if (!$user || empty($user['roles'])) {
            return [];
        }
        
        return array_column($user['roles'], 'name');
    }
}

/**
 * Get user groups as array of group names
 */
if (!function_exists('user_groups')) {
    function user_groups($userId = null) {
        if ($userId) {
            $user = get_user($userId);
        } else {
            $user = current_user();
        }
        
        if (!$user || empty($user['groups'])) {
            return [];
        }
        
        return array_column($user['groups'], 'name');
    }
}

/**
 * Check if user is admin (has admin or super_admin role)
 */
if (!function_exists('user_is_admin')) {
    function user_is_admin($userId = null) {
        return user_has_any_role(['admin', 'super_admin']);
    }
}

/**
 * Get user preference value
 */
if (!function_exists('user_preference')) {
    function user_preference($key, $default = null, $userId = null) {
        if ($userId) {
            $user = get_user($userId);
        } else {
            $user = current_user();
        }
        
        if (!$user || empty($user['preferences'])) {
            return $default;
        }
        
        return $user['preferences'][$key] ?? $default;
    }
}