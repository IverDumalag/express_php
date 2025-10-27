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
$is_favorite = isset($input['is_favorite']) ? (bool)$input['is_favorite'] : false;
$is_match = isset($input['is_match']) ? (bool)$input['is_match'] : false;
$sign_language = isset($input['sign_language']) ? $input['sign_language'] : null;
$status = isset($input['status']) ? $input['status'] : 'active';
$user_id = $input['user_id'];
$updated_at = date('Y-m-d H:i:s');

$success = false;
if ($useSupabase) {
    // Supabase insert
    $data = [
        'entry_id' => $entry_id,
        'words' => $words,
        'is_favorite' => $is_favorite,
        'is_match' => $is_match,
        'sign_language' => $sign_language,
        'status' => $status,
        'user_id' => $user_id,
        'updated_at' => $updated_at
    ];
    $result = supabaseRequest('tbl_phrases_words', 'POST', $data);
    $success = !isset($result['error']);
} else {
    // MySQL insert (convert boolean to integer)
    $is_favorite_int = $is_favorite ? 1 : 0;
    $is_match_int = $is_match ? 1 : 0;
    
    $stmt = $mysqli->prepare("INSERT INTO tbl_phrases_words (entry_id, words, is_favorite, is_match, sign_language, status, user_id, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssiissss",
        $entry_id,
        $words,
        $is_favorite_int,
        $is_match_int,
        $sign_language,
        $status,
        $user_id,
        $updated_at
    );
    $success = $stmt->execute();
    if (!$success) {
        $error_msg = $stmt->error;
    }
    $stmt->close();
}

if ($success) {
    http_response_code(201);
    echo json_encode(['status' => 201, 'message' => 'Phrase/word inserted successfully', 'entry_id' => $entry_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Database error: ' . ($error_msg ?? 'Unknown error')]);
}

if (!$useSupabase) {
    $mysqli->close();
}
?>