<?php
/**
 * Email Verification Controller
 * File: apps/api/v1/auth/VerificationController.php
 * 
 * Handles email verification for account activation
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

try {
    // Start secure session
    secure_session();
    
    // CSRF Protection
    if (request_method() === 'POST') {
        if (function_exists('csrf_verify') && !csrf_verify()) {
            http_response_code(403);
            json([
                'status' => 'Client Error',
                'message' => 'CSRF token mismatch',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_time' => time()
            ], 403);
            exit;
        }
    }

    // Determine action based on request
    $action = request('action', 'verify'); // 'verify' or 'resend'
    
    if ($action === 'resend') {
        handleResendVerification();
    } else {
        handleVerifyCode();
    }

} catch (Exception $e) {
    error_log("Verification Controller error: " . $e->getMessage());
    http_response_code(500);
    json([
        'status' => 'Server Error',
        'message' => 'Verification processing failed',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_time' => time()
    ], 500);
}

/**
 * Handle verification code submission
 */
function handleVerifyCode() {
    // Get and validate input
    $userId = request('user_id');
    $code = request('code');

    if (!$userId || !$code) {
        json([
            'status' => 'Client Error',
            'message' => 'User ID and verification code are required',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], 400);
        exit;
    }

    // Sanitize code (6 digits only)
    $code = preg_replace('/[^0-9]/', '', $code);
    if (strlen($code) !== 6) {
        json([
            'status' => 'Client Error',
            'message' => 'Invalid verification code format',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], 400);
        exit;
    }

    // Get user data
    $user = getUserById($userId);
    if (!$user) {
        json([
            'status' => 'Client Error',
            'message' => 'User not found',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], 404);
        exit;
    }

    // Verify the code using UserService
    $userService = UserService::getInstance();
    $isValid = $userService->verifyCode($userId, $code);

    if (!$isValid) {
        // Record failed attempt
        recordFailedVerification($userId, $code);
        
        json([
            'status' => 'Client Error',
            'message' => 'Invalid or expired verification code',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time(),
            'data' => [
                'can_resend' => true,
                'expires_in' => 300 // 5 minutes for new code
            ]
        ], 400);
        exit;
    }

    // Activate the account
    $activationResult = activateUserAccount($userId);
    
    if (!$activationResult['success']) {
        json([
            'status' => 'Server Error',
            'message' => $activationResult['message'],
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], 500);
        exit;
    }

    // Log successful verification
    if (function_exists('log_user_activity')) {
        log_user_activity("Account successfully activated via email verification");
    }

    // Clear any failed attempts
    clearFailedVerifications($userId);

    // Return success response
    json([
        'status' => 'Success',
        'message' => 'Account activated successfully',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_time' => time(),
        'data' => [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'first_name' => $user['first_name']
            ],
            'redirect' => '/auth/signin?verified=1'
        ]
    ], 200);
}

/**
 * Handle resend verification code
 */
function handleResendVerification() {
    $userId = request('user_id');

    if (!$userId) {
        json([
            'status' => 'Client Error',
            'message' => 'User ID is required',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], 400);
        exit;
    }

    // Rate limiting check
    if (isVerificationRateLimited($userId)) {
        json([
            'status' => 'Client Error',
            'message' => 'Too many requests. Please wait before requesting another code.',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time(),
            'data' => [
                'retry_after' => 300 // 5 minutes
            ]
        ], 429);
        exit;
    }

    // Get user data
    $user = getUserById($userId);
    if (!$user) {
        json([
            'status' => 'Client Error',
            'message' => 'User not found',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], 404);
        exit;
    }

    // Check if account is already active
    if ($user['is_active'] && $user['email_verified_at']) {
        json([
            'status' => 'Info',
            'message' => 'Account is already activated',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time(),
            'data' => [
                'redirect' => '/auth/signin'
            ]
        ], 200);
        exit;
    }

    // Generate new verification code
    $userService = UserService::getInstance();
    $verificationCode = $userService->createVerificationCode($userId);

    if (!$verificationCode) {
        json([
            'status' => 'Server Error',
            'message' => 'Failed to generate verification code',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ], 500);
        exit;
    }

    // Send verification email
    $emailSent = false;
    if (function_exists('sendAccountActivationEmail')) {
        $emailSent = sendAccountActivationEmail($user['email'], $verificationCode, $user['first_name']);
    } elseif (function_exists('send_verification_email')) {
        $emailSent = send_verification_email($user['email'], $verificationCode, $user['first_name']);
    }

    // Log resend attempt
    if (function_exists('log_user_activity')) {
        $status = $emailSent ? 'sent successfully' : 'failed to send';
        log_user_activity("Verification code resend {$status} to {$user['email']}");
    }

    // Return response
    json([
        'status' => $emailSent ? 'Success' : 'Warning',
        'message' => $emailSent ? 'Verification code sent successfully' : 'Failed to send email',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_time' => time(),
        'data' => [
            'sent_to' => maskEmail($user['email']),
            'expires_in' => 300, // 5 minutes
            'can_resend_after' => 60, // 1 minute
            'debug_code' => (function_exists('app_debug') && app_debug()) ? $verificationCode : null
        ]
    ], $emailSent ? 200 : 206);
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    try {
        if (!function_exists('db')) return null;
        
        $conn = db();
        
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.is_active, u.email_verified_at,
                   ud.first_name, ud.last_name
            FROM auth.users u
            LEFT JOIN auth.user_details ud ON u.id = ud.user_id
            WHERE u.id = :id
        ");
        
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get user by ID error: " . $e->getMessage());
        return null;
    }
}

/**
 * Activate user account
 */
function activateUserAccount($userId) {
    try {
        if (!function_exists('db')) {
            return ['success' => false, 'message' => 'Database not available'];
        }
        
        $conn = db();
        
        // Update user status to active and mark email as verified
        $stmt = $conn->prepare("
            UPDATE auth.users 
            SET is_active = true, 
                email_verified_at = NOW(),
                updated_at = NOW()
            WHERE id = :user_id
        ");
        
        $result = $stmt->execute([':user_id' => $userId]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Clean up verification codes for this user
            $cleanupStmt = $conn->prepare("DELETE FROM auth.verification_codes WHERE user_id = :user_id");
            $cleanupStmt->execute([':user_id' => $userId]);
            
            return ['success' => true, 'message' => 'Account activated successfully'];
        } else {
            return ['success' => false, 'message' => 'User not found or already activated'];
        }
        
    } catch (Exception $e) {
        error_log("Activate user account error: " . $e->getMessage());
        return ['success' => false, 'message' => 'System error during account activation'];
    }
}

/**
 * Check if verification requests are rate limited
 */
function isVerificationRateLimited($userId) {
    try {
        if (!function_exists('db')) return false;
        
        $conn = db();
        
        // Check recent verification codes (limit to 3 codes per 5 minutes)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as code_count
            FROM auth.verification_codes 
            WHERE user_id = :user_id 
            AND created_at > NOW() - INTERVAL '5 minutes'
        ");
        
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['code_count'] ?? 0) >= 3;
        
    } catch (Exception $e) {
        error_log("Verification rate limit check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Record failed verification attempt
 */
function recordFailedVerification($userId, $code) {
    try {
        if (function_exists('log_user_activity')) {
            log_user_activity("Failed verification attempt with code: {$code}");
        }
        
        error_log("Failed verification: User ID {$userId}, Code: {$code}, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
    } catch (Exception $e) {
        error_log("Record failed verification error: " . $e->getMessage());
    }
}

/**
 * Clear failed verification attempts (placeholder)
 */
function clearFailedVerifications($userId) {
    try {
        // This is optional - just for logging purposes
        if (function_exists('log_user_activity')) {
            log_user_activity("Cleared failed verification attempts");
        }
        
    } catch (Exception $e) {
        error_log("Clear failed verifications error: " . $e->getMessage());
    }
}

/**
 * Mask email for security
 */
function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    
    $username = $parts[0];
    $domain = $parts[1];
    
    if (strlen($username) <= 2) {
        return $username[0] . '*@' . $domain;
    }
    
    return $username[0] . str_repeat('*', strlen($username) - 2) . $username[-1] . '@' . $domain;
}