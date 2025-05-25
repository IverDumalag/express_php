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

// Get unique words and their match status (1 if any entry is matched, else 0)
$sql = "
    SELECT
        words,
        MAX(is_match) AS is_matched
    FROM
        tbl_phrases_words
    GROUP BY
        words
    ORDER BY
        words
";

$result = $mysqli->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'words' => $row['words'],
        'is_matched' => (bool)$row['is_matched']
    ];
}

echo json_encode([
    'status' => 200,
    'message' => 'Unique words with match status fetched successfully',
    'data' => $data
]);

$mysqli->close();
?>