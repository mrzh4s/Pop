<?php
/**
 * Surveys Routes (Updated)
 * File: routes/surveys.php
 */

// Survey Site with Type and SID
$router->get('/surveys/site/{type:alphanum}/{sid:id}', function($type, $sid, $params) {
    $params['sid'] = $sid;
    return view('surveys.site.' . $type, $params);
}, ['auth']);

// Survey with Type, SID and Project
$router->get('/surveys/{type:string}/{sid:id}/{proj:alphanum}', function($type, $sid, $proj, $params) {
    $params['sid'] = $sid;
    $params['proj'] = $proj;
    return view('surveys.' . $type, $params);
}, ['auth']);

// Survey Priority Actions
$router->get('/surveys/priority/{action:string}', function($action, $params) {
    return view('surveys.priority.' . $action, $params);
}, ['auth']);

// Survey Tasks Actions
$router->get('/surveys/tasks/{action:string}', function($action, $params) {
    return view('surveys.tasks.' . $action, $params);
}, ['auth']);

// Sharing (Public)
$router->get('/sharing/{uuid:uuid}', function($uuid, $params) {
    $params['uuid'] = $uuid;
    return view('surveys.sharing', $params);
}, ['public']);

// Generic Survey Actions (must be last)
$router->get('/surveys/{action:string}', function($action, $params) {
    return view('surveys.' . $action, $params);
}, ['auth']);