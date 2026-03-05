<?php
require 'config.php';
$today = date('Y-m-d');
$upcoming_reservations = $conn->query("
    SELECT r.*, e.name as event_name 
    FROM reservations r 
    JOIN events e ON r.event_id = e.id 
    WHERE r.booking_date > '$today'
");
echo "Today is $today\n";
echo "Total future reservations: " . $upcoming_reservations->num_rows . "\n";
while ($res = $upcoming_reservations->fetch_assoc()) {
    echo "ID: " . $res['id'] . ", Date: " . $res['booking_date'] . ", Status: " . $res['status'] . "\n";
}
?>
