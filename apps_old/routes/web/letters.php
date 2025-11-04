<?php
/**
 * Letters Routes (Updated)
 * File: routes/letters.php
 */

// Letters with Type, SID and LID
$router->get('/letters/{type:alphanum}/{sid:token}/{lid:number}', function($type, $sid, $lid, $params) {
    return view('letters.' . $type, $params);
}, ['auth']);

// Letter In with Type and LID
$router->get('/letter/in/{type:string}/{lid:[A-Z0-9]+}', function($type, $lid, $params) {
    return view('letterIn.' . $type, $params);
}, ['auth']);

// Letter In with Type Only
$router->get('/letter/in/{type:string}', function($type, $params) {
    return view('letterIn.' . $type, $params);
}, ['auth']);

// Generic Letters Route (must be last)
$router->get('/letters/{type:alphanum}', function($type, $params) {
    return view('letters.' . $type, $params);
}, ['auth']);