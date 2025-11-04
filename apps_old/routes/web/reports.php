<?php
/**
 * Reports Routes (Updated)
 * File: routes/reports.php
 */

// Report Site Task
$router->get('/reports/site/task/{sid:slug}', function($sid, $params) {
    $params['sid'] = $sid;
    return view('projects.site.list', $params);
}, ['auth']);

// Report Site Summary
$router->get('/reports/site/summary/{sid:slug}', function($sid, $params) {
    $params['sid'] = $sid;
    return view('projects.site.summary', $params);
}, ['auth']);

// Report Site Review
$router->get('/reports/site/review/{sid:slug}', function($sid, $params) {
    $params['sid'] = $sid;
    return view('projects.site.review', $params);
}, ['auth']);

// Report Site Amend Review
$router->get('/reports/site/amend/review/{sid:slug}', function($sid, $params) {
    $params['sid'] = $sid;
    return view('projects.site.amendReview', $params);
}, ['auth']);

// Report Site View
$router->get('/reports/site/view/{sid:slug}', function($sid, $params) {
    $params['sid'] = $sid;
    return view('projects.site.view', $params);
}, ['auth']);

// Report Site Generic
$router->get('/reports/site/{sid:slug}', function($sid, $params) {
    $params['sid'] = $sid;
    return view('projects.site.report', $params);
}, ['auth']);