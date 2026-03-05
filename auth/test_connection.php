<?php
// Test 'reserve_user' connection
$host = 'localhost';
$user = 'reserve_user';
$pass = 'reserve_pass123';
$dbname = 'resort_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("FAILED: " . $conn->connect_error);
}
echo "SUCCESS: Connected to $dbname as $user";
$conn->close();
?>
