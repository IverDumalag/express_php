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
if ($useSupabase) {
    // Test Supabase connection
    $test_result = supabaseRequest('tbl_users', 'GET', null, null, 'user_id', true);
    if (isset($test_result['error'])) {
        $wake_data['database'] = 'connection_error';
        $wake_data['status'] = 'degraded';
        $wake_data['database_type'] = 'supabase';
    } else {
        $wake_data['database'] = 'ready';
        $wake_data['database_type'] = 'supabase';
        $wake_data['database_time'] = $now;
    }
} else {
    // Test MySQL connection
    if ($mysqli->connect_errno) {
        $wake_data['database'] = 'connection_error';
        $wake_data['status'] = 'degraded';
        $wake_data['database_type'] = 'mysql';
    } else {
        $wake_data['database'] = 'ready';
        $wake_data['database_type'] = 'mysql';
        
        // Test a simple query to warm up the connection
        $result = $mysqli->query("SELECT NOW() as server_time");
        if ($result) {
            $row = $result->fetch_assoc();
            $wake_data['database_time'] = $row['server_time'];
            $result->close();
        }
    }
}

// Add some system info
$wake_data['php_version'] = phpversion();
$wake_data['memory_usage'] = memory_get_usage(true);

echo json_encode($wake_data, JSON_PRETTY_PRINT);
?>
