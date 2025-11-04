<?php
/**
 * Projects Routes (Updated - Complete)
 * File: routes/projects.php
 */

// Project Details
$router->get('/projects/details/{id:id}', function($id, $params) {
    return view('projects.details', $params);
}, ['auth']);

// Public Projects View
$router->get('/projects/view/{type:alphanum}/{sid:id}', function($type, $sid, $params) {
    return view('public.projects.' . $type, $params);
}, ['public']);

// Project Tasks
$router->get('/projects/tasks/{sid:id}', function($sid, $params) {
    return view('projects', $params);
}, ['auth']);

// Project Active Phase
$router->get('/projects/active/{phase:string}', function($phase, $params) {
    $params['phase'] = $phase;
    return view('projects', $params);
}, ['auth']);

// Project Record Authority
$router->get('/projects/record/authority', function($params) {
    return view('projects.records.entryAuthority', $params);
}, ['auth']);

// Project Applications
$router->get('/projects/{type:string}/{subtype:string}/{id:id}', function($type, $subtype, $id, $params) {
    $params['type'] = $subtype;
    $params['id'] = $id;
    return view('projects.' . $type . '.applications', $params);
}, ['auth']);

// Project Prints
$router->get('/projects/prints/{option:[A-Z]+}/{sid:id}', function($option, $sid, $params) {
    return view('projects.prints.' . $option, $params);
}, ['auth']);

// Project Site TP Amend
$router->get('/projects/site/tp/amend/{sid:id}', function($sid, $params) {
    return view('projects.site.tpAmend', $params);
}, ['auth']);

// Project Site TP Amend Review
$router->get('/projects/site/tp/amend/review/{sid:id}', function($sid, $params) {
    return view('projects.site.tpAmendReview', $params);
}, ['auth']);

// Wayleave Summary
$router->get('/wayleave/summary/{action:string}', function($action, $params) {
    return view('projects.wayleave.summary.' . $action, $params);
}, ['auth']);

// Wayleave Deposit
$router->get('/wayleave/deposit/{action:string}', function($action, $params) {
    return view('projects.wayleave.deposit.' . $action, $params);
}, ['auth']);

// Permits
$router->get('/permit/checklist/{sid:id}', function($sid, $params) {
    return view('projects.permit.checklist', $params);
}, ['auth']);

// Generic Projects Route (must be last)
$router->get('/projects/{action:string}', function($action, $params) {
    return view('projects.' . $action, $params);
}, ['auth']);