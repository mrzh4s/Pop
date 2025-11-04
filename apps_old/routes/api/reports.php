<?php

// ============== REPORTS API ==============
$router->post('/api/reports/geometry/{type:alphanum}', function($type, $params) {
    return api('reports.geom.' . $type, $params);
}, ['auth']);
$router->get('/api/reports/geometry/{type:alphanum}', function($type, $params) {
    return api('reports.geom.' . $type, $params);
}, ['auth']);

$router->post('/api/reports/summary/{type:string}', function($type, $params) {
    return api('reports.summary.' . $type, $params);
}, ['auth']);
$router->get('/api/reports/summary/{type:string}', function($type, $params) {
    return api('reports.summary.' . $type, $params);
}, ['auth']);

$router->post('/api/reports/{id:slug}', function($id, $params) {
    return api('reports.index', $params);
}, ['auth']);
$router->get('/api/reports/{id:slug}', function($id, $params) {
    return api('reports.index', $params);
}, ['auth']);

$router->post('/api/reports/site/amend/review/{sid:slug}', function($sid, $params) {
    return api('projects.sites.approvalAmendReview', $params);
}, ['auth']);
$router->get('/api/reports/site/amend/review/{sid:slug}', function($sid, $params) {
    return api('projects.sites.approvalAmendReview', $params);
}, ['auth']);

$router->post('/api/reports/tp/amend/{option:alphanum}/{sid:id}', function($option, $sid, $params) {
    $params['option'] = $option;
    return api('reports.tpAmend', $params);
}, ['auth']);
$router->get('/api/reports/tp/amend/{option:alphanum}/{sid:id}', function($option, $sid, $params) {
    $params['option'] = $option;
    return api('reports.tpAmend', $params);
}, ['auth']);