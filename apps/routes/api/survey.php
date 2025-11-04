<?php

// ============== SURVEYS API ==============
$router->post('/api/surveys/team/{action:string}', function($action, $params) {
    return api('survey.team.' . $action, $params);
}, ['auth']);
$router->get('/api/surveys/team/{action:string}', function($action, $params) {
    return api('survey.team.' . $action, $params);
}, ['auth']);

$router->post('/api/surveys/priority/{action:string}', function($action, $params) {
    return api('survey.priority.' . $action, $params);
}, ['auth']);
$router->get('/api/surveys/priority/{action:string}', function($action, $params) {
    return api('survey.priority.' . $action, $params);
}, ['auth']);

$router->post('/api/surveys/tasks/{action:string}', function($action, $params) {
    return api('survey.tasks.' . $action, $params);
}, ['auth']);
$router->get('/api/surveys/tasks/{action:string}', function($action, $params) {
    return api('survey.tasks.' . $action, $params);
}, ['auth']);

$router->post('/api/surveys/{action:string}', function($action, $params) {
    return api('survey.' . $action, $params);
}, ['auth']);
$router->get('/api/surveys/{action:string}', function($action, $params) {
    return api('survey.' . $action, $params);
}, ['auth']);