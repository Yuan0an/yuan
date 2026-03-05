<?php
require 'config.php';

$date = $_POST['date'];
$event_id = $_POST['event_id'];
$event_start = $_POST['start_time'];
$event_end = $_POST['end_time'];
$is_overnight = $_POST['is_overnight'] == 'true';

// Get event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

// For overnight stays, checking time conflicts
if ($is_overnight) {
    
    
    // Calculate actual start and end timestamps
    $start_datetime = $date . ' ' . $event_start;
    $end_date = date('Y-m-d', strtotime($date . ' +1 day'));
    $end_datetime = $end_date . ' ' . $event_end;
    
    // Check for conflicting bookings
    $stmt = $conn->prepare("
        SELECT SUM(persons) as total 
        FROM bookings 
        WHERE event_id = ? 
        AND (
            (booking_date = ? AND start_time = ? AND end_time = ?) OR
            (booking_date = ? AND is_overnight_event = 1)
        )
    ");
    $stmt->bind_param("issss", $event_id, $date, $event_start, $event_end, $date);
    
} else {
    // For regular day/night tours (single day)
    $start_datetime = $date . ' ' . $event_start;
    $end_datetime = $date . ' ' . $event_end;
    
    // Check for bookings at this exact time slot
    $stmt = $conn->prepare("
        SELECT SUM(persons) as total 
        FROM bookings 
        WHERE event_id = ? 
        AND booking_date = ?
        AND start_time = ?
        AND end_time = ?
    ");
    $stmt->bind_param("isss", $event_id, $date, $event_start, $event_end);
}

$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$total_booked = $result['total'] ?: 0;

$available = $event['max_persons'] - $total_booked;

if ($available <= 0) {
    echo json_encode([
        'available' => false,
        'message' => 'This time slot is fully booked for the selected date.'
    ]);
} else {
    // Generate time slot HTML
    $start_display = date('g:i A', strtotime($event_start));
    $end_display = $is_overnight ? 
        'Next day ' . date('g:i A', strtotime($event_end)) : 
        date('g:i A', strtotime($event_end));
    
    $slots_html = '<div class="time-slots-container">';
    $slots_html .= '<div class="time-slot available" data-slot="' . $event_start . '-' . $event_end . '">';
    $slots_html .= '<div class="slot-time">' . $start_display . ' - ' . $end_display . '</div>';
    $slots_html .= '<div class="slot-availability">' . $available . ' spots available</div>';
    $slots_html .= '</div>';
    $slots_html .= '</div>';
    
    echo json_encode([
        'available' => true,
        'slots_html' => $slots_html,
        'max_available' => $available
    ]);
}
?>