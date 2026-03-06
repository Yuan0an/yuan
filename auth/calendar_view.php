<?php
require 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Get month and year from URL or current date
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get all events for filter
$events = $conn->query("SELECT * FROM events ORDER BY name");
$selected_event = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Get approved reservations for the month
$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));

$query = "
    SELECT b.booking_date, b.start_time, b.end_time, b.persons, b.reservation_id,
           c.full_name, b.status, e.name as event_name, e.id as event_id
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN events e ON b.event_id = e.id
    WHERE b.booking_date BETWEEN ? AND ?
    AND b.status IN ('approved', 'pending')
";

if ($selected_event > 0) {
    $query .= " AND b.event_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $start_date, $end_date, $selected_event);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
}

$stmt->execute();
$result = $stmt->get_result();

// Group reservations by date
$reservations_by_date = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['booking_date'];
    if (!isset($reservations_by_date[$date])) {
        $reservations_by_date[$date] = [];
    }
    $reservations_by_date[$date][] = $row;
}

// Generate calendar
$days_in_month = date('t', strtotime("$year-$month-01"));
$first_day = date('w', strtotime("$year-$month-01"));
$today = date('Y-m-d');

// Previous and next month links
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar View - Admin</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <input type="checkbox" id="sidebar-toggle">
    <div class="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-calendar-check"></i> Event Admin</h2>
            <p>Welcome, <?php echo $_SESSION['admin_name']; ?></p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="reservations.php">
                <i class="fas fa-list"></i> All Reservations
            </a>
            <a href="calendar_view.php" class="active">
                <i class="fas fa-calendar"></i> Calendar View
            </a>
            <a href="logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="topbar">
            <div class="topbar-left">
                <label for="sidebar-toggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </label>
                <h1><i class="fas fa-calendar"></i> Calendar View</h1>
            </div>
            <div class="topbar-right">
                <span class="admin-info">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['admin_name']; ?>
                </span>
                <span class="current-time">
                    <?php echo date('F j, Y'); ?>
                </span>
            </div>
        </header>

        <!-- Calendar Controls -->
        <div class="calendar-controls">
            <div class="controls-left">
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?><?php echo $selected_event ? '&event_id=' . $selected_event : ''; ?>"
                    class="btn-nav">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>

                <h2><?php echo date('F Y', strtotime("$year-$month-01")); ?></h2>

                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?><?php echo $selected_event ? '&event_id=' . $selected_event : ''; ?>"
                    class="btn-nav">
                    Next <i class="fas fa-chevron-right"></i>
                </a>

                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn-today">
                    <i class="fas fa-calendar-day"></i> Today
                </a>
            </div>

            <div class="controls-right">
                <form method="GET" class="event-filter">
                    <select name="event_id" onchange="this.form.submit()">
                        <option value="0">All Events</option>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <option value="<?php echo $event['id']; ?>" <?php echo $selected_event == $event['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($event['name'] ?? ''); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if (isset($_GET['month'])): ?>
                        <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Calendar Legend -->
        <div class="calendar-legend">
            <div class="legend-item">
                <span class="legend-color approved"></span>
                <span>Approved Reservation</span>
            </div>
            <div class="legend-item">
                <span class="legend-color pending"></span>
                <span>Pending Reservation</span>
            </div>
            <div class="legend-item">
                <span class="legend-color today"></span>
                <span>Today</span>
            </div>
        </div>

        <!-- Calendar -->
        <div class="admin-calendar" 
             data-month-prefix="<?php echo date("F ", strtotime("$year-$month-01")); ?>"
             data-year-suffix="<?php echo date(", Y", strtotime("$year-$month-01")); ?>">
            <div class="calendar-header">
                <div class="day-header">Sunday</div>
                <div class="day-header">Monday</div>
                <div class="day-header">Tuesday</div>
                <div class="day-header">Wednesday</div>
                <div class="day-header">Thursday</div>
                <div class="day-header">Friday</div>
                <div class="day-header">Saturday</div>
            </div>

            <div class="calendar-body">
                <?php
                // Empty cells for days before first day of month
                for ($i = 0; $i < $first_day; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }

                // Days of the month
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    $date_str = date('Y-m-d', strtotime($current_date));

                    $class = 'calendar-day';
                    $is_today = ($date_str == $today) ? ' today' : '';

                    // Check for reservations
                    $reservations_today = isset($reservations_by_date[$date_str]) ? $reservations_by_date[$date_str] : [];
                    $reservation_count = count($reservations_today);

                    if ($reservation_count > 0) {
                        // Check if any are approved
                        $has_approved = false;
                        $has_pending = false;
                        foreach ($reservations_today as $res) {
                            if ($res['status'] == 'approved')
                                $has_approved = true;
                            if ($res['status'] == 'pending')
                                $has_pending = true;
                        }

                        if ($has_approved) {
                            $class .= ' has-approved';
                        } else if ($has_pending) {
                            $class .= ' has-pending';
                        }
                    }

                    echo "<div class='$class$is_today'>";
                    echo "<div class='day-number'>$day</div>";

                    if ($reservation_count > 0) {
                        echo "<div class='day-reservations'>";
                        echo "<span class='res-count'>$reservation_count booking" . ($reservation_count > 1 ? 's' : '') . "</span>";

                        // Show event names
                        $events_today = [];
                        foreach ($reservations_today as $res) {
                            $event_name = htmlspecialchars($res['event_name'] ?? '');
                            $res_id = htmlspecialchars($res['reservation_id'] ?? '');
                            $status = $res['status'];
                            $time = date('g:i A', strtotime($res['start_time']));
                            $persons = $res['persons'];

                            if (!in_array($res_id, $events_today)) {
                                $events_today[] = $res_id;
                                echo "<div class='event-item $status'>";
                                echo "<strong>#$res_id</strong> $event_name<br>";
                                echo "<small>$time • $persons pax</small>";
                                echo "</div>";
                            }
                        }

                        echo "</div>";
                    }

                    echo "</div>";

                    // New row after Saturday
                    if (($first_day + $day) % 7 == 0 && $day != $days_in_month) {
                        echo '</div><div class="calendar-body">';
                    }
                }

                // Fill remaining empty cells
                $last_day_of_week = ($first_day + $days_in_month) % 7;
                if ($last_day_of_week != 0) {
                    for ($i = 0; $i < 7 - $last_day_of_week; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Day Details Modal -->
        <div id="dayDetails" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3 id="modalDate"></h3>
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <script src="js/calendar.js"></script>
</body>

</html>