<?php

require_once ROOT_PATH . '/pages/BasePage.php';

class DashboardPage extends BasePage {

    public function show() {

        // Get user data from session
        $userData = [
            'name' => $_SESSION['user_name'] ?? 'Guest User',
            'email' => $_SESSION['user_email'] ?? 'guest@example.com',
        ];

        // Demo stats data
        $stats = [
            'totalSales' => 125000,
            'totalOrders' => 450,
            'customers' => 1250,
        ];

        // Use Inertia to render React component
        return Inertia::render('Dashboard', [
            'user' => $userData,
            'stats' => $stats
        ]);
    }
}
