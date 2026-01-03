<?php

namespace Infrastructure\Http\Controllers;

/**
 * Welcome Page Controller
 * Displays the landing page for non-authenticated users
 */
class WelcomeController
{
    public function show()
    {
        return view('welcome');
    }
}
