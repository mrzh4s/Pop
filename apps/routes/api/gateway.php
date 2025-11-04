<?php

// ============== GATEWAY API ==============
$router->post('/gateway/{type:string}', function($type, $params) {
    return api('gateways.' . $type, $params);
}, ['public']);
$router->get('/gateway/{type:string}', function($type, $params) {
    return api('gateways.' . $type, $params);
}, ['public']);

$router->post('/gateway/{type:string}/{action:string}', function($type, $action, $params) {
    return api('gateways.' . $type . '.' . $action, $params);
}, ['public']);
$router->get('/gateway/{type:string}/{action:string}', function($type, $action, $params) {
    return api('gateways.' . $type . '.' . $action, $params);
}, ['public']);

$router->post('/gateway/{type:string}/{action:alphanum}/{subId:id}', function($type, $action, $subId, $params) {
    $params['subId'] = $subId;
    return api('gateways.' . $type . '.' . $action, $params);
}, ['public']);
$router->get('/gateway/{type:string}/{action:alphanum}/{subId:id}', function($type, $action, $subId, $params) {
    $params['subId'] = $subId;
    return api('gateways.' . $type . '.' . $action, $params);
}, ['public']);

$router->post('/gateway/projects/details', function($params) {
    return api('gateways.projects.details', $params);
}, ['public']);
$router->get('/gateway/projects/details', function($params) {
    return api('gateways.projects.details', $params);
}, ['public']);