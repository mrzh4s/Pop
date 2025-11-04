<?php
/**
 * Gravity Forms Helper Functions
 * File: apps/services/helpers/gravityforms.php
 * 
 * Provides convenient helper functions for accessing Gravity Forms API
 */

// ============== SERVICE INSTANCE ==============

if (!function_exists('gf')) {
    /**
     * Get Gravity Forms service instance
     * 
     * Usage:
     * $gf = gf();
     * $forms = $gf->getForms();
     */
    function gf() {
        return GravityFormService::getInstance();
    }
}

// ============== FORMS ==============

if (!function_exists('gf_forms')) {
    /**
     * Get all forms
     * 
     * Usage:
     * $forms = gf_forms();
     * 
     * Returns:
     * Array of form objects with id, title, fields, etc.
     */
    function gf_forms() {
        try {
            return gf()->getForms();
        } catch (Exception $e) {
            error_log('gf_forms() error: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('gf_form')) {
    /**
     * Get specific form by ID
     * 
     * Usage:
     * $form = gf_form(3);
     * echo $form['title'];
     * 
     * Returns:
     * Form object with fields, settings, etc.
     */
    function gf_form($formId) {
        try {
            return gf()->getForm($formId);
        } catch (Exception $e) {
            error_log('gf_form() error: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('gf_form_title')) {
    /**
     * Get form title
     * 
     * Usage:
     * echo gf_form_title(3); // "Contact Form"
     */
    function gf_form_title($formId) {
        $form = gf_form($formId);
        return $form['title'] ?? "Form {$formId}";
    }
}

// ============== ENTRIES ==============

if (!function_exists('gf_entries')) {
    /**
     * Get entries for a specific form
     * 
     * Usage:
     * $entries = gf_entries(3);
     * $entries = gf_entries(3, ['paging[page_size]' => 50]);
     * $entries = gf_entries(3, [
     *     'paging[current_page]' => 1,
     *     'paging[page_size]' => 20,
     *     'sorting[key]' => 'date_created',
     *     'sorting[direction]' => 'DESC'
     * ]);
     * 
     * Returns:
     * Response with 'entries', 'total_count', etc.
     */
    function gf_entries($formId, $options = []) {
        try {
            return gf()->getFormEntries($formId, $options);
        } catch (Exception $e) {
            error_log('gf_entries() error: ' . $e->getMessage());
            return ['entries' => [], 'total_count' => 0];
        }
    }
}

if (!function_exists('gf_all_entries')) {
    /**
     * Get all entries across all forms
     * 
     * Usage:
     * $entries = gf_all_entries();
     * $entries = gf_all_entries(['paging[page_size]' => 100]);
     * 
     * Returns:
     * Response with all entries from all forms
     */
    function gf_all_entries($options = []) {
        try {
            return gf()->getAllEntries($options);
        } catch (Exception $e) {
            error_log('gf_all_entries() error: ' . $e->getMessage());
            return ['entries' => [], 'total_count' => 0];
        }
    }
}

if (!function_exists('gf_entry')) {
    /**
     * Get specific entry by ID
     * 
     * Usage:
     * $entry = gf_entry(1234);
     * echo $entry['1']; // Access field 1
     * 
     * Returns:
     * Entry object with all field values
     */
    function gf_entry($entryId) {
        try {
            return gf()->getEntry($entryId);
        } catch (Exception $e) {
            error_log('gf_entry() error: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('gf_entry_value')) {
    /**
     * Get specific field value from entry
     * 
     * Usage:
     * $name = gf_entry_value(1234, '1');
     * $email = gf_entry_value(1234, '2', 'N/A');
     */
    function gf_entry_value($entryId, $fieldId, $default = '') {
        $entry = gf_entry($entryId);
        return $entry[$fieldId] ?? $default;
    }
}

if (!function_exists('gf_create_entry')) {
    /**
     * Create new entry
     * 
     * Usage:
     * $result = gf_create_entry(3, [
     *     '1' => 'John Doe',
     *     '2' => 'john@example.com',
     *     '3' => 'This is a message'
     * ]);
     */
    function gf_create_entry($formId, $data) {
        try {
            return gf()->createEntry($formId, $data);
        } catch (Exception $e) {
            error_log('gf_create_entry() error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('gf_update_entry')) {
    /**
     * Update existing entry
     * 
     * Usage:
     * $result = gf_update_entry(1234, ['1' => 'Updated Name']);
     */
    function gf_update_entry($entryId, $data) {
        try {
            return gf()->updateEntry($entryId, $data);
        } catch (Exception $e) {
            error_log('gf_update_entry() error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('gf_delete_entry')) {
    /**
     * Delete entry
     * 
     * Usage:
     * $result = gf_delete_entry(1234);
     */
    function gf_delete_entry($entryId) {
        try {
            return gf()->deleteEntry($entryId);
        } catch (Exception $e) {
            error_log('gf_delete_entry() error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

// ============== SEARCH ==============

if (!function_exists('gf_search')) {
    /**
     * Search entries with criteria
     * 
     * Usage:
     * $results = gf_search(['form_id' => 3, 'status' => 'active']);
     * $results = gf_search([
     *     'form_id' => 3,
     *     'status' => 'active',
     *     'page' => 1,
     *     'per_page' => 20,
     *     'field_filters' => [
     *         ['key' => '1', 'value' => 'John']
     *     ]
     * ]);
     */
    function gf_search($criteria = []) {
        try {
            return gf()->searchEntries($criteria);
        } catch (Exception $e) {
            error_log('gf_search() error: ' . $e->getMessage());
            return ['entries' => [], 'total_count' => 0];
        }
    }
}

// ============== FIELDS ==============

if (!function_exists('gf_fields')) {
    /**
     * Get field mapping for a form (field ID => label)
     * 
     * Usage:
     * $fields = gf_fields(3);
     * // ['1' => 'Name', '2' => 'Email', '3' => 'Message']
     * 
     * foreach ($fields as $id => $label) {
     *     echo "Field {$id}: {$label}\n";
     * }
     */
    function gf_fields($formId) {
        try {
            return gf()->getFieldMapping($formId);
        } catch (Exception $e) {
            error_log('gf_fields() error: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('gf_field_label')) {
    /**
     * Get field label by ID
     * 
     * Usage:
     * echo gf_field_label(3, '1'); // "Name"
     */
    function gf_field_label($formId, $fieldId) {
        $fields = gf_fields($formId);
        return $fields[$fieldId] ?? "Field {$fieldId}";
    }
}

// ============== NOTES ==============

if (!function_exists('gf_notes')) {
    /**
     * Get entry notes
     * 
     * Usage:
     * $notes = gf_notes(1234);
     */
    function gf_notes($entryId) {
        try {
            return gf()->getEntryNotes($entryId);
        } catch (Exception $e) {
            error_log('gf_notes() error: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('gf_add_note')) {
    /**
     * Add note to entry
     * 
     * Usage:
     * $result = gf_add_note(1234, 'This is a note');
     */
    function gf_add_note($entryId, $note) {
        try {
            return gf()->addEntryNote($entryId, $note);
        } catch (Exception $e) {
            error_log('gf_add_note() error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

// ============== UTILITIES ==============

if (!function_exists('gf_test')) {
    /**
     * Test Gravity Forms API connection
     * 
     * Usage:
     * $test = gf_test();
     * if ($test['success']) {
     *     echo "Connected! Found {$test['forms_count']} forms";
     * }
     */
    function gf_test() {
        return gf()->testConnection();
    }
}

if (!function_exists('gf_config')) {
    /**
     * Get Gravity Forms configuration
     * 
     * Usage:
     * $config = gf_config();       // Get all config
     * $url = gf_config('url');     // Get specific value
     */
    function gf_config($key = null) {
        if ($key) {
            return config("gf.{$key}");
        }
        return config('gf');
    }
}

if (!function_exists('gf_status')) {
    /**
     * Get service status and configuration info
     * 
     * Usage:
     * $status = gf_status();
     * print_r($status);
     */
    function gf_status() {
        try {
            $service = gf();
            $test = $service->testConnection();
            $config = $service->getConfig();
            
            return [
                'configured' => $config['has_key'] && $config['has_secret'],
                'connected' => $test['success'],
                'url' => $config['url'],
                'message' => $test['message'],
                'forms_count' => $test['forms_count'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'configured' => false,
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// ============== PAGINATION HELPERS ==============

if (!function_exists('gf_paginate')) {
    /**
     * Helper to build pagination options
     * 
     * Usage:
     * $options = gf_paginate(1, 20);
     * $entries = gf_entries(3, $options);
     */
    function gf_paginate($page = 1, $perPage = 20) {
        return [
            'paging[current_page]' => max(1, $page),
            'paging[page_size]' => min(200, max(1, $perPage))
        ];
    }
}

if (!function_exists('gf_sort')) {
    /**
     * Helper to build sorting options
     * 
     * Usage:
     * $options = gf_sort('date_created', 'DESC');
     * $entries = gf_entries(3, $options);
     */
    function gf_sort($key = 'date_created', $direction = 'DESC') {
        return [
            'sorting[key]' => $key,
            'sorting[direction]' => strtoupper($direction)
        ];
    }
}

// ============== ENTRY STATUS HELPERS ==============

if (!function_exists('gf_active_entries')) {
    /**
     * Get active entries only
     * 
     * Usage:
     * $entries = gf_active_entries(3);
     */
    function gf_active_entries($formId, $options = []) {
        $options['status'] = 'active';
        return gf_entries($formId, $options);
    }
}

if (!function_exists('gf_trash_entries')) {
    /**
     * Get trashed entries
     * 
     * Usage:
     * $entries = gf_trash_entries(3);
     */
    function gf_trash_entries($formId, $options = []) {
        $options['status'] = 'trash';
        return gf_entries($formId, $options);
    }
}

if (!function_exists('gf_spam_entries')) {
    /**
     * Get spam entries
     * 
     * Usage:
     * $entries = gf_spam_entries(3);
     */
    function gf_spam_entries($formId, $options = []) {
        $options['status'] = 'spam';
        return gf_entries($formId, $options);
    }
}

// ============== COUNT HELPERS ==============

if (!function_exists('gf_count_entries')) {
    /**
     * Get total count of entries for a form
     * 
     * Usage:
     * $count = gf_count_entries(3);
     */
    function gf_count_entries($formId, $status = 'active') {
        $result = gf_entries($formId, [
            'status' => $status,
            'paging[page_size]' => 1
        ]);
        
        return $result['total_count'] ?? 0;
    }
}

if (!function_exists('gf_count_forms')) {
    /**
     * Get total count of forms
     * 
     * Usage:
     * $count = gf_count_forms();
     */
    function gf_count_forms() {
        $forms = gf_forms();
        return is_array($forms) ? count($forms) : 0;
    }
}