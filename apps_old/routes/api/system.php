<?php

// ============== ACCOUNT API ==============
$router->post('/api/activity/log', function($params) {
    return api('system.log', $params);
}, ['public']);

$router->get('/api/health', function($params) {
    return api('system.health', $params);
}, ['public']);

$router->get('/api/csrf-token', function($params) {
    return api('system.csrf-token', $params);
}, ['public']);