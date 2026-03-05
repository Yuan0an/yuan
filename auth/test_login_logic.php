<?php
require 'config.php';

echo "Testing Admin Login Logic...\n";
echo "Database: " . $dbname . "\n";

$username = 'admin';
$password = 'admin123';

echo "Attempting login for user: $username\n";

// 1. Check if user exists
$stmt = $conn->prepare("SELECT id, username, password, full_name FROM admins WHERE username = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error . "\n");
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User '$username' not found in database.\n");
}

$admin = $result->fetch_assoc();
echo "User found. ID: " . $admin['id'] . "\n";
echo "Stored Hash: " . $admin['password'] . "\n";

// 2. Verify password
if (password_verify($password, $admin['password'])) {
    echo "SUCCESS: Password verified!\n";
} else {
    echo "FAILURE: Password does NOT match.\n";
    echo "Target Password: $password\n";
    echo "Re-hashing target: " . password_hash($password, PASSWORD_DEFAULT) . "\n";
}
?>