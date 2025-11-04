<?php

// ============== TASKS API ==============
$router->post('/api/tasks/finance/{action:alphanum}', function($action, $params) {
    return api('tasks.finance.' . $action, $params);
}, ['auth']);

$router->get('/api/tasks/finance/{action:alphanum}', function($action, $params) {
    return api('tasks.finance.' . $action, $params);
}, ['auth']);

$router->post('/api/tasks/{status:number}/{action:string}', function($status, $action, $params) {
    return api('tasks.' . $action, $params);
}, ['auth']);

$router->get('/api/tasks/{status:number}/{action:string}', function($status, $action, $params) {
    return api('tasks.' . $action, $params);
}, ['auth']);

$router->post('/api/tasks/{action:string}', function($action, $params) {
    return api('tasks.' . $action, $params);
}, ['auth']);

$router->get('/api/tasks/{action:string}', function($action, $params) {
    return api('tasks.' . $action, $params);
}, ['auth']);