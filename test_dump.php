<?php
require 'config.php';

echo "Events:\n";
$res = $conn->query("SELECT id, name, start_time, end_time FROM events");
while($row = $res->fetch_assoc()) print_r($row);

echo "\nBookings for today:\n";
$res = $conn->query("SELECT id, event_id, booking_date, start_time, end_time, status FROM bookings WHERE booking_date = CURDATE()");
while($row = $res->fetch_assoc()) print_r($row);
