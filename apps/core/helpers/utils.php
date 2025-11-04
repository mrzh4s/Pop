<?php


// ============== UTILITIES HELPER FUNCTIONS ==============

/**
 * Main utilities helper function
 * 
 * Usage:
 * util('ref_no', 'ABC12345')
 * util('generate_code', 'username')
 * util('date_malay', '2024-01-15')
 */
if (!function_exists('util')) {
    function util($action, $data = null, $option = null, $authId = null) {
        try {
            $instance = Utilities::getInstance();
            
            switch ($action) {
                case 'ref_no':
                case 'reference':
                    return $instance->getRefNo($data);
                
                case 'extract_id':
                case 'system_extract':
                    return $instance->extractSystemId($data, $option ?? 'digits');
                
                case 'generate_code':
                case 'code':
                    return $instance->generateCode($data);
                
                case 'generate_entry':
                    return $instance->generateEntry($data ?? 8);
                
                case 'status':
                    return $instance->getStatus($data, $option, $authId);
                
                case 'check_token':
                case 'validate_token':
                    return $instance->checkDomainToken($data, $option);
                
                case 'date_malay':
                case 'malay_date':
                    return $instance->convertDateToMalay($data);
                
                case 'time_malay':
                case 'malay_time':
                    return $instance->formatTimeToMalayTimeString($data);
                
                case 'provider':
                    return $instance->getProvider($data);
                
                case 'authority_name':
                case 'authority':
                    return $instance->getAuthorityName($data);
                
                case 'current_status':
                    return $instance->getCurrentStatus($data, $option);
                
                case 'system_info':
                case 'info':
                    return $instance->getSystemInfo($data);
                
                case 'validate_id':
                case 'valid_id':
                    return $instance->validateSystemId($data);
                
                case 'random_string':
                case 'random':
                    return $instance->generateRandomString($data ?? 10, $option);
                
                default:
                    throw new InvalidArgumentException("Unknown utility action: {$action}");
            }
        } catch (Exception $e) {
            error_log("Utility helper error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get reference number
 * 
 * Usage:
 * $refNo = get_ref_no('ABC12345')
 */
if (!function_exists('get_ref_no')) {
    function get_ref_no($systemId) {
        return util('ref_no', $systemId);
    }
}

/**
 * Extract system ID parts
 * 
 * Usage:
 * $digits = extract_system_id('ABC12345', 'digits')
 * $year = extract_system_id('ABC12345', 'year')
 * $running = extract_system_id('ABC12345', 'running')
 */
if (!function_exists('extract_system_id')) {
    function extract_system_id($systemId, $method = 'digits') {
        return util('extract_id', $systemId, $method);
    }
}

/**
 * Generate activation code
 * 
 * Usage:
 * $code = generate_activation_code()
 * $code = generate_activation_code('username')
 */
if (!function_exists('generate_code')) {
    function generate_code($username = null) {
        return util('generate_code', $username);
    }
}

/**
 * Generate letter ID
 * 
 * Usage:
 * $letterId = generate_entry()
 * $letterId = generate_entry(10)
 */
if (!function_exists('generate_entry')) {
    function generate_entry($length = 8) {
        return util('generate_entry', $length);
    }
}

/**
 * Convert date to Malay format
 * 
 * Usage:
 * $malayDate = to_malay_date('2024-01-15') // "15 Januari 2024"
 */
if (!function_exists('to_malay_date')) {
    function to_malay_date($dateString) {
        return util('date_malay', $dateString);
    }
}

/**
 * Convert time to Malay format
 * 
 * Usage:
 * $malayTime = to_malay_time('2024-01-15 14:30:00') // "2:30 PM"
 */
if (!function_exists('to_malay_time')) {
    function to_malay_time($dateTimeString) {
        return util('time_malay', $dateTimeString);
    }
}

/**
 * Get provider information
 * 
 * Usage:
 * $provider = get_provider(123)
 * echo $provider->name
 */
if (!function_exists('get_provider')) {
    function get_provider($providerId) {
        return util('provider', $providerId);
    }
}

/**
 * Get authority name
 * 
 * Usage:
 * $authorityName = get_authority_name(456)
 */
if (!function_exists('get_authority_name')) {
    function get_authority_name($authorityId) {
        return util('authority_name', $authorityId);
    }
}

/**
 * Check domain token
 * 
 * Usage:
 * if (check_domain_token($token, $domain)) { ... }
 */
if (!function_exists('check_domain_token')) {
    function check_domain_token($token, $domain) {
        return util('check_token', $token, $domain);
    }
}

/**
 * Get system status
 * 
 * Usage:
 * $status = get_system_status('ABC12345', 'main')
 * $status = get_system_status('ABC12345', 'main', 123)
 */
if (!function_exists('get_system_status')) {
    function get_system_status($systemId, $flowGroup, $authId = null) {
        return util('status', $systemId, $flowGroup, $authId);
    }
}

/**
 * Get current status
 * 
 * Usage:
 * $status = get_current_status('ABC12345', 'department')
 * $status = get_current_status('ABC12345', 'department', 123)
 */
if (!function_exists('get_current_status')) {
    function get_current_status($systemId, $department, $authority = null) {
        return util('current_status', $systemId, $department, $authority);
    }
}

/**
 * Validate system ID format
 * 
 * Usage:
 * if (is_valid_system_id('ABC12345')) { ... }
 */
if (!function_exists('is_valid_system_id')) {
    function is_valid_system_id($systemId) {
        return util('validate_id', $systemId);
    }
}

/**
 * Get system information
 * 
 * Usage:
 * $info = get_system_info('ABC12345')
 */
if (!function_exists('get_system_info')) {
    function get_system_info($systemId) {
        return util('system_info', $systemId);
    }
}

/**
 * Generate random string
 * 
 * Usage:
 * $random = random_string(10)
 * $random = random_string(8, 'ABCDEF123456')
 */
if (!function_exists('random_string')) {
    function random_string($length = 10, $characters = null) {
        return util('random_string', $length, $characters);
    }
}

/**
 * Utilities helper with fluent interface
 * 
 * Usage:
 * $info = utilities()->system('ABC12345')->getInfo();
 * $date = utilities()->date('2024-01-15')->toMalay();
 */
if (!function_exists('utilities')) {
    function utilities($systemId = null) {
        return new UtilitiesHelper($systemId);
    }
}
