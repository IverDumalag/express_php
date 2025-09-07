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
    echo json_encode(['status' => 400, 'message' => 'Invalid or missing JSON']);
    exit();
}

// Validate required fields
$required = ['email', 'f_name', 'l_name', 'sex', 'birthdate', 'password', 'role'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => "Missing field: $field"]);
        exit();
    }
}

// Generate user_id (e.g., US-0000001)
$result = $mysqli->query("SELECT user_id FROM tbl_users ORDER BY user_id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $lastIdNum = (int)substr($row['user_id'], 3);
    $newIdNum = $lastIdNum + 1;
} else {
    $newIdNum = 1;
}
$user_id = 'US-' . str_pad($newIdNum, 7, '0', STR_PAD_LEFT);

// Prepare and insert user
$stmt = $mysqli->prepare("INSERT INTO tbl_users (user_id, email, f_name, m_name, l_name, sex, birthdate, password, role, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);
$m_name = isset($input['m_name']) ? $input['m_name'] : null;
$updated_at = date('Y-m-d H:i:s');
$stmt->bind_param(
    "ssssssssss",
    $user_id,
    $input['email'],
    $input['f_name'],
    $m_name,
    $input['l_name'],
    $input['sex'],
    $input['birthdate'],
    $hashed_password,
    $input['role'],
    $updated_at
);

if ($stmt->execute()) {
    // Log the creation in tbl_log_history
    $log_id = uniqid('log_');
    $action_type = 'create';
    $object_type = 'tbl_users';
    $object_id = $user_id;
    $user_role = $input['role'];
    $email = $input['email'];
    $old_data = null;
    $new_data = json_encode([
        'user_id' => $user_id,
        'email' => $input['email'],
        'f_name' => $input['f_name'],
        'm_name' => $m_name,
        'l_name' => $input['l_name'],
        'sex' => $input['sex'],
        'birthdate' => $input['birthdate'],
        'role' => $input['role']
    ]);

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
        $new_data
    );
    $log_stmt->execute();
    $log_stmt->close();

    http_response_code(201);
    echo json_encode(['status' => 201, 'message' => 'User created successfully', 'user_id' => $user_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>