<?php
require_once 'database/database.php';
require_once 'auto_email/send_booking_email.php';

// Handle form submission
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
$error = '';
$success = '';
$booking_data = null;
$debug_output = '';

if ($booking_id) {
    // Fetch booking details from database
    $query = "SELECT b.*, c.email as guest_email, c.full_name as guest_name, e.name as tour_type, e.is_overnight 
              FROM bookings b
              JOIN customers c ON b.customer_id = c.id
              JOIN events e ON b.event_id = e.id
              WHERE b.id = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $booking_data = $result->fetch_assoc();
        } else {
            $error = "No booking found with ID: " . htmlspecialchars($booking_id);
        }
    } else {
        $error = "Database error: Failed to prepare statement.";
    }
}

// Handle email sending
if (isset($_POST['send_email']) && $booking_data) {
    ob_start(); // Capture debug output
    try {
        require_once 'auto_email/config.php';
        $mail = getMailer();
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';
        
        $sent = sendBookingConfirmationEmail(
            $booking_data['reservation_id'],
            $booking_data['guest_email'],
            $booking_data['guest_name'],
            $booking_data['event_title'],
            $booking_data['event_type'],
            $booking_data['tour_type'],
            $booking_data['persons'],
            $booking_data['booking_date'],
            $booking_data['start_time'],
            $booking_data['end_time'],
            (bool)$booking_data['is_overnight']
        );

        if ($sent) {
            $success = "Email sent successfully to <strong>" . htmlspecialchars($booking_data['guest_email']) . "</strong>!";
        } else {
            $error = "Failed to send email. Check debug output below.";
        }
    } catch (Exception $e) {
        $error = "Caught Exception: " . $e->getMessage();
    }
    $debug_output = ob_get_clean();
}

// Fetch recent bookings for easy testing
$recent_bookings = $conn->query("SELECT id, event_title, created_at FROM bookings ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Delivery Test | Resort Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --success: #10b981;
            --error: #ef4444;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg); 
            color: var(--text-main); 
            margin: 0; 
            display: flex; 
            justify-content: center; 
            padding: 40px 20px;
        }

        .container { 
            max-width: 800px; 
            width: 100%; 
        }

        .header { 
            text-align: center; 
            margin-bottom: 32px; 
        }
        .header h1 { margin: 0; font-size: 24px; color: var(--primary); }
        .header p { color: var(--text-muted); margin-top: 8px; }

        .card { 
            background: var(--card-bg); 
            padding: 32px; 
            border-radius: 16px; 
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        input[type="number"], input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        input:focus { border-color: var(--primary); outline: none; }

        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            font-size: 16px;
        }
        .btn:hover { background: var(--primary-hover); }

        .alert { 
            padding: 16px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            font-weight: 500;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .booking-details {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid var(--border);
        }
        .booking-details h3 { margin-top: 0; font-size: 18px; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .detail-label { color: var(--text-muted); }
        .detail-value { font-weight: 600; }

        .recent-list { margin-top: 24px; }
        .recent-title { font-weight: 600; font-size: 14px; color: var(--text-muted); margin-bottom: 12px; display: block; }
        .recent-item-btn { 
            display: flex; 
            justify-content: space-between; 
            padding: 12px; 
            background: #fff; 
            border: 1px solid var(--border); 
            border-radius: 8px; 
            margin-bottom: 8px;
            cursor: pointer;
            width:100%; 
            text-align:left; 
            font-family:inherit;
            transition: all 0.2s;
        }
        .recent-item-btn:hover { background: #f8fafc; border-color: var(--primary); transform: translateY(-1px); }
        .recent-id { font-weight: bold; color: var(--primary); margin-right: 8px; }

        .debug-container {
            margin-top: 32px;
            padding: 20px;
            background: #0f172a;
            color: #cbd5e1;
            border-radius: 12px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            overflow-x: auto;
            border: 1px solid #1e293b;
        }
        .debug-container h4 { margin-top: 0; color: #94a3b8; border-bottom: 1px solid #1e293b; padding-bottom: 8px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Delivery Test</h1>
            <p>Admin Tool: Verify SMTP delivery with real database records.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label for="booking_id">Search Booking ID</label>
                    <div style="display:flex; gap: 8px;">
                        <input type="number" name="booking_id" id="booking_id" placeholder="ID..." value="<?php echo $booking_id; ?>" required style="flex:1;">
                        <button type="submit" class="btn" style="width: auto;">Fetch</button>
                    </div>
                </div>
            </form>

            <?php if ($booking_data): ?>
                <div class="booking-details">
                    <h3>Booking #<?php echo $booking_data['id']; ?></h3>
                    <div class="detail-row">
                        <span class="detail-label">Guest Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking_data['guest_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Guest Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking_data['guest_email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Event:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking_data['event_title']); ?> (<?php echo htmlspecialchars($booking_data['event_type']); ?>)</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tour Type:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking_data['tour_type']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check-in:</span>
                        <span class="detail-value"><?php echo date('F j, Y', strtotime($booking_data['booking_date'])); ?> at <?php echo date('h:i A', strtotime($booking_data['start_time'])); ?></span>
                    </div>

                    <form method="POST" style="margin-top: 24px;">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_data['id']; ?>">
                        <button type="submit" name="send_email" class="btn">🚀 Dispatch Confirmation Email</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
            <div class="recent-list">
                <span class="recent-title">Recent Reservations</span>
                <?php while($row = $recent_bookings->fetch_assoc()): ?>
                    <form method="POST">
                        <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="recent-item-btn">
                            <span><span class="recent-id">#<?php echo $row['id']; ?></span> <?php echo htmlspecialchars($row['event_title'] ?: 'Unnamed Event'); ?></span>
                            <span style="color:var(--text-muted); font-size:12px;"><?php echo date('M d, H:m', strtotime($row['created_at'])); ?></span>
                        </button>
                    </form>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if ($debug_output): ?>
            <div class="debug-container">
                <h4>📡 SMTP Debug Output</h4>
                <div style="white-space: pre-wrap; line-height: 1.5;">
                    <?php echo $debug_output; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
