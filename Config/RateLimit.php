<?php

/**
 * Rate Limiting Configuration
 *
 * This file contains all rate limiting configuration including
 * backend selection, default limits, and per-route limits.
 */

return [
    /**
     * Rate limiting backend
     *
     * Options:
     * - 'database': Persistent storage using SQLite (survives server restarts)
     * - 'memory': Non-persistent in-memory storage (faster, but resets on restart)
     */
    'backend' => env('RATE_LIMIT_BACKEND', 'database'),

    /**
     * Default rate limits
     * Applied when no specific limit is defined
     */
    'default' => [
        'max_attempts' => (int) env('RATE_LIMIT_DEFAULT_ATTEMPTS', 60),
        'decay_minutes' => (int) env('RATE_LIMIT_DEFAULT_DECAY', 1), // 1 minute window
    ],

    /**
     * API rate limits
     * Applied to API endpoints
     */
    'api' => [
        'max_attempts' => (int) env('RATE_LIMIT_API_ATTEMPTS', 60),
        'decay_minutes' => (int) env('RATE_LIMIT_API_DECAY', 1), // 1 minute window
    ],

    /**
     * Login rate limits
     * Applied to authentication endpoints to prevent brute force attacks
     */
    'login' => [
        'max_attempts' => (int) env('RATE_LIMIT_LOGIN_ATTEMPTS', 5),
        'decay_minutes' => (int) env('RATE_LIMIT_LOGIN_DECAY', 15), // 15 minute lockout
    ],

    /**
     * Registration rate limits
     * Applied to user registration to prevent spam
     */
    'registration' => [
        'max_attempts' => (int) env('RATE_LIMIT_REGISTRATION_ATTEMPTS', 3),
        'decay_minutes' => (int) env('RATE_LIMIT_REGISTRATION_DECAY', 60), // 1 hour
    ],

    /**
     * Password reset rate limits
     * Applied to password reset requests
     */
    'password_reset' => [
        'max_attempts' => (int) env('RATE_LIMIT_PASSWORD_RESET_ATTEMPTS', 3),
        'decay_minutes' => (int) env('RATE_LIMIT_PASSWORD_RESET_DECAY', 60), // 1 hour
    ],

    /**
     * File upload rate limits
     * Applied to file upload endpoints
     */
    'upload' => [
        'max_attempts' => (int) env('RATE_LIMIT_UPLOAD_ATTEMPTS', 10),
        'decay_minutes' => (int) env('RATE_LIMIT_UPLOAD_DECAY', 5), // 5 minutes
    ],

    /**
     * Search rate limits
     * Applied to search endpoints
     */
    'search' => [
        'max_attempts' => (int) env('RATE_LIMIT_SEARCH_ATTEMPTS', 30),
        'decay_minutes' => (int) env('RATE_LIMIT_SEARCH_DECAY', 1), // 1 minute
    ],

    /**
     * Per-route rate limits
     * Override defaults for specific routes
     *
     * Format: 'route_pattern' => ['max_attempts' => X, 'decay_minutes' => Y]
     */
    'routes' => [
        '/api/search' => [
            'max_attempts' => 30,
            'decay_minutes' => 1,
        ],
        '/api/upload' => [
            'max_attempts' => 10,
            'decay_minutes' => 5,
        ],
        '/api/auth/login' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        '/api/auth/register' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
    ],

    /**
     * Headers to include in rate limit responses
     * Set to true to include X-RateLimit-* headers in responses
     */
    'headers' => [
        'enabled' => (bool) env('RATE_LIMIT_HEADERS', true),
        'limit_header' => 'X-RateLimit-Limit',
        'remaining_header' => 'X-RateLimit-Remaining',
        'reset_header' => 'X-RateLimit-Reset',
    ],

    /**
     * Whitelist IP addresses (never rate limited)
     */
    'whitelist' => [
        // '127.0.0.1',
        // '::1',
    ],

    /**
     * Blacklist IP addresses (always rate limited)
     */
    'blacklist' => [
        // Add IPs to always block
    ],
];
