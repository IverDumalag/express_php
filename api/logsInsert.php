<?php

require_once __DIR__ . '/../config.php';

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Method Not Allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
$required_fields = ['user_id', 'email', 'user_role', 'action_type'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => "Field '{$field}' is required"]);
        exit();
    }
}

$user_id = $input['user_id'];
$email = $input['email'];
$user_role = $input['user_role'];
$action_type = $input['action_type'];
$object_type = $input['object_type'] ?? null;
$object_id = $input['object_id'] ?? null;
$old_data = isset($input['old_data']) ? json_encode($input['old_data']) : null;
$new_data = isset($input['new_data']) ? json_encode($input['new_data']) : null;

try {
    // Generate unique log ID like in userLogin.php
    $log_id = uniqid('log_');
    
    // Insert log entry into tbl_log_history
    $log_stmt = $mysqli->prepare("INSERT INTO tbl_log_history (log_id, user_id, email, user_role, action_type, object_type, object_id, old_data, new_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$log_stmt) {
        throw new Exception("Failed to prepare statement: " . $mysqli->error);
    }
    
    $log_stmt->bind_param(
        "sssssssss",
        $log_id,
        $user_id,
        $email,
        $user_role,
        $action_type,
        $object_type,
        $object_id,
        $old_data,
        $new_data
    );
    
    if (!$log_stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $log_stmt->error);
    }
    
    $log_stmt->close();
    
    http_response_code(200);
    echo json_encode([
        'status' => 200, 
        'message' => 'Log entry created successfully',
        'log_id' => $log_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 500, 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
} finally {
    $mysqli->close();
}
?>