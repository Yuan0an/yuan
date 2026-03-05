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
    "Add receipt_data column to payments" =>
        "ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_data LONGTEXT",
];

$all_ok = true;
foreach ($migrations as $label => $sql) {
    echo "Running: $label ... ";
    if ($conn->query($sql)) {
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
