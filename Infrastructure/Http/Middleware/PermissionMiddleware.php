<?php

/**
 * Permission Middleware (Parameterized)
 * File: Infrastructure/Http/Middleware/PermissionMiddleware.php
 *
 * Checks user permissions dynamically based on parameters
 *
 * Usage examples:
 * - ['permission:view:admin'] -> requires 'view:admin' permission
 * - ['permission:users.create'] -> requires 'users.create' permission
 * - ['permission:*'] -> requires any authenticated user
 */

namespace Infrastructure\Http\Middleware;

class PermissionMiddleware
{
    /**
     * Handle the middleware logic with permission parameters
     *
     * @param string ...$permissions Permission parts to check
     * @return bool|void Returns false to halt request, true/void to continue
     */
    public function handle(...$permissions)
    {
        // Check if user is authenticated first
        if (!session('authenticated')) {
            redirect('auth.signin');
            return false;
        }

        // If no permissions specified, just check authentication
        if (empty($permissions)) {
            return true;
        }

        // Join permissions with appropriate separator
        // Examples:
        // permission:view:admin -> "view:admin"
        // permission:users.create -> "users.create"
        $permissionString = implode('.', $permissions);

        // Check the permission using the can() helper
        if (!can($permissionString)) {
            // User doesn't have permission, redirect to dashboard
            header("Location: /dashboard", true, 302);
            exit;
        }

        // Permission granted
        return true;
    }
}
