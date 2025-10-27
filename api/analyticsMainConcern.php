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
    // Fetch all feedback and process in PHP
    $result = supabaseRequest('tbl_feedback', 'GET', null, [], 'feedback_id,main_concern');
    if (!isset($result['error'])) {
        $concern_counts = [];
        
        foreach ($result as $feedback) {
            $concern = $feedback['main_concern'];
            if (!isset($concern_counts[$concern])) {
                $concern_counts[$concern] = 0;
            }
            $concern_counts[$concern]++;
        }
        
        foreach ($concern_counts as $concern => $count) {
            $data[] = [
                'main_concern' => $concern,
                'concern_count' => $count
            ];
        }
        
        // Sort by count descending
        usort($data, function($a, $b) {
            return $b['concern_count'] - $a['concern_count'];
        });
    }
} else {
    // MySQL query
    $sql = "
        SELECT
            main_concern,
            COUNT(feedback_id) AS concern_count
        FROM
            tbl_feedback
        GROUP BY
            main_concern
        ORDER BY
            concern_count DESC
    ";
    
    $result = $mysqli->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $mysqli->close();
}

echo json_encode([
    'status' => 200,
    'message' => 'Main concerns fetched successfully',
    'data' => $data
]);
?>