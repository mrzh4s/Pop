<?php

// ============== AUTHENTICATION API ==============
$router->post('/api/auth/login', function($params) {
    return api('auth.login', $params);
}, ['guest']);

$router->post('/api/auth/logout', function($params) {
    return api('auth.logout', $params);
}, ['auth']);

$router->get('/api/auth/register', function($action, $params) {
    return api('auth.register', $params);
}, ['guest']);

$router->post('/api/auth/verify', function($params) {
    return api('auth.verification', $params);
}, ['guest']);

$router->post('/api/auth/session/trust', function($params) {
    return api('auth.trust', $params);
}, ['guest']);
