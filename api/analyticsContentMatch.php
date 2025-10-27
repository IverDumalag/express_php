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
    // Fetch all phrases_words and process in PHP
    $result = supabaseRequest('tbl_phrases_words', 'GET', null, null, 'words,is_match');
    if (!isset($result['error'])) {
        $words_map = [];
        foreach ($result as $row) {
            $word = $row['words'];
            if (!isset($words_map[$word])) {
                $words_map[$word] = false;
            }
            if ($row['is_match']) {
                $words_map[$word] = true;
            }
        }
        foreach ($words_map as $word => $is_matched) {
            $data[] = [
                'words' => $word,
                'is_matched' => $is_matched
            ];
        }
        // Sort by words
        usort($data, function($a, $b) {
            return strcmp($a['words'], $b['words']);
        });
    }
} else {
    // MySQL query
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
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'words' => $row['words'],
            'is_matched' => (bool)$row['is_matched']
        ];
    }
    
    $mysqli->close();
}

echo json_encode([
    'status' => 200,
    'message' => 'Unique words with match status fetched successfully',
    'data' => $data
]);
?>