<?php

/**
 * Global helper functions for permissions
 */

if (!function_exists('can')) {
    /**
     * Check if user can perform action
     */
    function can($permission, $value = null, $attribute = null, $attributeValue = null) {
        return Permission::getInstance()->can($permission, $value, $attribute, $attributeValue);
    }
}

if (!function_exists('cannot')) {
    /**
     * Check if user cannot perform action
     */
    function cannot($permission, $value = null, $attribute = null, $attributeValue = null) {
        return !can($permission, $value, $attribute, $attributeValue);
    }
}

if (!function_exists('can_any')) {
    /**
     * Check multiple permissions (OR logic)
     */
    function can_any($permissions) {
        return Permission::getInstance()->canAny($permissions);
    }
}

if (!function_exists('can_all')) {
    /**
     * Check multiple permissions (AND logic)
     */
    function can_all($permissions) {
        return Permission::getInstance()->canAll($permissions);
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check if user is admin
     */
    function is_admin() {
        return Permission::getInstance()->isAdmin();
    }
}

if (!function_exists('is_super_admin')) {
    /**
     * Check if user is super admin
     */
    function is_super_admin() {
        return Permission::getInstance()->isSuperAdmin();
    }
}

if (!function_exists('user_roles')) {
    /**
     * Get user's effective roles
     */
    function user_roles() {
        $session = Session::getInstance();
        $userRole = $session->get('user.role') ?: $session->get('role');
        
        if (!$userRole) {
            return [];
        }
        
        return Permission::getInstance()->getUserRoles($userRole);
    }
}

if (!function_exists('user_permissions')) {
    /**
     * Get user's permissions
     */
    function user_permissions() {
        $session = Session::getInstance();
        $userRole = $session->get('user.role') ?: $session->get('role');
        
        if (!$userRole) {
            return [];
        }
        
        return Permission::getInstance()->getRolePermissions($userRole);
    }
}
