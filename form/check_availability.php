<?php
require 'config.php';

$date = $_POST['date'];
$event_id = $_POST['event_id'];

// Get event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

// Check if past date
if(strtotime($date) < strtotime(date('Y-m-d'))) {
    echo json_encode([
        'available' => false,
        'maxAvailable' => 0,
        'message' => '<div class="error">This date has passed. Please select a future date.</div>'
    ]);
    exit;
}

// Get total bookings for this date
$stmt = $conn->prepare("SELECT SUM(persons) as total FROM bookings WHERE event_id = ? AND booking_date = ?");
$stmt->bind_param("is", $event_id, $date);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$total_booked = $result['total'] ?: 0;

$available = $event['max_persons'] - $total_booked;

if($available <= 0) {
    echo json_encode([
        'available' => false,
        'maxAvailable' => 0,
        'message' => '<div class="error">This date is fully booked. Please select another date.</div>'
    ]);
} else {
    echo json_encode([
        'available' => true,
        'maxAvailable' => $available,
        'alreadyBooked' => $total_booked,
        'message' => '<div class="success">' . 
                    '<strong>Available!</strong><br>' .
                    'Spots left: ' . $available . ' out of ' . $event['max_persons'] . '<br>' .
                    'Date: ' . date('F j, Y', strtotime($date)) . '<br>' .
                    'Event: ' . $event['name'] . 
                    '</div>'
    ]);
}
?>