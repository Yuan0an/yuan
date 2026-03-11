<?php
// Temporary script to fix double-escaped JSON in the database
require 'form/config.php';

$res = $conn->query("SELECT id, addons_json FROM bookings WHERE addons_json LIKE '%\\\\\"%'");
$count = 0;
while ($r = $res->fetch_assoc()) {
    $id = $r['id'];
    $raw = $r['addons_json'];
    // Strip slashes
    $fixed = stripslashes($raw);
    
    // Test if it decodes now
    if (json_decode($fixed, true) !== null) {
        $stmt = $conn->prepare("UPDATE bookings SET addons_json = ? WHERE id = ?");
        $stmt->bind_param("si", $fixed, $id);
        $stmt->execute();
        $count++;
    }
}
echo "Fixed $count reservations.";
?>
