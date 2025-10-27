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

if (!$input || empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Email and password required']);
    exit();
}

$email = $input['email'];
$password = $input['password'];

$user = null;
$login_success = false;
$user_id = null;
$user_role = null;
$log_new_data = null;

if ($useSupabase) {
    // Supabase implementation
    $result = supabaseRequest('tbl_users', 'GET', null, ['email' => 'eq.' . $email], '*', true);
    
    if (!isset($result['error']) && $result && isset($result['user_id'])) {
        $user = $result;
    }
} else {
    // MySQL implementation
    $stmt = $mysqli->prepare("SELECT * FROM tbl_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

if ($user && password_verify($password, $user['password'])) {
    $login_success = true;
    $user_id = $user['user_id'];
    $user_role = $user['role'];
    unset($user['password']);
    $log_new_data = json_encode(['user_id' => $user_id, 'email' => $email, 'role' => $user_role]);
    http_response_code(200);
    echo json_encode(['status' => 200, 'message' => 'Login successful', 'user' => $user]);
} else {
    // If user exists, log with their id/role, else log as guest
    if ($user) {
        $user_id = $user['user_id'];
        $user_role = $user['role'];
    } else {
        $user_id = 'guest';
        $user_role = 'guest';
    }
    $log_new_data = json_encode(['email' => $email]);
    http_response_code(401);
    echo json_encode(['status' => 401, 'message' => 'Invalid credentials']);
}

// Log the login attempt
$log_id = uniqid('log_');
$action_type = 'login';
$object_type = 'tbl_users';
$object_id = $user_id;
$old_data = null;

if ($useSupabase) {
    // Supabase log insert
    $logData = [
        'log_id' => $log_id,
        'user_id' => $user_id,
        'email' => $email,
        'user_role' => $user_role,
        'action_type' => $action_type,
        'object_type' => $object_type,
        'object_id' => $object_id,
        'old_data' => $old_data,
        'new_data' => json_decode($log_new_data, true)
    ];
    supabaseRequest('tbl_log_history', 'POST', $logData);
} else {
    // MySQL log insert
    $log_stmt = $mysqli->prepare("INSERT INTO tbl_log_history (log_id, user_id, email, user_role, action_type, object_type, object_id, old_data, new_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
        $log_new_data
    );
    $log_stmt->execute();
    $log_stmt->close();
    $mysqli->close();
}
?>