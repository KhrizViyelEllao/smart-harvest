<?php
$host = 'localhost';
$db   = 'smart_harvest';   
$user = 'root';
$pass = '';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_errno) {
    error_log("DB connect error: " . $conn->connect_error);
    die("Database connection failed.");
}
$conn->set_charset('utf8mb4');
?>
