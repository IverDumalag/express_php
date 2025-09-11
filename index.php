<?php
require_once 'config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request URI and method
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove base path if any
$request_uri = str_replace('/index.php', '', $request_uri);
if ($request_uri === '') $request_uri = '/';

// Route handlers
switch ($request_uri) {
    case '/':
        handleRoot();
        break;
    
    case '/health':
        handleHealth();
        break;
    
    case '/wake':
        handleWake();
        break;
        
    case '/api/status':
        handleApiStatus();
        break;
        
    default:
        // Check if it's an API route
        if (strpos($request_uri, '/api/') === 0) {
            handleApiProxy($request_uri);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
}

// ================= Keep-Alive & Health Endpoints =================

function handleRoot() {
    global $mysqli;
    
    $db_status = 'connected';
    if ($mysqli->connect_errno) {
        $db_status = 'error: ' . $mysqli->connect_error;
    }
    
    $response = [
        'service' => 'FSL Express PHP Backend',
        'status' => 'alive',
        'timestamp' => date('c'),
        'database' => $db_status,
        'php_version' => phpversion(),
        'memory_usage' => memory_get_usage(true),
        'version' => '1.0.0'
    ];
    
    echo json_encode($response);
}

function handleHealth() {
    global $mysqli;
    
    $healthy = true;
    $checks = [];
    
    // Database health check
    if ($mysqli->connect_errno) {
        $healthy = false;
        $checks['database'] = 'failed';
    } else {
        $checks['database'] = 'ok';
    }
    
    // Memory check (warn if over 128MB)
    $memory = memory_get_usage(true);
    $checks['memory'] = $memory < (128 * 1024 * 1024) ? 'ok' : 'high';
    
    http_response_code($healthy ? 200 : 503);
    echo json_encode([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'timestamp' => date('c'),
        'service' => 'express-php',
        'checks' => $checks
    ]);
}

function handleWake() {
    $now = date('c');
    error_log("🌅 Wake-up ping received at $now");
    
    echo json_encode([
        'message' => 'Service is awake!',
        'timestamp' => $now,
        'status' => 'active'
    ]);
}

function handleApiStatus() {
    global $mysqli;
    
    // Get API endpoints count
    $api_dir = __DIR__ . '/api';
    $api_files = 0;
    if (is_dir($api_dir)) {
        $api_files = count(glob($api_dir . '/*.php'));
    }
    
    echo json_encode([
        'api_endpoints' => $api_files,
        'database_status' => $mysqli->connect_errno ? 'error' : 'connected',
        'timestamp' => date('c')
    ]);
}

function handleApiProxy($request_uri) {
    // Extract API file name from URI
    $api_path = str_replace('/api/', '', $request_uri);
    $api_file = __DIR__ . '/api/' . $api_path . '.php';
    
    if (file_exists($api_file)) {
        // Include the API file
        include $api_file;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
    }
}
?>