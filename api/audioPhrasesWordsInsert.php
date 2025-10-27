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

// --- MODIFIED HERE ---
// 'sign_language' is no longer a strictly required field; it can be empty.
$required = ['words', 'user_id'];
foreach ($required as $field) {
    if (!isset($input[$field]) || $input[$field] === '') { // Check for existence and non-empty for 'words', 'user_id'
        http_response_code(422);
        echo json_encode(['status' => 422, 'message' => "Missing or empty field: $field"]);
        exit();
    }
}
// --- END MODIFICATION ---

$entry_id = uniqid('apw_');
$words = $input['words'];
$sign_language = isset($input['sign_language']) ? $input['sign_language'] : ''; // Ensure it defaults to empty string if not provided
$is_match_input = isset($input['is_match']) ? $input['is_match'] : 0;
$user_id = $input['user_id'];
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');

$success = false;
if ($useSupabase) {
    // Supabase insert - use boolean directly
    $is_match = (bool)$is_match_input;
    $data = [
        'entry_id' => $entry_id,
        'words' => $words,
        'sign_language' => $sign_language,
        'is_match' => $is_match,
        'created_at' => $created_at,
        'updated_at' => $updated_at,
        'user_id' => $user_id
    ];
    $result = supabaseRequest('tbl_audiotext_phrases_words', 'POST', $data);
    $success = !isset($result['error']);
} else {
    // MySQL insert - convert to integer
    $is_match = (int)$is_match_input;
    $stmt = $mysqli->prepare("INSERT INTO tbl_audiotext_phrases_words (entry_id, words, sign_language, is_match, created_at, updated_at, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $entry_id, $words, $sign_language, $is_match, $created_at, $updated_at, $user_id);
    $success = $stmt->execute();
    if (!$success) {
        $error_msg = $stmt->error;
    }
    $stmt->close();
    $mysqli->close();
}

if ($success) {
    http_response_code(201);
    echo json_encode(['status' => 201, 'message' => 'Audio phrase inserted successfully', 'entry_id' => $entry_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Database error: ' . ($error_msg ?? 'Unknown error')]);
}
?>