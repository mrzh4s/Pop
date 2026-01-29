<?php

namespace Infrastructure\Http\Middleware;

use Framework\Validation\RateLimiter;
use Framework\Http\Request;
use Framework\Http\Response;

/**
 * ThrottleMiddleware - Rate limits requests per IP/user
 *
 * Usage in routes:
 *   Router::post('/api/search', 'SearchController@search', ['throttle:30:1']);
 *   Format: throttle:maxAttempts:decayMinutes
 *
 * The middleware will track requests and return 429 status when limit is exceeded.
 */
class ThrottleMiddleware
{
    /**
     * Handle the middleware
     *
     * @param int $maxAttempts Maximum number of attempts (default: 60)
     * @param int $decayMinutes Time window in minutes (default: 1)
     * @return bool True to continue, false to halt
     */
    public function handle(int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        // Resolve request key (IP + route)
        $key = $this->resolveRequestKey();

        // Get rate limiter
        $limiter = RateLimiter::getInstance();

        // Check if too many attempts
        if ($limiter->tooManyAttempts($key, $maxAttempts)) {
            $this->handleTooManyAttempts($key, $limiter);
            return false; // Halt request
        }

        // Increment counter
        $limiter->hit($key, $decayMinutes);

        // Add rate limit headers
        $this->addRateLimitHeaders($key, $maxAttempts, $limiter);

        return true; // Continue
    }

    /**
     * Resolve request key for rate limiting
     *
     * @return string Unique identifier for this request
     */
    private function resolveRequestKey(): string
    {
        // Get IP address
        $ip = $this->getClientIp();

        // Get request path
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string
        $path = strtok($path, '?');

        // Create unique key
        return 'throttle:' . md5($ip . ':' . $path);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function getClientIp(): string
    {
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Handle too many attempts
     *
     * @param string $key Rate limit key
     * @param RateLimiter $limiter Rate limiter instance
     * @return void
     */
    private function handleTooManyAttempts(string $key, RateLimiter $limiter): void
    {
        $retryAfter = $limiter->availableIn($key);

        // Add rate limit headers
        header('X-RateLimit-Limit: 0');
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . (time() + $retryAfter));
        header('Retry-After: ' . $retryAfter);

        // Check if request expects JSON
        if ($this->expectsJson()) {
            Response::json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429);
        } else {
            // HTML response
            http_response_code(429);
            echo "<!DOCTYPE html>
<html>
<head>
    <title>Too Many Requests</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 50px; text-align: center; }
        h1 { color: #e74c3c; }
        p { color: #7f8c8d; }
    </style>
</head>
<body>
    <h1>Too Many Requests</h1>
    <p>You have made too many requests. Please try again in {$retryAfter} seconds.</p>
</body>
</html>";
            exit;
        }
    }

    /**
     * Add rate limit headers to response
     *
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum attempts
     * @param RateLimiter $limiter Rate limiter instance
     * @return void
     */
    private function addRateLimitHeaders(string $key, int $maxAttempts, RateLimiter $limiter): void
    {
        $attempts = $limiter->attempts($key);
        $remaining = max(0, $maxAttempts - $attempts);
        $resetTime = time() + $limiter->availableIn($key);

        header('X-RateLimit-Limit: ' . $maxAttempts);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $resetTime);
    }

    /**
     * Check if request expects JSON response
     *
     * @return bool
     */
    private function expectsJson(): bool
    {
        // Check Content-Type header
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        // Check Accept header
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        // Check if it's an API route
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($requestUri, '/api/')) {
            return true;
        }

        return false;
    }
}
