<?php
/**
 * Utilities Core Class
 * Handles various utility functions and system operations
 * File: core/Utilities.php
 */

class Utilities {
    private static $instance = null;

    private function __construct() {
        
    }

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
     * Extract system ID information
     */
    public function extractSystemId($systemId, $method) {
        try {
            $conn = db();
            
            $query = "SELECT * FROM ctrl_reference_no WHERE system_id = :systemId";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':systemId', $systemId);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return null;
            }
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            switch ($method) {
                case 'digits':
                    return $row['year'] . $row['running'];
                case 'running':
                    return $row['running'];
                case 'year':
                    return $row['year'];
                default:
                    return $row;
            }
        } catch (Exception $e) {
            error_log("Utilities extractSystemId error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get reference number by system ID
     */
    public function getRefNo($systemId) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("SELECT reference_no FROM flw_appl_entries WHERE system_id = :systemId");
            $stmt->bindParam(":systemId", $systemId);
            $stmt->execute();
            
            return $stmt->rowCount() > 0 ? $stmt->fetchColumn() : null;
        } catch (Exception $e) {
            error_log("Utilities getRefNo error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate unique activation code
     */
    public function generateCode($username = null) {
        try {
            $conn = db();
            $user = $username ?? session('username') ?? $_POST['username'] ?? null;
            
            if (!$user) {
                throw new Exception("Username is required for code generation");
            }

            $code = rand(100000, 999999);
            $requested = date('Y-m-d H:i:s');

            // Check if code already exists
            $stmt = $conn->prepare("SELECT activation_code FROM sys_users WHERE activation_code = :code");
            $stmt->bindValue(':code', $code);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Recursively generate new code if duplicate found
                return $this->generateCode($username);
            }

            // Update user with new code
            $updateQuery = "UPDATE sys_users SET activation_code = :code, activation_request = :requested WHERE username = :user";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':code', $code);
            $updateStmt->bindParam(':requested', $requested);
            $updateStmt->bindParam(':user', $user);
            $updateStmt->execute();

            // Log the activity
            log_user_activity("Permintaan kod pengesahan yang berdigit: $code telah dibuat oleh $user");

            return $code;
        } catch (Exception $e) {
            error_log("Utilities generateCode error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique letter ID
     */
    public function generateEntry($length = 8) {
        try {
            $conn = db();
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            
            // Generate unique ID
            $shuffledChars = str_shuffle($characters);
            $uniqueId = substr($shuffledChars, 0, $length);

            // Check if ID already exists
            $checkQuery = "SELECT id FROM ctrl_reference_no WHERE system_id = :uniqueId";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bindParam(':uniqueId', $uniqueId);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Recursively generate new ID if duplicate found
                return $this->generateEntry($length);
            }

            return $uniqueId;
        } catch (Exception $e) {
            error_log("Utilities generateEntry error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get status for system
     */
    public function getStatus($systemId, $flowGroup, $authId = null) {
        try {
            $conn = db();
            
            $condition = ($authId !== null) ? "AND authority = :authorityId" : "";
            $query = "SELECT {$flowGroup}_flow FROM ctrl_statuses WHERE system_id = :systemId {$condition} ORDER BY {$flowGroup}_flow ASC LIMIT 1";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':systemId', $systemId);
            
            if ($authId !== null) {
                $stmt->bindParam(':authorityId', $authId);
            }
            
            $stmt->execute();
            $status = $stmt->fetchColumn();

            return $status !== false ? $status : null;
        } catch (Exception $e) {
            error_log("Utilities getStatus error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check domain token validity
     */
    public function checkDomainToken($token, $domain) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("SELECT * FROM sys_api_tokens WHERE token = :token AND domain = :domain");
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':domain', $domain);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Utilities checkDomainToken error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert date to Malay format
     */
    public function convertDateToMalay($dateString) {
        try {
            $dateObj = new DateTime($dateString);
            
            $monthNames = [
                'Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun',
                'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'
            ];

            $day = $dateObj->format('d');
            $monthIndex = $dateObj->format('n') - 1;
            $year = $dateObj->format('Y');

            return "$day {$monthNames[$monthIndex]} $year";
        } catch (Exception $e) {
            error_log("Utilities convertDateToMalay error: " . $e->getMessage());
            return $dateString; // Return original if conversion fails
        }
    }

    /**
     * Format time to Malay time string
     */
    public function formatTimeToMalayTimeString($dateTimeString) {
        try {
            $dateTimeObj = new DateTime($dateTimeString);
            return $dateTimeObj->format('h:i A');
        } catch (Exception $e) {
            error_log("Utilities formatTimeToMalayTimeString error: " . $e->getMessage());
            return $dateTimeString;
        }
    }

    /**
     * Get provider information
     */
    public function getProvider($id) {
        try {
            $ecUrl = config('app.ec_url');
            if (!$ecUrl) {
                throw new Exception("EC URL not configured");
            }

            $curl = Curl::getInstance();
            $response = $curl->get($ecUrl . '/api/providers/' . $id, [
                'ssl_verify' => false,
                'timeout' => 30
            ]);

            if (is_success_response($response)) {
                $data = response_json($response);
                return (object)$data;
            }

            // Return default empty object on error
            return (object)[
                "id" => "",
                "name" => "",
                "logo" => "",
                "sort_name" => ""
            ];
        } catch (Exception $e) {
            error_log("Utilities getProvider error: " . $e->getMessage());
            return (object)[
                "id" => "",
                "name" => "",
                "logo" => "",
                "sort_name" => ""
            ];
        }
    }

    /**
     * Get authority name
     */
    public function getAuthorityName($authorityId) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare('SELECT sort_name FROM ls_authorities WHERE id = :authority_id');
            $stmt->bindParam(':authority_id', $authorityId);
            $stmt->execute();

            return $stmt->rowCount() > 0 ? $stmt->fetchColumn() : null;
        } catch (Exception $e) {
            error_log("Utilities getAuthorityName error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get current status
     */
    public function getCurrentStatus($systemId, $department, $authority = null) {
        try {
            $conn = db();
            
            if ($authority === null) {
                $authority = 0;
            }

            $query = "SELECT status_id FROM ctrl_statuses WHERE system_id = :systemId AND department = :department AND authority = :authority";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':systemId', $systemId);
            $stmt->bindParam(':department', $department);
            $stmt->bindParam(':authority', $authority);
            $stmt->execute();

            return $stmt->rowCount() > 0 ? $stmt->fetchColumn() : null;
        } catch (Exception $e) {
            error_log("Utilities getCurrentStatus error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate random string
     */
    public function generateRandomString($length = 10, $characters = null) {
        $characters = $characters ?? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($characters), 0, $length);
    }

    /**
     * Validate system ID format
     */
    public function validateSystemId($systemId) {
        return preg_match('/^[A-Z0-9]{8}$/', $systemId);
    }

    /**
     * Get system information
     */
    public function getSystemInfo($systemId) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare("SELECT * FROM flw_appl_entries WHERE system_id = :systemId");
            $stmt->bindParam(':systemId', $systemId);
            $stmt->execute();
            
            return $stmt->rowCount() > 0 ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        } catch (Exception $e) {
            error_log("Utilities getSystemInfo error: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Fluent Utilities Helper Class
 */
class UtilitiesHelper {
    private $systemId = null;
    private $dateString = null;
    private $instance;
    
    public function __construct($systemId = null) {
        $this->systemId = $systemId;
        $this->instance = Utilities::getInstance();
    }
    
    /**
     * Set system ID for operations
     */
    public function system($systemId) {
        $this->systemId = $systemId;
        return $this;
    }
    
    /**
     * Set date for operations
     */
    public function date($dateString) {
        $this->dateString = $dateString;
        return $this;
    }
    
    /**
     * Get reference number
     */
    public function getRefNo() {
        return $this->instance->getRefNo($this->systemId);
    }
    
    /**
     * Get system info
     */
    public function getInfo() {
        return $this->instance->getSystemInfo($this->systemId);
    }
    
    /**
     * Extract system ID
     */
    public function extract($method = 'digits') {
        return $this->instance->extractSystemId($this->systemId, $method);
    }
    
    /**
     * Validate system ID
     */
    public function isValid() {
        return $this->instance->validateSystemId($this->systemId);
    }
    
    /**
     * Convert date to Malay
     */
    public function toMalay() {
        return $this->instance->convertDateToMalay($this->dateString);
    }
    
    /**
     * Convert time to Malay
     */
    public function timeToMalay() {
        return $this->instance->formatTimeToMalayTimeString($this->dateString);
    }
    
    /**
     * Get provider
     */
    public function provider($providerId) {
        return $this->instance->getProvider($providerId);
    }
    
    /**
     * Get authority name
     */
    public function authority($authorityId) {
        return $this->instance->getAuthorityName($authorityId);
    }
    
    /**
     * Generate codes
     */
    public function generateCode($username = null) {
        return $this->instance->generateCode($username);
    }
    
    public function generateEnty($length = 8) {
        return $this->instance->generateEntry($length);
    }
}