<?php

// ============== LETTERS API ==============
$router->post('/api/letters/{action:string}', function($action, $params) {
    return api('letters.' . $action, $params);
}, ['auth']);
$router->get('/api/letters/{action:string}', function($action, $params) {
    return api('letters.' . $action, $params);
}, ['auth']);


// ============== LETTER IN API ==============
$router->post('/api/letter/in/{action:string}', function($action, $params) {
    return api('letterIn.' . $action, $params);
}, ['auth']);
$router->get('/api/letter/in/{action:string}', function($action, $params) {
    return api('letterIn.' . $action, $params);
}, ['auth']);
