<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/encryption_util.php';

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Method Not Allowed']);
    exit();
}

if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Missing user_id']);
    exit();
}

$user_id = $_GET['user_id'];

$stmt = $mysqli->prepare("SELECT * FROM tbl_phrases_words WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$phrases = [];
while ($row = $result->fetch_assoc()) {
    // Decrypt the words field before sending to frontend
    if (isset($row['words']) && !empty($row['words'])) {
        $row['words'] = EncryptionUtil::decrypt($row['words']);
    }
    $phrases[] = $row;
}

$stmt->close();
$mysqli->close();

echo json_encode([
    'status' => 200,
    'message' => 'Phrases/words fetched successfully',
    'data' => $phrases
]);
?>