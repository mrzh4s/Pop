<?php
/**
 * User Services Class with Global Helpers (Updated)
 * File: apps/services/UserService.php
 * 
 * Provides comprehensive user management functionality
 * with global helper functions for the new auth schema
 */

class UserService {
    private static $instance = null;
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create a new user with full profile
     */
    public function createUser($userData) {
        try {
            $conn = db();
            $conn->beginTransaction();
            
            // Generate UUID for user - prefer database function if available
            $userId = $this->generateUUID();
            
            // Prepare user data
            $name = $userData['name'] ?? $userData['first_name'] . ' ' . ($userData['last_name'] ?? '');
            $username = $userData['username'];
            $email = $userData['email'];
            $password = password_hash($userData['password'], PASSWORD_ARGON2ID);
            
            // Validate required fields
            if (empty($email)) {
                throw new Exception("Email is required");
            }
            
            if (empty($userData['password'])) {
                throw new Exception("Password is required");
            }
            
            // Create main user record
            $userStmt = $conn->prepare("
                INSERT INTO auth.users (id, name, username, email, password, is_active, created_at, updated_at)
                VALUES (:id, :name, :username, :email, :password, :is_active, NOW(), NOW())
            ");
            
            $userStmt->execute([
                ':id' => $userId,
                ':name' => $name,
                ':username' => $username,
                ':email' => $email,
                ':password' => $password,
                ':is_active' => $userData['is_active'] ?? true
            ]);
            
            // Create user details record
            if (!empty($userData['details'])) {
                $this->createUserDetails($userId, $userData['details']);
            }
            
            // Assign roles if provided
            if (!empty($userData['roles'])) {
                $this->assignUserRoles($userId, $userData['roles']);
            }
            
            // Assign groups if provided
            if (!empty($userData['groups'])) {
                $this->assignUserGroups($userId, $userData['groups']);
            }
            
            $conn->commit();
            
            // Log user creation
            if (function_exists('log_user_activity')) {
                log_user_activity("New user created: {$email}");
            }
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'User created successfully'
            ];
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            error_log("Create user error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user by ID with all related data
     */
    public function getUserById($userId) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.password,
                    u.email_verified_at,
                    u.is_active,
                    u.last_login_at,
                    u.created_at,
                    u.updated_at,
                    ud.first_name,
                    ud.last_name,
                    ud.phone,
                    ud.date_of_birth,
                    ud.gender,
                    ud.unit_no,
                    ud.street_name,
                    ud.city,
                    ud.state,
                    ud.postcode,
                    ud.country,
                    ud.employee_id,
                    ud.telegram_id,
                    ud.bio,
                    ud.profile_picture,
                    ud.preferences
                FROM auth.users u
                LEFT JOIN auth.user_details ud ON u.id = ud.user_id
                WHERE u.id = :user_id
            ");
            
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return null;
            }
            
            // Get user roles
            $user['roles'] = $this->getUserRoles($userId);
            
            // Get user groups
            $user['groups'] = $this->getUserGroups($userId);
            
            // Parse JSON preferences
            if ($user['preferences']) {
                $user['preferences'] = json_decode($user['preferences'], true);
            }
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        try {
            $stmt = db_query("SELECT id FROM auth.users WHERE email = :email AND is_active = true", [
                ':email' => $email
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return null;
            }
            
            return $this->getUserById($result['id']);
            
        } catch (Exception $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by username
     */

    public function getUserByUsername($username) {
        try {
            $conn = db();

            $stmt = $conn->prepare("SELECT id FROM auth.users WHERE username = :username AND is_active = 'true'");

            $stmt->execute([":username"=> $username]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return null;
            }

            return $this->getUserById($result["id"]);

        } catch (Exception $e) {
            error_log("Get user by username error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user information
     */
    public function updateUser($userId, $userData) {
        try {
            $conn = db();
            $conn->beginTransaction();
            
            // Update main user table
            if (isset($userData['name']) || isset($userData['email']) || isset($userData['is_active']) || isset($userData['password'])) {
                $updateFields = [];
                $params = [':user_id' => $userId];
                
                if (isset($userData['name'])) {
                    $updateFields[] = "name = :name";
                    $params[':name'] = $userData['name'];
                }
                
                if (isset($userData['email'])) {
                    $updateFields[] = "email = :email";
                    $params[':email'] = $userData['email'];
                }
                
                if (isset($userData['is_active'])) {
                    $updateFields[] = "is_active = :is_active";
                    $params[':is_active'] = $userData['is_active'];
                }
                
                if (isset($userData['password'])) {
                    $updateFields[] = "password = :password";
                    $params[':password'] = password_hash($userData['password'], PASSWORD_ARGON2ID);
                }
                
                $updateFields[] = "updated_at = NOW()";
                
                $stmt = $conn->prepare("
                    UPDATE auth.users 
                    SET " . implode(', ', $updateFields) . "
                    WHERE id = :user_id
                ");
                
                $stmt->execute($params);
            }
            
            // Update user details if provided
            if (isset($userData['details'])) {
                $this->updateUserDetails($userId, $userData['details']);
            }
            
            // Update roles if provided
            if (isset($userData['roles'])) {
                $this->updateUserRoles($userId, $userData['roles']);
            }
            
            // Update groups if provided
            if (isset($userData['groups'])) {
                $this->updateUserGroups($userId, $userData['groups']);
            }
            
            $conn->commit();
            
            if (function_exists('log_user_activity')) {
                log_user_activity("User updated: {$userId}");
            }
            
            return [
                'success' => true,
                'message' => 'User updated successfully'
            ];
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            error_log("Update user error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Soft delete user
     */
    public function deleteUser($userId) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("
                UPDATE auth.users 
                SET 
                    is_active = false,
                    updated_at = NOW()
                WHERE id = :user_id
            ");
            
            $stmt->execute([':user_id' => $userId]);
            
            if (function_exists('log_user_activity')) {
                log_user_activity("User deactivated: {$userId}");
            }
            
            return [
                'success' => true,
                'message' => 'User deactivated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Authenticate user for login
     */
    public function authenticateUser($email, $password) {
        try {

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = $this->getUserByEmail($email);
            }


            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            if (!$user['is_active']) {
                return [
                    'success' => false,
                    'message' => 'Account is deactivated'
                ];
            }
            
            if (!password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid password'
                ];
            }
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            return [
                'success' => true,
                'user' => $user
            ];
            
        } catch (Exception $e) {
            error_log("Authenticate user error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create or update verification code (FIXED)
     */
    public function createVerificationCode($userId) {
        try {
            $conn = db();
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes
            
            $stmt = $conn->prepare("
                INSERT INTO auth.verification_codes (user_id, code, expires_at, created_at)
                VALUES (:user_id, :code, :expires_at, NOW())
                ON CONFLICT (user_id) DO UPDATE SET
                    code = EXCLUDED.code,
                    expires_at = EXCLUDED.expires_at,
                    created_at = NOW(),
                    updated_at = NOW()
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':code' => $code,
                ':expires_at' => $expiresAt
            ]);
            
            return $code;
            
        } catch (Exception $e) {
            error_log("Create verification code error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify code
     */
    public function verifyCode($userId, $code) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("
                SELECT code, expires_at 
                FROM auth.verification_codes 
                WHERE user_id = :user_id 
                AND expires_at > NOW()
            ");
            
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || $result['code'] !== $code) {
                return false;
            }
            
            // Delete used code
            $deleteStmt = $conn->prepare("
                DELETE FROM auth.verification_codes WHERE user_id = :user_id
            ");
            $deleteStmt->execute([':user_id' => $userId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Verify code error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users with pagination and filtering
     */
    public function getUsers($options = []) {
        try {
            $conn = db();
            
            // Default options
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            $search = $options['search'] ?? '';
            $role = $options['role'] ?? null;
            $group = $options['group'] ?? null;
            $active = $options['active'] ?? null;
            
            // Base query
            $whereConditions = [];
            $params = [];
            
            // Search condition
            if (!empty($search)) {
                $whereConditions[] = "(u.name ILIKE :search OR u.email ILIKE :search OR ud.first_name ILIKE :search OR ud.last_name ILIKE :search)";
                $params[':search'] = "%{$search}%";
            }
            
            // Active status filter
            if ($active !== null) {
                $whereConditions[] = "u.is_active = :active";
                $params[':active'] = $active;
            }
            
            // Role filter
            if ($role) {
                $whereConditions[] = "EXISTS (SELECT 1 FROM auth.role_user ru INNER JOIN auth.roles r ON ru.role_id = r.id WHERE ru.user_id = u.id AND r.name = :role)";
                $params[':role'] = $role;
            }
            
            // Group filter
            if ($group) {
                $whereConditions[] = "EXISTS (SELECT 1 FROM auth.group_user gu INNER JOIN auth.groups g ON gu.group_id = g.id WHERE gu.user_id = u.id AND g.name = :group)";
                $params[':group'] = $group;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Count query
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as total
                FROM auth.users u
                LEFT JOIN auth.user_details ud ON u.id = ud.user_id
                {$whereClause}
            ");
            $countStmt->execute($params);
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Main query
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $conn->prepare("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.is_active,
                    u.last_login_at,
                    u.created_at,
                    ud.first_name,
                    ud.last_name,
                    ud.employee_id,
                    ud.phone
                FROM auth.users u
                LEFT JOIN auth.user_details ud ON u.id = ud.user_id
                {$whereClause}
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add roles and groups for each user
            foreach ($users as &$user) {
                $user['roles'] = $this->getUserRoles($user['id']);
                $user['groups'] = $this->getUserGroups($user['id']);
            }
            
            return [
                'success' => true,
                'data' => $users,
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ============== PRIVATE HELPER METHODS ==============
    
    private function createUserDetails($userId, $details) {
        $conn = db();
        
        $stmt = $conn->prepare("
            INSERT INTO auth.user_details (
                user_id, first_name, last_name, phone, date_of_birth, gender,
                unit_no, street_name, city, state, postcode, country,
                employee_id, telegram_id, bio, profile_picture, preferences,
                created_at, updated_at
            ) VALUES (
                :user_id, :first_name, :last_name, :phone, :date_of_birth, :gender,
                :unit_no, :street_name, :city, :state, :postcode, :country,
                :employee_id, :telegram_id, :bio, :profile_picture, :preferences,
                NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':first_name' => $details['first_name'] ?? null,
            ':last_name' => $details['last_name'] ?? null,
            ':phone' => $details['phone'] ?? null,
            ':date_of_birth' => $details['date_of_birth'] ?? null,
            ':gender' => $details['gender'] ?? null,
            ':unit_no' => $details['unit_no'] ?? null,
            ':street_name' => $details['street_name'] ?? null,
            ':city' => $details['city'] ?? null,
            ':state' => $details['state'] ?? null,
            ':postcode' => $details['postcode'] ?? null,
            ':country' => $details['country'] ?? null,
            ':employee_id' => $details['employee_id'] ?? null,
            ':telegram_id' => $details['telegram_id'] ?? null,
            ':bio' => $details['bio'] ?? null,
            ':profile_picture' => $details['profile_picture'] ?? null,
            ':preferences' => isset($details['preferences']) ? json_encode($details['preferences']) : null
        ]);
    }
    
    private function updateUserDetails($userId, $details) {
        $conn = db();
        
        // Check if user details exist
        $checkStmt = $conn->prepare("SELECT id FROM auth.user_details WHERE user_id = :user_id");
        $checkStmt->execute([':user_id' => $userId]);
        
        if (!$checkStmt->fetch()) {
            // Create if not exists
            $this->createUserDetails($userId, $details);
            return;
        }
        
        // Update existing record
        $updateFields = [];
        $params = [':user_id' => $userId];
        
        $allowedFields = [
            'first_name', 'last_name', 'phone', 'date_of_birth', 'gender',
            'unit_no', 'street_name', 'city', 'state', 'postcode', 'country',
            'employee_id', 'telegram_id', 'bio', 'profile_picture'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($details[$field])) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $details[$field];
            }
        }
        
        if (isset($details['preferences'])) {
            $updateFields[] = "preferences = :preferences";
            $params[':preferences'] = json_encode($details['preferences']);
        }
        
        if (!empty($updateFields)) {
            $updateFields[] = "updated_at = NOW()";
            
            $stmt = $conn->prepare("
                UPDATE auth.user_details 
                SET " . implode(', ', $updateFields) . "
                WHERE user_id = :user_id
            ");
            
            $stmt->execute($params);
        }
    }
    
    private function assignUserRoles($userId, $roles) {
        $conn = db();
        
        foreach ($roles as $roleId) {
            $stmt = $conn->prepare("
                INSERT INTO auth.role_user (user_id, role_id, created_at, updated_at)
                VALUES (:user_id, :role_id, NOW(), NOW())
                ON CONFLICT (user_id, role_id) DO NOTHING
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':role_id' => $roleId
            ]);
        }
    }
    
    private function assignUserGroups($userId, $groups) {
        $conn = db();
        
        foreach ($groups as $groupId) {
            $stmt = $conn->prepare("
                INSERT INTO auth.group_user (user_id, group_id, created_at, updated_at)
                VALUES (:user_id, :group_id, NOW(), NOW())
                ON CONFLICT (user_id, group_id) DO NOTHING
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':group_id' => $groupId
            ]);
        }
    }
    
    private function getUserRoles($userId) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("
                SELECT r.id, r.name, r.display_name, r.description
                FROM auth.roles r
                INNER JOIN auth.role_user ru ON r.id = ru.role_id
                WHERE ru.user_id = :user_id AND r.is_active = true
                ORDER BY r.display_name
            ");
            
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get user roles error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getUserGroups($userId) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("
                SELECT g.id, g.name, g.display_name, g.description
                FROM auth.groups g
                INNER JOIN auth.group_user gu ON g.id = gu.group_id
                WHERE gu.user_id = :user_id AND g.is_active = true
                ORDER BY g.display_name
            ");
            
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get user groups error: " . $e->getMessage());
            return [];
        }
    }
    
    private function updateUserRoles($userId, $roles) {
        $conn = db();
        
        // Remove existing roles
        $deleteStmt = $conn->prepare("DELETE FROM auth.role_user WHERE user_id = :user_id");
        $deleteStmt->execute([':user_id' => $userId]);
        
        // Add new roles
        if (!empty($roles)) {
            $this->assignUserRoles($userId, $roles);
        }
    }
    
    private function updateUserGroups($userId, $groups) {
        $conn = db();
        
        // Remove existing groups
        $deleteStmt = $conn->prepare("DELETE FROM auth.group_user WHERE user_id = :user_id");
        $deleteStmt->execute([':user_id' => $userId]);
        
        // Add new groups
        if (!empty($groups)) {
            $this->assignUserGroups($userId, $groups);
        }
    }
    
    private function updateLastLogin($userId) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("
                UPDATE auth.users 
                SET last_login_at = NOW(), updated_at = NOW()
                WHERE id = :user_id
            ");
            
            $stmt->execute([':user_id' => $userId]);
            
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    private function generateUUID() {
        // Try to use database UUID function if available
        try {
            $conn = db();
            $stmt = $conn->prepare("SELECT uuid_generate_v4() as uuid");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['uuid']) {
                return $result['uuid'];
            }
        } catch (Exception $e) {
            // Fall back to PHP UUID generation
        }
        
        // PHP UUID v4 generation
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}