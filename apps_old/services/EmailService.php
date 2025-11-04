<?php
/**
 * Simple Brevo Email Service
 * File: apps/core/BrevoEmailService.php
 * 
 * Handles email sending via Brevo (formerly Sendinblue) API
 * Uses your existing HTTP helpers and system functions
 */

class EmailService
{
    private string $apiKey;
    private string $baseUrl;
    private array $defaultHeaders;

    public function __construct()
    {
        // Get API key from environment or config
        $this->apiKey = $_ENV['BREVO_API_KEY'] ?? getenv('BREVO_API_KEY') ?? '';
        $this->baseUrl = $_ENV['BREVO_BASE_URL'] ?? 'https://api.brevo.com/v3';
        
        // Validate API key
        if (empty($this->apiKey)) {
            throw new Exception('Brevo API key is not configured. Please set BREVO_API_KEY in your .env file.');
        }
        
        $this->defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'api-key' => $this->apiKey,
        ];
    }

    /**
     * Send a single email using Brevo API
     */
    public function sendEmail(array $emailData): array
    {
        try {
            $payload = $this->buildEmailPayload($emailData);
            
            // Log the email attempt
            error_log("Sending email via Brevo to: " . implode(', ', $this->getRecipientEmails($payload['to'])));

            // Use your existing HTTP helper
            $response = http_post($this->baseUrl . '/smtp/email', $payload, [
                'headers' => $this->defaultHeaders,
                'timeout' => 30
            ]);

            if ($response['success'] && isset($response['data'])) {
                $responseData = is_string($response['data']) ? json_decode($response['data'], true) : $response['data'];
                $messageId = $responseData['messageId'] ?? null;
                
                error_log("Email sent successfully via Brevo. Message ID: " . $messageId);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'response' => $responseData
                ];
            }

            $error = is_string($response['data']) ? json_decode($response['data'], true) : $response['data'];
            error_log("Brevo email send failed: " . ($error['message'] ?? 'Unknown error'));

            return [
                'success' => false,
                'error' => $error['message'] ?? 'Email sending failed',
                'response' => $error
            ];

        } catch (Exception $e) {
            error_log("Brevo email service exception: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send multiple emails
     */
    public function sendBulkEmail(array $recipients, string $subject, string $htmlContent, string $textContent = '', array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;
        
        foreach ($recipients as $recipient) {
            $emailData = [
                'to' => is_array($recipient) ? $recipient : ['email' => $recipient],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
                'textContent' => $textContent,
                'sender' => $options['sender'] ?? $this->getDefaultSender(),
                'replyTo' => $options['reply_to'] ?? null,
                'tags' => $options['tags'] ?? [],
            ];

            $result = $this->sendEmail($emailData);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
            
            $results[] = array_merge($result, [
                'recipient' => is_array($recipient) ? $recipient['email'] : $recipient
            ]);
        }

        return [
            'success' => $successCount > 0,
            'results' => $results,
            'summary' => [
                'total' => count($recipients),
                'success' => $successCount,
                'failed' => $failCount
            ]
        ];
    }

    /**
     * Get email delivery status from Brevo
     */
    public function getEmailStatus(string $messageId): array
    {
        try {
            $response = http_get($this->baseUrl . '/smtp/emails/' . $messageId, [
                'headers' => $this->defaultHeaders,
                'timeout' => 15
            ]);

            if ($response['success']) {
                return [
                    'success' => true,
                    'status' => is_string($response['data']) ? json_decode($response['data'], true) : $response['data']
                ];
            }

            return [
                'success' => false,
                'error' => 'Unable to get email status'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test Brevo connection and API key
     */
    public function testConnection(): array
    {
        try {
            $response = http_get($this->baseUrl . '/account', [
                'headers' => $this->defaultHeaders,
                'timeout' => 10
            ]);

            if ($response['success']) {
                $account = is_string($response['data']) ? json_decode($response['data'], true) : $response['data'];
                
                error_log("Brevo connection test successful for account: " . ($account['email'] ?? 'unknown'));

                return [
                    'success' => true,
                    'account' => $account,
                    'message' => 'Connection to Brevo is working correctly'
                ];
            }

            error_log("Brevo connection test failed");

            return [
                'success' => false,
                'error' => 'Connection test failed'
            ];

        } catch (Exception $e) {
            error_log("Brevo connection test exception: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create or update contact in Brevo
     */
    public function upsertContact(array $contactData): array
    {
        try {
            $response = http_post($this->baseUrl . '/contacts', $contactData, [
                'headers' => $this->defaultHeaders,
                'timeout' => 15
            ]);

            if ($response['success']) {
                return [
                    'success' => true,
                    'response' => is_string($response['data']) ? json_decode($response['data'], true) : $response['data']
                ];
            }

            // Handle duplicate contact (try update)
            if (isset($contactData['email'])) {
                return $this->updateContact($contactData['email'], $contactData);
            }

            return [
                'success' => false,
                'error' => 'Failed to create/update contact'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing contact in Brevo
     */
    private function updateContact(string $email, array $contactData): array
    {
        try {
            $response = http_put($this->baseUrl . '/contacts/' . $email, $contactData, [
                'headers' => $this->defaultHeaders,
                'timeout' => 15
            ]);

            if ($response['success']) {
                return [
                    'success' => true,
                    'response' => is_string($response['data']) ? json_decode($response['data'], true) : $response['data']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to update contact'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build email payload for Brevo API
     */
    private function buildEmailPayload(array $emailData): array
    {
        $payload = [
            'to' => $this->formatRecipients($emailData['to']),
            'subject' => $emailData['subject'],
        ];

        // Add sender information
        if (isset($emailData['sender'])) {
            $payload['sender'] = $emailData['sender'];
        } else {
            $payload['sender'] = $this->getDefaultSender();
        }

        // Add content
        if (isset($emailData['htmlContent']) && !empty($emailData['htmlContent'])) {
            $payload['htmlContent'] = $emailData['htmlContent'];
        }

        if (isset($emailData['textContent']) && !empty($emailData['textContent'])) {
            $payload['textContent'] = $emailData['textContent'];
        }

        // Add optional fields
        if (isset($emailData['cc']) && !empty($emailData['cc'])) {
            $payload['cc'] = $this->formatRecipients($emailData['cc']);
        }

        if (isset($emailData['bcc']) && !empty($emailData['bcc'])) {
            $payload['bcc'] = $this->formatRecipients($emailData['bcc']);
        }

        if (isset($emailData['replyTo']) && !empty($emailData['replyTo'])) {
            $payload['replyTo'] = $emailData['replyTo'];
        }

        if (isset($emailData['tags']) && !empty($emailData['tags'])) {
            $payload['tags'] = array_slice($emailData['tags'], 0, 10); // Brevo limit
        }

        if (isset($emailData['attachments']) && !empty($emailData['attachments'])) {
            $payload['attachment'] = $this->formatAttachments($emailData['attachments']);
        }

        return $payload;
    }

    /**
     * Format recipients for Brevo API
     */
    private function formatRecipients($recipients): array
    {
        if (!is_array($recipients)) {
            return [['email' => $recipients]];
        }

        // If it's already properly formatted
        if (isset($recipients['email'])) {
            return [$recipients];
        }

        // Format array of recipients
        $formatted = [];
        foreach ($recipients as $recipient) {
            if (is_string($recipient)) {
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    $formatted[] = ['email' => $recipient];
                }
            } elseif (is_array($recipient) && isset($recipient['email'])) {
                if (filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                    $formatted[] = $recipient;
                }
            }
        }

        return $formatted;
    }

    /**
     * Format attachments for Brevo API
     */
    private function formatAttachments(array $attachments): array
    {
        $formatted = [];
        
        foreach ($attachments as $attachment) {
            if (is_array($attachment) && isset($attachment['content'], $attachment['name'])) {
                $formatted[] = [
                    'content' => base64_encode($attachment['content']),
                    'name' => $attachment['name']
                ];
            } elseif (is_string($attachment) && file_exists($attachment)) {
                $formatted[] = [
                    'content' => base64_encode(file_get_contents($attachment)),
                    'name' => basename($attachment)
                ];
            }
        }
        
        return $formatted;
    }

    /**
     * Get recipient emails from formatted array
     */
    private function getRecipientEmails(array $recipients): array
    {
        return array_map(function($recipient) {
            return $recipient['email'] ?? 'unknown';
        }, $recipients);
    }

    /**
     * Get default sender configuration
     */
    private function getDefaultSender(): array
    {
        return [
            'name' => config('brevo.sender.name', config('app.name', 'KUTT System')),
            'email' => config('brevo.sender.email', 'noreply@kutt.my')
        ];
    }
}

