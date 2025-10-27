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
    // Fetch all users and process in PHP
    $result = supabaseRequest('tbl_users', 'GET', null, null, 'user_id,sex');
    if (!isset($result['error'])) {
        $sex_counts = [];
        $total = count($result);
        
        foreach ($result as $user) {
            $sex = $user['sex'];
            if (!isset($sex_counts[$sex])) {
                $sex_counts[$sex] = 0;
            }
            $sex_counts[$sex]++;
        }
        
        foreach ($sex_counts as $sex => $count) {
            $data[] = [
                'sex' => $sex,
                'user_count' => $count,
                'percentage' => $total > 0 ? ($count * 100.0 / $total) : 0.0
            ];
        }
    }
} else {
    // MySQL query
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
    
    while ($row = $result->fetch_assoc()) {
        $row['percentage'] = (float)$row['percentage'];
        $data[] = $row;
    }
    
    $mysqli->close();
}

echo json_encode([
    'status' => 200,
    'message' => 'Demographics by sex fetched successfully',
    'data' => $data
]);
?>