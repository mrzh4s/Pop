<?php

// ============== RECORDS API ==============
$router->post('/api/records/entry/authority', function($params) {
    return api('records.entryAuthority', $params);
}, ['auth']);
$router->get('/api/records/entry/authority', function($params) {
    return api('records.entryAuthority', $params);
}, ['auth']);

$router->post('/api/records/entry/{option:string}/{id:id}', function($option, $id, $params) {
    $params['option'] = $option;
    return api('records.entry', $params);
}, ['auth']);
$router->get('/api/records/entry/{option:string}/{id:id}', function($option, $id, $params) {
    $params['option'] = $option;
    return api('records.entry', $params);
}, ['auth']);

$router->post('/api/records/reference/{option:string}', function($option, $params) {
    $params['option'] = $option;
    return api('records.reference', $params);
}, ['auth']);
$router->get('/api/records/reference/{option:string}', function($option, $params) {
    $params['option'] = $option;
    return api('records.reference', $params);
}, ['auth']);