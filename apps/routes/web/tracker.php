<?php
/**
 * Tracker Routes (Updated)
 * File: routes/tracker.php
 */

// Tracker Overall
$router->get('/tracker/overall/{type:alphanum}', function($type, $params) {
    return view('projects.tracker.' . $type, $params);
}, ['auth']);

// Tracker Attachments List
$router->get('/tracker/attachments/list/{id:id}', function($id, $params) {
    $params['id'] = $id;
    return view('projects.tracker.attachments', $params);
}, ['auth']);