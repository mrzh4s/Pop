<?php

/**
 * Admin Middleware (Feature-Specific)
 * File: Features/Auth/Middleware/AdminMiddleware.php
 *
 * Example of feature-specific middleware
 * Ensures user has admin permissions
 */

namespace Features\Auth\Middleware;

class AdminMiddleware
{
    /**
     * Handle the middleware logic
     *
     * @return bool|void Returns false to halt request, true/void to continue
     */
    public function handle()
    {
        // Check if user is authenticated
        if (!session('authenticated')) {
            redirect('auth.signin');
            return false;
        }

        // Check if user has admin permission
        if (!can('system.admin')) {
            // Redirect to unauthorized page or dashboard
            header("Location: /dashboard", true, 302);
            exit;
        }

        // User is admin, continue
        return true;
    }
}
