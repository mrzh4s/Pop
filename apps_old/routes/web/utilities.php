<?php
/**
 * Utilities Routes (Attachments, QR Codes, etc.) (Updated)
 * File: routes/utilities.php
 */

// FSlightBox - File Attachments Viewer
$router->get('/attachments/view', function($params) {
    return view('fslightbox', $params);
}, ['auth']);

// QR-Code Attendance
$router->get('/attendance/{type:string}/{sid:id}', function($type, $sid, $params) {
    $params['sid'] = $sid;
    return view('apps.attendance.' . $type, $params);
}, ['auth']);

// QR-Code Plan
$router->get('/qrcode/{type:string}/{token:token}', function($type, $token, $params) {
    $params['token'] = $token;
    return view('apps.' . $type, $params);
}, ['auth']);

// QR-Code Download
$router->get('/qrcode/download/{type:string}/{sid:id}', function($type, $sid, $params) {
    return view('surveys.qrcode.download.' . $type, $params);
}, ['auth']);