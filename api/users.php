<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Example: GET list of users
$result = $mysqli->query("SELECT * FROM tbl_users");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode($users);
