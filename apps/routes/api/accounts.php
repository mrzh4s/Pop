<?php

// ============== ACCOUNT API ==============
$router->post('/api/account/{action:string}', function($action, $params) {
    return api('settings.' . $action, $params);
}, ['auth']);
$router->get('/api/account/{action:string}', function($action, $params) {
    return api('settings.' . $action, $params);
}, ['auth']);