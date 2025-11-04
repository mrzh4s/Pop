<?php

// ============== TRACKER API ==============
$router->post('/api/tracker/all', function($params) {
    return api('trackers.tracker-all', $params);
}, ['auth']);
$router->get('/api/tracker/all', function($params) {
    return api('trackers.tracker-all', $params);
}, ['auth']);

$router->post('/api/tracker/utility', function($params) {
    return api('trackers.tracker-utility', $params);
}, ['auth']);
$router->get('/api/tracker/utility', function($params) {
    return api('trackers.tracker-utility', $params);
}, ['auth']);

$router->post('/api/tracker', function($params) {
    return api('trackers.tracker', $params);
}, ['auth']);
$router->get('/api/tracker', function($params) {
    return api('trackers.tracker', $params);
}, ['auth']);