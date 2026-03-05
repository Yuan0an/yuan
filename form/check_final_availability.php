<?php
require 'config.php';

$date = $_POST['date'];
$event_id = $_POST['event_id'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];

// Get event max capacity
$stmt = $conn->prepare("SELECT max_persons FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

// Check current bookings for this exact time slot
$stmt = $conn->prepare("
    SELECT SUM(persons) as total 
    FROM bookings 
    WHERE event_id = ? 
    AND booking_date = ?
    AND start_time = ?
    AND end_time = ?
");
$stmt->bind_param("isss", $event_id, $date, $start_time, $end_time);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$total_booked = $result['total'] ?: 0;

$available = $event['max_persons'] - $total_booked;

if ($available <= 0) {
    echo json_encode([
        'available' => false,
        'maxAvailable' => 0
    ]);
} else {
    echo json_encode([
        'available' => true,
        'maxAvailable' => $available,
        'alreadyBooked' => $total_booked
    ]);
}
?>