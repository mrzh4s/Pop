<?php
/**
 * Calendar Routes (Updated - Complete)
 * File: routes/calendar.php
 */

// Calendar with SID and RT
$router->get('/calendar/{sid:alphanum}/{rt:number}', function($sid, $rt, $params) {
    $params['sid'] = $sid;
    $params['rt'] = $rt;
    return view('apps.calendar.index', $params);
}, ['auth']);

// Calendar Main Page
$router->get('/calendar', function($params) {
    return view('apps.calendar.index', $params);
}, ['auth']);