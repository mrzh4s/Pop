<?php

// ============== TELEGRAM API ==============
$router->post('/api/notification/{action:string}', function($action, $params) {
    return api('telegram.' . $action, $params);
}, ['auth']);
$router->get('/api/notification/{action:string}', function($action, $params) {
    return api('telegram.' . $action, $params);
}, ['auth']);