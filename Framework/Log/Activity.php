<?php
/**
 * Activity Core Class
 * Handles user activity logging and project activity tracking
 * File: core/Activity.php
 */
namespace Framework\Log;
use Exception;
use PDO;
class Activity {
    private static $instance = null;
    private $user;

    private function __construct($userId = null) {
        $this->user = $userId ?? session('user.id');
    }

    /**
     * Singleton pattern
     */
    public static function getInstance($userId = null) {
        if (self::$instance === null) {
            self::$instance = new self($userId);
        }
        return self::$instance;
    }

    /**
     * Log user activity with location and device info
     */
    public function logUserActivity($userId, $message) {
    try {
        $conn = db();
        $timestamp = date('Y-m-d H:i:s');

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $device = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $currentUrl = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        // Get location info with error handling
        $locationInfo = $this->getLocationInfo($ip);

        $query = "INSERT INTO user_activities
            (user_id, ip_address, url, location, device, message, action_at)
            VALUES (:user, :ip, :url, :location, :device, :message, :timestamp)";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user', $userId);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':url', $currentUrl);
        $stmt->bindParam(':location', $locationInfo);
        $stmt->bindParam(':device', $device);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':timestamp', $timestamp);

        if (!$stmt->execute()) {
            // Execution failed → check error info
            $error = $stmt->errorInfo();
            error_log("SQL Error: " . print_r($error, true));
            return false;
        }

        return true;

    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

    /**
     * Get location information from IP
     */
    private function getLocationInfo($ip) {
        if ($ip === '127.0.0.1') {
            return '127.0.0.1';
        }

        $accessKey = config('localhost.api.key', 'd569fd0625f7463290ebc6f9d3d0c870');
        $url = config('location.api.url', 'https://api.ipgeolocation.io/v2/ipgeo') . "?apiKey={$accessKey}&ip={$ip}";
        
        $response = @file_get_contents($url);
        
        if ($response !== false) {
            return $response;
        } else {
            error_log("Failed to fetch IP info for {$ip}");
            return json_encode(['error' => 'API call failed']);
        }
    }

    /**
     * Get user activity history
     */
    public function getUserActivity($limit = 1) {
        try {
            $conn = db();
            
            $query = "SELECT * FROM user_activities WHERE user_id = :userId
                     ORDER BY action_at DESC
                     LIMIT :limit";
                     
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':userId', $this->user);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $limit === 1 ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get user activity error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log project activity
     */
    public function logProjectActivity($systemId, $currentFlow, $details, $authorityId = null) {
        try {
            $conn = db();
            $timestamp = date('Y-m-d H:i:s');

            $query = "INSERT INTO project_activities (system_id, current_flow, flow_timestamp, username, details";
            $query .= $authorityId !== null ? ", authority" : "";
            $query .= ") VALUES (:system_id, :currentFlow, :timestamp, :username, :details";
            $query .= $authorityId !== null ? ", :authority" : "";
            $query .= ")";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':system_id', $systemId);
            $stmt->bindParam(':currentFlow', $currentFlow);
            $stmt->bindParam(':timestamp', $timestamp);
            $stmt->bindParam(':username', $this->user);
            $stmt->bindParam(':details', $details);
            
            if ($authorityId !== null) {
                $stmt->bindParam(':authority', $authorityId);
            }

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Project activity log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get project activity history
     */
    public function getProjectActivity($systemId) {
        try {
            $conn = db();
            
            $query = "SELECT * FROM project_activities
                     WHERE system_id = :systemId
                     ORDER BY flow_timestamp DESC";
                     
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':systemId', $systemId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get project activity error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Static method for API activity logging (placeholder)
     */
    public static function logApiActivity($data = []) {
        // Implementation for API activity logging
        // This would typically log API requests and responses
        return true;
    }

    /**
     * Static method for updating API activity (placeholder)
     */
    public static function updateApiActivity($id, $data = []) {
        // Implementation for updating API activity logs
        return true;
    }
}

