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

if (empty($input['entry_id']) || empty($input['status'])) {
    http_response_code(422);
    echo json_encode(['status' => 422, 'message' => 'Missing entry_id or status']);
    exit();
}

$entry_id = $input['entry_id'];
$status = $input['status'];
$updated_at = date('Y-m-d H:i:s');

$success = false;
if ($useSupabase) {
    // Supabase update
    $data = [
        'status' => $status,
        'updated_at' => $updated_at
    ];
    $result = supabaseRequest('tbl_phrases_words', 'PATCH', $data, ['entry_id' => 'eq.' . $entry_id]);
    $success = !isset($result['error']);
} else {
    // MySQL update
    $stmt = $mysqli->prepare("UPDATE tbl_phrases_words SET status = ?, updated_at = ? WHERE entry_id = ?");
    $stmt->bind_param("sss", $status, $updated_at, $entry_id);
    $success = $stmt->execute();
    if (!$success) {
        $error_msg = $stmt->error;
    }
    $stmt->close();
    $mysqli->close();
}

if ($success) {
    http_response_code(200);
    echo json_encode(['status' => 200, 'message' => 'Status updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Database error: ' . ($error_msg ?? 'Unknown error')]);
}
?>