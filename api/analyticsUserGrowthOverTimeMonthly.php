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
    $result = supabaseRequest('tbl_users', 'GET', null, null, 'user_id,created_at');
    if (!isset($result['error'])) {
        $monthly_counts = [];
        
        foreach ($result as $user) {
            $month = substr($user['created_at'], 0, 7); // YYYY-MM
            if (!isset($monthly_counts[$month])) {
                $monthly_counts[$month] = 0;
            }
            $monthly_counts[$month]++;
        }
        
        foreach ($monthly_counts as $month => $count) {
            $data[] = [
                'registration_month' => $month,
                'new_users_count' => $count
            ];
        }
        
        // Sort by month
        usort($data, function($a, $b) {
            return strcmp($a['registration_month'], $b['registration_month']);
        });
    }
} else {
    // MySQL query
    $sql = "
       SELECT
          DATE_FORMAT(created_at, '%Y-%m') AS registration_month,
          COUNT(user_id) AS new_users_count
       FROM
          tbl_users
       GROUP BY
          registration_month
       ORDER BY
          registration_month
    ";
    
    $result = $mysqli->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $mysqli->close();
}

echo json_encode([
    'status' => 200,
    'message' => 'User growth data fetched successfully',
    'data' => $data
]);
?>