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

$sql = "
    SELECT
        sex,
        COUNT(user_id) AS user_count,
        COUNT(user_id) * 100.0 / (SELECT COUNT(*) FROM tbl_users) AS percentage
    FROM
        tbl_users
    GROUP BY
        sex
";

$result = $mysqli->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    // Optionally cast percentage to float for JSON
    $row['percentage'] = (float)$row['percentage'];
    $data[] = $row;
}

echo json_encode([
    'status' => 200,
    'message' => 'Demographics by sex fetched successfully',
    'data' => $data
]);

$mysqli->close();
?>