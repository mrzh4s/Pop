<?php
/**
 * Account Routes (Updated)
 * File: routes/account.php
 */

// Account Actions (Profile, Settings, etc.) with named routes
$router->get('/account/{action:string}', function($action, $params) {
    return view('account.' . $action, $params);
}, ['auth'])->name('account.show');

// Specific account routes with individual names
$router->get('/account/profile', function($params) {
    return view('account.profile', $params);
}, ['auth'])->name('account.profile');

$router->get('/account/settings', function($params) {
    return view('account.settings', $params);
}, ['auth'])->name('account.settings');