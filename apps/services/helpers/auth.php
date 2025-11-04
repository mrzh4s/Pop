<?php

/**
 * Authentication Helper Functions (Updated for Session class only)
 * File: auth.php
 * 
 * Global helper functions for authentication management
 */

// ============== CORE AUTH HELPERS ==============

if (!function_exists('auth_service')) {
    /**
     * Get AuthService instance
     */
    function auth_service() {
        return AuthService::getInstance();
    }
}

if (!function_exists('auth_login')) {
    /**
     * Login user with email and password
     */
    function auth_login($identifier, $password, $rememberMe = false) {
        return auth_service()->login($identifier, $password, $rememberMe);
    }
}

if (!function_exists('auth_logout')) {
    /**
     * Logout current user
     */
    function auth_logout() {
        return auth_service()->logout();
    }
}

if (!function_exists('auth_register')) {
    /**
     * Register new user
     */
    function auth_register($userData) {
        return auth_service()->register($userData);
    }
}

if (!function_exists('auth_check')) {
    /**
     * Check if user is authenticated
     */
    function auth_check() {
        return auth_service()->check();
    }
}

if (!function_exists('auth_user')) {
    /**
     * Get current authenticated user (basic session data)
     */
    function auth_user() {
        return auth_service()->user();
    }
}

if (!function_exists('auth_full_user')) {
    /**
     * Get current authenticated user with full data (roles, groups, etc.)
     */
    function auth_full_user() {
        return auth_service()->getFullUser();
    }
}

if (!function_exists('auth_id')) {
    /**
     * Get current user ID
     */
    function auth_id() {
        return auth_service()->getCurrentUserId();
    }
}

// ============== EMAIL VERIFICATION HELPERS ==============

if (!function_exists('auth_verify_email')) {
    /**
     * Verify email with code
     */
    function auth_verify_email($userId, $code) {
        return auth_service()->verifyEmail($userId, $code);
    }
}

if (!function_exists('auth_send_verification')) {
    /**
     * Send verification code
     */
    function auth_send_verification($userId) {
        return auth_service()->sendVerificationCode($userId);
    }
}

// ============== PASSWORD RESET HELPERS ==============

if (!function_exists('auth_request_password_reset')) {
    /**
     * Request password reset
     */
    function auth_request_password_reset($email) {
        return auth_service()->requestPasswordReset($email);
    }
}

if (!function_exists('auth_reset_password')) {
    /**
     * Reset password with token
     */
    function auth_reset_password($token, $newPassword) {
        return auth_service()->resetPassword($token, $newPassword);
    }
}

if (!function_exists('auth_change_password')) {
    /**
     * Change password for authenticated user
     */
    function auth_change_password($currentPassword, $newPassword) {
        return auth_service()->changePassword($currentPassword, $newPassword);
    }
}

// ============== AUTHORIZATION HELPERS ==============

if (!function_exists('auth_has_role')) {
    /**
     * Check if current user has specific role
     */
    function auth_has_role($roleName) {
        return auth_service()->hasRole($roleName);
    }
}

if (!function_exists('auth_has_any_role')) {
    /**
     * Check if current user has any of the specified roles
     */
    function auth_has_any_role($roles) {
        return auth_service()->hasAnyRole($roles);
    }
}

if (!function_exists('auth_in_group')) {
    /**
     * Check if current user is in specific group
     */
    function auth_in_group($groupName) {
        return auth_service()->inGroup($groupName);
    }
}

if (!function_exists('auth_is_admin')) {
    /**
     * Check if current user is admin
     */
    function auth_is_admin() {
        return auth_service()->isAdmin();
    }
}

// ============== REMEMBER TOKEN HELPERS ==============

if (!function_exists('auth_validate_remember_token')) {
    /**
     * Validate remember token and auto-login
     */
    function auth_validate_remember_token() {
        return auth_service()->validateRememberToken();
    }
}

// ============== CONVENIENCE HELPERS ==============

if (!function_exists('auth_guest')) {
    /**
     * Check if user is guest (not authenticated)
     */
    function auth_guest() {
        return !auth_check();
    }
}

if (!function_exists('auth_name')) {
    /**
     * Get current user's name
     */
    function auth_name() {
        $user = auth_user();
        return $user ? $user['name'] : null;
    }
}

if (!function_exists('auth_email')) {
    /**
     * Get current user's email
     */
    function auth_email() {
        $user = auth_user();
        return $user ? $user['email'] : null;
    }
}

if (!function_exists('auth_username')) {
    /**
     * Get current user's username
     */
    function auth_username() {
        $user = auth_user();
        return $user ? $user['username'] : null;
    }
}

if (!function_exists('auth_is_verified')) {
    /**
     * Check if current user's email is verified
     */
    function auth_is_verified() {
        $user = auth_full_user();
        return $user && !empty($user['email_verified_at']);
    }
}

if (!function_exists('auth_is_active')) {
    /**
     * Check if current user is active
     */
    function auth_is_active() {
        $user = auth_full_user();
        return $user && $user['is_active'];
    }
}

// ============== ROLE & GROUP ARRAY HELPERS ==============

if (!function_exists('auth_roles')) {
    /**
     * Get current user's roles as array of role names
     */
    function auth_roles() {
        $user = auth_full_user();
        if (!$user || empty($user['roles'])) {
            return [];
        }
        return array_column($user['roles'], 'name');
    }
}

if (!function_exists('auth_groups')) {
    /**
     * Get current user's groups as array of group names
     */
    function auth_groups() {
        $user = auth_full_user();
        if (!$user || empty($user['groups'])) {
            return [];
        }
        return array_column($user['groups'], 'name');
    }
}

// ============== REDIRECT HELPERS ==============

if (!function_exists('auth_redirect_if_guest')) {
    /**
     * Redirect to login if user is not authenticated
     */
    function auth_redirect_if_guest($loginUrl = '/login') {
        if (auth_guest()) {
            header("Location: $loginUrl");
            exit;
        }
    }
}

if (!function_exists('auth_redirect_if_authenticated')) {
    /**
     * Redirect to dashboard if user is already authenticated
     */
    function auth_redirect_if_authenticated($dashboardUrl = '/dashboard') {
        if (auth_check()) {
            header("Location: $dashboardUrl");
            exit;
        }
    }
}

if (!function_exists('auth_require_role')) {
    /**
     * Require specific role or redirect
     */
    function auth_require_role($roleName, $redirectUrl = '/unauthorized') {
        if (!auth_has_role($roleName)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
}

if (!function_exists('auth_require_admin')) {
    /**
     * Require admin role or redirect
     */
    function auth_require_admin($redirectUrl = '/unauthorized') {
        if (!auth_is_admin()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
}

// ============== AUTO-LOGIN HELPER ==============

if (!function_exists('auth_attempt_auto_login')) {
    /**
     * Attempt auto-login with remember token
     */
    function auth_attempt_auto_login() {
        if (auth_guest() && isset($_COOKIE['remember_token'])) {
            return auth_validate_remember_token();
        }
        return false;
    }
}

// ============== UTILITY HELPERS ==============

if (!function_exists('auth_avatar')) {
    /**
     * Get current user's avatar URL
     */
    function auth_avatar($default = '/assets/images/default-avatar.png') {
        $user = auth_full_user();
        if (!$user) {
            return $default;
        }
        return $user['profile_picture'] ?? $default;
    }
}

if (!function_exists('auth_display_name')) {
    /**
     * Get current user's display name
     */
    function auth_display_name() {
        $user = auth_full_user();
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

if (!function_exists('auth_initials')) {
    /**
     * Get current user's initials
     */
    function auth_initials() {
        $user = auth_full_user();
        if (!$user) {
            return 'G';
        }
        
        if (!empty($user['first_name']) && !empty($user['last_name'])) {
            return strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
        }
        
        $name = $user['first_name'] ?? $user['name'] ?? 'User';
        $nameParts = explode(' ', $name);
        
        if (count($nameParts) >= 2) {
            return strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
        }
        
        return strtoupper(substr($name, 0, 1));
    }
}

// ============== PREFERENCE HELPERS ==============

if (!function_exists('auth_preference')) {
    /**
     * Get current user's preference value
     */
    function auth_preference($key, $default = null) {
        $user = auth_full_user();
        if (!$user || empty($user['preferences'])) {
            return $default;
        }
        
        return $user['preferences'][$key] ?? $default;
    }
}

// ============== SESSION INTEGRATION HELPERS ==============

if (!function_exists('auth_session_info')) {
    /**
     * Get current session information from database
     */
    function auth_session_info() {
        return session_info();
    }
}

if (!function_exists('auth_devices')) {
    /**
     * Get current user's devices
     */
    function auth_devices() {
        $userId = auth_id();
        if (!$userId) {
            return [];
        }
        return get_user_devices($userId);
    }
}

if (!function_exists('auth_terminate_device')) {
    /**
     * Terminate specific device for current user
     */
    function auth_terminate_device($sessionId) {
        $userId = auth_id();
        if (!$userId) {
            return false;
        }
        return session_terminate($sessionId, $userId);
    }
}

if (!function_exists('auth_terminate_other_devices')) {
    /**
     * Terminate all other devices for current user
     */
    function auth_terminate_other_devices() {
        $userId = auth_id();
        if (!$userId) {
            return false;
        }
        return session_terminate_others($userId);
    }
}

if (!function_exists('auth_trust_current_device')) {
    /**
     * Mark current device as trusted
     */
    function auth_trust_current_device() {
        return session_trust_device();
    }
}

if (!function_exists('auth_active_session_count')) {
    /**
     * Count active sessions for current user
     */
    function auth_active_session_count() {
        $userId = auth_id();
        if (!$userId) {
            return 0;
        }
        return count_active_sessions($userId);
    }
}