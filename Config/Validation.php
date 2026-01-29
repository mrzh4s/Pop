<?php

/**
 * Validation Configuration
 *
 * This file contains all validation-related configuration including
 * default error messages, attribute names, file validation settings,
 * and sanitization options.
 */

return [
    /**
     * Default validation error messages
     *
     * Placeholders:
     *   :field - The field name
     *   :min, :max, :length - Rule parameters
     *   :other, :date, :mimes - Context-specific parameters
     */
    'messages' => [
        'required' => 'The :field field is required.',
        'email' => 'The :field must be a valid email address.',
        'numeric' => 'The :field must be a number.',
        'integer' => 'The :field must be an integer.',
        'string' => 'The :field must be a string.',
        'min' => 'The :field must be at least :min.',
        'max' => 'The :field may not be greater than :max.',
        'url' => 'The :field must be a valid URL.',
        'alpha' => 'The :field may only contain letters.',
        'alpha_numeric' => 'The :field may only contain letters and numbers.',
        'length' => 'The :field must be exactly :length characters.',
        'regex' => 'The :field format is invalid.',
        'same' => 'The :field must match :other.',
        'different' => 'The :field must be different from :other.',
        'confirmed' => 'The :field confirmation does not match.',
        'in' => 'The selected :field is invalid.',
        'not_in' => 'The selected :field is invalid.',
        'date' => 'The :field is not a valid date.',
        'before_date' => 'The :field must be a date before :date.',
        'after_date' => 'The :field must be a date after :date.',
        'unique' => 'The :field has already been taken.',
        'exists' => 'The selected :field is invalid.',
        'file' => 'The :field must be a file.',
        'mimes' => 'The :field must be a file of type: :mimes.',
        'array' => 'The :field must be an array.',
        'boolean' => 'The :field must be true or false.',
    ],

    /**
     * Custom attribute names for more user-friendly error messages
     *
     * Example: Instead of "The email field is required"
     *          Display "The email address is required"
     */
    'attributes' => [
        'email' => 'email address',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'first_name' => 'first name',
        'last_name' => 'last name',
        'phone' => 'phone number',
        'address' => 'address',
        'city' => 'city',
        'state' => 'state',
        'zip' => 'ZIP code',
        'country' => 'country',
        'dob' => 'date of birth',
    ],

    /**
     * File validation defaults
     */
    'files' => [
        /**
         * Maximum file size in kilobytes
         */
        'max_size' => (int) env('MAX_FILE_SIZE', 10240), // 10MB default

        /**
         * Allowed MIME types by category
         */
        'allowed_mimes' => [
            'image' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
            ],
            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'text/csv',
            ],
            'video' => [
                'video/mp4',
                'video/mpeg',
                'video/quicktime',
                'video/x-msvideo',
                'video/webm',
            ],
            'audio' => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/webm',
            ],
            'archive' => [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-tar',
                'application/gzip',
            ],
        ],

        /**
         * Allowed file extensions
         */
        'allowed_extensions' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'],
            'video' => ['mp4', 'mpeg', 'mov', 'avi', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'webm'],
            'archive' => ['zip', 'rar', 'tar', 'gz'],
        ],
    ],

    /**
     * Sanitization configuration
     */
    'sanitize' => [
        /**
         * Enable automatic sanitization for all input
         * (Usually applied via middleware)
         */
        'auto_sanitize' => (bool) env('AUTO_SANITIZE_INPUT', false),

        /**
         * Default sanitization type
         */
        'default_type' => 'string',

        /**
         * Sanitization options
         */
        'options' => [
            'trim' => true,
            'strip_tags' => false, // Disabled by default to preserve HTML
            'escape_html' => true,
        ],
    ],

    /**
     * Validation behavior
     */
    'behavior' => [
        /**
         * Stop validation on first error
         * If true, validation stops after the first rule failure
         * If false, all rules are checked and all errors are collected
         */
        'stop_on_first_error' => false,

        /**
         * Error message format
         * Options: 'array', 'object'
         */
        'error_format' => 'array',
    ],
];
