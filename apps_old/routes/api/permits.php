<?php


// ============== PERMITS API ==============
$router->post('/api/projects/permits/checklist/{sid:token}', function($sid, $params) {
    return api('projects.permits.checklist', $params);
}, ['auth']);
$router->get('/api/projects/permits/checklist/{sid:token}', function($sid, $params) {
    return api('projects.permits.checklist', $params);
}, ['auth']);

$router->post('/api/projects/permits/expiry', function($params) {
    return api('projects.permits.expiryCheck', $params);
}, ['auth']);
$router->get('/api/projects/permits/expiry', function($params) {
    return api('projects.permits.expiryCheck', $params);
}, ['auth']);