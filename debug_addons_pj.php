<?php
require 'form/config.php';
$res = $conn->query("SELECT id, reservation_id, addons_json FROM bookings ORDER BY id DESC LIMIT 5");
while ($r = $res->fetch_assoc()) {
    echo "Res: " . $r['reservation_id'] . "<br>";
    echo "Raw addons: " . htmlspecialchars($r['addons_json']) . "<br>";
    $addons = json_decode($r['addons_json'], true);
    echo "Decoded keys: ";
    if (is_array($addons)) {
        foreach ($addons as $k => $v) {
            echo htmlspecialchars($k) . " => " . htmlspecialchars($v) . ", ";
        }
    } else {
        echo "FAILED TO DECODE";
    }
    echo "<br><br>";
}
