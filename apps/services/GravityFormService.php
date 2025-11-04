<?php
/**
 * Gravity Forms API Service
 * File: apps/services/GravityFormService.php
 * 
 * Handles all interactions with Gravity Forms REST API
 * Uses the http() helper functions from core/helpers/http.php
 */

class GravityFormService
{
    private $baseUrl;
    private $consumerKey;
    private $consumerSecret;
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - loads config from environment
     */
    private function __construct()
    {
        $this->baseUrl = gf_config('api_url');
        $this->consumerKey = gf_config('key');
        $this->consumerSecret = gf_config('secret');
        
        if (empty($this->baseUrl) || empty($this->consumerKey) || empty($this->consumerSecret)) {
            throw new Exception('Gravity Forms API credentials not configured. Check your .env file.');
        }
        
        // Ensure base URL ends with proper path
        $this->baseUrl = rtrim($this->baseUrl, '/');
        if (!str_contains($this->baseUrl, '/wp-json/gf/v2')) {
            $this->baseUrl .= '/wp-json/gf/v2';
        }
    }
    
    /**
     * Build authorization headers
     */
    private function getAuthHeaders()
    {
        $auth = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        return [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
    }
    
    /**
     * Make authenticated request to Gravity Forms API
     */
    private function request($endpoint, $method = 'GET', $data = null)
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        // Build request options with auth headers
        $options = [
            'headers' => $this->getAuthHeaders(),
            'timeout' => 30
        ];
        
        try {
            $response = null;
            
            switch (strtoupper($method)) {
                case 'POST':
                    $response = http_post($url, json_encode($data), $options);
                    break;
                    
                case 'PUT':
                    $response = http_put($url, json_encode($data), $options);
                    break;
                    
                case 'DELETE':
                    $response = http_delete($url, $options);
                    break;
                    
                case 'GET':
                default:
                    $response = http_get($url, $options);
                    break;
            }
            
            // Check if request was successful
            if (!is_success_response($response)) {
                $errorMsg = 'API Request failed';
                if (isset($response['error'])) {
                    $errorMsg .= ': ' . $response['error'];
                }
                throw new Exception($errorMsg);
            }
            
            // Parse JSON response
            return response_json($response, []);
            
        } catch (Exception $e) {
            error_log('Gravity Forms API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all forms
     */
    public function getForms()
    {
        return $this->request('forms');
    }
    
    /**
     * Get specific form by ID
     */
    public function getForm($formId)
    {
        return $this->request("forms/{$formId}");
    }
    
    /**
     * Get entries for a specific form
     */
    public function getFormEntries($formId, $options = [])
    {
        $endpoint = "forms/{$formId}/entries";
        
        if (!empty($options)) {
            $endpoint .= '?' . build_query($options);
        }
        
        return $this->request($endpoint);
    }
    
    /**
     * Get all entries (across all forms)
     */
    public function getAllEntries($options = [])
    {
        $endpoint = 'entries';
        
        if (!empty($options)) {
            $endpoint .= '?' . build_query($options);
        }
        
        return $this->request($endpoint);
    }
    
    /**
     * Get specific entry by ID
     */
    public function getEntry($entryId)
    {
        return $this->request("entries/{$entryId}");
    }
    
    /**
     * Create new entry
     */
    public function createEntry($formId, $data)
    {
        return $this->request("forms/{$formId}/entries", 'POST', $data);
    }
    
    /**
     * Update existing entry
     */
    public function updateEntry($entryId, $data)
    {
        return $this->request("entries/{$entryId}", 'PUT', $data);
    }
    
    /**
     * Delete entry
     */
    public function deleteEntry($entryId)
    {
        return $this->request("entries/{$entryId}", 'DELETE');
    }
    
    /**
     * Get form fields mapping (field ID => label)
     */
    public function getFieldMapping($formId)
    {
        $form = $this->getForm($formId);
        $mapping = [];
        
        if (isset($form['fields']) && is_array($form['fields'])) {
            foreach ($form['fields'] as $field) {
                $fieldId = $field['id'] ?? null;
                $fieldLabel = $field['label'] ?? 'Field ' . $fieldId;
                
                if ($fieldId) {
                    $mapping[$fieldId] = $fieldLabel;
                    
                    // Handle fields with inputs (like name, address)
                    if (isset($field['inputs']) && is_array($field['inputs'])) {
                        foreach ($field['inputs'] as $input) {
                            $inputId = $input['id'] ?? null;
                            $inputLabel = $input['label'] ?? $input['name'] ?? '';
                            
                            if ($inputId) {
                                $mapping[$inputId] = $fieldLabel . ' - ' . $inputLabel;
                            }
                        }
                    }
                }
            }
        }
        
        return $mapping;
    }
    
    /**
     * Search entries with criteria
     */
    public function searchEntries($search = [])
    {
        $params = [];
        
        if (isset($search['form_id'])) {
            $params['form_id'] = $search['form_id'];
        }
        
        if (isset($search['status'])) {
            $params['status'] = $search['status'];
        }
        
        if (isset($search['field_filters'])) {
            $params['search'] = json_encode($search['field_filters']);
        }
        
        if (isset($search['page'])) {
            $params['paging[current_page]'] = $search['page'];
        }
        
        if (isset($search['per_page'])) {
            $params['paging[page_size]'] = $search['per_page'];
        }
        
        if (isset($search['sorting'])) {
            $params['sorting'] = $search['sorting'];
        }
        
        return $this->getAllEntries($params);
    }
    
    /**
     * Get entry notes
     */
    public function getEntryNotes($entryId)
    {
        return $this->request("entries/{$entryId}/notes");
    }
    
    /**
     * Add note to entry
     */
    public function addEntryNote($entryId, $note)
    {
        return $this->request("entries/{$entryId}/notes", 'POST', [
            'value' => $note
        ]);
    }
    
    /**
     * Test API connection
     */
    public function testConnection()
    {
        try {
            $forms = $this->getForms();
            return [
                'success' => true,
                'message' => 'Successfully connected to Gravity Forms API',
                'forms_count' => is_array($forms) ? count($forms) : 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get API configuration
     */
    public function getConfig()
    {
        return [
            'url' => $this->baseUrl,
            'has_key' => !empty($this->consumerKey),
            'has_secret' => !empty($this->consumerSecret)
        ];
    }
}