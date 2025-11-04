<?php
/**
 * Permission Manager
 * File: apps/core/PermissionManager.php
 * 
 * Handles role-based permissions and access control
 */

class Permission {
    private static $instance = null;
    private $permissions = [];
    private $roleHierarchy = [];
    private $customRules = [];
    
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
     * Constructor - Initialize default role hierarchy
     */
    public function __construct() {
        $this->loadDefaultRoleHierarchy();
        $this->loadPermissions();
    }
    
    /**
     * Load default role hierarchy
     */
    private function loadDefaultRoleHierarchy() {
        $this->roleHierarchy = [
            "superadmin"=> ["system"],
            // Super admin - full access
            'corridor' => ['executive', 'manager', 'officer', 'assistant', 'admin', 'user'],
            
            // Management roles
            'manager' => ['executive', 'geospatial', 'technology', 'officer'],
            'executive' => ['officer', 'assistant'],
            
            // Authority roles
            'authority' => ['officer'],
            
            // Technical roles
            'geospatial' => ['user'],
            'technology' => ['user'],
            
            // Basic roles
            'officer' => [],
            'assistant' => [],
            'admin' => ['user'],
            'user' => []
        ];
    }
    
    /**
     * Load permission definitions
     */
    private function loadPermissions() {
        $this->permissions = [
            // System permissions
            'system.admin' => ['corridor', 'admin'],
            'system.config' => ['corridor', 'manager'],
            'system.logs' => ['corridor', 'manager', 'technology'],
            
            // User management
            'users.view' => ['corridor', 'manager', 'executive'],
            'users.create' => ['corridor', 'manager'],
            'users.edit' => ['corridor', 'manager'],
            'users.delete' => ['corridor'],
            
            // Project permissions
            'projects.view' => '*', // All authenticated users
            'projects.create' => ['corridor', 'manager', 'executive', 'officer'],
            'projects.edit' => ['corridor', 'manager', 'executive', 'officer'],
            'projects.delete' => ['corridor', 'manager'],
            'projects.approve' => ['corridor', 'manager', 'authority'],
            
            // Reports
            'reports.view' => '*',
            'reports.export' => ['corridor', 'manager', 'executive', 'officer'],
            'reports.admin' => ['corridor', 'manager'],
            
            // Geospatial
            'geospatial.view' => '*',
            'geospatial.edit' => ['corridor', 'manager', 'geospatial'],
            'geospatial.admin' => ['corridor', 'geospatial'],
            
            // Authority actions
            'authority.approve' => ['corridor', 'authority', 'manager'],
            'authority.reject' => ['corridor', 'authority', 'manager'],
        ];
    }
    
    /**
     * Check if user can perform action
     */
    public function can($permission, $value = null, $attribute = null, $attributeValue = null) {
        $session = Session::getInstance();
        
        // Get current user data
        $userRole = $session->get('user.role') ?: $session->get('role');
        $userDepartment = $session->get('user.department') ?: $session->get('department');
        $userLocation = $session->get('user.location') ?: $session->get('location');
        $username = $session->get('user.username') ?: $session->get('username');
        
        // No role means no permissions
        if (!$userRole) {
            return false;
        }
        
        // Check direct permission
        if ($this->checkDirectPermission($permission, $userRole, $value)) {
            // If attribute check is required
            if ($attribute && $attributeValue) {
                return $this->checkAttributePermission($attribute, $attributeValue, [
                    'role' => $userRole,
                    'department' => $userDepartment,
                    'location' => $userLocation,
                    'username' => $username
                ]);
            }
            return true;
        }
        
        // Check custom rules
        return $this->checkCustomRules($permission, $userRole, $value, $attribute, $attributeValue);
    }
    
    /**
     * Check direct permission
     */
    private function checkDirectPermission($permission, $userRole, $value) {
        // Special case: role permission check
        if (strtolower($permission) === 'role' && $value) {
            return $this->hasRole($userRole, $value);
        }
        
        // Check if permission exists in definitions
        if (isset($this->permissions[$permission])) {
            $allowedRoles = $this->permissions[$permission];
            
            // Universal access
            if ($allowedRoles === '*') {
                return true;
            }
            
            // Check if user role is in allowed roles
            if (is_array($allowedRoles)) {
                return $this->hasAnyRole($userRole, $allowedRoles);
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has specific role or inherits it
     */
    private function hasRole($userRole, $requiredRole) {
        $userRole = strtolower($userRole);
        $requiredRole = strtolower($requiredRole);
        
        // Direct match
        if ($userRole === $requiredRole) {
            return true;
        }
        
        // Check role hierarchy
        if (isset($this->roleHierarchy[$userRole])) {
            return in_array($requiredRole, array_map('strtolower', $this->roleHierarchy[$userRole]));
        }
        
        return false;
    }
    
    /**
     * Check if user has any of the required roles
     */
    private function hasAnyRole($userRole, $allowedRoles) {
        foreach ($allowedRoles as $allowedRole) {
            if ($this->hasRole($userRole, $allowedRole)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check attribute-based permission
     */
    private function checkAttributePermission($attribute, $value, $userData) {
        switch (strtolower($attribute)) {
            case 'department':
                return strtolower($userData['department'] ?? '') === strtolower($value);
            
            case 'location':
                return strtolower($userData['location'] ?? '') === strtolower($value);
            
            case 'role':
                return $this->hasRole($userData['role'] ?? '', $value);
            
            case 'username':
                return strtolower($userData['username'] ?? '') === strtolower($value);
            
            case 'own':
                // Check if user owns the resource
                return strtolower($userData['username'] ?? '') === strtolower($value);
            
            default:
                return false;
        }
    }
    
    /**
     * Check custom permission rules
     */
    private function checkCustomRules($permission, $userRole, $value, $attribute, $attributeValue) {
        if (isset($this->customRules[$permission])) {
            $rule = $this->customRules[$permission];
            
            if (is_callable($rule)) {
                return $rule($userRole, $value, $attribute, $attributeValue);
            }
        }
        
        return false;
    }
    
    /**
     * Check multiple permissions (OR logic)
     */
    public function canAny($permissions) {
        foreach ($permissions as $permission => $value) {
            if (is_numeric($permission)) {
                // Simple permission list
                if ($this->can($value)) {
                    return true;
                }
            } else {
                // Permission => value pairs
                if ($this->can($permission, $value)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Check multiple permissions (AND logic)
     */
    public function canAll($permissions) {
        foreach ($permissions as $permission => $value) {
            if (is_numeric($permission)) {
                // Simple permission list
                if (!$this->can($value)) {
                    return false;
                }
            } else {
                // Permission => value pairs
                if (!$this->can($permission, $value)) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Add custom permission rule
     */
    public function addRule($permission, $callback) {
        $this->customRules[$permission] = $callback;
    }
    
    /**
     * Add permission definition
     */
    public function addPermission($permission, $roles) {
        $this->permissions[$permission] = $roles;
    }
    
    /**
     * Remove permission
     */
    public function removePermission($permission) {
        unset($this->permissions[$permission]);
    }
    
    /**
     * Set role hierarchy
     */
    public function setRoleHierarchy($hierarchy) {
        $this->roleHierarchy = $hierarchy;
    }
    
    /**
     * Get role hierarchy
     */
    public function getRoleHierarchy() {
        return $this->roleHierarchy;
    }
    
    /**
     * Get user's effective roles (including inherited)
     */
    public function getUserRoles($userRole) {
        $roles = [strtolower($userRole)];
        
        if (isset($this->roleHierarchy[strtolower($userRole)])) {
            $inheritedRoles = array_map('strtolower', $this->roleHierarchy[strtolower($userRole)]);
            $roles = array_merge($roles, $inheritedRoles);
        }
        
        return array_unique($roles);
    }
    
    /**
     * Get all permissions for a role
     */
    public function getRolePermissions($role) {
        $rolePermissions = [];
        
        foreach ($this->permissions as $permission => $allowedRoles) {
            if ($allowedRoles === '*' || 
                (is_array($allowedRoles) && $this->hasAnyRole($role, $allowedRoles))) {
                $rolePermissions[] = $permission;
            }
        }
        
        return $rolePermissions;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin($userRole = null) {
        $session = Session::getInstance();
        $role = $userRole ?: $session->get('user.role') ?: $session->get('role');
        
        return $this->hasAnyRole($role, ['corridor', 'admin', 'manager']);
    }
    
    /**
     * Check if user is super admin
     */
    public function isSuperAdmin($userRole = null) {
        $session = Session::getInstance();
        $role = $userRole ?: $session->get('user.role') ?: $session->get('role');
        
        return $this->hasRole($role, 'superadmin');
    }
    
    /**
     * Get debug information
     */
    public function getDebugInfo() {
        $session = Session::getInstance();
        $userRole = $session->get('user.role') ?: $session->get('role');
        
        return [
            'current_role' => $userRole,
            'effective_roles' => $userRole ? $this->getUserRoles($userRole) : [],
            'is_admin' => $this->isAdmin($userRole),
            'is_super_admin' => $this->isSuperAdmin($userRole),
            'total_permissions' => count($this->permissions),
            'role_permissions' => $userRole ? count($this->getRolePermissions($userRole)) : 0,
            'custom_rules' => array_keys($this->customRules)
        ];
    }
}

