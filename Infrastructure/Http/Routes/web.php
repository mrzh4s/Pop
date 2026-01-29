<?php

/**
 * Web Routes
 * File: Infrastructure/Http/Routes/web.php
 */

use Framework\Http\Router;

// ============== ROOT ROUTES ==============
Router::get('/', 'WelcomeController@show', ['public'])->name('root');


// ============== DASHBOARD ROUTES ==============
Router::get('/dashboard', 'DashboardController@show', ['auth'])->name('dashboard');


// Load Error routes
// 404 Error Page
Router::get('/error/404', function($params) {
    return view('error.404', $params);
}, ['public']);

// 505 Error Page
Router::get('/error/505', function($params) {
    return view('error.505', $params);
}, ['public']);