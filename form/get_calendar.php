<?php
require 'config.php';

$month = $_POST['month'];
$year = $_POST['year'];
$event_id = $_POST['event_id'];

// Get approved bookings for this month
$start_date = sprintf("%04d-%02d-01", $year, $month);
$end_date = date('Y-m-t', strtotime($start_date));

// --- DYNAMIC CONFLICT LOGIC START ---

// Get the requested event's valid hours
$stmt_event = $conn->prepare("SELECT start_time, end_time FROM events WHERE id = ?");
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$event_data = $stmt_event->get_result()->fetch_assoc();
$req_start_time = $event_data['start_time'];
$req_end_time = $event_data['end_time'];
$event_start_time = $req_start_time; // Keep for the "past date" check

// Fetch all approved bookings that could possibly overlap with any day in this month
// We expand the search window by 1 day on each side to catch overnight events
$extended_start = date('Y-m-d', strtotime($start_date . ' -1 day'));
$extended_end = date('Y-m-d', strtotime($end_date . ' +1 day'));

$stmt_bookings = $conn->prepare("
    SELECT booking_date, start_time, end_time 
    FROM bookings 
    WHERE status = 'approved' 
    AND booking_date BETWEEN ? AND ?
");
$stmt_bookings->bind_param("ss", $extended_start, $extended_end);
$stmt_bookings->execute();
$approved_bookings = $stmt_bookings->get_result()->fetch_all(MYSQLI_ASSOC);

$conflict_blocked_dates = [];

// For each day of the month, test if the requested event schedule overlaps with ANY approved booking
$days_in_month = date('t', strtotime("$year-$month-01"));
for ($day = 1; $day <= $days_in_month; $day++) {
    $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
    
    // Requested event timeline for this date
    $req_start_dt = $current_date . ' ' . $req_start_time;
    if ($req_end_time <= $req_start_time) {
        $req_end_dt = date('Y-m-d H:i:s', strtotime($current_date . ' +1 day ' . $req_end_time));
    } else {
        $req_end_dt = $current_date . ' ' . $req_end_time;
    }

    foreach ($approved_bookings as $bk) {
        $bk_start_dt = $bk['booking_date'] . ' ' . $bk['start_time'];
        if ($bk['end_time'] <= $bk['start_time']) {
            $bk_end_dt = date('Y-m-d H:i:s', strtotime($bk['booking_date'] . ' +1 day ' . $bk['end_time']));
        } else {
            $bk_end_dt = $bk['booking_date'] . ' ' . $bk['end_time'];
        }
        
        // Strict Overlap Condition: A overlaps B if A starts before B ends AND B starts before A ends
        if ($req_start_dt < $bk_end_dt && $bk_start_dt < $req_end_dt) {
            $conflict_blocked_dates[] = $current_date;
            break; // No need to check other bookings for this date
        }
    }
}

// --- DYNAMIC CONFLICT LOGIC END ---

// Generate calendar
$first_day = date('w', strtotime("$year-$month-01"));
$today = date('Y-m-d');
$current_time = date('H:i:s');


$calendar = '<table class="calendar-table">';
$calendar .= '<tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr>';

$day_count = 1;
$calendar .= '<tr>';

// Empty cells for first week
for ($i = 0; $i < $first_day; $i++) {
    $calendar .= '<td class="empty"></td>';
}

// Days of the month
for ($day = 1; $day <= $days_in_month; $day++) {
    $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
    $date_str = date('Y-m-d', strtotime($current_date));

    $class = 'calendar-day';
    $status = '';

    // Check if past date or today but start time has passed
    if ($date_str < $today || ($date_str == $today && $current_time > $event_start_time)) {
        $class .= ' past';
        $status = 'past';
    }
    // Check if BLOCKED BY CONFLICT (Different or Same Event)
    else if (in_array($date_str, $conflict_blocked_dates)) {
        $class .= ' approved';
        $status = 'Booked';
    }
    // Check for pending reservations
    else {
        // Count pending reservations for this date
        $stmt = $conn->prepare("
            SELECT COUNT(*) as pending_count 
            FROM bookings 
            WHERE event_id = ? 
            AND booking_date = ?
            AND status = 'pending'
        ");
        $stmt->bind_param("is", $event_id, $date_str);
        $stmt->execute();
        $pending_result = $stmt->get_result()->fetch_assoc();
        $pending_count = $pending_result['pending_count'];

        if ($pending_count > 0) {
            $class .= ' pending';
            $status = $pending_count . ' pending';
        } else {
            $class .= ' available';
            $status = 'Available';
        }
    }

    $calendar .= "<td class='$class' data-date='$date_str'>";
    $calendar .= "<div class='day-number'>$day</div>";
    $calendar .= "<div class='day-availability'>$status</div>";
    $calendar .= "</td>";

    // New row after Saturday
    if (($first_day + $day) % 7 == 0 && $day != $days_in_month) {
        $calendar .= '</tr><tr>';
    }
}

// Fill remaining empty cells
while (($first_day + $days_in_month) % 7 != 0) {
    $calendar .= '<td class="empty"></td>';
    $days_in_month++;
}

$calendar .= '</tr></table>';

echo json_encode([
    'calendar' => $calendar,
    'monthName' => date('F Y', strtotime("$year-$month-01"))
]);
?>