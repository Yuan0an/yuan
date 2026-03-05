<?php
mysqli_report(MYSQLI_REPORT_OFF); // Suppress fatal errors to handle them manually

$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) { die("Root connection failed: " . $conn->connect_error); }

echo "Connected as root.<br>";

// Check DB existence
$res = $conn->query("SHOW DATABASES LIKE 'resort_db'");
if ($res->num_rows == 0) {
    echo "Creating resort_db...<br>";
    $conn->query("CREATE DATABASE IF NOT EXISTS resort_db");
} else {
    echo "Database resort_db exists.<br>";
}

// Aggressive Repair
$tables = ['mysql.db', 'mysql.user', 'mysql.tables_priv', 'mysql.columns_priv', 'mysql.procs_priv'];
foreach ($tables as $table) {
    echo "Repairing $table... ";
    $conn->query("REPAIR TABLE $table");
    echo "Done.<br>";
}

// Re-create User
echo "Recreating user...<br>";
$queries = [
    "DROP USER IF EXISTS 'reserve_user'@'localhost'",
    "CREATE USER 'reserve_user'@'localhost' IDENTIFIED BY 'reserve_pass123'",
    "GRANT ALL PRIVILEGES ON resort_db.* TO 'reserve_user'@'localhost'",
    "FLUSH PRIVILEGES"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Query OK: $q<br>";
    } else {
        echo "Query FAILED: " . $conn->error . "<br>";
    }
}

// Test internal connection
$test = new mysqli('localhost', 'reserve_user', 'reserve_pass123', 'resort_db');
if ($test->connect_error) {
    echo "Internal Test FAILED: " . $test->connect_error;
} else {
    echo "Internal Test SUCCESS! User works.";
}
?>
