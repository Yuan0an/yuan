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
    "Add reservation_id to bookings" => [
        "sql" => "ALTER TABLE bookings ADD COLUMN reservation_id VARCHAR(10) UNIQUE",
        "check" => "SHOW COLUMNS FROM bookings LIKE 'reservation_id'"
    ],
];

$all_ok = true;
foreach ($migrations as $label => $m) {
    echo "Running: $label ... ";
    
    // Check if column exists
    $check = $conn->query($m['check']);
    if ($check && $check->num_rows > 0) {
        echo "✅ Already exists (Skipped)\n";
        continue;
    }

    if ($conn->query($m['sql'])) {
        echo "✅ OK\n";
    } else {
        echo "❌ FAILED: " . $conn->error . "\n";
        $all_ok = false;
    }
}

echo '</pre>';
if ($all_ok) {
    echo '<p style="color:#4CAF50;font-size:18px;">✅ All migrations complete! <strong>Delete this file now.</strong></p>';
} else {
    echo '<p style="color:#f44336;font-size:18px;">⚠️ Some migrations failed. Check errors above.</p>';
}
?>
