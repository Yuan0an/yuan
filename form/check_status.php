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
$addon_info = [];
$addon_by_name = [];
$ai_res = $conn->query("SELECT * FROM addons");
while($ai_row = $ai_res->fetch_assoc()) {
    $addon_info[$ai_row['id']] = $ai_row;
    $addon_by_name[$ai_row['name']] = $ai_row;
}

// Fetch site settings
$settings = [];
$settings_res = $conn->query("SELECT setting_key, setting_value FROM site_settings");
while($s_row = $settings_res->fetch_assoc()) {
    $settings[$s_row['setting_key']] = $s_row['setting_value'];
}
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
    <style>
        /* Download Receipt Styles */
        #receipt-download-container {
            position: absolute;
            left: -9999px;
            top: 0;
            width: 450px; /* Mobile width optimized */
            background: white;
            padding: 0;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        .receipt-download-card {
            padding: 30px;
            border: 1px solid #eee;
            background: white;
        }

        .receipt-dl-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .receipt-dl-header h1 {
            font-size: 28px;
            margin: 0;
            letter-spacing: 2px;
            color: #000;
        }

        .receipt-dl-header p {
            margin: 5px 0 0;
            font-size: 16px;
            text-transform: uppercase;
            color: #333;
            font-weight: 700;
        }

        .receipt-dl-section {
            margin-bottom: 25px;
        }

        .receipt-dl-section h4 {
            font-size: 12px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .receipt-dl-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .receipt-dl-row.prominent {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
        }

        .receipt-dl-row.total {
            border-top: 2px solid #333;
            margin-top: 15px;
            padding-top: 15px;
            font-weight: 800;
            font-size: 18px;
            color: #000;
        }

        .receipt-dl-row.downpayment {
            font-weight: 700;
            color: #166534;
            background: #f0fdf4;
            padding: 10px;
            border-radius: 4px;
            margin-top: 5px;
        }

        .receipt-dl-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #888;
            border-top: 1px dashed #ccc;
            padding-top: 20px;
        }

        .status-badge {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .status-pending { background: #fff3e0; color: #e65100; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        
        .cs-btn-download {
            background: #292929;
            color: #fff;
        }
        
        .cs-btn-download:hover {
            background: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
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
                    <div class="input-group">
                        <label><i class="fas fa-hashtag"></i> Reference ID</label>
                        <input type="text" name="ref_id" placeholder="e.g. RES-12345" 
                            value="<?php echo isset($_POST['ref_id']) ? htmlspecialchars($_POST['ref_id'] ?? '') : ''; ?>">
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" placeholder="Your registered email" 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'] ?? '') : ''; ?>"
                            required>
                    </div>
                    <button type="submit" class="cs-btn-search">
                        <i class="fas fa-search"></i> Check Status
                    </button>
                </div>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error ?? ''); ?>
            </div>
        <?php endif; ?>

        <?php if ($reservation): ?>
            <!-- Status Banner -->
            <?php
            $status = $reservation['status'];
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
                        <div class="cs-info-item">
                            <label>Name</label>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['full_name'] ?? ''); ?></span>
                        </div>
                        <div class="cs-info-item">
                            <label>Email</label>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['email'] ?? ''); ?></span>
                        </div>
                        <div class="cs-info-item">
                            <label>Phone</label>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['phone'] ?? ''); ?></span>
                        </div>
                        <?php if (!empty($reservation['company'])): ?>
                            <div class="cs-info-item">
                                <label>Company</label>
                                <span class="cs-info-value"><?php echo htmlspecialchars($reservation['company'] ?? ''); ?></span>
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
                        <div class="cs-info-item">
                            <label>Event Name</label>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['event_base_name'] ?? ''); ?></span>
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
                        <div class="cs-info-item">
                            <label>Payment Method</label>
                            <span class="cs-info-value"><?php echo htmlspecialchars($reservation['payment_method'] ?? ''); ?></span>
                        </div>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Payment Status</span>
                            <span class="cs-info-value">
                                <span class="cs-payment-badge cs-pay-<?php echo $reservation['payment_status']; ?>">
                                    <?php echo ucfirst($reservation['payment_status']); ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Itemized Breakdown -->
                <div class="cs-detail-card">
                    <div class="cs-card-header">
                        <i class="fas fa-receipt"></i>
                        <h3>Itemized Breakdown</h3>
                    </div>
                    <div class="cs-card-body">
                        <?php
                        $addons_booked = json_decode($reservation['addons_json'], true) ?: [];
                        $total = floatval($reservation['total_price']);
                        
                        // Recalculate Surcharge
                        $surcharge = 0;
                        $booking_date = $reservation['booking_date'];
                        $day_of_week = date('N', strtotime($booking_date)); // 1 (Mon) to 7 (Sun)
                        if ($day_of_week >= 5) { // Fri, Sat, Sun
                            $surcharge = 1000;
                        } else {
                            $special_dates = explode(',', $settings['special_dates'] ?? '');
                            if (in_array($booking_date, array_map('trim', $special_dates))) {
                                $surcharge = 1000;
                            }
                        }

                        $addons_sum = 0;
                        $addons_html = '';
                        foreach ($addons_booked as $name => $qty) {
                            if (isset($addon_by_name[$name])) {
                                $info = $addon_by_name[$name];
                                $p = is_numeric($qty) ? intval($qty) * $info['price'] : $info['price'];
                                $addons_sum += $p;
                                $addons_html .= '<div class="cs-info-row">
                                    <span class="cs-info-label">' . htmlspecialchars($info['name']) . (is_numeric($qty) ? " (x$qty)" : '') . '</span>
                                    <span class="cs-info-value">₱' . number_format($p) . '</span>
                                </div>';
                            }
                        }
                        $base_rate = $total - $addons_sum - $surcharge;
                        ?>
                        <div class="cs-info-row">
                            <span class="cs-info-label">Base Rate (<?php echo htmlspecialchars($reservation['event_base_name'] ?? 'Unknown Event'); ?>)</span>
                            <span class="cs-info-value">₱<?php echo number_format($base_rate); ?></span>
                        </div>
                        <?php echo $addons_html; ?>
                        
                        <?php if ($surcharge > 0): ?>
                            <div class="cs-info-row" style="color: var(--cs-danger);">
                                <span class="cs-info-label">Weekend/Holiday Surcharge</span>
                                <span class="cs-info-value">₱<?php echo number_format($surcharge); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="cs-info-row cs-total-row">
                            <span class="cs-info-label">Total Price</span>
                            <span class="cs-info-value cs-total-value">₱<?php echo number_format($total); ?></span>
                        </div>
                        <div class="cs-info-row" style="margin-top: 5px; color: var(--cs-primary); font-weight: 500;">
                            <span class="cs-info-label">Downpayment Required (50%)</span>
                            <span class="cs-info-value">₱<?php echo number_format($total * 0.5); ?></span>
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
                <button onclick="downloadSummary()" class="cs-btn-action cs-btn-download">
                    <i class="fas fa-download"></i> Download Summary
                </button>
                <a href="/index.php" class="cs-btn-action cs-btn-home">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
    <div id="receipt-download-container">
        <div class="receipt-download-card">
            <div class="receipt-dl-header">
                <h1>CK RESORT</h1>
                <p>Reservation Summary</p>
            </div>

            <div class="receipt-dl-section">
                <div class="receipt-dl-row prominent">
                    <span style="font-weight:700">REFERENCE:</span>
                    <span style="font-weight:800; color:#166534">#<?php echo $reservation['reservation_id']; ?></span>
                </div>
                <div class="receipt-dl-row">
                    <span>Guest Name:</span>
                    <span style="font-weight:600"><?php echo htmlspecialchars($reservation['full_name']); ?></span>
                </div>
                <div class="receipt-dl-row">
                    <span>Event Date:</span>
                    <span style="font-weight:600"><?php echo date('F j, Y', strtotime($reservation['booking_date'])); ?></span>
                </div>
                <div class="receipt-dl-row">
                    <span>Time Slot:</span>
                    <span style="font-weight:600"><?php echo date('h:i A', strtotime($reservation['start_time'])) . ' - ' . date('h:i A', strtotime($reservation['end_time'])); ?></span>
                </div>
                <div class="receipt-dl-row">
                    <span>Event Type:</span>
                    <span style="font-weight:600"><?php echo htmlspecialchars($reservation['event_base_name']); ?></span>
                </div>
            </div>

            <div class="receipt-dl-section">
                <h4>Status Information</h4>
                <div class="receipt-dl-row">
                    <span>Reservation Status:</span>
                    <span class="status-badge status-<?php echo $reservation['status']; ?>"><?php echo ucfirst($reservation['status']); ?></span>
                </div>
                <div class="receipt-dl-row">
                    <span>Payment Status:</span>
                    <span class="status-badge status-<?php echo $reservation['payment_status']; ?>"><?php echo ucfirst($reservation['payment_status']); ?></span>
                </div>
            </div>

            <div class="receipt-dl-section">
                <h4>Cost Breakdown</h4>
                <div class="receipt-dl-row">
                    <span>Base Rate</span>
                    <span>P<?php echo number_format($base_rate); ?></span>
                </div>

                <?php
                // Use the same loop logic as above
                foreach ($addons_booked as $name => $qty) {
                    if (isset($addon_by_name[$name])) {
                        $info = $addon_by_name[$name];
                        $p = is_numeric($qty) ? intval($qty) * $info['price'] : $info['price'];
                        echo '<div class="receipt-dl-row">
                            <span>' . htmlspecialchars($info['name']) . (is_numeric($qty) ? " (x$qty)" : '') . '</span>
                            <span>P' . number_format($p) . '</span>
                        </div>';
                    }
                }
                ?>

                <?php if ($surcharge > 0): ?>
                    <div class="receipt-dl-row" style="color: #e11d48; font-weight:600;">
                        <span>Weekend/Holiday Surcharge</span>
                        <span>P<?php echo number_format($surcharge); ?></span>
                    </div>
                <?php endif; ?>

                <div class="receipt-dl-row total">
                    <span>GRAND TOTAL</span>
                    <span>P<?php echo number_format($total); ?></span>
                </div>
                <div class="receipt-dl-row downpayment">
                    <span>Downpayment Required (50%)</span>
                    <span>P<?php echo number_format($total * 0.5); ?></span>
                </div>
            </div>

            <div class="receipt-dl-footer">
                <p>Thank you for choosing CK Resort!</p>
                <p style="margin-top:10px; font-size:10px;"><?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Footer -->
    <footer class="cs-footer">
        <p>&copy; <?php echo date('Y'); ?> CK Resort & Events Place. All Rights Reserved.</p>
    </footer>

    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <script>
        function downloadSummary() {
            const btn = document.querySelector('.cs-btn-download');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            btn.disabled = true;

            const receipt = document.getElementById('receipt-download-container');
            
            // Temporary styles to ensure capture is perfect
            receipt.style.left = "0";
            receipt.style.position = "static";
            receipt.style.display = "block";

            html2canvas(receipt, {
                scale: 2, // High resolution
                useCORS: true,
                backgroundColor: "#ffffff"
            }).then(canvas => {
                // Restore styles
                receipt.style.position = "absolute";
                receipt.style.left = "-9999px";

                const link = document.createElement('a');
                link.download = 'CK_Resort_Summary_#<?php echo $reservation['reservation_id']; ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();

                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(err => {
                console.error("Download failed:", err);
                alert("Download failed. Please try again.");
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                receipt.style.position = "absolute";
                receipt.style.left = "-9999px";
            });
        }
    </script>
</body>

</html>
