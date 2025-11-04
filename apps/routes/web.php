<?php

// ============== ROOT ROUTES ==============
$router->get('/', function() {

    if (session('authenticated')) {
        header("Location: home", true, 302);
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


// Load home routes
$router->get('/home', 'HomePage@show', ['auth'])->name('home');

// Load Applications routes
$router->get('/applications/create', 'application/CreatePage@show', ['auth'])->name('applications.create');
$router->get('/applications/migrate', 'application/MigratePage@show', ['auth'])->name('applications.migrate');
$router->get('/applications/migrate/new/{id:number}', 'application/migrate/NewPage@show', ['auth'])->name('applications.migrate.new');


// Load Error routes
// 404 Error Page
$router->get('/error/404', function($params) {
    return view('error.404', $params);
}, ['public']);

// 505 Error Page
$router->get('/error/505', function($params) {
    return view('error.505', $params);
}, ['public']);