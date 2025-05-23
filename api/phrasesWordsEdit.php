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

if (empty($input['entry_id']) || empty($input['words'])) {
    http_response_code(422);
    echo json_encode(['status' => 422, 'message' => 'Missing entry_id or words']);
    exit();
}

$entry_id = $input['entry_id'];
$words = $input['words'];
$sign_language = isset($input['sign_language']) ? $input['sign_language'] : null;

$stmt = $mysqli->prepare("UPDATE tbl_phrases_words SET words = ?, sign_language = ? WHERE entry_id = ?");
$stmt->bind_param("sss", $words, $sign_language, $entry_id);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['status' => 200, 'message' => 'Phrase/word updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>