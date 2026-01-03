<?php

namespace Framework\Http;

class Response {
    
    protected $data = [];
    
    /**
     * Set security headers for API responses
     */
    protected function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Content-Type: application/json');
    }
    
    /**
     * Render view with data
     */
    protected function view($viewName, $data = []) {
        $this->data = array_merge($this->data, $data);
        return view($viewName, $this->data);
    }
    
    /**
     * Return JSON success response
     */
    protected function jsonSuccess($message, $data = [], $code = 200) {
        $this->setSecurityHeaders();
        json([
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    /**
     * Return JSON error response
     */
    protected function jsonError($message, $code = 400) {
        $this->setSecurityHeaders();
        json(['message' => $message], $code);
    }
    
    /**
     * Validate CSRF for POST requests
     */
    protected function verifyCsrf() {
        if (request_method() === 'POST' && !csrf_verify()) {
            $this->jsonError('CSRF token mismatch', 403);
            exit;
        }
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($fields) {
        foreach ($fields as $field) {
            if (empty(request($field))) {
                $this->jsonError(ucfirst($field) . ' is required', 400);
                exit;
            }
        }
    }
    
    /**
     * Check if request wants JSON response
     */
    protected function wantsJson() {
        // Check Accept header
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }
        
        // Check if URL starts with /api/
        if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            return true;
        }
        
        // Check for AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        
        return false;
    }
}
