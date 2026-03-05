<?php
require_once 'config.php';

$reservation = null;
$error = '';
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    $ref_id = trim($_POST['ref_id'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($ref_id) || empty($email)) {
        $error = 'Please enter both your Reservation ID and Email address.';
    } else {
        $stmt = $conn->prepare("
            SELECT b.*, c.full_name, c.email, c.phone, c.company,
                   p.payment_method, p.payment_status, p.total_price,
                   e.name as event_base_name
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN payments p ON p.booking_id = b.id
            JOIN events e ON b.event_id = e.id
            WHERE b.reservation_id = ? AND c.email = ?
        ");
        $stmt->bind_param("ss", $ref_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();

        if (!$reservation) {
            $error = 'No reservation found with that ID and email combination. Please check your details and try again.';
        }
    }
}

// Addon info for display
$addon_prices = [
    'lpg' => 250, 'butane' => 150, 'bonfire' => 500,
    'pet' => 200, 'darts' => 250, 'billiard' => 500
];
$addon_names = [
    'lpg' => 'LPGas', 'butane' => 'Butane', 'bonfire' => 'Bonfire',
    'pet' => 'Pet Fee', 'darts' => 'Darts Game', 'billiard' => 'Billiard'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Reservation Status - CK Resort & Events Place</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="check_status_style.css">
    <link rel="stylesheet" href="../uploader/style.css">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="cs-navbar">
        <div class="cs-nav-left">
            <img src="/assets/images/logo.jpg" alt="CK Resort Logo" class="cs-nav-logo">
            <a href="/index.php" class="cs-nav-brand">CK RESORT</a>
        </div>
        <div class="cs-nav-right">
            <a href="/index.php" class="cs-nav-link">Home</a>
            <a href="/form/index.php" class="cs-nav-link cs-nav-book">Book Now</a>
        </div>
    </nav>

    <div class="cs-container">
        <!-- Hero Header -->
        <div class="cs-hero">
            <div class="cs-hero-icon">
                <i class="fas fa-search"></i>
            </div>
            <h1>Check Your Reservation</h1>
            <p>Enter your Reservation ID and the email address you used when booking to view your reservation status.</p>
        </div>

        <!-- Search Form -->
        <div class="cs-search-card">
            <form method="POST" class="cs-form">
                <div class="cs-form-row">
                    <div class="cs-form-group">
                        <label for="ref_id">
                            <i class="fas fa-hashtag"></i> Reservation ID
                        </label>
                        <input type="text" id="ref_id" name="ref_id" placeholder="e.g. 54321"
                            value="<?php echo isset($_POST['ref_id']) ? htmlspecialchars($_POST['ref_id']) : ''; ?>">
                    </div>
                    <div class="cs-form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" placeholder="your@email.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required>
                    </div>
                    <div class="cs-form-group cs-form-btn-group">
                        <button type="submit" class="cs-btn-search">
                            <i class="fas fa-search"></i> Check Status
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="cs-alert cs-alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($reservation): ?>
            <!-- Status Banner -->
            <?php
            $status = $reservation['status'];
            $status_icons = [
                'pending' => 'fa-clock',
                'approved' => 'fa-check-circle',
                'rejected' => 'fa-times-circle',
                'cancelled' => 'fa-ban'
            ];
            $status_messages = [
                'pending' => 'Your reservation is awaiting approval. We will review it shortly.',
                'approved' => 'Great news! Your reservation has been approved. See you soon!',
                'completed' => 'Your reservation has been completed. Thank you for choosing CK Resort!',
                'rejected' => 'Unfortunately, your reservation was not approved. Please contact us for details.',
                'cancelled' => 'This reservation has been cancelled.'
            ];
            $status_icons = [
                'pending' => 'fa-clock',
                'approved' => 'fa-check-circle',
                'completed' => 'fa-flag-checkered',
                'rejected' => 'fa-times-circle',
                'cancelled' => 'fa-ban'
            ];
            $status_icon = $status_icons[$status] ?? 'fa-info-circle';
            $status_message = $status_messages[$status] ?? '';
            ?>
            <div class="cs-status-banner cs-status-<?php echo $status; ?>">
                <div class="cs-status-icon-wrap">
                    <i class="fas <?php echo $status_icon; ?>"></i>
                </div>
                <div class="cs-status-info">
                    <span class="cs-status-label"><?php echo ucfirst($status); ?></span>
                    <p><?php echo $status_message; ?></p>
                </div>
            </div>

            <!-- Reservation Details -->
            <div class="cs-details-grid">
                <!-- Customer Info -->
                <div class="cs-detail-card">
                    <div class="cs-card-header">
                        <i class="fas fa-user"></i>
                        <h3>Customer Information</h3>
                    </div>
                    <div class="cs-card-body">
                        <div class="cs-info-row">
                            <span class="cs-info-label">Name</span>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['full_name']); ?></span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Email</span>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['email']); ?></span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Phone</span>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['phone']); ?></span>
                        </div>
                        <?php if (!empty($reservation['company'])): ?>
                            <div class="cs-info-row">
                                <span class="cs-info-label">Company</span>
                                <span class="cs-info-value"><?php echo htmlspecialchars($reservation['company']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Event Info -->
                <div class="cs-detail-card">
                    <div class="cs-card-header">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Event Details</h3>
                    </div>
                    <div class="cs-card-body">
                        <div class="cs-info-row">
                            <span class="cs-info-label">Event</span>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['event_base_name']); ?></span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Title</span>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['event_title']); ?></span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Date</span>
                            <span class="cs-info-value"><?php echo date('F j, Y', strtotime($reservation['booking_date'])); ?></span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Time</span>
                            <span class="cs-info-value">
                                <?php echo date('g:i A', strtotime($reservation['start_time'])); ?> –
                                <?php echo date('g:i A', strtotime($reservation['end_time'])); ?>
                            </span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Guests</span>
                            <span class="cs-info-value"><?php echo $reservation['persons']; ?> pax</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="cs-detail-card">
                    <div class="cs-card-header">
                        <i class="fas fa-credit-card"></i>
                        <h3>Payment Information</h3>
                    </div>
                    <div class="cs-card-body">
                        <div class="cs-info-row">
                            <span class="cs-info-label">Method</span>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['payment_method']); ?></span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Payment Status</span>
                            <span class="cs-info-value">
                                <span class="cs-payment-badge cs-pay-<?php echo $reservation['payment_status']; ?>">
                                    <?php echo ucfirst($reservation['payment_status']); ?>
                                </span>
                            </span>
                        </div>
                        <?php
                        $addons = json_decode($reservation['addons_json'], true) ?: [];
                        $total = floatval($reservation['total_price']);
                        $addons_sum = 0;
                        foreach ($addons as $key => $qty) {
                            $addons_sum += is_numeric($qty) ? intval($qty) * $addon_prices[$key] : $addon_prices[$key];
                        }
                        $base_rate = $total - $addons_sum;
                        ?>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Base Rate</span>
                            <span class="cs-info-value">₱<?php echo number_format($base_rate); ?></span>
                        </div>
                        <?php foreach ($addons as $key => $qty):
                            $p = is_numeric($qty) ? intval($qty) * $addon_prices[$key] : $addon_prices[$key];
                        ?>
                            <div class="cs-info-row">
                                <span class="cs-info-label"><?php echo $addon_names[$key]; ?><?php echo is_numeric($qty) ? " (x$qty)" : ''; ?></span>
                                <span class="cs-info-value">₱<?php echo number_format($p); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="cs-info-row cs-total-row">
                            <span class="cs-info-label">Total Price</span>
                            <span class="cs-info-value cs-total-value">₱<?php echo number_format($total); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="cs-timeline-card">
                <div class="cs-card-header">
                    <i class="fas fa-history"></i>
                    <h3>Reservation Timeline</h3>
                </div>
                <div class="cs-timeline">
                    <div class="cs-timeline-item completed">
                        <div class="cs-timeline-dot"></div>
                        <div class="cs-timeline-content">
                            <span class="cs-timeline-title">Reservation Submitted</span>
                            <span class="cs-timeline-date"><?php echo date('F j, Y – g:i A', strtotime($reservation['created_at'])); ?></span>
                        </div>
                    </div>
                    <?php if ($status === 'approved'): ?>
                        <div class="cs-timeline-item completed">
                            <div class="cs-timeline-dot"></div>
                            <div class="cs-timeline-content">
                                <span class="cs-timeline-title">Reservation Approved</span>
                                <span class="cs-timeline-date">
                                    <?php echo $reservation['approved_at'] ? date('F j, Y – g:i A', strtotime($reservation['approved_at'])) : 'Confirmed'; ?>
                                </span>
                            </div>
                        </div>
                    <?php elseif ($status === 'rejected'): ?>
                        <div class="cs-timeline-item rejected">
                            <div class="cs-timeline-dot"></div>
                            <div class="cs-timeline-content">
                                <span class="cs-timeline-title">Reservation Rejected</span>
                                <span class="cs-timeline-date"><?php echo date('F j, Y – g:i A', strtotime($reservation['updated_at'])); ?></span>
                            </div>
                        </div>
                    <?php elseif ($status === 'cancelled'): ?>
                        <div class="cs-timeline-item cancelled">
                            <div class="cs-timeline-dot"></div>
                            <div class="cs-timeline-content">
                                <span class="cs-timeline-title">Reservation Cancelled</span>
                                <span class="cs-timeline-date"><?php echo date('F j, Y – g:i A', strtotime($reservation['updated_at'])); ?></span>
                            </div>
                        </div>
                    <?php elseif ($status === 'pending'): ?>
                        <div class="cs-timeline-item pending">
                            <div class="cs-timeline-dot"></div>
                            <div class="cs-timeline-content">
                                <span class="cs-timeline-title">Awaiting Approval</span>
                                <span class="cs-timeline-date">We'll review your booking shortly</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="cs-timeline-item <?php echo $status === 'approved' ? 'upcoming' : 'inactive'; ?>">
                        <div class="cs-timeline-dot"></div>
                        <div class="cs-timeline-content">
                            <span class="cs-timeline-title">Event Day</span>
                            <span class="cs-timeline-date"><?php echo date('F j, Y', strtotime($reservation['booking_date'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($status === 'pending' || $status === 'approved' || $status === 'completed'): ?>
                <!-- Upload Receipt Section -->
                <div class="cs-detail-card" style="margin-top: 25px; grid-column: 1 / -1; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                    <div class="cs-card-header" style="padding: 15px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-file-invoice" style="color: #3b82f6;"></i>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #1e293b;">Upload Payment Receipt</h3>
                    </div>
                    <div class="cs-card-body" style="padding: 20px;">
                        <p style="text-align: center; color: #64748b; margin-bottom: 20px; font-size: 0.95rem;">
                            If you have already made your downpayment, please upload a screenshot of your receipt here for verification.
                        </p>
                        <?php 
                        $_GET['res_id'] = $reservation['reservation_id'];
                        include '../uploader/index.php'; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="cs-action-row">
                <button onclick="window.print()" class="cs-btn-action cs-btn-print">
                    <i class="fas fa-print"></i> Print Details
                </button>
                <a href="/index.php" class="cs-btn-action cs-btn-home">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        <?php elseif ($searched && !$error): ?>
            <!-- This shouldn't normally be reached, but just in case -->
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="cs-footer">
        <p>&copy; <?php echo date('Y'); ?> CK Resort & Events Place. All Rights Reserved.</p>
    </footer>
</body>

</html>
