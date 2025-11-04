<?php
/**
 * Dashboard Routes (Updated)
 * File: routes/dashboard.php
 */

// Main dashboard
$router->get('/admin/dashboard', function($params) {
    return view('dashboard.admin', $params);
}, ['auth'])->name('admin.dashboard');

// Details Widget
$router->get('/details/{type:string}/{department:string}', function($type, $department, $params) {
    $params['type'] = $type;
    $params['department'] = $department;
    return view('details.' . $type, $params);
}, ['auth']);