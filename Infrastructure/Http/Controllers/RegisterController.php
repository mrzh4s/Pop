<?php

namespace Infrastructure\Http\Controllers;

/**
 * Register Page Controller
 * Displays the registration form using Inertia
 */
class RegisterController
{
    public function show()
    {
        return inertia('Register');
    }
}
