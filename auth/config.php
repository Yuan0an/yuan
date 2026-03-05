<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'resort_db';

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
} catch (mysqli_sql_exception $e) {
    die("Database Connection Error: " . $e->getMessage() . "<br><br><b>Troubleshooting Tip:</b> Ensure you have run the database setup script provided in the implementation plan.");
}

// Set session save path to a writable directory
$session_save_path = __DIR__ . '/sessions';
if (!file_exists($session_save_path)) {
    mkdir($session_save_path, 0777, true);
}
session_save_path($session_save_path);
session_start();
?>