<?php
try {

    secure_session();
    
    $email = session('user.email') ?? null;
    
    // Log logout using your existing helper
    if ($email && function_exists('log_user_activity')) {
        log_user_activity("Email: $email logged out successfully");
    }
    
    // Clear session
    session_clear();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    json([
        'status' => 'Success',
        'message' => 'Logged out successfully',
        'success' => true,
        'redirect' => '/auth/signin'
    ], 200);
    
} catch (Exception $e) {
    error_log("Logout API error: " . $e->getMessage());
    json(['message' => 'Logout completed'], 200);
}
