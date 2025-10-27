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

if (empty($input['user_id'])) {
   http_response_code(422);
   echo json_encode(['status' => 422, 'message' => 'Missing user_id']);
   exit();
}

$user_id = $input['user_id'];

$success = false;
if ($useSupabase) {
    // Supabase delete
    $result = supabaseRequest('tbl_audiotext_phrases_words', 'DELETE', null, ['user_id' => 'eq.' . $user_id]);
    $success = !isset($result['error']);
} else {
    // MySQL delete
    $stmt = $mysqli->prepare("DELETE FROM tbl_audiotext_phrases_words WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $success = $stmt->execute();
    if (!$success) {
        $error_msg = $stmt->error;
    }
    $stmt->close();
    $mysqli->close();
}

if ($success) {
   http_response_code(200);
   echo json_encode(['status' => 200, 'message' => 'All audio phrases/words deleted for user']);
} else {
   http_response_code(500);
   echo json_encode(['status' => 500, 'message' => 'Database error: ' . ($error_msg ?? 'Unknown error')]);
}
?>