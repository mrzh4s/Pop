<?php
/**
 * TelegramBot Core Class
 * Handles Telegram bot messaging and notifications
 * File: core/TelegramBot.php
 */

class TelegramService {
    private static $instance = null;
    private $token;

    private function __construct() {
        $this->token = config('telegram.bot_token');
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
     * Get chat ID by username
     */
    private function getChatId($username) {
        try {
            $conn = db();
            $stmt = $conn->prepare("SELECT telegram_id FROM sys_users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $row['telegram_id'] : null;
        } catch (Exception $e) {
            error_log("TelegramBot getChatId error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all chat IDs
     */
    private function getAllChatId() {
        try {
            $conn = db();
            $stmt = $conn->prepare("SELECT telegram_id FROM sys_users WHERE telegram_id IS NOT NULL");
            $stmt->execute();
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'telegram_id');
        } catch (Exception $e) {
            error_log("TelegramBot getAllChatId error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get chat IDs by role
     */
    private function getChatIdByRole($role) {
        try {
            $conn = db();
            $stmt = $conn->prepare("SELECT telegram_id FROM sys_users WHERE role_id = :role AND telegram_id IS NOT NULL");
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'telegram_id');
        } catch (Exception $e) {
            error_log("TelegramBot getChatIdByRole error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get chat IDs by department
     */
    private function getChatIdByDepartment($department) {
        try {
            $conn = db();

            $subDepartments = ['registration', 'permit', 'project', 'survey', 'plan', 'charting', 'translation'];
            $departments = ['mapping', 'operation', 'geospatial', 'management', 'business', 'hr', 'finance'];

            if (in_array($department, $subDepartments)) {
                $stmt = $conn->prepare("SELECT id FROM sys_hr_employee WHERE sub_department = :value");
            } elseif (in_array($department, $departments)) {
                $stmt = $conn->prepare("SELECT id FROM sys_hr_employee WHERE department = :value");
            } else {
                return [];
            }

            $stmt->bindParam(':value', $department);
            $stmt->execute();
            $employeeIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');

            if (empty($employeeIds)) {
                return [];
            }

            $users = '{' . implode(',', $employeeIds) . '}';
            $stmt = $conn->prepare("SELECT telegram_id FROM sys_users WHERE employee_id = ANY (:id) AND telegram_id IS NOT NULL");
            $stmt->bindParam(':id', $users);
            $stmt->execute();

            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'telegram_id');
        } catch (Exception $e) {
            error_log("TelegramBot getChatIdByDepartment error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get chat IDs by flow status
     */
    private function getChatIdByFlow($status) {
        try {
            $conn = db();
            
            $stmt = $conn->prepare('SELECT flow_role_id FROM ls_statuses WHERE id = :status');
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $role = $stmt->fetchColumn();

            if (!$role) {
                return [];
            }

            $stmt = $conn->prepare("SELECT telegram_id FROM sys_users WHERE role_id = ANY (:role) AND telegram_id IS NOT NULL");
            $stmt->bindParam(':role', $role);
            $stmt->execute();

            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'telegram_id');
        } catch (Exception $e) {
            error_log("TelegramBot getChatIdByFlow error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get chat IDs by group
     */
    private function getChatIdByGroup($groupName) {
        try {
            $conn = db();
            $stmt = $conn->prepare("SELECT telegram_id FROM ls_telegram_group WHERE (department = :groupName OR sub_department = :groupName) AND telegram_id IS NOT NULL");
            $stmt->bindParam(':groupName', $groupName);
            $stmt->execute();
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'telegram_id');
        } catch (Exception $e) {
            error_log("TelegramBot getChatIdByGroup error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send message to recipients
     */
    public function sendMessage($method, $variable, $message = null, $parseMode = 'HTML') {
        try {
            $chatIds = $this->getChatIds($method, $variable);
            
            if (empty($chatIds)) {
                return ['error' => 'No chat IDs found'];
            }

            $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
            $responses = [];

            foreach (array_unique($chatIds) as $chatId) {
                $data = [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => $parseMode,
                ];

                $response = $this->makeRequest($url, $data);
                $responses[] = $response;
            }

            return $responses;
        } catch (Exception $e) {
            error_log("TelegramBot sendMessage error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Send document to recipients
     */
    public function sendDocument($method, $variable, $documentPath, $replyMsgId = null, $caption = null, $notification = false) {
        try {
            $chatIds = $this->getChatIds($method, $variable);
            
            if (empty($chatIds)) {
                return ['error' => 'No chat IDs found'];
            }

            $url = "https://api.telegram.org/bot{$this->token}/sendDocument";
            $responses = [];

            foreach (array_unique($chatIds) as $chatId) {
                $data = [
                    'chat_id' => $chatId,
                    'document' => $documentPath,
                    'caption' => $caption,
                    'disable_notification' => $notification,
                ];

                if ($replyMsgId) {
                    $data['reply_to_message_id'] = $replyMsgId;
                }

                $response = $this->makeRequest($url, $data);
                $responses[] = $response;
            }

            return $responses;
        } catch (Exception $e) {
            error_log("TelegramBot sendDocument error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Send photo to recipients
     */
    public function sendPhoto($method, $variable, $photoPath, $replyMsgId = null, $caption = null, $notification = false) {
        try {
            $chatIds = $this->getChatIds($method, $variable);
            
            if (empty($chatIds)) {
                return ['error' => 'No chat IDs found'];
            }

            $url = "https://api.telegram.org/bot{$this->token}/sendPhoto";
            $responses = [];

            foreach (array_unique($chatIds) as $chatId) {
                $data = [
                    'chat_id' => $chatId,
                    'photo' => $photoPath,
                    'caption' => $caption,
                    'disable_notification' => $notification,
                ];

                if ($replyMsgId) {
                    $data['reply_to_message_id'] = $replyMsgId;
                }

                $response = $this->makeRequest($url, $data);
                $responses[] = $response;
            }

            return $responses;
        } catch (Exception $e) {
            error_log("TelegramBot sendPhoto error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get chat IDs based on method
     */
    private function getChatIds($method, $variable) {
        switch ($method) {
            case 'assignee':
            case 'user':
                return [$this->getChatId($variable)];
            case 'role':
                return $this->getChatIdByRole($variable);
            case 'flow':
                return $this->getChatIdByFlow($variable);
            case 'department':
                return $this->getChatIdByDepartment($variable);
            case 'group':
                return $this->getChatIdByGroup($variable);
            case 'all':
                return $this->getAllChatId();
            default:
                return [];
        }
    }

    /**
     * Make HTTP request to Telegram API
     */
    private function makeRequest($url, $data) {
        $curl = Curl::getInstance();
        $query = http_build_query($data);
        
        $result = $curl->request($url, $query, 'POST', ['Content-Type: application/x-www-form-urlencoded']);
        
        // Log API traffic if Traffic class is available
        if (class_exists('Traffic')) {
            $traffic = Traffic::getInstance();
            $traffic->requestAPI(
                $result['traffic'],
                $result['url'],
                $result['request_method'],
                $result['headers'],
                $result['body'],
                $result['response'],
                $result['status']
            );
        }

        return $result;
    }
}


