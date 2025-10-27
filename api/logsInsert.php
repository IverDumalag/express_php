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

try {
    // Generate unique log ID
    $log_id = uniqid('log_');
    
    $success = false;
    if ($useSupabase) {
        // Supabase insert - use JSONB directly
        $data = [
            'log_id' => $log_id,
            'user_id' => $user_id,
            'email' => $email,
            'user_role' => $user_role,
            'action_type' => $action_type,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'old_data' => isset($input['old_data']) ? $input['old_data'] : null,
            'new_data' => isset($input['new_data']) ? $input['new_data'] : null
        ];
        $result = supabaseRequest('tbl_log_history', 'POST', $data);
        $success = !isset($result['error']);
    } else {
        // MySQL insert - encode JSON as string
        $old_data = isset($input['old_data']) ? json_encode($input['old_data']) : null;
        $new_data = isset($input['new_data']) ? json_encode($input['new_data']) : null;
        
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
        
        $success = $log_stmt->execute();
        if (!$success) {
            throw new Exception("Failed to execute statement: " . $log_stmt->error);
        }
        
        $log_stmt->close();
        $mysqli->close();
    }
    
    if ($success) {
        http_response_code(200);
        echo json_encode([
            'status' => 200, 
            'message' => 'Log entry created successfully',
            'log_id' => $log_id
        ]);
    } else {
        throw new Exception("Failed to insert log entry");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 500, 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>