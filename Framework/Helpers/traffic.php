<?php

use Framework\Log\Traffic;

// ============== TRAFFIC HELPER FUNCTIONS ==============

/**
 * Main traffic helper function
 * 
 * Usage:
 * traffic('log', ['traffic' => 'outbound', 'url' => $url, ...])
 * traffic('stats')
 * traffic('errors')
 * traffic('cleanup', ['days' => 30])
 */
if (!function_exists('traffic')) {
    function traffic($action, $data = []) {
        try {
            $instance = Traffic::getInstance();
            
            switch ($action) {
                case 'log':
                case 'request':
                    return $instance->requestAPI(
                        $data['traffic'],
                        $data['url'],
                        $data['method'],
                        $data['headers'],
                        $data['body'],
                        $data['response'],
                        $data['status']
                    );
                
                case 'get':
                case 'all':
                    return $instance->getAPI($data);
                
                case 'stats':
                case 'statistics':
                    return $instance->getStats($data['period'] ?? '24h');
                
                case 'errors':
                    return $instance->getErrors($data['limit'] ?? 100);
                
                case 'endpoint':
                    return $instance->getByEndpoint($data['endpoint'], $data['limit'] ?? 50);
                
                case 'cleanup':
                    return $instance->cleanup($data['days'] ?? 30);
                
                case 'dashboard':
                    return $instance->getDashboard();
                
                case 'export':
                    return $instance->export($data['filters'] ?? [], $data['format'] ?? 'json');
                
                default:
                    throw new Exception("Unknown traffic action: {$action}");
            }
            
        } catch (Exception $e) {
            error_log("Traffic helper error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get traffic statistics
 * 
 * Usage:
 * $stats = traffic_stats()
 * $stats = traffic_stats('7d')
 */
if (!function_exists('traffic_stats')) {
    function traffic_stats($period = '24h') {
        return traffic('stats', ['period' => $period]);
    }
}

/**
 * Get traffic errors
 * 
 * Usage:
 * $errors = traffic_errors()
 * $errors = traffic_errors(50)
 */
if (!function_exists('traffic_errors')) {
    function traffic_errors($limit = 100) {
        return traffic('errors', ['limit' => $limit]);
    }
}

/**
 * Get traffic dashboard
 * 
 * Usage:
 * $dashboard = traffic_dashboard()
 */
if (!function_exists('traffic_dashboard')) {
    function traffic_dashboard() {
        return traffic('dashboard');
    }
}

/**
 * Log API traffic manually
 * 
 * Usage:
 * log_traffic('outbound', $url, 'POST', $headers, $body, $response, 'success')
 */
if (!function_exists('log_traffic')) {
    function log_traffic($traffic, $url, $method, $headers, $body, $response, $status) {
        return traffic('log', [
            'traffic' => $traffic,
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
            'response' => $response,
            'status' => $status
        ]);
    }
}

/**
 * Clean old traffic logs
 * 
 * Usage:
 * clean_traffic_logs()
 * clean_traffic_logs(7) // Keep only 7 days
 */
if (!function_exists('clean_traffic_logs')) {
    function clean_traffic_logs($daysToKeep = 30) {
        return traffic('cleanup', ['days' => $daysToKeep]);
    }
}

/**
 * Get traffic by endpoint
 * 
 * Usage:
 * $logs = traffic_endpoint('/api/users')
 * $logs = traffic_endpoint('/api/users', 25)
 */
if (!function_exists('traffic_endpoint')) {
    function traffic_endpoint($endpoint, $limit = 50) {
        return traffic('endpoint', ['endpoint' => $endpoint, 'limit' => $limit]);
    }
}

/**
 * Export traffic data
 * 
 * Usage:
 * $json = export_traffic()
 * $csv = export_traffic(['method' => 'POST'], 'csv')
 */
if (!function_exists('export_traffic')) {
    function export_traffic($filters = [], $format = 'json') {
        return traffic('export', ['filters' => $filters, 'format' => $format]);
    }
}

/**
 * Get filtered traffic logs
 * 
 * Usage:
 * $logs = get_traffic(['traffic' => 'outbound', 'limit' => 100])
 * $logs = get_traffic(['method' => 'POST', 'status' => 'success'])
 */
if (!function_exists('get_traffic')) {
    function get_traffic($filters = []) {
        return traffic('get', $filters);
    }
}

/**
 * Traffic helper with fluent interface
 * 
 * Usage:
 * $logs = traffic_query()->outbound()->method('POST')->errors()->get();
 * $stats = traffic_query()->period('7d')->stats();
 */
if (!function_exists('traffic_query')) {
    function traffic_query() {
        return new Framework\Log\Traits\TrafficQueryBuilder();
    }
}

