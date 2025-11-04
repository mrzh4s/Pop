<?php

// ============== QRCODE API ==============
$router->post('/api/qrcode/download/{type:alphanum}/{sid:id}', function($type, $sid, $params) {
    return api('survey.' . $type, $params);
}, ['auth']);
$router->get('/api/qrcode/download/{type:alphanum}/{sid:id}', function($type, $sid, $params) {
    return api('survey.' . $type, $params);
}, ['auth']);