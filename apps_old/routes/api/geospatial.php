<?php

// ============== GEOSPATIAL API ==============
$router->post('/api/geospatial/{action:string}', function($action, $params) {
    return api('geospatial.' . $action, $params);
}, ['auth']);

$router->get('/api/geospatial/{action:string}', function($action, $params) {
    return api('geospatial.' . $action, $params);
}, ['auth']);