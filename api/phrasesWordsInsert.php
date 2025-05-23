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

$required = ['words', 'user_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => "Missing field: $field"]);
        exit();
    }
}

$entry_id = uniqid('pw_');
$words = $input['words'];
$is_favorite = isset($input['is_favorite']) ? (int)$input['is_favorite'] : 0;
$is_match = isset($input['is_match']) ? (int)$input['is_match'] : 0;
$sign_language = isset($input['sign_language']) ? $input['sign_language'] : null;
$status = isset($input['status']) ? $input['status'] : 'active';
$user_id = $input['user_id'];

$stmt = $mysqli->prepare("INSERT INTO tbl_phrases_words (entry_id, words, is_favorite, is_match, sign_language, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "ssiisss",
    $entry_id,
    $words,
    $is_favorite,
    $is_match,
    $sign_language,
    $status,
    $user_id
);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(['status' => 201, 'message' => 'Phrase/word inserted successfully', 'entry_id' => $entry_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>