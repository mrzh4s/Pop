<?php
/**
 * Authentication Service (Updated for Session class only)
 * File: apps/services/AuthService.php
 * 
 * Handles authentication logic and integrates with Session and UserService
 */

class AuthService {
    private static $instance = null;
    private $userService;
    private $session;
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->userService = UserService::getInstance();
        $this->session = Session::getInstance();
    }
    
    /**
     * Authenticate user with email/username and password
     */
    public function login($identifier, $password, $rememberMe = false) {
        try {

            $block = $this->isBlocked($identifier);
            // Check for too many failed attempts
            if ($block['blocked']) {
                return [
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again later.',
                    'blocked' => true,
                    'user_id' => $block['user_id']
                ];
            }
            
            // Authenticate user
            $authResult = $this->userService->authenticateUser($identifier, $password);
            
            if (!$authResult['success']) {
                // Log failed attempt
                $this->logLoginAttempt($identifier, false);
                
                return [
                    'success' => false,
                    'message' => $authResult['message']
                ];
            }
            
            $user = $authResult['user'];
            
            // Start session if not already started
            if (!$this->session->isActive()) {
                $this->session->start();
            }
            
            // Set user in session
            $this->session->setUserId($user['id']);
            $this->session->set('user.id', $user['id']);
            $this->session->set('user.email', $user['email']);
            $this->session->set('user.name', $user['name']);
            $this->session->set('user.username', $user['username'] ?? '');
            $this->session->set('user.logged_in_at', time());
            
            // Handle remember me functionality
            if ($rememberMe) {
                $this->setRememberToken($user['id']);
            }
            
            // Clear failed attempts on successful login
            $this->clearLoginAttempts($identifier);
            
            return [
                'success' => true,
                'user' => $user,
                'message' => 'Login successful'
            ];
            
        } catch (Exception $e) {
            error_log("Auth login error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Login failed due to system error'
            ];
        }
    }
    
    /**
     * Logout current user
     */
    public function logout() {
        try {
            $userId = $this->getCurrentUserId();
            
            if ($userId) {
                // Clear remember token
                $this->clearRememberToken($userId);
            }
            
            // Destroy session
            $this->session->destroy();
            
            return [
                'success' => true,
                'message' => 'Logout successful'
            ];
            
        } catch (Exception $e) {
            error_log("Auth logout error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Logout failed'
            ];
        }
    }
    
    /**
     * Register new user
     */
    public function register($userData) {
        try {
            // Validate required fields
            $validation = $this->validateRegistration($userData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }
            
            // Check if user already exists
            if ($this->userExists($userData['email'])) {
                return [
                    'success' => false,
                    'message' => 'User with this email already exists'
                ];
            }
            
            if (isset($userData['username']) && $this->usernameExists($userData['username'])) {
                return [
                    'success' => false,
                    'message' => 'Username is already taken'
                ];
            }
            
            // Create user
            $createResult = $this->userService->createUser($userData);
            
            if (!$createResult['success']) {
                return $createResult;
            }
            
            // Auto-login after registration if enabled
            if ($userData['auto_login'] ?? false) {
                $user = $this->userService->getUserById($createResult['user_id']);
                if ($user) {
                    $this->loginUser($user);
                }
            }
            
            return [
                'success' => true,
                'user_id' => $createResult['user_id'],
                'message' => 'Registration successful'
            ];
            
        } catch (Exception $e) {
            error_log("Auth register error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Registration failed'
            ];
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function check() {
        return $this->session->has('user.id') && $this->session->getUserId() !== null;
    }
    
    /**
     * Get current authenticated user
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        return [
            'id' => $this->session->get('user.id'),
            'email' => $this->session->get('user.email'),
            'name' => $this->session->get('user.name'),
            'username' => $this->session->get('user.username'),
            'logged_in_at' => $this->session->get('user.logged_in_at')
        ];
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId() {
        return $this->session->getUserId() ?? $this->session->get('user.id');
    }
    
    /**
     * Get full user data (including roles, groups, etc.)
     */
    public function getFullUser() {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return null;
        }
        
        return $this->userService->getUserById($userId);
    }
    
    /**
     * Verify email with code
     */
    public function verifyEmail($userId, $code) {
        try {
            if (!$this->userService->verifyCode($userId, $code)) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired verification code'
                ];
            }
            
            // Mark email as verified
            $updateResult = $this->userService->updateUser($userId, [
                'email_verified_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($updateResult['success']) {
                return [
                    'success' => true,
                    'message' => 'Email verified successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update verification status'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Auth verify email error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Email verification failed'
            ];
        }
    }
    
    /**
     * Send verification code
     */
    public function sendVerificationCode($userId) {
        try {
            $user = $this->userService->getUserById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            $code = $this->userService->createVerificationCode($userId);
            if (!$code) {
                return [
                    'success' => false,
                    'message' => 'Failed to generate verification code'
                ];
            }
            
            // TODO: Send email with verification code
            // You would integrate with your email service here
            
            return [
                'success' => true,
                'message' => 'Verification code sent',
                'code' => $code // Remove this in production
            ];
            
        } catch (Exception $e) {
            error_log("Auth send verification error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to send verification code'
            ];
        }
    }
    
    /**
     * Reset password request
     */
    public function requestPasswordReset($email) {
        try {
            $user = $this->userService->getUserByEmail($email);
            if (!$user) {
                // Don't reveal if email exists or not
                return [
                    'success' => true,
                    'message' => 'If the email exists, a reset link has been sent'
                ];
            }
            
            $token = $this->generatePasswordResetToken($user['id']);
            
            // TODO: Send password reset email
            // You would integrate with your email service here
            
            return [
                'success' => true,
                'message' => 'Password reset link sent to your email',
                'token' => $token // Remove this in production
            ];
            
        } catch (Exception $e) {
            error_log("Auth password reset request error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to process password reset request'
            ];
        }
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword) {
        try {
            $resetData = $this->validatePasswordResetToken($token);
            if (!$resetData) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ];
            }
            
            // Update password
            $updateResult = $this->userService->updateUser($resetData['user_id'], [
                'password' => $newPassword
            ]);
            
            if ($updateResult['success']) {
                // Delete used reset token
                $this->deletePasswordResetToken($token);
                
                return [
                    'success' => true,
                    'message' => 'Password reset successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update password'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Auth reset password error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Password reset failed'
            ];
        }
    }
    
    /**
     * Change password for authenticated user
     */
    public function changePassword($currentPassword, $newPassword) {
        try {
            if (!$this->check()) {
                return [
                    'success' => false,
                    'message' => 'Not authenticated'
                ];
            }
            
            $user = $this->getFullUser();
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Update password
            $updateResult = $this->userService->updateUser($user['id'], [
                'password' => $newPassword
            ]);
            
            return $updateResult;
            
        } catch (Exception $e) {
            error_log("Auth change password error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Password change failed'
            ];
        }
    }
    
    /**
     * Validate remember token and auto-login
     */
    public function validateRememberToken() {
        if (!isset($_COOKIE['remember_token']) || !function_exists('db')) {
            return false;
        }
        
        try {
            $token = $_COOKIE['remember_token'];
            $hashedToken = hash('sha256', $token);
            
            $conn = db();
            $stmt = $conn->prepare("
                SELECT id, email, name, username 
                FROM auth.users 
                WHERE remember_token = :token AND is_active = true
            ");
            $stmt->execute([':token' => $hashedToken]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Auto-login user
                $this->loginUser($user);
                return $user;
            }
            
        } catch (Exception $e) {
            error_log("Validate remember token error: " . $e->getMessage());
        }
        
        return false;
    }
    
    // ============== AUTHORIZATION METHODS ==============
    
    /**
     * Check if user has specific role
     */
    public function hasRole($roleName) {
        $user = $this->getFullUser();
        if (!$user || empty($user['roles'])) {
            return false;
        }
        
        foreach ($user['roles'] as $role) {
            if ($role['name'] === $roleName) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles) {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is in specific group
     */
    public function inGroup($groupName) {
        $user = $this->getFullUser();
        if (!$user || empty($user['groups'])) {
            return false;
        }
        
        foreach ($user['groups'] as $group) {
            if ($group['name'] === $groupName) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }
    
    // ============== PRIVATE HELPER METHODS ==============
    
    private function loginUser($user) {
        // Start session if not already started
        if (!$this->session->isActive()) {
            $this->session->start();
        }
        
        // Set user in session
        $this->session->setUserId($user['id']);
        $this->session->set('user.id', $user['id']);
        $this->session->set('user.email', $user['email']);
        $this->session->set('user.name', $user['name']);
        $this->session->set('user.username', $user['username'] ?? '');
        $this->session->set('user.logged_in_at', time());
    }
    
    private function validateRegistration($userData) {
        $errors = [];
        
        if (empty($userData['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (empty($userData['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($userData['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters long';
        }
        
        if (empty($userData['name']) && (empty($userData['first_name']) || empty($userData['last_name']))) {
            $errors[] = 'Name is required';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function userExists($email) {
        return $this->userService->getUserByEmail($email) !== null;
    }
    
    private function usernameExists($username) {
        return $this->userService->getUserByUsername($username) !== null;
    }
    
    private function setRememberToken($userId) {
        if (!function_exists('db')) return;
        
        try {
            $token = bin2hex(random_bytes(32));
            
            // Store in database
            $conn = db();
            $stmt = $conn->prepare("UPDATE auth.users SET remember_token = :token WHERE id = :user_id");
            $stmt->execute([':token' => hash('sha256', $token), ':user_id' => $userId]);
            
            // Set cookie (valid for 30 days)
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            
        } catch (Exception $e) {
            error_log("Set remember token error: " . $e->getMessage());
        }
    }
    
    private function clearRememberToken($userId) {
        if (!function_exists('db')) return;
        
        try {
            // Clear from database
            $conn = db();
            $stmt = $conn->prepare("UPDATE auth.users SET remember_token = NULL WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            
            // Clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            
        } catch (Exception $e) {
            error_log("Clear remember token error: " . $e->getMessage());
        }
    }
    
    private function isBlocked($identifier): array|bool {

        try {
            $conn = db();

            $stmt = $conn->prepare("SELECT id FROM auth.users WHERE email = :email");
            $stmt->execute([':email' => $identifier]);
            $user = $stmt->fetch();

            // Check if user is blocked
            $stmt = $conn->prepare("
                SELECT blocked_until 
                FROM auth.login_attempts 
                WHERE (ip_address = :ip OR email = :email) 
                AND blocked_until > NOW()
            ");
            
            $stmt->execute([
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':email' => $identifier
            ]);

            $block = $stmt->fetch();
            
            return [
                'user_id' => $user ?? null,
                'blocked' => $block !== false,
            ];
            
        } catch (Exception $e) {
            error_log("Check blocked error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logLoginAttempt($identifier, $success) {

        try {
            $conn = db();
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            if ($success) {
                // Clear attempts on success
                $stmt = $conn->prepare("DELETE FROM auth.login_attempts WHERE ip_address = :ip OR email = :email");
                $stmt->execute([':ip' => $ip, ':email' => $identifier]);
            } else {
                // Increment failed attempts
                $stmt = $conn->prepare("INSERT INTO auth.login_attempts (ip_address, email, attempts, last_attempt, created_at, updated_at)
                    VALUES (:ip, :email, 1, NOW(), NOW(), NOW())
                    ON CONFLICT (ip_address, email) DO UPDATE SET
                        attempts = auth.login_attempts.attempts + 1,
                        last_attempt = NOW(),
                        updated_at = NOW(),
                        blocked_until = CASE 
                            WHEN auth.login_attempts.attempts + 1 >= 5 THEN NOW() + INTERVAL '15 minutes'
                            ELSE auth.login_attempts.blocked_until
                        END
                ");
                $stmt->execute([':ip' => $ip, ':email' => $identifier]);
            }
            
        } catch (Exception $e) {
            error_log("Log login attempt error: " . $e->getMessage());
        }
    }
    
    private function clearLoginAttempts($identifier) {
        $this->logLoginAttempt($identifier, true);
    }
    
    private function generatePasswordResetToken($userId) {
        if (!function_exists('db')) {
            return false;
        }
        
        try {
            $conn = db();
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $user = $this->userService->getUserById($userId);
            if (!$user) {
                return false;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO auth.password_resets (email, token, expires_at, created_at, updated_at)
                VALUES (:email, :token, :expires_at, NOW(), NOW())
            ");
            
            $stmt->execute([
                ':email' => $user['email'],
                ':token' => hash('sha256', $token),
                ':expires_at' => $expiresAt
            ]);
            
            return $token;
            
        } catch (Exception $e) {
            error_log("Generate password reset token error: " . $e->getMessage());
            return false;
        }
    }
    
    private function validatePasswordResetToken($token) {
        if (!function_exists('db')) {
            return false;
        }
        
        try {
            $conn = db();
            $hashedToken = hash('sha256', $token);
            
            $stmt = $conn->prepare("
                SELECT pr.email, u.id as user_id
                FROM auth.password_resets pr
                INNER JOIN auth.users u ON pr.email = u.email
                WHERE pr.token = :token AND pr.expires_at > NOW()
            ");
            
            $stmt->execute([':token' => $hashedToken]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Validate password reset token error: " . $e->getMessage());
            return false;
        }
    }
    
    private function deletePasswordResetToken($token) {
        if (!function_exists('db')) {
            return;
        }
        
        try {
            $conn = db();
            $hashedToken = hash('sha256', $token);
            
            $stmt = $conn->prepare("DELETE FROM auth.password_resets WHERE token = :token");
            $stmt->execute([':token' => $hashedToken]);
            
        } catch (Exception $e) {
            error_log("Delete password reset token error: " . $e->getMessage());
        }
    }
}