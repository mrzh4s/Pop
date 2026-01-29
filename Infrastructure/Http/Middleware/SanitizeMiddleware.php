<?php

namespace Infrastructure\Http\Middleware;

use Framework\Validation\Sanitizer;

/**
 * SanitizeMiddleware - Auto-sanitizes all request input
 *
 * Usage in routes:
 *   Router::post('/posts', 'PostController@store', ['sanitize']);
 *
 * This middleware automatically sanitizes all incoming request data
 * to prevent XSS and other injection attacks. It's a passive middleware
 * that cleans data before it reaches controllers.
 *
 * Note: This performs basic sanitization. You may still need additional
 * validation for your specific use cases.
 */
class SanitizeMiddleware
{
    /**
     * Handle the middleware
     *
     * @return bool Always returns true (passive middleware)
     */
    public function handle(): bool
    {
        // Get sanitizer instance
        $sanitizer = Sanitizer::getInstance();

        // Sanitize $_GET
        if (!empty($_GET)) {
            $_GET = $sanitizer->deepSanitize($_GET, 'string');
        }

        // Sanitize $_POST
        if (!empty($_POST)) {
            $_POST = $sanitizer->deepSanitize($_POST, 'string');
        }

        // Note: We don't modify $_FILES as file data shouldn't be sanitized this way
        // File validation should be done separately using FileValidator

        // Note: We also don't modify the Router's request data directly
        // as it may have already been parsed. The sanitization above will
        // affect new calls to Request::all() and related methods.

        return true; // Always continue
    }
}
