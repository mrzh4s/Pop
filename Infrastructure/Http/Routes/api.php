<?php

/**
 * API Routes
 * File: Infrastructure/Http/Routes/api.php
 */

use Framework\Http\Router;

// ============== SYSTEM API ==============
Router::post('/api/system/health', function($action, $params) {
    return json_encode(['success' => false, 'message' => 'System is healthy.']);
}, ['public']);


// ============== AUTHENTICATION API ==============
Router::post('/api/auth/login', 'LoginController@login', ['guest']);
Router::post('/api/auth/register', 'RegisterController@register', ['guest']);
Router::post('/api/auth/logout', 'LoginController@logout', ['auth']);