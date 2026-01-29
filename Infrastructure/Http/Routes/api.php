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