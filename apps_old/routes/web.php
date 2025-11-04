<?php

// ============== ROOT ROUTES ==============
$router->get('/', function() {

    if (session_has('user.id') || is_cookie_authenticated()) {
        header("Location: ".session('user.intended_url') ?? 'dashboard', true, 302);
    } else {
        redirect('auth.signin');
    }

})->name('home');


// Load authentication routes
require_once 'web/auth.php';

// Load dashboard routes
require_once 'web/dashboard.php';

// Load project routes
require_once 'web/projects.php';

// Load geospatial routes
require_once 'web/geospatial.php';

// Load survey routes
require_once 'web/surveys.php';

// Load letter routes
require_once 'web/letters.php';

// Load calendar routes
require_once 'web/calendar.php';

// Load task routes
require_once 'web/tasks.php';

// Load report routes
require_once 'web/reports.php';

// Load tracker routes
require_once 'web/tracker.php';

// Load account routes
require_once 'web/account.php';

// Load utility routes (attachments, QR codes, etc.)
require_once 'web/utilities.php';

// Load error routes
require_once 'web/errors.php';