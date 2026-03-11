<?php
/**
 * migrate.php — One-time database migration runner
 * Visit this URL ONCE on Railway, then DELETE this file for security.
 * Example: https://your-app.up.railway.app/migrate.php
 */
require_once 'form/config.php';

echo '<style>body{font-family:monospace;padding:30px;background:#111;color:#0f0;}</style>';
echo '<h2>🛠 CK Resort — Database Migration</h2>';
echo '<pre>';

$migrations = [
    "Add receipt_data to payments" => [
        "sql" => "ALTER TABLE payments ADD COLUMN receipt_data LONGTEXT",
        "check" => "SHOW COLUMNS FROM payments LIKE 'receipt_data'"
    ],
    "Add time_uploaded to payments" => [
        "sql" => "ALTER TABLE payments ADD COLUMN time_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        "check" => "SHOW COLUMNS FROM payments LIKE 'time_uploaded'"
    ],
    "Add addons_json to bookings" => [
        "sql" => "ALTER TABLE bookings ADD COLUMN addons_json TEXT",
        "check" => "SHOW COLUMNS FROM bookings LIKE 'addons_json'"
    ],
    "Add reservation_id to bookings" => [
        "sql" => "ALTER TABLE bookings ADD COLUMN reservation_id VARCHAR(10) UNIQUE",
        "check" => "SHOW COLUMNS FROM bookings LIKE 'reservation_id'"
    ],
    "Add role to admins" => [
        "sql" => "ALTER TABLE admins ADD COLUMN role VARCHAR(20) DEFAULT 'admin' AFTER email",
        "check" => "SHOW COLUMNS FROM admins LIKE 'role'"
    ],
    "Add pricing_logic to events" => [
        "sql" => "ALTER TABLE events ADD COLUMN pricing_logic TEXT AFTER is_overnight",
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
    ],
];

// Seed logic for migrate.php
$conn->query("INSERT IGNORE INTO admins (username, password, full_name, email, role) VALUES ('superadmin', '$2y$10$qGGbmj2VZzdjVPVGH4Ufgel09HVjV0LYoiw32kQ7fPgp933JO7feI2', 'Super Administrator', 'superadmin@example.com', 'superadmin')");

echo "<h3>Database Check Results:</h3>";
$all_ok = true;

foreach ($migrations as $label => $m) {
    echo "Processing: <strong>$label</strong> ... ";
    
    // Check if column exists
    try {
        $check = $conn->query($m['check']);
        if ($check && $check->num_rows > 0) {
            echo "<span style='color:#4CAF50;'>[ALREADY EXISTS]</span>\n";
            continue;
        }

        if ($conn->query($m['sql'])) {
            echo "<span style='color:#4CAF50;'>[FIXED / ADDED]</span>\n";
        } else {
            echo "<span style='color:#f44336;'>[FAILED]</span>: " . $conn->error . "\n";
            $all_ok = false;
        }
    } catch (Exception $e) {
        echo "<span style='color:#f44336;'>[ERROR]</span>: " . $e->getMessage() . "\n";
        $all_ok = false;
    }
}

echo '</pre>';
if ($all_ok) {
    echo '<div style="background:#222;padding:15px;border-left:5px solid #4CAF50;margin-top:20px;">';
    echo '<p style="color:#4CAF50;font-size:18px;margin:0;">✅ Database is now up to date!</p>';
    echo '<p style="color:#fff;margin:5px 0 0;">Please try submitting your booking again.</p>';
    echo '</div>';
} else {
    echo '<p style="color:#f44336;font-size:18px;">⚠️ Some migrations failed. Please check the errors above.</p>';
}
?>
