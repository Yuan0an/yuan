<?php
// Temporary debug — DELETE after use
require 'form/config.php';

echo "<h2>Addons Table (name & price)</h2><pre>";
$res = $conn->query("SELECT id, name, price, type FROM addons ORDER BY id");
while ($r = $res->fetch_assoc()) echo json_encode($r) . "\n";
echo "</pre>";

echo "<h2>Last 5 Bookings — addons_json raw</h2><pre>";
$res2 = $conn->query("
    SELECT b.reservation_id, b.addons_json, p.total_price 
    FROM bookings b 
    LEFT JOIN payments p ON p.booking_id = b.id
    ORDER BY b.id DESC LIMIT 5
");
while ($r = $res2->fetch_assoc()) {
    echo "Res #" . $r['reservation_id'] . " | total: " . $r['total_price'] . "\n";
    echo "  raw: " . ($r['addons_json'] ?? 'NULL') . "\n";
    $d = json_decode($r['addons_json'], true);
    echo "  keys: " . (is_array($d) && count($d) ? implode(', ', array_keys($d)) : 'EMPTY') . "\n\n";
}
echo "</pre>";
?>
