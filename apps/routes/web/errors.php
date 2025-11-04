<?php
/**
 * Error Pages Routes (Updated)
 * File: routes/errors.php
 */

// 404 Error Page
$router->get('/error/404', function($params) {
    return view('error.404', $params);
}, ['public']);

// 505 Error Page
$router->get('/error/505', function($params) {
    return view('error.505', $params);
}, ['public']);