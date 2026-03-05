<?php
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$dbname = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'resort_db');
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
} catch (mysqli_sql_exception $e) {
    die("Database Connection Error: " . $e->getMessage() . "<br><br><b>Troubleshooting Tip:</b> Ensure you have configured the production database credentials.");
}

// Set session save path to a writable directory
$session_save_path = __DIR__ . '/sessions';
if (!file_exists($session_save_path)) {
    mkdir($session_save_path, 0777, true);
}
session_save_path($session_save_path);
session_start();
?>