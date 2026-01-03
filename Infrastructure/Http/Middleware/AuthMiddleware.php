<?php

/**
 * Authentication Middleware
 * File: Infrastructure/Http/Middleware/AuthMiddleware.php
 *
 * Ensures the user is authenticated before accessing the route
 */

namespace Infrastructure\Http\Middleware;

class AuthMiddleware
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
            // Redirect to login page
            redirect('auth.signin');
            return false; // Halt the request
        }

        // User is authenticated, continue
        return true;
    }
}
