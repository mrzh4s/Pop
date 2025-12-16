<?php
try {
    $healthData = [];
    $overallStatus = 'healthy';
    $httpCode = 200;
    
    try {
        $conn = db();
        $stmt = $conn->query("SELECT 1");
        $healthData['database'] = ['status' => 'connected'];
    } catch (Exception $e) {
        $healthData['database'] = ['status' => 'error', 'error' => $e->getMessage()];
        $overallStatus = 'unhealthy';
        $httpCode = 503;
    }
    
    // Session status
    $healthData['session'] = [
        'status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
        'id' => session_id() ? substr(session_id(), 0, 8) . '...' : 'none'
    ];
    
    // CSRF support
    $healthData['csrf'] = [
        'supported' => function_exists('csrf_token'),
        'token_exists' => session('csrf_token')
    ];
    
    // Using your existing app helpers
    $healthData['api'] = [
        'version' => '1.0.0',
        'environment' => app_env(),
        'debug_mode' => app_debug(),
        'timezone' => date_default_timezone_get(),
    ];
    
    json([
        'message' => $overallStatus === 'healthy' ? 'All systems operational' : 'System issues detected',
        'data' => [
            'health' => $overallStatus,
            'checks' => $healthData
        ]
        
    ], $httpCode);
    
} catch (Exception $e) {
    error_log("Health check error: " . $e->getMessage());
    json(['message' => 'Health check failed'], 503);
}