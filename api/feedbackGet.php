<?php
require_once __DIR__ . '/../config.php';

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

$data = [];
if ($useSupabase) {
    // Supabase query with order by created_at descending
    $result = supabaseRequest('tbl_feedback', 'GET', null, [], 'feedback_id,user_id,email,main_concern,details,created_at');
    if (!isset($result['error'])) {
        // Sort by created_at descending in PHP
        usort($result, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $data = $result;
    }
} else {
    // MySQL query
    $sql = "SELECT feedback_id, user_id, email, main_concern, details, created_at FROM tbl_feedback ORDER BY created_at DESC";
    $result = $mysqli->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $mysqli->close();
}

echo json_encode([
    'status' => 200,
    'message' => 'Feedback fetched successfully',
    'data' => $data
]);
?>