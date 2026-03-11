<?php
// super_admin_migrate.php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'resort_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to $dbname\n";

$migrations = [
    "Add role to admins" => [
        "sql" => "ALTER TABLE admins ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'admin' AFTER email",
        "check" => "SHOW COLUMNS FROM admins LIKE 'role'"
    ],
    "Insert Super Admin" => [
        "sql" => "INSERT INTO admins (username, password, full_name, email, role) VALUES ('superadmin', '$2y$10$qGGbmj2VZzdjVPVGH4Ufgel09HVjV0LYoiw32kQ7fPgp933JO7feI2', 'Super Administrator', 'superadmin@example.com', 'superadmin')",
        "check" => "SELECT 1 FROM admins WHERE username = 'superadmin' LIMIT 1"
    ],
    "Add pricing_logic to events" => [
        "sql" => "ALTER TABLE events ADD COLUMN IF NOT EXISTS pricing_logic TEXT AFTER is_overnight",
        "check" => "SHOW COLUMNS FROM events LIKE 'pricing_logic'"
    ],
    "Create addons table" => [
        "sql" => "CREATE TABLE IF NOT EXISTS addons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT,
            type ENUM('counter', 'checkbox') DEFAULT 'counter',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "check" => "SHOW TABLES LIKE 'addons'"
    ],
    "Create payment_methods table" => [
        "sql" => "CREATE TABLE IF NOT EXISTS payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50),
            details TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "check" => "SHOW TABLES LIKE 'payment_methods'"
    ]
];

foreach ($migrations as $label => $m) {
    echo "Processing: $label ... ";
    try {
        if ($label === "Insert Super Admin") {
            $check = $conn->query($m['check']);
            if ($check && $check->num_rows > 0) {
                echo "[SKIP/ALREADY EXISTS]\n";
                continue;
            }
        }
        
        if ($conn->query($m['sql'])) {
            echo "[SUCCESS]\n";
        } else {
            echo "[FAILED]: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
             echo "[ALREADY EXISTS]\n";
        } else {
            echo "[ERROR]: " . $e->getMessage() . "\n";
        }
    }
}

// Seed default addons if empty
$check_addons = $conn->query("SELECT id FROM addons LIMIT 1");
if ($check_addons && $check_addons->num_rows === 0) {
    echo "Seeding default addons ... ";
    $conn->query("INSERT INTO addons (name, price, type) VALUES ('LPGas', 250, 'counter'), ('Butane', 150, 'counter'), ('Bonfire', 500, 'counter'), ('Pet Fee', 200, 'counter'), ('Darts Game', 250, 'checkbox'), ('Billiard', 500, 'checkbox')");
    echo "[DONE]\n";
}

// Seed default payment methods if empty
$check_pm = $conn->query("SELECT id FROM payment_methods LIMIT 1");
if ($check_pm && $check_pm->num_rows === 0) {
    echo "Seeding default payment methods ... ";
    $conn->query("INSERT INTO payment_methods (name, icon, details) VALUES ('GCash', 'fas fa-mobile-alt', '0917-123-4567\\nEvent Venue Booking'), ('Bank Transfer', 'fas fa-university', 'BPI: 1234-5678-90\\nEvent Venue Booking')");
    echo "[DONE]\n";
}

echo "\nMigration finished.\n";
?>
