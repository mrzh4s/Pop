<?php
/**
 * Permissions & Roles Configuration
 * File: apps/config/Permissions.php
 *
 * Features:
 * - Define unlimited roles with hierarchies
 * - Define unlimited permissions
 * - Support for wildcard permissions (*)
 * - Role inheritance
 * - Attribute-based access control (ABAC)
 * - Environment-specific configurations
 *
 * Usage:
 * Define your roles and permissions here.
 * The Permission system will auto-discover them.
 */

return [

    /**
     * Role Hierarchy
     *
     * Defines which roles inherit permissions from other roles.
     * Format: 'parent_role' => ['child_role1', 'child_role2']
     *
     * Example:
     * If 'manager' => ['officer', 'user'], then:
     * - Manager can do everything Officer and User can do
     * - Plus any permissions specifically assigned to Manager
     */
    'roles' => [
        // Super Admin - Top level access
        'superadmin' => ['system', 'corridor', 'admin'],

        // Corridor - Full administrative access
        'corridor' => ['executive', 'manager', 'officer', 'assistant', 'admin', 'user'],

        // Management Hierarchy
        'manager' => ['executive', 'geospatial', 'technology', 'officer'],
        'executive' => ['officer', 'assistant'],

        // Authority Roles
        'authority' => ['officer'],

        // Technical Roles
        'geospatial' => ['user'],
        'technology' => ['user'],

        // Basic Roles
        'admin' => ['user'],
        'officer' => [],
        'assistant' => [],
        'user' => [],

        // Custom Roles (Examples - add your own!)
        // 'department_head' => ['team_lead', 'employee'],
        // 'team_lead' => ['employee'],
        // 'employee' => [],
        // 'guest' => [],
    ],

    /**
     * Permissions
     *
     * Define who can do what.
     * Format: 'permission.name' => ['role1', 'role2'] or '*' for all
     *
     * Permission Naming Convention:
     * - Use dot notation: 'module.action'
     * - Examples: 'users.create', 'projects.delete', 'reports.export'
     *
     * Special Values:
     * - '*' = All authenticated users
     * - [] = No one (permission exists but not granted)
     */
    'permissions' => [

        // ============== SYSTEM PERMISSIONS ==============
        'system.admin' => ['superadmin', 'corridor', 'admin'],
        'system.config' => ['superadmin', 'corridor', 'manager'],
        'system.logs' => ['superadmin', 'corridor', 'manager', 'technology'],
        'system.debug' => ['superadmin', 'technology'],
        'system.maintenance' => ['superadmin', 'corridor'],

        // ============== USER MANAGEMENT ==============
        'users.view' => ['corridor', 'manager', 'executive'],
        'users.view.all' => ['corridor', 'manager'],
        'users.view.own' => '*',  // Everyone can view their own profile
        'users.create' => ['corridor', 'manager'],
        'users.edit' => ['corridor', 'manager'],
        'users.edit.own' => '*',  // Everyone can edit their own profile
        'users.delete' => ['corridor'],
        'users.suspend' => ['corridor', 'manager'],
        'users.activate' => ['corridor', 'manager'],
        'users.export' => ['corridor', 'manager', 'executive'],

        // ============== PROJECT MANAGEMENT ==============
        'projects.view' => '*',  // All authenticated users
        'projects.view.all' => ['corridor', 'manager', 'executive'],
        'projects.view.own' => '*',
        'projects.create' => ['corridor', 'manager', 'executive', 'officer'],
        'projects.edit' => ['corridor', 'manager', 'executive', 'officer'],
        'projects.edit.own' => ['officer', 'assistant'],
        'projects.delete' => ['corridor', 'manager'],
        'projects.approve' => ['corridor', 'manager', 'authority'],
        'projects.reject' => ['corridor', 'manager', 'authority'],
        'projects.archive' => ['corridor', 'manager'],
        'projects.export' => ['corridor', 'manager', 'executive'],

        // ============== REPORTS ==============
        'reports.view' => '*',
        'reports.view.all' => ['corridor', 'manager', 'executive'],
        'reports.create' => ['corridor', 'manager', 'executive', 'officer'],
        'reports.edit' => ['corridor', 'manager', 'executive'],
        'reports.delete' => ['corridor', 'manager'],
        'reports.export' => ['corridor', 'manager', 'executive', 'officer'],
        'reports.admin' => ['corridor', 'manager'],
        'reports.schedule' => ['corridor', 'manager'],

        // ============== GEOSPATIAL ==============
        'geospatial.view' => '*',
        'geospatial.edit' => ['corridor', 'manager', 'geospatial'],
        'geospatial.create' => ['corridor', 'manager', 'geospatial'],
        'geospatial.delete' => ['corridor', 'geospatial'],
        'geospatial.admin' => ['corridor', 'geospatial'],
        'geospatial.export' => ['corridor', 'manager', 'geospatial'],

        // ============== AUTHORITY ACTIONS ==============
        'authority.approve' => ['corridor', 'authority', 'manager'],
        'authority.reject' => ['corridor', 'authority', 'manager'],
        'authority.review' => ['corridor', 'authority', 'manager', 'executive'],
        'authority.delegate' => ['corridor', 'authority'],

        // ============== SETTINGS ==============
        'settings.view' => '*',
        'settings.edit' => ['corridor', 'manager', 'admin'],
        'settings.system' => ['superadmin', 'corridor'],
        'settings.security' => ['superadmin', 'corridor'],

        // ============== ANALYTICS ==============
        'analytics.view' => ['corridor', 'manager', 'executive'],
        'analytics.export' => ['corridor', 'manager'],
        'analytics.admin' => ['corridor'],

        // ============== CUSTOM PERMISSIONS (Examples) ==============
        // Add your own permissions here!

        // 'invoices.create' => ['manager', 'accountant'],
        // 'invoices.approve' => ['manager'],
        // 'invoices.pay' => ['corridor', 'finance'],

        // 'documents.upload' => '*',
        // 'documents.delete' => ['manager', 'admin'],
        // 'documents.archive' => ['manager'],

        // 'notifications.send' => ['manager', 'admin'],
        // 'notifications.broadcast' => ['corridor'],

        // 'api.access' => ['developer', 'admin'],
        // 'api.keys.create' => ['corridor', 'admin'],
    ],

    /**
     * Attribute-Based Access Control (ABAC)
     *
     * Define which attributes can be used for permission checks
     * Available by default: department, location, username, own, role
     *
     * You can add custom attribute checkers via Permission::addAttributeChecker()
     */
    'attributes' => [
        'department' => true,  // Check user's department
        'location' => true,    // Check user's location
        'username' => true,    // Check specific username
        'own' => true,         // Check if user owns the resource
        'role' => true,        // Check specific role

        // Custom attributes (implement via addAttributeChecker)
        // 'team' => true,
        // 'branch' => true,
        // 'level' => true,
    ],

    /**
     * Wildcards
     *
     * Configure wildcard behavior for permissions
     */
    'wildcards' => [
        'enabled' => true,           // Enable wildcard permissions
        'all_users_symbol' => '*',   // Symbol for "all authenticated users"
    ],

    /**
     * Permission Groups (Optional)
     *
     * Group related permissions for easier management
     * These don't affect functionality, just for organization
     */
    'groups' => [
        'user_management' => [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
        ],

        'project_management' => [
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.delete',
            'projects.approve',
        ],

        'administration' => [
            'system.admin',
            'system.config',
            'settings.system',
        ],
    ],

    /**
     * Super Admin Configuration
     *
     * Super admins bypass all permission checks
     */
    'super_admin' => [
        'enabled' => true,
        'roles' => ['superadmin'],  // Which roles are considered super admin
        'bypass_all' => true,        // Super admins can do everything
    ],

    /**
     * Guest Configuration
     *
     * Permissions for unauthenticated users
     */
    'guest' => [
        'role' => 'guest',
        'permissions' => [
            // What can unauthenticated users do?
            // Usually none, but you might allow some public views
        ],
    ],

    /**
     * Debugging
     */
    'debug' => [
        'log_permission_checks' => env('APP_DEBUG', false),  // Log all permission checks
        'show_debug_info' => env('APP_DEBUG', false),        // Show debug info
    ],
];
