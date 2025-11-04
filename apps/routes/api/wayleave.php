<?php

// ============== WAYLEAVE SUMMARY API ==============
$router->post('/api/wayleave/summary/{action:alphanum}', function($action, $params) {
    return api('projects.wayleaves.summary.' . $action, $params);
}, ['auth']);
$router->get('/api/wayleave/summary/{action:alphanum}', function($action, $params) {
    return api('projects.wayleaves.summary.' . $action, $params);
}, ['auth']);

$router->post('/api/wayleave/summary/{action:alphanum}/{sid:token}', function($action, $sid, $params) {
    return api('projects.wayleaves.summary.' . $action, $params);
}, ['auth']);
$router->get('/api/wayleave/summary/{action:alphanum}/{sid:token}', function($action, $sid, $params) {
    return api('projects.wayleaves.summary.' . $action, $params);
}, ['auth']);

// ============== WAYLEAVE DEPOSIT API ==============
$router->post('/api/wayleave/deposit/{action:alphanum}', function($action, $params) {
    return api('projects.wayleaves.deposit.' . $action, $params);
}, ['auth']);
$router->get('/api/wayleave/deposit/{action:alphanum}', function($action, $params) {
    return api('projects.wayleaves.deposit.' . $action, $params);
}, ['auth']);

$router->post('/api/wayleave/deposit/{action:alphanum}/{sid:token}', function($action, $sid, $params) {
    return api('projects.wayleaves.deposit.' . $action, $params);
}, ['auth']);
$router->get('/api/wayleave/deposit/{action:alphanum}/{sid:token}', function($action, $sid, $params) {
    return api('projects.wayleaves.deposit.' . $action, $params);
}, ['auth']);