<?php
/**
 * All API Routes (Updated - No String Shortcuts)
 * File: routes/api.php
 */

// ============== SYSTEM API ==============
$router->post('/api/system/health', function($action, $params) {
    return json_encode(['success' => false, 'message' => 'System is healthy.']);
}, ['public']);


$router->post('/api/auth/login', 'LoginPage@login', ['guest']);

// Logout API - destroy session
$router->post('/api/auth/logout', 'LoginPage@logout', ['auth']);