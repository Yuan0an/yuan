<?php
require 'config.php';

$month = $_POST['month'];
$year = $_POST['year'];
$event_id = $_POST['event_id'];

// Get approved bookings for this month
$start_date = sprintf("%04d-%02d-01", $year, $month);
$end_date = date('Y-m-t', strtotime($start_date));

// CONFLICT LOGIC START

// 1. SAME DAY CONFLICTS
$same_day_conflicts = [
    1 => [3, 4],       // Day Tour vs Overnight 9AM, Overnight 2PM
    2 => [3, 4, 5],    // Night Tour vs All Overnight
    3 => [1, 2, 4, 5], // Overnight 9AM vs Day, Night, other Overnights
    4 => [1, 2, 3, 5], // Overnight 2PM vs Day, Night, other Overnights
    5 => [2, 3, 4]     // Overnight 7PM vs Night, other Overnights
];

// 2. PREVIOUS DAY CONFLICTS (Events on Date-1 blocking Date)
// Map: [Previous Event ID] => [List of Blocked Current Event IDs]
$prev_day_map = [
    5 => [1, 2, 3, 4],
    4 => [1, 3]
];

// 3. NEXT DAY CONFLICTS (Events on Date+1 blocking Date)
// Map: [Current Event ID] => [List of Blocked Next Event IDs]
$next_day_check_map = [
    5 => [1, 2, 3, 4],
    4 => [1, 3]
];

$direct_booked_dates = [];
$conflict_blocked_dates = [];

// Helper to add dates
function add_dates($result, &$array)
{
    while ($row = $result->fetch_assoc()) {
        $array[] = $row['booking_date'];
    }
}

// A. Get bookings for THIS event (Standard check -> 'Booked')
$stmt = $conn->prepare("SELECT DISTINCT booking_date FROM bookings WHERE event_id = ? AND booking_date BETWEEN ? AND ? AND status = 'approved'");
$stmt->bind_param("iss", $event_id, $start_date, $end_date);
$stmt->execute();
add_dates($stmt->get_result(), $direct_booked_dates);

// B. Get SAME DAY conflicting bookings (Conflict -> 'Unavailable')
if (isset($same_day_conflicts[$event_id])) {
    $ids = $same_day_conflicts[$event_id];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids)) . 'ss';
    $params = array_merge($ids, [$start_date, $end_date]);

    $sql = "SELECT DISTINCT booking_date FROM bookings WHERE event_id IN ($placeholders) AND booking_date BETWEEN ? AND ? AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    add_dates($stmt->get_result(), $conflict_blocked_dates);
}

// C. Get PREVIOUS DAY conflicting bookings (Spillover -> 'Unavailable')
foreach ($prev_day_map as $prev_id => $blocked_list) {
    if (in_array($event_id, $blocked_list)) {
        $prev_start = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $prev_end = date('Y-m-d', strtotime($end_date . ' -1 day'));

        $sql = "SELECT DISTINCT booking_date FROM bookings WHERE event_id = ? AND booking_date BETWEEN ? AND ? AND status = 'approved'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $prev_id, $prev_start, $prev_end);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            // Block the NEXT DAY
            $blocked_day = date('Y-m-d', strtotime($row['booking_date'] . ' +1 day'));
            if ($blocked_day >= $start_date && $blocked_day <= $end_date) {
                $conflict_blocked_dates[] = $blocked_day;
            }
        }
    }
}

// D. Get NEXT DAY conflicting bookings (Overlap -> 'Unavailable')
if (isset($next_day_check_map[$event_id])) {
    $ids_to_check = $next_day_check_map[$event_id];
    $placeholders = implode(',', array_fill(0, count($ids_to_check), '?'));

    // We check date range [Start+1, End+1]
    $next_start = date('Y-m-d', strtotime($start_date . ' +1 day'));
    $next_end = date('Y-m-d', strtotime($end_date . ' +1 day'));

    $types = str_repeat('i', count($ids_to_check)) . 'ss';
    $params = array_merge($ids_to_check, [$next_start, $next_end]);

    $sql = "SELECT DISTINCT booking_date FROM bookings WHERE event_id IN ($placeholders) AND booking_date BETWEEN ? AND ? AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        // Block the PREVIOUS DAY (Our booking day)
        $blocked_day = date('Y-m-d', strtotime($row['booking_date'] . ' -1 day'));
        if ($blocked_day >= $start_date && $blocked_day <= $end_date) {
            $conflict_blocked_dates[] = $blocked_day;
        }
    }
}

// Ensure Uniqueness
$direct_booked_dates = array_unique($direct_booked_dates);
$conflict_blocked_dates = array_unique($conflict_blocked_dates);

// --- CONFLICT LOGIC END ---

// Generate calendar
$days_in_month = date('t', strtotime("$year-$month-01"));
$first_day = date('w', strtotime("$year-$month-01"));
$today = date('Y-m-d');

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

    // Check if past date
    if ($date_str < $today) {
        $class .= ' past';
        $status = 'Past';
    }
    // Check if DIRECTLY BOOKED (Same Event)
    else if (in_array($date_str, $direct_booked_dates)) {
        $class .= ' approved';
        $status = 'Booked';
    }
    // Check if BLOCKED BY CONFLICT (Different Event)
    else if (in_array($date_str, $conflict_blocked_dates)) {
        $class .= ' approved';
        $status = 'Unavailable';
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