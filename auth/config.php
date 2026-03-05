<?php
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$dbname = getenv('MYSQL_DATABASE') ?: (getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: (getenv('MYSQLHOST') ? 'railway' : 'resort_db')));
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    // 🛡️ SELF-HEALING DATABASE: Ensure critical columns exist.
    // Handles cases where migrate.php was not run or failed on Railway.
    $check_res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'reservation_id'");
    if ($check_res && $check_res->num_rows === 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS addons_json TEXT");
        $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS reservation_id VARCHAR(10) UNIQUE");
    }
    
    $check_pay = $conn->query("SHOW COLUMNS FROM payments LIKE 'receipt_data'");
    if ($check_pay && $check_pay->num_rows === 0) {
        $conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_data LONGTEXT");
        $conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS time_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
} catch (mysqli_sql_exception $e) {
    die("Database Connection Error: " . $e->getMessage() . "<br><br><b>Troubleshooting Tip:</b> Ensure you have configured the production database credentials.");
}

session_start();
?>