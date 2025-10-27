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

$overall_match_rate = 0.0;
$monthly = [];

if ($useSupabase) {
    // Fetch all phrases_words and process in PHP
    $result = supabaseRequest('tbl_phrases_words', 'GET', null, null, 'entry_id,is_match,created_at');
    if (!isset($result['error'])) {
        $total = count($result);
        $matched = 0;
        $monthly_data = [];
        
        foreach ($result as $row) {
            if ($row['is_match']) {
                $matched++;
            }
            $month = substr($row['created_at'], 0, 7); // YYYY-MM
            if (!isset($monthly_data[$month])) {
                $monthly_data[$month] = ['total' => 0, 'matched' => 0];
            }
            $monthly_data[$month]['total']++;
            if ($row['is_match']) {
                $monthly_data[$month]['matched']++;
            }
        }
        
        $overall_match_rate = $total > 0 ? ($matched * 100.0 / $total) : 0.0;
        
        foreach ($monthly_data as $month => $counts) {
            $monthly[] = [
                'creation_month' => $month,
                'monthly_match_rate' => $counts['total'] > 0 ? ($counts['matched'] * 100.0 / $counts['total']) : 0.0
            ];
        }
        
        usort($monthly, function($a, $b) {
            return strcmp($a['creation_month'], $b['creation_month']);
        });
    }
} else {
    // MySQL queries
    $sql_overall = "
        SELECT
            SUM(is_match) * 100.0 / COUNT(entry_id) AS match_rate_percentage
        FROM
            tbl_phrases_words
    ";
    $result_overall = $mysqli->query($sql_overall);
    $overall = $result_overall->fetch_assoc();
    $overall_match_rate = (float)$overall['match_rate_percentage'];
    
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
    
    while ($row = $result_monthly->fetch_assoc()) {
        $row['monthly_match_rate'] = (float)$row['monthly_match_rate'];
        $monthly[] = $row;
    }
    
    $mysqli->close();
}

echo json_encode([
    'status' => 200,
    'message' => 'Content match rate analytics fetched successfully',
    'overall_match_rate' => $overall_match_rate,
    'monthly_trend' => $monthly
]);
?>