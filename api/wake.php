<?php
require_once '../config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$now = date('c');
error_log("🌅 API Wake-up ping received at $now");

$wake_data = [
    'message' => 'API service is awake!',
    'timestamp' => $now,
    'status' => 'active',
    'service' => 'express-php-api'
];

// Test database to ensure it's ready
if ($mysqli->connect_errno) {
    $wake_data['database'] = 'connection_error';
    $wake_data['status'] = 'degraded';
} else {
    $wake_data['database'] = 'ready';
    
    // Test a simple query to warm up the connection
    $result = $mysqli->query("SELECT NOW() as server_time");
    if ($result) {
        $row = $result->fetch_assoc();
        $wake_data['database_time'] = $row['server_time'];
        $result->close();
    }
}

// Add some system info
$wake_data['php_version'] = phpversion();
$wake_data['memory_usage'] = memory_get_usage(true);

echo json_encode($wake_data, JSON_PRETTY_PRINT);
?>
