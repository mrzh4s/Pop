<?php
try {
    secure_session();
    $forceRefresh = request('refresh', false);
    
    if ($forceRefresh || session_has('csrf_token')) {
        session_remove('csrf_token');
        session_remove('csrf_token_time');

    }
    
    $token = csrf_token();
    $expiresAt = (session('csrf_token_time') ?? time()) + 7200;
    
    json(['data' => [
        'csrf_token' => $token,
        'expires_at' => $expiresAt,
        'expires_in' => $expiresAt - time(),
        'session_id' => session_id()
    ]], 200);
    
} catch (Exception $e) {
    error_log("CSRF token API error: " . $e->getMessage());
    json(['message' => 'Failed to generate CSRF token'], 500);
}