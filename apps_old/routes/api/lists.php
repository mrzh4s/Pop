<?php


// ============== LISTS API ==============
$router->get('/api/status/lists', function($params) {
    return api('lists.status', $params);
}, ['auth']);
$router->post('/api/lists/{type:alphanum}', function($type, $params) {
    return api('lists.' . $type, $params);
}, ['auth']);
$router->get('/api/lists/{type:alphanum}', function($type, $params) {
    return api('lists.' . $type, $params);
}, ['auth']);

$router->post('/api/status/lists/{type:string}', function($type, $params) {
    return api('lists.' . $type, $params);
}, ['auth']);
$router->get('/api/status/lists/{type:string}', function($type, $params) {
    return api('lists.' . $type, $params);
}, ['auth']);

$router->get('/api/postcode/{postcode:number}', function($postcode, $params) {
    return api('lists.postcode', $params);
}, ['auth']);