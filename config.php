<?php
date_default_timezone_set('Asia/Manila');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Determine if we're using Supabase or MySQL
$useSupabase = getenv('SUPABASE_URL') && getenv('SUPABASE_ANON_KEY');

if ($useSupabase) {
    // Supabase configuration
    $supabaseUrl = getenv('SUPABASE_URL');
    $supabaseKey = getenv('SUPABASE_ANON_KEY');
    
    // Helper function to make Supabase REST API calls
    function supabaseRequest($table, $method = 'GET', $data = null, $filters = [], $select = '*', $single = false) {
        global $supabaseUrl, $supabaseKey;
        
        $url = $supabaseUrl . '/rest/v1/' . $table;
        
        // Add select parameter
        $queryParams = ['select=' . urlencode($select)];
        
        // Add filters
        foreach ($filters as $key => $value) {
            $queryParams[] = $key . '=' . urlencode($value);
        }
        
        if (!empty($queryParams)) {
            $url .= '?' . implode('&', $queryParams);
        }
        
        $headers = [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        if ($single) {
            $headers[] = 'Accept: application/vnd.pgrst.object+json';
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            return ['error' => true, 'message' => 'Supabase error: ' . $response, 'code' => $httpCode];
        }
        
        return json_decode($response, true);
    }
    
    // Set a flag to indicate we're using Supabase
    $mysqli = null;
} else {
    // MySQL configuration (for local development)
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $name = getenv('DB_NAME');
    $port = getenv('DB_PORT') ?: 3306;
    
    // Create MySQLi connection
    $mysqli = new mysqli($host, $user, $pass, $name, $port);
    $mysqli->query("SET time_zone = '+08:00'");
    
    if ($mysqli->connect_errno) {
        http_response_code(500);
        die(json_encode(['error' => 'DB connect failed: '.$mysqli->connect_error]));
    }
}
?>
