<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'resort_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set session save path to a writable directory
$session_save_path = __DIR__ . '/sessions';
if (!file_exists($session_save_path)) {
    mkdir($session_save_path, 0777, true);
}
session_save_path($session_save_path);
session_start();
?>