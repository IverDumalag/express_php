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

// Overall match rate
$sql_overall = "
    SELECT
        SUM(is_match) * 100.0 / COUNT(entry_id) AS match_rate_percentage
    FROM
        tbl_phrases_words
";
$result_overall = $mysqli->query($sql_overall);
$overall = $result_overall->fetch_assoc();
$overall['match_rate_percentage'] = (float)$overall['match_rate_percentage'];

// Monthly trend
$sql_monthly = "
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS creation_month,
        SUM(is_match) * 100.0 / COUNT(entry_id) AS monthly_match_rate
    FROM
        tbl_phrases_words
    GROUP BY
        creation_month
    ORDER BY
        creation_month
";
$result_monthly = $mysqli->query($sql_monthly);

$monthly = [];
while ($row = $result_monthly->fetch_assoc()) {
    $row['monthly_match_rate'] = (float)$row['monthly_match_rate'];
    $monthly[] = $row;
}

echo json_encode([
    'status' => 200,
    'message' => 'Content match rate analytics fetched successfully',
    'overall_match_rate' => $overall['match_rate_percentage'],
    'monthly_trend' => $monthly
]);

$mysqli->close();
?>