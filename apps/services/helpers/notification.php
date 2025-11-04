<?php

// ============== GLOBAL HELPER FUNCTIONS ==============

/**
 * Get Brevo email service instance
 */
if (!function_exists('email_service')) {
    function email_service() {
        static $instance = null;
        if ($instance === null) {
            $instance = new EmailService();
        }
        return $instance;
    }
}

/**
 * Send email via Brevo
 * 
 * Usage:
 * send_email([
 *     'to' => 'user@example.com',
 *     'subject' => 'Welcome!',
 *     'htmlContent' => '<h1>Welcome to KUTT!</h1>',
 *     'textContent' => 'Welcome to KUTT!'
 * ]);
 */
if (!function_exists('send_email')) {
    function send_email(array $emailData) {
        try {
            return email_service()->sendEmail($emailData);
        } catch (Exception $e) {
            error_log("Send email helper error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Send bulk emails
 * 
 * Usage:
 * send_bulk_email(
 *     ['user1@example.com', 'user2@example.com'],
 *     'Newsletter',
 *     '<h1>Monthly Newsletter</h1>',
 *     'Monthly Newsletter'
 * );
 */
if (!function_exists('send_bulk_email')) {
    function send_bulk_email(array $recipients, string $subject, string $htmlContent, string $textContent = '', array $options = []) {
        try {
            return email_service()->sendBulkEmail($recipients, $subject, $htmlContent, $textContent, $options);
        } catch (Exception $e) {
            error_log("Send bulk email helper error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Test Brevo connection
 * 
 * Usage:
 * $result = test_email_connection();
 * if ($result['success']) {
 *     echo "Email service is working!";
 * }
 */
if (!function_exists('test_email_connection')) {
    function test_email_connection() {
        try {
            return email_service()->testConnection();
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Get email delivery status
 * 
 * Usage:
 * $status = get_email_status($messageId);
 */
if (!function_exists('get_email_status')) {
    function get_email_status(string $messageId) {
        try {
            return email_service()->getEmailStatus($messageId);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Send verification email (helper for login system)
 */
if (!function_exists('send_verification_email')) {
    function send_verification_email(string $email, string $code, string $firstName = '') {
        $subject = 'Email Verification - KUTT System';
        $displayName = $firstName ?: 'User';
        
        $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #2c3e50;'>Email Verification - KUTT</h2>
                <p>Hi {$displayName},</p>
                <p>Your email verification code is:</p>
                <div style='background: #f8f9fa; padding: 20px; text-align: center; margin: 20px 0;'>
                    <h1 style='color: #007bff; font-size: 32px; margin: 0;'>{$code}</h1>
                </div>
                <p>This code will expire in 5 minutes.</p>
                <p>If you did not request this verification, please ignore this email.</p>
                <hr style='margin: 30px 0;'>
                <p style='color: #6c757d; font-size: 12px;'>
                    KUTT - Koridor Utiliti Teknologi Terengganu<br>
                    This is an automated message, please do not reply.
                </p>
            </div>
        ";
        
        $textContent = "Email Verification - KUTT System\n\n" .
                      "Hi {$displayName},\n\n" .
                      "Your email verification code is: {$code}\n\n" .
                      "This code will expire in 5 minutes.\n\n" .
                      "If you did not request this verification, please ignore this email.\n\n" .
                      "---\n" .
                      "KUTT - Koridor Utiliti Teknologi Terengganu";

        return send_email([
            'to' => $email,
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => $textContent
        ]);
    }
}

// ============== TELEGRAM BOT HELPER FUNCTIONS ==============

/**
 * Get TelegramBot instance
 */
if (!function_exists('telegram')) {
    function telegram() {
        if (!class_exists('TelegramService')) {
            throw new Exception('TelegramBot class not found. Make sure to include core/TelegramBot.php');
        }
        
        return TelegramService::getInstance();
    }
}

/**
 * Send message to specific user
 */
if (!function_exists('telegram_user')) {
    function telegram_user($username, $message, $parseMode = 'HTML') {
        try {
            $bot = telegram();
            return $bot->sendMessage('user', $username, $message, $parseMode);
        } catch (Exception $e) {
            error_log("telegram_user error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

/**
 * Send message to all users with specific role
 */
if (!function_exists('telegram_role')) {
    function telegram_role($role, $message, $parseMode = 'HTML') {
        try {
            $bot = telegram();
            return $bot->sendMessage('role', $role, $message, $parseMode);
        } catch (Exception $e) {
            error_log("telegram_role error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

/**
 * Send message to department
 */
if (!function_exists('telegram_department')) {
    function telegram_department($department, $message, $parseMode = 'HTML') {
        try {
            $bot = telegram();
            return $bot->sendMessage('department', $department, $message, $parseMode);
        } catch (Exception $e) {
            error_log("telegram_department error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

/**
 * Send message to group
 */
if (!function_exists('telegram_group')) {
    function telegram_group($groupName, $message, $parseMode = 'HTML') {
        try {
            $bot = telegram();
            return $bot->sendMessage('group', $groupName, $message, $parseMode);
        } catch (Exception $e) {
            error_log("telegram_group error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}


/**
 * Send message to all users
 */
if (!function_exists('telegram_all')) {
    function telegram_all($message, $parseMode = 'HTML') {
        try {
            $bot = telegram();
            return $bot->sendMessage('all', null, $message, $parseMode);
        } catch (Exception $e) {
            error_log("telegram_all error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

/**
 * Send message based on flow status
 */
if (!function_exists('telegram_flow')) {
    function telegram_flow($status, $message, $parseMode = 'HTML') {
        try {
            $bot = telegram();
            return $bot->sendMessage('flow', $status, $message, $parseMode);
        } catch (Exception $e) {
            error_log("telegram_flow error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

/**
 * Send document to user
 */
if (!function_exists('telegram_send_document')) {
    function telegram_send_document($method, $recipient, $documentPath, $replyMsgId = null, $caption = null, $notification = false) {
        try {
            $bot = telegram();
            return $bot->sendDocument($method, $recipient, $documentPath, $replyMsgId, $caption, $notification);
        } catch (Exception $e) {
            error_log("telegram_send_document error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

/**
 * Send document to specific user
 */
if (!function_exists('telegram_user_document')) {
    function telegram_user_document($username, $documentPath, $caption = null, $notification = false) {
        return telegram_send_document('user', $username, $documentPath, null, $caption, $notification);
    }
}

/**
 * Send document to role
 */
if (!function_exists('telegram_role_document')) {
    function telegram_role_document($role, $documentPath, $caption = null, $notification = false) {
        return telegram_send_document('role', $role, $documentPath, null, $caption, $notification);
    }
}

/**
 * Send document to department
 */
if (!function_exists('telegram_department_document')) {
    function telegram_department_document($department, $documentPath, $caption = null, $notification = false) {
        return telegram_send_document('department', $department, $documentPath, null, $caption, $notification);
    }
}

/**
 * Send photo to user
 */
if (!function_exists('telegram_send_photo')) {
    function telegram_send_photo($method, $recipient, $photoPath, $replyMsgId = null, $caption = null, $notification = false) {
        try {
            $bot = telegram();
            return $bot->sendPhoto($method, $recipient, $photoPath, $replyMsgId, $caption, $notification);
        } catch (Exception $e) {
            error_log("telegram_send_photo error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

/**
 * Send photo to specific user
 */
if (!function_exists('telegram_user_photo')) {
    function telegram_user_photo($username, $photoPath, $caption = null, $notification = false) {
        return telegram_send_photo('user', $username, $photoPath, null, $caption, $notification);
    }
}

/**
 * Send photo to role
 */
if (!function_exists('telegram_role_photo')) {
    function telegram_role_photo($role, $photoPath, $caption = null, $notification = false) {
        return telegram_send_photo('role', $role, $photoPath, null, $caption, $notification);
    }
}

/**
 * Send photo to department
 */
if (!function_exists('telegram_department_photo')) {
    function telegram_department_photo($department, $photoPath, $caption = null, $notification = false) {
        return telegram_send_photo('department', $department, $photoPath, null, $caption, $notification);
    }
}

// ============== ADVANCED TELEGRAM HELPER FUNCTIONS ==============

/**
 * Send notification to current user
 */
if (!function_exists('telegram_notify_me')) {
    function telegram_notify_me($message, $parseMode = 'HTML') {
        $username = session('username');
        if (!$username) {
            return ['error' => 'User not authenticated'];
        }
        
        return telegram_user($username, $message, $parseMode);
    }
}

/**
 * Send notification to user's role
 */
if (!function_exists('telegram_notify_my_role')) {
    function telegram_notify_my_role($message, $parseMode = 'HTML') {
        $role = session('role');
        if (!$role) {
            return ['error' => 'User role not found'];
        }
        
        return telegram_role($role, $message, $parseMode);
    }
}

/**
 * Send notification to user's department
 */
if (!function_exists('telegram_notify_my_department')) {
    function telegram_notify_my_department($message, $parseMode = 'HTML') {
        $department = session('department');
        if (!$department) {
            return ['error' => 'User department not found'];
        }
        
        return telegram_department($department, $message, $parseMode);
    }
}

/**
 * Send formatted notification message
 */
if (!function_exists('telegram_notify')) {
    function telegram_notify($method, $recipient, $title, $message, $priority = 'normal') {
        $priorityEmojis = [
            'low' => '📘',
            'normal' => '📋',
            'high' => '⚠️',
            'urgent' => '🚨'
        ];
        
        $emoji = $priorityEmojis[$priority] ?? '📋';
        $appName = app_name();
        $timestamp = date('Y-m-d H:i:s');
        
        $formattedMessage = "<b>{$emoji} {$title}</b>\n\n";
        $formattedMessage .= "{$message}\n\n";
        $formattedMessage .= "<i>📱 {$appName}</i>\n";
        $formattedMessage .= "<i>🕐 {$timestamp}</i>";
        
        return telegram()->sendMessage($method, $recipient, $formattedMessage, 'HTML');
    }
}

/**
 * Send system alert
 */
if (!function_exists('telegram_system_alert')) {
    function telegram_system_alert($message, $priority = 'high') {
        return telegram_notify('role', 'admin', 'System Alert', $message, $priority);
    }
}

/**
 * Send error notification to admins
 */
if (!function_exists('telegram_error_alert')) {
    function telegram_error_alert($error, $context = '') {
        $message = "<b>🔥 System Error Detected</b>\n\n";
        $message .= "<code>{$error}</code>\n\n";
        
        if ($context) {
            $message .= "<b>Context:</b>\n{$context}\n\n";
        }
        
        $message .= "<b>Server:</b> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\n";
        $message .= "<b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
        $message .= "<b>User:</b> " . (session('username') ?? 'Anonymous');
        
        return telegram_role('admin', $message);
    }
}

/**
 * Send task notification
 */
if (!function_exists('telegram_task_notify')) {
    function telegram_task_notify($username, $taskTitle, $taskDescription, $dueDate = null) {
        $message = "<b>📋 New Task Assigned</b>\n\n";
        $message .= "<b>Task:</b> {$taskTitle}\n";
        $message .= "<b>Description:</b> {$taskDescription}\n";
        
        if ($dueDate) {
            $message .= "<b>Due Date:</b> {$dueDate}\n";
        }
        
        $message .= "\n<i>Please check your dashboard for more details.</i>";
        
        return telegram_user($username, $message);
    }
}

/**
 * Send project update notification
 */
if (!function_exists('telegram_project_update')) {
    function telegram_project_update($method, $recipient, $projectName, $status, $details = '') {
        $statusEmojis = [
            'started' => '🚀',
            'in_progress' => '⚠️',
            'completed' => '✅',
            'on_hold' => '⏸️',
            'cancelled' => '❌'
        ];
        
        $emoji = $statusEmojis[$status] ?? '📋';
        
        $message = "<b>{$emoji} Project Update</b>\n\n";
        $message .= "<b>Project:</b> {$projectName}\n";
        $message .= "<b>Status:</b> " . ucfirst(str_replace('_', ' ', $status)) . "\n";
        
        if ($details) {
            $message .= "<b>Details:</b> {$details}\n";
        }
        
        $message .= "\n<i>Check the project dashboard for complete information.</i>";
        
        return telegram()->sendMessage($method, $recipient, $message);
    }
}

/**
 * Send approval request notification
 */
if (!function_exists('telegram_approval_request')) {
    function telegram_approval_request($approverUsername, $requesterName, $itemType, $itemTitle, $approvalUrl = null) {
        $message = "<b>📋 Approval Required</b>\n\n";
        $message .= "<b>Requested by:</b> {$requesterName}\n";
        $message .= "<b>Type:</b> " . ucfirst($itemType) . "\n";
        $message .= "<b>Item:</b> {$itemTitle}\n\n";
        
        if ($approvalUrl) {
            $message .= "<a href='{$approvalUrl}'>🔗 View Details & Approve</a>\n\n";
        }
        
        $message .= "<i>Your approval is needed to proceed.</i>";
        
        return telegram_user($approverUsername, $message);
    }
}

/**
 * Send welcome message to new user
 */
if (!function_exists('telegram_welcome_user')) {
    function telegram_welcome_user($username, $fullName, $role, $department) {
        $appName = app_name();
        $companyName = app_company();
        
        $message = "<b>🎉 Welcome to {$appName}!</b>\n\n";
        $message .= "Hello <b>{$fullName}</b>,\n\n";
        $message .= "Your account has been successfully created:\n";
        $message .= "<b>👤 Username:</b> {$username}\n";
        $message .= "<b>🎯 Role:</b> {$role}\n";
        $message .= "<b>🏢 Department:</b> {$department}\n\n";
        $message .= "You can now access all the features available for your role.\n\n";
        
        if ($companyName) {
            $message .= "<i>Welcome to {$companyName} team! 🚀</i>";
        }
        
        return telegram_user($username, $message);
    }
}

/**
 * Send maintenance notification
 */
if (!function_exists('telegram_maintenance_alert')) {
    function telegram_maintenance_alert($startTime, $endTime, $description = 'System maintenance') {
        $message = "<b>🔧 Scheduled Maintenance</b>\n\n";
        $message .= "<b>📅 Start:</b> {$startTime}\n";
        $message .= "<b>📅 End:</b> {$endTime}\n";
        $message .= "<b>📝 Details:</b> {$description}\n\n";
        $message .= "<i>The system may be unavailable during this period.</i>";
        
        return telegram_all($message);
    }
}

/**
 * Send backup completion notification to admins
 */
if (!function_exists('telegram_backup_complete')) {
    function telegram_backup_complete($backupType, $fileSize = null, $duration = null) {
        $message = "<b>💾 Backup Completed Successfully</b>\n\n";
        $message .= "<b>Type:</b> {$backupType}\n";
        $message .= "<b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
        
        if ($fileSize) {
            $message .= "<b>Size:</b> {$fileSize}\n";
        }
        
        if ($duration) {
            $message .= "<b>Duration:</b> {$duration}\n";
        }
        
        $message .= "\n<i>✅ System backup completed without errors.</i>";
        
        return telegram_role('admin', $message);
    }
}

/**
 * Send custom formatted message with templates
 */
if (!function_exists('telegram_template')) {
    function telegram_template($method, $recipient, $template, $variables = []) {
        $templates = [
            'login_alert' => "<b>🔐 Login Alert</b>\n\nUser: <b>{username}</b>\nTime: {time}\nIP: {ip}\nDevice: {device}",
            'password_changed' => "<b>🔑 Password Changed</b>\n\nYour password has been successfully updated.\nTime: {time}\n\nIf this wasn't you, please contact support immediately.",
            'deadline_reminder' => "<b>⏰ Deadline Reminder</b>\n\nTask: <b>{task}</b>\nDue: <b>{due_date}</b>\n\nDon't forget to complete your task!",
            'meeting_reminder' => "<b>📅 Meeting Reminder</b>\n\nMeeting: <b>{title}</b>\nTime: {time}\nLocation: {location}\n\nSee you there!",
            'report_ready' => "<b>📊 Report Ready</b>\n\nReport: <b>{report_name}</b>\nGenerated: {time}\n\nYour report is ready for download."
        ];
        
        if (!isset($templates[$template])) {
            return ['error' => 'Template not found'];
        }
        
        $message = $templates[$template];
        
        // Replace variables in template
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return telegram()->sendMessage($method, $recipient, $message);
    }
}

/**
 * Quick login alert
 */
if (!function_exists('telegram_login_alert')) {
    function telegram_login_alert($username, $ip = null, $device = null) {
        $variables = [
            'username' => $username,
            'time' => date('Y-m-d H:i:s'),
            'ip' => $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'device' => $device ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        return telegram_template('user', $username, 'login_alert', $variables);
    }
}

/**
 * Quick deadline reminder
 */
if (!function_exists('telegram_deadline_reminder')) {
    function telegram_deadline_reminder($username, $taskName, $dueDate) {
        $variables = [
            'task' => $taskName,
            'due_date' => $dueDate
        ];
        
        return telegram_template('user', $username, 'deadline_reminder', $variables);
    }
}

// ============== TELEGRAM CONFIGURATION HELPERS ==============

/**
 * Check if Telegram is configured
 */
if (!function_exists('telegram_configured')) {
    function telegram_configured() {
        $token = config('telegram.bot_token');
        return !empty($token);
    }
}

/**
 * Get Telegram bot info
 */
if (!function_exists('telegram_bot_info')) {
    function telegram_bot_info() {
        if (!telegram_configured()) {
            return ['error' => 'Telegram bot not configured'];
        }
        
        return [
            'token' => config('telegram.bot_token'),
            'bot_name' => config('telegram.bot_name'),
            'chat_id' => config('telegram.chat_id')
        ];
    }
}

/**
 * Test Telegram connection
 */
if (!function_exists('telegram_test')) {
    function telegram_test($testMessage = 'Test message from APP system') {
        if (!telegram_configured()) {
            return ['error' => 'Telegram bot not configured'];
        }
        
        $username = session('username');
        if (!$username) {
            return ['error' => 'No user logged in for test'];
        }
        
        return telegram_user($username, $testMessage);
    }
}

if (!function_exists('sendAccountActivationEmail')) {
    /**
     * Send account activation email with verification code using existing Brevo service
     * 
     * @param string $email User's email address
     * @param string $verificationCode 6-digit verification code
     * @param string $firstName User's first name
     * @return bool Success status
     */
    function sendAccountActivationEmail($email, $verificationCode, $firstName = '') {
        try {
            $subject = app_name() . ' - Aktifkan Akaun Anda';
            $displayName = $firstName ? $firstName : 'Pengguna';
            
            // HTML email template - enhanced for account activation
            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f8f9fa; padding: 20px;'>
                <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <h2 style='color: #2c3e50; margin: 0;'>Aktifkan Akaun Anda</h2>
                        <p style='color: #6c757d; margin: 5px 0;'>" . app_name() . "</p>
                    </div>
                    
                    <p style='color: #2c3e50;'>Assalamualaikum <strong>{$displayName}</strong>,</p>
                    
                    <p style='color: #495057; line-height: 1.6;'>Akaun anda di " . app_name() . " perlu diaktifkan. Sila gunakan kod pengesahan di bawah untuk mengaktifkan akaun anda:</p>
                    
                    <div style='background: linear-gradient(135deg, #007bff, #0056b3); padding: 25px; text-align: center; margin: 25px 0; border-radius: 8px;'>
                        <div style='color: white; font-size: 36px; font-weight: bold; letter-spacing: 8px; font-family: monospace;'>{$verificationCode}</div>
                        <p style='color: #cce7ff; margin: 10px 0 0 0; font-size: 14px;'>Kod Pengesahan Akaun</p>
                    </div>
                    
                    <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                        <h4 style='color: #856404; margin: 0 0 10px 0;'>📋 Penting:</h4>
                        <ul style='color: #856404; margin: 0; padding-left: 20px; line-height: 1.6;'>
                            <li>Kod ini akan <strong>tamat tempoh dalam 10 minit</strong></li>
                            <li>Jangan kongsi kod ini dengan sesiapa</li>
                            <li>Jika anda tidak meminta kod ini, sila abaikan email ini</li>
                        </ul>
                    </div>
                    
                    <p style='color: #495057; line-height: 1.6;'>Selepas memasukkan kod pengesahan, akaun anda akan diaktifkan dan anda boleh log masuk seperti biasa.</p>
                    
                    <p style='color: #495057; line-height: 1.6;'>Jika anda menghadapi sebarang masalah, sila hubungi pasukan sokongan kami.</p>
                    
                    <hr style='border: none; border-top: 1px solid #dee2e6; margin: 30px 0;'>
                    
                    <p style='color: #2c3e50;'>Terima kasih,<br>
                    <strong>Pasukan " . app_name() . "</strong></p>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; text-align: center;'>
                        <p style='margin: 0;'>Email ini dijana secara automatik. Sila jangan balas email ini.</p>
                        <p style='margin: 5px 0 0 0;'>&copy; " . date('Y') . " " . app_name() . ". Hak cipta terpelihara.</p>
                    </div>
                </div>
            </div>";
            
            // Plain text version
            $textContent = "Aktifkan Akaun Anda - " . app_name() . "

Assalamualaikum {$displayName},

Akaun anda di " . app_name() . " perlu diaktifkan. Sila gunakan kod pengesahan di bawah:

KOD PENGESAHAN: {$verificationCode}

PENTING:
- Kod ini akan tamat tempoh dalam 10 minit
- Jangan kongsi kod ini dengan sesiapa
- Jika anda tidak meminta kod ini, sila abaikan email ini

Selepas memasukkan kod pengesahan, akaun anda akan diaktifkan.

Terima kasih,
Pasukan " . app_name() . "

---
Email ini dijana secara automatik. Sila jangan balas email ini.
            ";
            
            // Use your existing Brevo email service
            $result = send_email([
                'to' => $email,
                'subject' => $subject,
                'htmlContent' => $htmlContent,
                'textContent' => $textContent
            ]);
            
            // Return boolean for compatibility
            return isset($result['success']) ? $result['success'] : false;
            
        } catch (Exception $e) {
            error_log("Send account activation email error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('store_temp_verification')) {
    /**
     * Store verification code using existing auth.verification_codes table
     * 
     * @param string $userId User ID
     * @param string $code Verification code
     * @param string $type Verification type (ignored - uses existing table structure)
     * @param int $expirySeconds Expiry time in seconds (default: 600 = 10 minutes)
     * @return bool Success status
     */
    function store_temp_verification($userId, $code, $type = 'account_activation', $expirySeconds = 600) {
        try {
            if (!function_exists('db')) return false;
            
            $conn = db();
            
            // Use PostgreSQL syntax for your existing table
            $expiresAt = date('Y-m-d H:i:s', time() + $expirySeconds);
            
            $stmt = $conn->prepare("
                INSERT INTO auth.verification_codes (user_id, code, expires_at, created_at, updated_at)
                VALUES (:user_id, :code, :expires_at, NOW(), NOW())
                ON CONFLICT (user_id) DO UPDATE SET
                    code = EXCLUDED.code,
                    expires_at = EXCLUDED.expires_at,
                    updated_at = NOW()
            ");
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':code' => $code,
                ':expires_at' => $expiresAt
            ]);
            
        } catch (Exception $e) {
            error_log("Store verification code error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('verify_temp_code')) {
    /**
     * Verify verification code using existing auth.verification_codes table
     * 
     * @param string $userId User ID
     * @param string $code Verification code to check
     * @param string $type Verification type (ignored - uses existing table)
     * @return array Result with success status and message
     */
    function verify_temp_code($userId, $code, $type = 'account_activation') {
        try {
            if (!function_exists('db')) {
                return ['success' => false, 'message' => 'Database not available'];
            }
            
            $conn = db();
            
            // Find valid verification record
            $stmt = $conn->prepare("
                SELECT * FROM auth.verification_codes 
                WHERE user_id = :user_id 
                AND code = :code 
                AND expires_at > NOW()
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':code' => $code
            ]);
            
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verification) {
                return ['success' => false, 'message' => 'Kod pengesahan tidak sah atau telah tamat tempoh'];
            }
            
            // Delete used verification code
            $deleteStmt = $conn->prepare("DELETE FROM auth.verification_codes WHERE id = :id");
            $deleteStmt->execute([':id' => $verification['id']]);
            
            return ['success' => true, 'message' => 'Kod pengesahan sah'];
            
        } catch (Exception $e) {
            error_log("Verify verification code error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ralat sistem semasa mengesahkan kod'];
        }
    }
}

if (!function_exists('activate_user_account')) {
    /**
     * Activate user account after successful verification
     * 
     * @param string $userId User ID
     * @return array Result with success status and message
     */
    function activate_user_account($userId) {
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
                // Log the activation
                if (function_exists('log_user_activity')) {
                    log_user_activity("User account activated via email verification");
                }
                
                // Clean up verification codes for this user
                $cleanupStmt = $conn->prepare("DELETE FROM auth.verification_codes WHERE user_id = :user_id");
                $cleanupStmt->execute([':user_id' => $userId]);
                
                return ['success' => true, 'message' => 'Akaun berjaya diaktifkan'];
            } else {
                return ['success' => false, 'message' => 'Pengguna tidak dijumpai atau akaun sudah aktif'];
            }
            
        } catch (Exception $e) {
            error_log("Activate user account error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ralat sistem semasa mengaktifkan akaun'];
        }
    }
}

if (!function_exists('cleanup_expired_verifications')) {
    /**
     * Clean up expired verification codes using existing table
     * 
     * @return int Number of cleaned up records
     */
    function cleanup_expired_verifications() {
        try {
            if (!function_exists('db')) return 0;
            
            $conn = db();
            
            $stmt = $conn->prepare("DELETE FROM auth.verification_codes WHERE expires_at < NOW()");
            $stmt->execute();
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Cleanup expired verifications error: " . $e->getMessage());
            return 0;
        }
    }
}