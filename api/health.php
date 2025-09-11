<?php
require_once '../config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// API Health Check
$health_data = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'service' => 'express-php-api'
];

// Test database connection
if ($mysqli->connect_errno) {
    $health_data['status'] = 'unhealthy';
    $health_data['database'] = 'connection_failed';
    $health_data['error'] = $mysqli->connect_error;
    http_response_code(503);
} else {
    $health_data['database'] = 'connected';
    
    // Test a simple query
    $result = $mysqli->query("SELECT 1 as test");
    if ($result) {
        $health_data['database_query'] = 'success';
        $result->close();
    } else {
        $health_data['database_query'] = 'failed';
        $health_data['status'] = 'degraded';
    }
}

// Add memory usage
$health_data['memory_usage'] = [
    'current' => memory_get_usage(true),
    'peak' => memory_get_peak_usage(true),
    'limit' => ini_get('memory_limit')
];

// Add PHP version
$health_data['php_version'] = phpversion();

echo json_encode($health_data, JSON_PRETTY_PRINT);
?>
