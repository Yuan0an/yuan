<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Debug</h1>";
echo "PHP Version: " . phpversion() . "<br>";

$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$dbname = getenv('MYSQL_DATABASE') ?: (getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'resort_db'));
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);

echo "<h3>Environment:</h3>";
echo "Host: " . $host . "<br>";
echo "User: " . $user . "<br>";
echo "DB: " . $dbname . "<br>";
echo "Port: " . $port . "<br>";

echo "<h3>Connection Test:</h3>";
try {
    if (!class_exists('mysqli')) {
        throw new Error("PHP Extension 'mysqli' is NOT installed/enabled!");
    }
    
    $conn = @new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Error("Connection Error (" . $conn->connect_errno . "): " . $conn->connect_error);
    }
    
    echo "✅ Connection Successful!<br>";
    
    $res = $conn->query("SHOW TABLES");
    echo "Tables found: " . ($res ? $res->num_rows : 0) . "<br>";
    if ($res) {
        while ($row = $res->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
    }
} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "<br>";
}
?>
