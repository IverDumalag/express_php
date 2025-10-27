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

$age_groups = ['Children', 'Teens', 'Young Adults', 'Adults', 'Middle Age Adults', 'Senior Adults'];
$data = [];

if ($useSupabase) {
    // Fetch all users and process in PHP
    $result = supabaseRequest('tbl_users', 'GET', null, null, 'user_id,birthdate');
    if (!isset($result['error'])) {
        $age_counts = array_fill_keys($age_groups, 0);
        
        foreach ($result as $user) {
            $birthdate = new DateTime($user['birthdate']);
            $today = new DateTime();
            $age = $today->diff($birthdate)->y;
            
            if ($age >= 0 && $age <= 12) {
                $age_counts['Children']++;
            } elseif ($age >= 13 && $age <= 19) {
                $age_counts['Teens']++;
            } elseif ($age >= 18 && $age <= 29) {
                $age_counts['Young Adults']++;
            } elseif ($age >= 20 && $age <= 39) {
                $age_counts['Adults']++;
            } elseif ($age >= 40 && $age <= 59) {
                $age_counts['Middle Age Adults']++;
            } elseif ($age >= 60) {
                $age_counts['Senior Adults']++;
            }
        }
        
        foreach ($age_groups as $group) {
            $data[] = [
                'age_group' => $group,
                'user_count' => $age_counts[$group]
            ];
        }
    }
} else {
    // MySQL query
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
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $mysqli->close();
}

echo json_encode([
    'status' => 200,
    'message' => 'Demographics by age group fetched successfully',
    'data' => $data
]);
?>