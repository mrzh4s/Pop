<?php

// ============== CALENDAR API ==============
$router->post('/api/calendar/{item:alphanum}/{sid:slug}', function($item, $sid, $params) {
    $params['item'] = $item;
    return api('calendar.index', $params);
}, ['auth']);
$router->get('/api/calendar/{item:alphanum}/{sid:slug}', function($item, $sid, $params) {
    $params['item'] = $item;
    return api('calendar.index', $params);
}, ['auth']);

$router->post('/api/calendar/{item:alphanum}', function($item, $params) {
    $params['item'] = $item;
    return api('calendar.index', $params);
}, ['auth']);
$router->get('/api/calendar/{item:alphanum}', function($item, $params) {
    $params['item'] = $item;
    return api('calendar.index', $params);
}, ['auth']);

$router->post('/api/calendar', function($params) {
    return api('calendar.index', $params);
}, ['auth']);
$router->get('/api/calendar', function($params) {
    return api('calendar.index', $params);
}, ['auth']);