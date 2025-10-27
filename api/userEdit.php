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

$required = ['user_id', 'email', 'f_name', 'l_name', 'sex', 'birthdate'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => "Missing field: $field"]);
        exit();
    }
}

$user_id = $input['user_id'];
$email = $input['email'];
$f_name = $input['f_name'];
$m_name = isset($input['m_name']) ? $input['m_name'] : null;
$l_name = $input['l_name'];
$sex = $input['sex'];
$birthdate = $input['birthdate'];
$updated_at = date('Y-m-d H:i:s');

$success = false;
if ($useSupabase) {
    // Supabase update
    $data = [
        'email' => $email,
        'f_name' => $f_name,
        'm_name' => $m_name,
        'l_name' => $l_name,
        'sex' => $sex,
        'birthdate' => $birthdate,
        'updated_at' => $updated_at
    ];
    
    // If password is provided, update it as well
    if (!empty($input['password'])) {
        $data['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    
    $result = supabaseRequest('tbl_users', 'PATCH', $data, ['user_id' => 'eq.' . $user_id]);
    $success = !isset($result['error']);
} else {
    // MySQL update
    if (!empty($input['password'])) {
        $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE tbl_users SET email=?, f_name=?, m_name=?, l_name=?, sex=?, birthdate=?, password=?, updated_at=? WHERE user_id=?");
        $stmt->bind_param("sssssssss", $email, $f_name, $m_name, $l_name, $sex, $birthdate, $hashed_password, $updated_at, $user_id);
    } else {
        $stmt = $mysqli->prepare("UPDATE tbl_users SET email=?, f_name=?, m_name=?, l_name=?, sex=?, birthdate=?, updated_at=? WHERE user_id=?");
        $stmt->bind_param("ssssssss", $email, $f_name, $m_name, $l_name, $sex, $birthdate, $updated_at, $user_id);
    }
    $success = $stmt->execute();
    if (!$success) {
        $error_msg = $stmt->error;
    }
    $stmt->close();
    $mysqli->close();
}

if ($success) {
    http_response_code(200);
    echo json_encode(['status' => 200, 'message' => 'User updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Database error: ' . ($error_msg ?? 'Unknown error')]);
}
?>