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
    // Helper function to add column if not exists
    $ensureColumn = function($table, $column, $definition) use ($conn) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE `$table` ADD COLUMN $column $definition");
        }
    };

    // Check bookings table
    $ensureColumn('bookings', 'addons_json', 'TEXT');
    $ensureColumn('bookings', 'reservation_id', 'VARCHAR(10) UNIQUE');
    
    // Check payments table
    $ensureColumn('payments', 'receipt_data', 'LONGTEXT');
    $ensureColumn('payments', 'time_uploaded', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    
} catch (Exception $e) {
    // Silently continue; let actual query errors reveal deeper issues if the ADD COLUMN fails
}

session_start();
?>