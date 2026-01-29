<?php

namespace Infrastructure\Http\Middleware;

use Framework\Validation\Validator;
use Framework\Validation\ValidationException;
use Framework\Http\Request;
use Framework\Http\Response;

/**
 * ValidateMiddleware - Auto-validates request data against rules
 *
 * Usage in routes:
 *   Router::post('/users', 'UserController@store', ['validate:name|required,email|required|email']);
 *
 * The middleware will parse validation rules from parameters and validate
 * the request data. If validation fails, it returns a 422 error response.
 */
class ValidateMiddleware
{
    /**
     * Handle the middleware
     *
     * @param string ...$rules Validation rules in format "field|rule1|rule2,field2|rule1"
     * @return bool True to continue, false to halt
     */
    public function handle(...$rules): bool
    {
        if (empty($rules)) {
            return true; // No rules, continue
        }

        // Parse rules from parameters
        $parsedRules = $this->parseRules($rules);

        if (empty($parsedRules)) {
            return true; // No valid rules, continue
        }

        // Get request data
        $data = Request::all();

        // Create validator
        $validator = Validator::getInstance()->make($data, $parsedRules);

        if ($validator->fails()) {
            // Handle validation failure
            $this->handleValidationFailure($validator);
            return false; // Halt request
        }

        return true; // Continue
    }

    /**
     * Parse validation rules from middleware parameters
     *
     * Format: "name|required,email|required|email"
     * Becomes: ['name' => 'required', 'email' => 'required|email']
     *
     * @param array $rules Rule parameters
     * @return array Parsed rules
     */
    private function parseRules(array $rules): array
    {
        $parsed = [];

        // Join all parameters into one string (in case they were split)
        $rulesString = implode(',', $rules);

        // Split by comma to get individual field rules
        $fieldRules = explode(',', $rulesString);

        foreach ($fieldRules as $fieldRule) {
            // Split by first pipe to separate field from rules
            $parts = explode('|', $fieldRule, 2);

            if (count($parts) >= 2) {
                $field = trim($parts[0]);
                $rule = trim($parts[1]);

                if (!empty($field) && !empty($rule)) {
                    // If field already has rules, append with pipe
                    if (isset($parsed[$field])) {
                        $parsed[$field] .= '|' . $rule;
                    } else {
                        $parsed[$field] = $rule;
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * Handle validation failure
     *
     * @param Validator $validator Validator with errors
     * @return void
     */
    private function handleValidationFailure(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        // Check if request expects JSON response
        if ($this->expectsJson()) {
            Response::validationError($errors);
        } else {
            // Store errors in session for form display
            if (function_exists('session_set')) {
                session_set('validation_errors', $errors);
                session_set('old_input', Request::all());
            }

            // Redirect back
            Response::redirectBack();
        }
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
