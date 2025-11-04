<?php
/**
 * Authentication Routes
 * File: routes/auth.php
 */

// Authentication pages (public access)
$router->get('/auth/signin', function($params) {
    return view('auth.signin');
}, ['public'])->name('auth.signin');

$router->get('/auth/signup', function($params) {
    return view('auth.signup');
}, ['public'])->name('auth.signup');

$router->get('/auth/signout', function($params) {
    return view('auth.signout');
}, ['public'])->name('auth.signout');

$router->get('/auth/reset', function($params) {
    return view('auth.reset');
}, ['public'])->name('auth.reset');
