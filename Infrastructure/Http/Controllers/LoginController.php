<?php

namespace Infrastructure\Http\Controllers;

/**
 * Login Page Controller
 * Displays the login form using Inertia
 */
class LoginController
{
    public function show()
    {
        return inertia('Login');
    }
}
