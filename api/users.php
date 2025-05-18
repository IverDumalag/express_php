<?php
require 'config.php';
header('Content-Type: application/json');

// Example: GET list of users
$result = $mysqli->query("SELECT id, name, email FROM users");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode($users);
