<?php
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$dbname = getenv('MYSQL_DATABASE') ?: (getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: (getenv('MYSQLHOST') ? 'railway' : 'resort_db')));
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 🛡️ SELF-HEALING DATABASE: Ensure critical columns exist.
// This handles cases where migrate.php was not run or failed on Railway.
try {
    // Check bookings table
    $check_res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'reservation_id'");
    if ($check_res && $check_res->num_rows === 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS addons_json TEXT");
        $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS reservation_id VARCHAR(10) UNIQUE");
    }
    
    // Check payments table
    $check_pay = $conn->query("SHOW COLUMNS FROM payments LIKE 'receipt_data'");
    if ($check_pay && $check_pay->num_rows === 0) {
        $conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_data LONGTEXT");
        $conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS time_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
} catch (Exception $e) {
    // Silently continue; let actual query errors reveal deeper issues if the ADD COLUMN fails
}

session_start();
?>