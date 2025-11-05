<?php

require_once ROOT_PATH . '/pages/BasePage.php';

class LoginPage extends BasePage {
    
    /**
     * Show login view (GET /auth/signin)
     * Renders the login form
     */
    public function show($params = []) {
        // If already authenticated, redirect to home
        if (session('authenticated')) {
            redirect('dashboard');
            return;
        }
        
        // Render the signin view
        return $this->view('auth.signin', [
            'title' => 'Sign In',
            'error' => $params['error'] ?? null
        ]);
    }
    
    /**
     * Handle login API (POST /api/auth/login)
     * Processes login credentials and creates session
     */
    public function login($params = []) {
        $this->setSecurityHeaders();
        $this->verifyCsrf();
        
        try {
            // Validate input
            $this->validateRequired(['username', 'password']);
            
            $username = request('username');
            $password = request('password');
            
            // Authenticate user
            $user = $this->authenticateUser($username, $password);
            
            if (!$user || !is_object($user)) {
                $this->jsonError('Invalid username or password', 401);
                return;
            }
            
            // Create session
            $this->createUserSession($user);
            
            // Return success response
            $this->jsonSuccess('Welcome back, ' . $user->name . '!');
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $this->jsonError('An error occurred during login', 500);
        }
    }
    
    /**
     * Handle logout API (POST /api/auth/logout)
     * Destroys user session
     */
    public function logout($params = []) {
        $this->setSecurityHeaders();
        
        try {
            secure_session();
            
            // Clear session data
            session_clear();
            
            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $cookieParams = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $cookieParams["path"], 
                    $cookieParams["domain"],
                    $cookieParams["secure"], 
                    $cookieParams["httponly"]
                );
            }
            
            // Destroy session
            session_destroy();
            
            $this->jsonSuccess('Logged out successfully', [
                'redirect' => '/auth/signin'
            ]);
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            $this->jsonSuccess('Logged out successfully', [
                'redirect' => '/auth/signin'
            ]);
        }
    }
    
    /**
     * Authenticate user (replace with your actual logic)
     * @return array|null User data or null if authentication fails
     */
    private function authenticateUser($username, $password) {
         try {
            $db = db('main');
            
            // Find user by username
            $stmt = $db->prepare("
                SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1
            ");
            
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);

            
            if (!$user) {
                return null;
            }
            
            // Verify password
            if (!password_verify($password, $user->password)) {
                return null;
            }
            
            // Remove password from returned data
            unset($user->password);
            
            return $user;
            
        } catch (PDOException $e) {
            error_log("Database error during authentication: " . $e->getMessage());
            return null;
        }
        
    }
    
    /**
     * Create user session after successful authentication
     */
    private function createUserSession($user) {

        session_set('authenticated', true);
        session_set('user.id', $user->id);
        session_set('user.name', $user->name);
        session_set('user.email', $user->email);
        session_set('user.username', $user->username);
        session_set('user.role', $user->role ?? 'user');
        session_set('login_time', time());
        
        // Regenerate session ID for security
        session_regenerate_id(true);

        
    }
}
