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

$required = ['user_id', 'email', 'main_concern', 'details'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => "Missing field: $field"]);
        exit();
    }
}

$feedback_id = uniqid('fb_');
$user_id = $input['user_id'];
$email = $input['email'];
$main_concern = $input['main_concern'];
$details = $input['details'];
$created_at = date('Y-m-d H:i:s');

$stmt = $mysqli->prepare("INSERT INTO tbl_feedback (feedback_id, user_id, email, main_concern, details, created_at) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $feedback_id, $user_id, $email, $main_concern, $details, $created_at);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(['status' => 201, 'message' => 'Feedback submitted successfully', 'feedback_id' => $feedback_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>