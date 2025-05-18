<?php
// config.php

header_remove("X-Powered-By");
header_remove("Server");
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self';");
header("X-Frame-Options: DENY");
header("Access-Control-Allow-Origin: *");

// InfinityFree MySQL credentials
$host     = 'sql100.infinityfree.com';
$username = 'if0_38959997';
$password = 'DsdJ2DjLYIi';
$database = 'if0_38959997_db_express';

// $host = "localhost";
// $username = "root";
// $password = "";
// $database = "db_express";

// $host = "serverless-us-central1.sysp0000.db2.skysql.com";
// $username = "dbpgf20388261";
// $port = 4087;
// $password = "sTxMf!I41vNYdWC0{9BXL52";
// $database = "db_express";

// Create connection using MySQLi
$conn = new mysqli($host, $username, $password, $database);
// $conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
// If you want to debug uncomment the next line
echo '✅ Database connected successfully';
?>
