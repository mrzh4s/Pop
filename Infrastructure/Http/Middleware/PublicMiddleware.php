<?php

/**
 * Public Middleware
 * File: Infrastructure/Http/Middleware/PublicMiddleware.php
 *
 * Allows access to everyone (authenticated or not)
 */

namespace Infrastructure\Http\Middleware;

class PublicMiddleware
{
    /**
     * Handle the middleware logic
     *
     * @return bool|void Returns false to halt request, true/void to continue
     */
    public function handle()
    {
        // Public routes are accessible to everyone
        return true;
    }
}
