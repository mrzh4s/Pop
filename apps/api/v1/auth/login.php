<?php

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

try {
    
    // CSRF Protection for POST requests
    if (request_method() === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            json([
                'message' => 'CSRF token mismatch',
            ], 403);
            exit;
        }
    }
    
    // Rate limiting check (using AuthService built-in protection)
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Get and validate input
    $email = request('email');
    $password = request('password');
    $remember = request('remember', false);

    if (!$email || !$password) {
        json([
            'message' => 'Please provide email and password',
        ], 400);
        exit;
    }

    // Use AuthService for authentication
    $authResult = auth_login($email, $password, $remember);

    if (!$authResult['success']) {
        // Check if blocked
        $message = isset($authResult['blocked']) && $authResult['blocked'] 
            ? $authResult['message'] 
            : 'Invalid credentials';
            
        $statusCode = isset($authResult['blocked']) && $authResult['blocked'] ? 429 : 401;

        log_user_activity("Failed login attempt for email: $email", $authResult['user_id']);
        
        // Random delay to prevent timing attacks
        usleep(rand(100000, 300000));
        
        json([
            'message' => $message,
        ], $statusCode);
    }
    
    $user = $authResult['user'];
    
    // Check if user account is active
    if (isset($user['is_active']) && !$user['is_active']) {
        handleInactiveAccount($user);
        exit;
    }
    
    // Check if email verification is required (optional)
    if (isset($user['email_verified_at']) && empty($user['email_verified_at'])) {
        handleEmailVerificationRequired($user);
        exit;
    }
    
    // Handle successful login
    handleSuccessfulLogin($user, $remember);

} catch (Exception $e) {
    error_log("Login API error: " . $e->getMessage());
    http_response_code(500);
    json([
        'message' => 'Internal server error',
    ], 500);
}

/**
 * Handle successful login with enhanced session management
 */
function handleSuccessfulLogin($user, $remember = false) {
    try {

        // Determine redirect URL
        $redirectUrl = determineRedirectUrl($user);
        
        // Get CSRF token for response   
        $csrfToken = function_exists('csrf_token') ? csrf_token() : null; 

        // Get current session info for response
        $sessionInfo = session_info();

        // Store additional user data in session for compatibility
        session_set('user.id',  $user['id']);
        session_set('user.email',  $user['email']);
        session_set('user.name', $user['name'] ?? '');
        session_set('user.phone', $user['phone'] ?? '');
        session_set('user.telegram_id', $user['telegram_id'] ?? '');
        
        // Store roles and groups
        session_set('user.roles', $user['roles'] ?? []);
        session_set('user.groups', $user['groups'] ?? []);
        
        // Security markers
        session_set('user.login.ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        session_set('user.intended_url', $redirectUrl);
        
        // CSRF token
        session_set('security.csrf_token', $csrfToken);

        session_set('security.user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
        
        // Set authentication cookies for backend access (if function exists)
        $cookieExpiry = $remember ? time() + (30 * 24 * 60 * 60) : time() + (24 * 60 * 60);
        
        $cookieUserData = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'roles' => $user['roles'] ?? [],
            'groups' => $user['groups'] ?? [],
            'session_id' => session_id()
        ];
        
        set_auth_cookies($cookieUserData, $cookieExpiry);
        
        log_user_activity("Email: {$user['email']} logged in successfully");
        
        // Return success response
        json([
            'message' => 'success',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'roles' => array_column($user['roles'] ?? [], 'name'),
                    'groups' => array_column($user['groups'] ?? [], 'name'),
                    'avatar' => function_exists('user_avatar') ? user_avatar($user['id']) : '/assets/images/default-avatar.png',
                ],
                'session' => [
                    'id' => session_id(),
                    'device_info' => [
                        'type' => $sessionInfo['device_type'] ?? 'unknown',
                        'name' => $sessionInfo['device_name'] ?? 'Unknown Device',
                        'platform' => $sessionInfo['platform'] ?? 'Unknown OS',
                        'browser' => $sessionInfo['browser'] ?? 'Unknown Browser',
                        'ip_address' => $sessionInfo['ip_address'] ?? 'unknown',
                        'is_trusted' => $sessionInfo['is_trusted'] ?? false
                    ],
                    'active_sessions_count' => count_active_sessions()
                ],
                'csrf_token' => $csrfToken,
                'redirect' => $redirectUrl
            ]
        ], 200);
        
    } catch (Exception $e) {
        error_log("Handle successful login error: " . $e->getMessage());
        json([
            'status' => 'Server Error',
            'message' => 'Login successful but session setup failed',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], 500);
    }
}

/**
 * Handle inactive account
 */
function handleInactiveAccount($user) {
    json([
        'status' => 'Client Error',
        'message' => 'Your account has been deactivated. Please contact support.',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_time' => time(),
        'data' => [
            'account_status' => 'inactive',
            'support_contact' => 'support@yourcompany.com' // Update with your support contact
        ]
    ], 403);
}

/**
 * Handle email verification required
 */
function handleEmailVerificationRequired($user) {
    json([
        'status' => 'Client Error',
        'message' => 'Please verify your email address before logging in.',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_time' => time(),
        'data' => [
            'verification_required' => true,
            'user_id' => $user['id'],
            'email' => maskEmail($user['email'])
        ]
    ], 403);
}

/**
 * Determine redirect URL based on user role
 */
function determineRedirectUrl($user) {
    // Check for admin roles first
    $groups = ['client', 'admin', 'vendor', 'authority', 'board'];
    $userGroups = array_column($user['groups'] ?? [], 'name');
    
    foreach ($userGroups as $group) {
        if (in_array($group, $groups)) {
            return $group.'/dashboard';
        }
    }
    
    // Default redirect
    return '/dashboard';
}

/**
 * Mask email for security (show first char and domain)
 */
function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    
    $username = $parts[0];
    $domain = $parts[1];
    
    $maskedUsername = substr($username, 0, 1) . str_repeat('*', strlen($username) - 1);
    return $maskedUsername . '@' . $domain;
}