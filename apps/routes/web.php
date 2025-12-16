<?php

// ============== ROOT ROUTES ==============
$router->get('/', function() {

    if (session('authenticated')) {
        header("Location: dashboard", true, 302);
    } else {
        redirect('auth.signin');
    }

})->name('root');


// Load authentication routes
$router->get('/auth/signin', 'LoginPage@show', ['guest'])
    ->name('auth.signin');

// You can also add register page
$router->get('/auth/register', 'RegisterPage@show', ['guest'])
    ->name('auth.register');


// Load home routes (public for testing)
$router->get('/dashboard', 'DashboardPage@show', ['public'])->name('dashboard');

// Map page (public for testing)
$router->get('/map', 'MapPage@show', ['public'])->name('map');


// Load Error routes
// 404 Error Page
$router->get('/error/404', function($params) {
    return view('error.404', $params);
}, ['public']);

// 505 Error Page
$router->get('/error/505', function($params) {
    return view('error.505', $params);
}, ['public']);