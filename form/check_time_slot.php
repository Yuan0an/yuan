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

// Check for approved reservations (block the slot)
$stmt = $conn->prepare("
    SELECT COUNT(*) as approved_count 
    FROM reservations 
    WHERE event_id = ? 
    AND booking_date = ?
    AND start_time = ?
    AND end_time = ?
    AND status = 'approved'
");
$stmt->bind_param("isss", $event_id, $date, $event_start, $event_end);
$stmt->execute();
$approved_result = $stmt->get_result()->fetch_assoc();

// --- CONFLICT LOGIC START ---

// 1. SAME DAY CONFLICTS (Events on the SAME booking_date)
$same_day_conflicts = [
    1 => [3, 4],       // Day Tour vs Overnight 9AM, Overnight 2PM
    2 => [3, 4, 5],    // Night Tour vs All Overnight
    3 => [1, 2, 4, 5], // Overnight 9AM vs Day, Night, other Overnights
    4 => [1, 2, 3, 5], // Overnight 2PM vs Day, Night, other Overnights
    5 => [2, 3, 4]     // Overnight 7PM vs Night, other Overnights
];

// 2. PREVIOUS DAY CONFLICTS (Check if yesterday had an event that spills over to today)
$prev_day_map = [
    5 => [1, 2, 3, 4],
    4 => [1, 3]
];

// 3. NEXT DAY CONFLICTS (If WE are the long event, check if tomorrow is already booked)
$next_day_check_map = [
    5 => [1, 2, 3, 4],
    4 => [1, 3]
];

// --- EXECUTE CHECKS ---

// Check 1: Same Day
if (isset($same_day_conflicts[$event_id])) {
    $ids = $same_day_conflicts[$event_id];
    if (check_db_conflict($conn, $ids, $date)) {
        block_slot("Unavailable due to conflict with another event on this date.");
    }
}

// Check 2: Previous Day (Did someone book a long event yesterday?)
$prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
foreach ($prev_day_map as $prev_id => $blocked_current_ids) {
    if (in_array($event_id, $blocked_current_ids)) {
        // This specific previous-day event blocks US. Check if it exists.
        if (check_db_conflict($conn, [$prev_id], $prev_date)) {
            block_slot("Unavailable due to an overnight event from the previous day.");
        }
    }
}

// Check 3: Next Day (If we are a long event, will we hit someone tomorrow?)
if (isset($next_day_check_map[$event_id])) {
    $next_date = date('Y-m-d', strtotime($date . ' +1 day'));
    $ids_to_check_tomorrow = $next_day_check_map[$event_id];
    if (check_db_conflict($conn, $ids_to_check_tomorrow, $next_date)) {
        block_slot("Unavailable. This overnight stay overlaps with an event booked on the following day.");
    }
}

// Helper function to check DB
function check_db_conflict($conn, $event_ids, $check_date)
{
    if (empty($event_ids))
        return false;

    $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
    $types = str_repeat('i', count($event_ids)) . 's'; // ids... then date string

    $sql = "SELECT COUNT(*) as count FROM reservations 
            WHERE event_id IN ($placeholders) 
            AND booking_date = ? 
            AND status = 'approved'";

    $stmt = $conn->prepare($sql);
    $params = array_merge($event_ids, [$check_date]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['count'] > 0;
}

function block_slot($msg)
{
    echo json_encode([
        'has_approved' => true,
        'pending_count' => 0,
        'slot_html' => '<div class="unavailable-slot"><i class="fas fa-ban"></i> ' . $msg . '</div>'
    ]);
    exit;
}

// --- CONFLICT LOGIC END ---

// If approved reservation exists, slot is not available
if ($approved_result['approved_count'] > 0) {
    echo json_encode([
        'has_approved' => true,
        'pending_count' => 0,
        'slot_html' => ''
    ]);
    exit;
}

// Count pending reservations
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_count 
    FROM reservations 
    WHERE event_id = ? 
    AND booking_date = ?
    AND start_time = ?
    AND end_time = ?
    AND status = 'pending'
");
$stmt->bind_param("isss", $event_id, $date, $event_start, $event_end);
$stmt->execute();
$pending_result = $stmt->get_result()->fetch_assoc();
$pending_count = $pending_result['pending_count'];

// Generate time slot HTML
$start_display = date('g:i A', strtotime($event_start));
$end_display = $is_overnight ?
    'Next day ' . date('g:i A', strtotime($event_end)) :
    date('g:i A', strtotime($event_end));

$slot_html = '<div class="time-slots-container">';
$slot_html .= '<div class="time-slot available" data-slot="' . $event_start . '-' . $event_end . '">';
$slot_html .= '<div class="slot-time">' . $start_display . ' - ' . $end_display . '</div>';
$slot_html .= '<div class="slot-status">Available for reservation</div>';

if ($pending_count > 0) {
    $slot_html .= '<div class="slot-pending">';
    $slot_html .= '<i class="fas fa-clock"></i> ' . $pending_count . ' pending request(s)';
    $slot_html .= '</div>';
}

$slot_html .= '</div>';
$slot_html .= '</div>';

echo json_encode([
    'has_approved' => false,
    'pending_count' => $pending_count,
    'slot_html' => $slot_html
]);
?>