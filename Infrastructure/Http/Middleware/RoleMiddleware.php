<?php

/**
 * Role Middleware (Parameterized)
 * File: Infrastructure/Http/Middleware/RoleMiddleware.php
 *
 * Checks if user has a specific role
 *
 * Usage examples:
 * - ['role:admin'] -> requires admin role
 * - ['role:superadmin'] -> requires superadmin role
 * - ['role:manager'] -> requires manager role
 */

namespace Infrastructure\Http\Middleware;

class RoleMiddleware
{
    /**
     * Handle the middleware logic with role parameter
     *
     * @param string $requiredRole The role to check
     * @return bool|void Returns false to halt request, true/void to continue
     */
    public function handle($requiredRole)
    {
        // Check if user is authenticated first
        if (!session('authenticated')) {
            redirect('auth.signin');
            return false;
        }

        // Get user's role from session
        $userRole = session('user_role');

        // If no role specified or user doesn't have a role
        if (empty($requiredRole) || empty($userRole)) {
            header("Location: /dashboard", true, 302);
            exit;
        }

        // Define role hierarchy (higher index = higher privilege)
        $roleHierarchy = [
            'officer' => 1,
            'manager' => 2,
            'corridor' => 3,
            'superadmin' => 4,
        ];

        $userRoleLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredRoleLevel = $roleHierarchy[$requiredRole] ?? 999;

        // Check if user's role level meets or exceeds required role
        if ($userRoleLevel < $requiredRoleLevel) {
            // Insufficient role level
            header("Location: /dashboard", true, 302);
            exit;
        }

        // Role check passed
        return true;
    }
}
