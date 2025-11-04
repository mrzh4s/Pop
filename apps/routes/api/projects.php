<?php

// ============== PROJECTS API ==============
$router->post('/api/projects/uploads', function($params) {
    return api('projects.upload', $params);
}, ['auth']);

$router->post('/api/projects/wayleaves/{action:string}/{sid:token}', function($action, $sid, $params) {
    return api('projects.wayleaves.' . $action, $params);
}, ['auth']);
$router->get('/api/projects/wayleaves/{action:string}/{sid:token}', function($action, $sid, $params) {
    return api('projects.wayleaves.' . $action, $params);
}, ['auth']);

$router->post('/api/projects/wayleaves/{action:alphanum}', function($action, $params) {
    return api('projects.wayleaves.' . $action, $params);
}, ['auth']);
$router->get('/api/projects/wayleaves/{action:alphanum}', function($action, $params) {
    return api('projects.wayleaves.' . $action, $params);
}, ['auth']);

$router->get('/api/projects/pins', function($params) {
    return api('projects.pins', $params);
}, ['auth']);
$router->get('/api/projects/pins/lists', function($params) {
    return api('projects.pins', $params);
}, ['auth']);

$router->post('/api/projects/wayleaves/feedback/{action:string}', function($action, $params) {
    return api('projects.wayleaves.feedback.' . $action, $params);
}, ['auth']);
$router->get('/api/projects/wayleaves/feedback/{action:string}', function($action, $params) {
    return api('projects.wayleaves.feedback.' . $action, $params);
}, ['auth']);

$router->post('/api/projects/team/{action:string}', function($action, $params) {
    return api('projects.team.' . $action, $params);
}, ['auth']);
$router->get('/api/projects/team/{action:string}', function($action, $params) {
    return api('projects.team.' . $action, $params);
}, ['auth']);

$router->post('/api/projects/archieve', function($params) {
    return api('projects.archieve', $params);
}, ['auth']);
$router->get('/api/projects/archieve', function($params) {
    return api('projects.archieve', $params);
}, ['auth']);

$router->post('/api/projects/jumpstep', function($params) {
    return api('projects.jumpstep', $params);
}, ['auth']);
$router->get('/api/projects/jumpstep', function($params) {
    return api('projects.jumpstep', $params);
}, ['auth']);

$router->post('/api/projects/wayleaves/roads/{action:string}/{sid:id}', function($action, $sid, $params) {
    $params['action'] = $action;
    return api('projects.wayleaves.roads', $params);
}, ['auth']);
$router->get('/api/projects/wayleaves/roads/{action:string}/{sid:id}', function($action, $sid, $params) {
    $params['action'] = $action;
    return api('projects.wayleaves.roads', $params);
}, ['auth']);