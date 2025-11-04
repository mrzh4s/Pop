<?php

// ============== NOTES API ==============
$router->post('/api/notes/authority/{sysId:id}', function($sysId, $params) {
    $params['sysId'] = $sysId;
    return api('notes.add', $params);
}, ['auth']);
$router->get('/api/notes/authority/{sysId:id}', function($sysId, $params) {
    $params['sysId'] = $sysId;
    return api('notes.add', $params);
}, ['auth']);

$router->post('/api/notes/{type:alphanum}', function($type, $params) {
    $params['type'] = $type;
    return api('notes.add', $params);
}, ['auth']);
$router->get('/api/notes/{type:alphanum}', function($type, $params) {
    $params['type'] = $type;
    return api('notes.add', $params);
}, ['auth']);