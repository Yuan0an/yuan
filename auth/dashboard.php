<?php
require 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Get statistics
$stats = [];

// Total reservations
$result = $conn->query("SELECT COUNT(*) as total FROM bookings");
$stats['total_reservations'] = $result->fetch_assoc()['total'];

// Pending reservations
$result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
$stats['pending'] = $result->fetch_assoc()['total'];

// Approved reservations
$result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'approved'");
$stats['approved'] = $result->fetch_assoc()['total'];

// Today's reservations
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE booking_date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$stats['today'] = $stmt->get_result()->fetch_assoc()['total'];

// Pending Refunds (Rejected/Cancelled but Paid, or For Refund) - Excludes fake payments
$result = $conn->query("SELECT COUNT(*) as total FROM bookings b JOIN payments p ON b.id = p.booking_id WHERE (b.status IN ('rejected', 'cancelled') AND p.payment_status = 'paid') OR b.status = 'for_refund'");
$stats['pending_refunds'] = $result->fetch_assoc()['total'];

// Total Refunded
$result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE payment_status = 'refunded'");
$stats['total_refunded'] = $result->fetch_assoc()['total'];

// Recent reservations (last 7 days)
$week_ago = date('Y-m-d', strtotime('-7 days'));
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE created_at >= ?");
$stmt->bind_param("s", $week_ago);
$stmt->execute();
$stats['recent'] = $stmt->get_result()->fetch_assoc()['total'];


// Get today's reservations
$today_reservations = $conn->query("
    SELECT b.*, e.name as event_name, c.full_name 
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    JOIN customers c ON b.customer_id = c.id
    WHERE b.booking_date = '$today' AND b.status = 'approved'
    ORDER BY b.start_time ASC
");

// Get upcoming reservations
$upcoming_reservations = $conn->query("
    SELECT b.*, e.name as event_name, c.full_name 
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    JOIN customers c ON b.customer_id = c.id
    WHERE b.booking_date > '$today' AND b.status = 'approved'
    ORDER BY b.booking_date ASC, b.start_time ASC
    LIMIT 3
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CK Reservation System</title>
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
            <a href="dashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="reservations.php">
                <i class="fas fa-list"></i> All Reservations
            </a>
            <a href="calendar_view.php">
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
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            </div>
            <div class="topbar-right">
                <span class="admin-info">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['admin_name']; ?>
                </span>
                <span class="current-time">
                    <i class="far fa-clock"></i>
                    <?php date_default_timezone_set('Asia/Manila');
                    echo date('F j, Y, g:i A'); ?>
                </span>
            </div>
        </header>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_reservations']; ?></h3>
                    <p>Total Reservations</p>
                </div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending Approval</p>
                </div>
            </div>

            <div class="stat-card rejected">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <?php
                    $res_rejected = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status IN ('rejected', 'cancelled')");
                    $rejected_count = $res_rejected->fetch_assoc()['total'];
                    ?>
                    <h3><?php echo $rejected_count; ?></h3>
                    <p>Rejected / Cancelled</p>
                </div>
            </div>

            <div class="stat-card approved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['approved']; ?></h3>
                    <p>Approved</p>
                </div>
            </div>

            <div class="stat-card today">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['today']; ?></h3>
                    <p>Today's Bookings</p>
                </div>
            </div>

            <div class="stat-card refund">
                <div class="stat-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_refunds']; ?></h3>
                    <p>Pending Refunds</p>
                </div>
            </div>

            <div class="stat-card approved">
                <div class="stat-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_refunded']; ?></h3>
                    <p>Total Refunded</p>
                </div>
            </div>
        </div>

        <!-- Reservation Timeline -->
        <div class="dashboard-section timeline-section">
            <div class="section-header">
                <h2><i class="fas fa-stream"></i> Reservation Timeline</h2>
            </div>
            
            <div class="timeline-container">
                <div class="timeline-column">
                    <h3 class="timeline-title"><i class="fas fa-calendar-day"></i> Today's Reservations (<?php echo date('M j, Y'); ?>)</h3>
                    <div class="timeline">
                        <?php if ($today_reservations && $today_reservations->num_rows > 0): ?>
                            <?php while ($res = $today_reservations->fetch_assoc()): ?>
                                <div class="timeline-item <?php echo $res['status'] === 'completed' ? 'completed' : ($res['status'] === 'pending' ? 'pending' : 'active'); ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h4><?php echo htmlspecialchars($res['full_name'] ?? ''); ?> - <?php echo htmlspecialchars($res['event_name'] ?? ''); ?></h4>
                                        <p class="timeline-time">
                                            <i class="far fa-clock"></i> 
                                            <?php echo date('g:i A', strtotime($res['start_time'])); ?> - <?php echo date('g:i A', strtotime($res['end_time'])); ?>
                                        </p>
                                        <p class="timeline-details">Guests: <?php echo $res['persons']; ?> | Status: <span class="status-badge <?php echo $res['status']; ?>"><?php echo ucfirst($res['status']); ?></span></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="timeline-empty">No reservations for today.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="timeline-column">
                    <h3 class="timeline-title"><i class="fas fa-calendar-alt"></i> Upcoming Reservations</h3>
                    <div class="timeline">
                        <?php if ($upcoming_reservations && $upcoming_reservations->num_rows > 0): ?>
                            <?php while ($res = $upcoming_reservations->fetch_assoc()): ?>
                                <div class="timeline-item upcoming">
                                    <div class="timeline-marker <?php echo $res['status'] === 'pending' ? 'pending' : ''; ?>"></div>
                                    <div class="timeline-content">
                                        <h4><?php echo htmlspecialchars($res['full_name'] ?? ''); ?> - <?php echo htmlspecialchars($res['event_name'] ?? ''); ?></h4>
                                        <p class="timeline-time">
                                            <i class="far fa-calendar"></i> <?php echo date('M j, Y', strtotime($res['booking_date'])); ?> 
                                        </p>
                                        <p class="timeline-time">
                                            <i class="far fa-clock"></i> 
                                            <?php echo date('g:i A', strtotime($res['start_time'])); ?> - <?php echo date('g:i A', strtotime($res['end_time'])); ?>
                                        </p>
                                        <p class="timeline-details">Guests: <?php echo $res['persons']; ?> | Status: <span class="status-badge <?php echo $res['status']; ?>"><?php echo ucfirst($res['status']); ?></span></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="timeline-empty">No upcoming reservations.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-stats">
            <div class="stat-box">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="action-buttons">
                    <a href="reservations.php?status=pending" class="btn-quick pending">
                        <i class="fas fa-clock"></i>
                        <span>Review Pending (<?php echo $stats['pending']; ?>)</span>
                    </a>
                    <a href="reservations.php?status=rejected" class="btn-quick all">
                        <i class="fas fa-times-circle"></i>
                        <span>View Rejected/Cancelled</span>
                    </a>
                    <a href="calendar_view.php" class="btn-quick calendar">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Open Calendar View</span>
                    </a>
                </div>
            </div>

            <div class="stat-box">
                <h3><i class="fas fa-chart-bar"></i> Recent Activity</h3>
                <ul class="activity-list">
                    <li>
                        <i class="fas fa-user-plus activity-icon new"></i>
                        <span><?php echo $stats['recent']; ?> new reservations in last 7 days</span>
                    </li>
                    <li>
                        <i class="fas fa-calendar-day activity-icon today"></i>
                        <span><?php echo $stats['today']; ?> bookings scheduled for today</span>
                    </li>
                    <li>
                        <i class="fas fa-bell activity-icon pending"></i>
                        <span><?php echo $stats['pending']; ?> require your attention</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>

</html>