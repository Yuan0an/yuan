<?php
// diag.php - Temporary diagnostic script. DELETE AFTER USE.
require 'auth/config.php';

echo "<h2>Diagnostic Info</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "MySQLi Charset: " . $conn->character_set_name() . "<br>";

// Check admins table
$result = $conn->query("SELECT id, username, email, role, LENGTH(password) as pass_len FROM admins");
echo "<h3>Admins Table Content (Masked)</h3>";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | ";
    echo "User: " . htmlspecialchars($row['username']) . " | ";
    echo "Email: " . htmlspecialchars($row['email']) . " | ";
    echo "Role: " . htmlspecialchars($row['role']) . " | ";
    echo "Pass Len: " . $row['pass_len'] . "<br>";
}

// Test specific superadmin lookup
$username = 'superadmin';
$stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$res = $stmt->get_result();

echo "<h3>Superadmin Specific Check</h3>";
if ($res->num_rows === 0) {
    echo "❌ Superadmin NOT FOUND by username 'superadmin'<br>";
} else {
    $admin = $res->fetch_assoc();
    echo "✅ Superadmin FOUND<br>";
    echo "Stored Hash Prefix: " . substr($admin['password'], 0, 7) . "...<br>";
    
    // Test password_verify locally in this script
    $test_pass = 'Superadmin@ckresort1';
    $verify = password_verify($test_pass, $admin['password']);
    echo "Internal password_verify Test: " . ($verify ? "✅ MATCH" : "❌ FAIL") . "<br>";
}

echo "<h3>Environment Check</h3>";
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ? 'Set' : 'Not Set') . "<br>";
echo "MYSQL_DATABASE: " . (getenv('MYSQL_DATABASE') ? 'Set' : 'Not Set') . "<br>";
?>
