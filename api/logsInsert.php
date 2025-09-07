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

// Get the input data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Invalid JSON']);
    exit();
}

// Validate required fields
$required_fields = ['user_id', 'email', 'user_role', 'action_type'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$user_id = trim($data['user_id']);
$email = trim($data['email']);
$user_role = trim($data['user_role']);
$action_type = trim($data['action_type']);
$object_type = isset($data['object_type']) ? trim($data['object_type']) : null;
$object_id = isset($data['object_id']) ? trim($data['object_id']) : null;
$old_data = isset($data['old_data']) ? json_encode($data['old_data']) : null;
$new_data = isset($data['new_data']) ? json_encode($data['new_data']) : null;

try {
    $stmt = $mysqli->prepare("INSERT INTO tbl_log_history (user_id, email, user_role, action_type, object_type, object_id, old_data, new_data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("ssssssss", $user_id, $email, $user_role, $action_type, $object_type, $object_id, $old_data, $new_data);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $log_id = $mysqli->insert_id;
    
    echo json_encode([
        'status' => 200,
        'message' => 'Log entry created successfully',
        'log_id' => $log_id
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Failed to create log entry: ' . $e->getMessage()
    ]);
}

$mysqli->close();
?>
