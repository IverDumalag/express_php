<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
// config.php – use getenv() or $_ENV to retrieve credentials set on the server
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 3306;  // default MySQL port
// Create MySQLi connection
$mysqli = new mysqli($host, $user, $pass, $name, $port);
if ($mysqli->connect_errno) {
    // Connection failed
    http_response_code(500);
    die(json_encode(['error' => 'DB connect failed: '.$mysqli->connect_error]));
}
// echo json_encode(['status' => 'success', 'message' => 'Database connected successfully']);
?>
