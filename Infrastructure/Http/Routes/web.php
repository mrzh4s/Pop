<?php

/**
 * Web Routes
 * File: Infrastructure/Http/Routes/web.php
 */

use Framework\Http\Router;

// ============== ROOT ROUTES ==============
Router::get('/', 'WelcomeController@show', ['public'])->name('root');


// ============== AUTHENTICATION ROUTES ==============
Router::get('/auth/signin', 'LoginController@show', ['guest'])
    ->name('auth.signin');

Router::get('/auth/register', 'RegisterController@show', ['guest'])
    ->name('auth.register');


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