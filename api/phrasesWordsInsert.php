<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/encryption_util.php';

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

// Decrypt incoming data from frontend
$decryptedInput = EncryptionUtil::decryptArray($input, ['words']);

$required = ['words', 'user_id'];
foreach ($required as $field) {
    if (empty($decryptedInput[$field])) {
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => "Missing field: $field"]);
        exit();
    }
}

$entry_id = uniqid('pw_');

// Encrypt the words before storing in database
$encryptedWords = EncryptionUtil::encrypt($decryptedInput['words']);

$is_favorite = isset($decryptedInput['is_favorite']) ? (int)$decryptedInput['is_favorite'] : 0;
$is_match = isset($decryptedInput['is_match']) ? (int)$decryptedInput['is_match'] : 0;
$sign_language = isset($decryptedInput['sign_language']) ? $decryptedInput['sign_language'] : null;
$status = isset($decryptedInput['status']) ? $decryptedInput['status'] : 'active';
$user_id = $decryptedInput['user_id'];

$stmt = $mysqli->prepare("INSERT INTO tbl_phrases_words (entry_id, words, is_favorite, is_match, sign_language, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "ssiisss",
    $entry_id,
    $encryptedWords,
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