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
        g.age_group,
        IFNULL(t.user_count, 0) AS user_count
    FROM
        (
            SELECT 'Children' AS age_group
            UNION ALL SELECT 'Teens'
            UNION ALL SELECT 'Young Adults'
            UNION ALL SELECT 'Adults'
            UNION ALL SELECT 'Middle Age Adults'
            UNION ALL SELECT 'Senior Adults'
        ) g
    LEFT JOIN (
        SELECT
            CASE
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 0 AND 12 THEN 'Children'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 13 AND 19 THEN 'Teens'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 18 AND 29 THEN 'Young Adults'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 20 AND 39 THEN 'Adults'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 40 AND 59 THEN 'Middle Age Adults'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 THEN 'Senior Adults'
            END AS age_group,
            COUNT(user_id) AS user_count
        FROM
            tbl_users
        GROUP BY
            age_group
    ) t ON g.age_group = t.age_group
    ORDER BY
        FIELD(g.age_group, 'Children', 'Teens', 'Young Adults', 'Adults', 'Middle Age Adults', 'Senior Adults')
";

$result = $mysqli->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'status' => 200,
    'message' => 'Demographics by age group fetched successfully',
    'data' => $data
]);

$mysqli->close();
?>