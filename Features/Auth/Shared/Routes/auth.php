<?php
namespace Features\Auth\Shared\Routes;
use Framework\Http\Router;


// ============== AUTHENTICATION ROUTES ==============
Router::get('/auth/signin', 'LoginController@show', ['guest'])->name('auth.signin');
Router::get('/auth/register', 'RegisterController@show', ['guest'])->name('auth.register');

// ============== AUTHENTICATION API ==============
Router::post('/api/auth/login', 'LoginController@login', ['guest']);
Router::post('/api/auth/register', 'RegisterController@register', ['guest']);
Router::post('/api/auth/logout', 'LoginController@logout', ['auth']);