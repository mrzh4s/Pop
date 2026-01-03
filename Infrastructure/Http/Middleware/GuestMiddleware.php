<?php

/**
 * Guest Middleware
 * File: Infrastructure/Http/Middleware/GuestMiddleware.php
 *
 * Ensures the user is NOT authenticated (guest only routes)
 */

namespace Infrastructure\Http\Middleware;

class GuestMiddleware
{
    /**
     * Handle the middleware logic
     *
     * @return bool|void Returns false to halt request, true/void to continue
     */
    public function handle()
    {
        // Check if user is authenticated
        if (session('authenticated')) {
            // User is logged in, redirect to dashboard
            header("Location: /dashboard", true, 302);
            exit;
        }

        // User is guest, continue
        return true;
    }
}
