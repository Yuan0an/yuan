<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$res_id = $_GET['id'];

// Fetch reservation details from the correct tables
$stmt = $conn->prepare("
    SELECT b.*,
           c.full_name, c.email, c.phone, c.alt_phone, c.company,
           p.payment_method, p.payment_status, p.total_price,
           e.name as event_base_name
    FROM bookings b
    JOIN customers  c ON b.customer_id = c.id
    JOIN payments   p ON p.booking_id  = b.id
    JOIN events     e ON b.event_id    = e.id
    WHERE b.reservation_id = ?
");
$stmt->bind_param("s", $res_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    die("Reservation not found.");
}

// Fetch all addons for names and current prices
$addon_info = [];
$addons_res = $conn->query("SELECT id, name, price FROM addons");
while ($row = $addons_res->fetch_assoc()) {
    $addon_info[$row['id']] = [
        'name' => $row['name'],
        'price' => floatval($row['price'])
    ];
}

// Fetch special dates for surcharge
$special_dates = [];
$sd_res = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'special_dates' LIMIT 1");
if ($sd_res && $sd_row = $sd_res->fetch_assoc()) {
    $special_dates = array_map('trim', explode(',', $sd_row['setting_value']));
}

// Calculate surcharge again for display
$booking_date = $reservation['booking_date'];
$day_of_week = date('w', strtotime($booking_date));
$is_weekend = ($day_of_week == 0 || $day_of_week == 5 || $day_of_week == 6);
$is_holiday = in_array($booking_date, $special_dates);
$surcharge = ($is_weekend || $is_holiday) ? 1000 : 0;

$addons_booked = json_decode($reservation['addons_json'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Success - CK Reservation</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../uploader/style.css">
    <style>
        .success-wrapper {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .thank-you-banner {
            background: #4CAF50;
            color: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }

        .thank-you-banner i {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .thank-you-banner h1 {
            margin: 0;
            font-size: 32px;
        }

        .thank-you-banner p {
            font-size: 18px;
            margin-top: 10px;
            opacity: 0.9;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #eee;
        }

        .summary-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .summary-header h2 {
            margin: 0;
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }

        .ref-container {
            background: #f0fdf4;
            border: 2px dashed #4CAF50;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .ref-label {
            display: block;
            font-size: 12px;
            color: #4CAF50;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .ref-number {
            font-weight: 800;
            color: #166534;
            font-size: 24px;
            display: block;
            margin: 5px 0;
        }

        .ref-note {
            font-size: 13px;
            color: #166534;
            font-style: italic;
        }

        .summary-body {
            padding: 30px;
        }

        .summary-body h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            color: #2e7d32;
            border-bottom: 2px solid #e8f5e9;
            padding-bottom: 8px;
        }

        .summary-body h3:not(:first-child) {
            margin-top: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item label {
            display: block;
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-item span {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .price-breakdown {
            border-top: 2px dashed #eee;
            padding-top: 25px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
            color: #555;
        }

        .price-row.total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-weight: bold;
            font-size: 20px;
            color: #333;
        }

        .price-row.downpayment {
            color: #2e7d32;
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-weight: bold;
        }

        .payment-instructions {
            margin-top: 30px;
            padding: 20px;
            background: #fff8e1;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }

        .payment-instructions h3 {
            margin-top: 0;
            font-size: 18px;
            color: #856404;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .receipt-header {
            display: none;
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .receipt-header h1 {
            margin: 0;
            font-size: 24px;
        }

        @media print {
            body { background: white; color: black; }
            .success-wrapper { margin: 0; padding: 0; max-width: 100%; }
            .thank-you-banner, .action-buttons, .uploader-section, .payment-instructions, .ref-note {
                display: none !important;
            }
            .summary-card { box-shadow: none; border: 1px solid #ccc; }
            .receipt-header { display: block; }
            .summary-header { border-bottom: 1px solid #333; }
            .ref-container { border: 1px solid #333; background: none; }
            .summary-body h3 { color: black; border-bottom: 1px solid #333; }
            .price-row.downpayment { background: none; color: black; border: 1px solid #333; }
        }
    </style>
</head>

<body>
    <div class="success-wrapper">
        <div class="receipt-header">
            <h1>CK RESORT</h1>
            <p>Official Reservation Receipt</p>
        </div>

        <div class="thank-you-banner">
            <i class="fas fa-check-circle"></i>
            <h1>Thank You!</h1>
            <p>Your reservation request has been submitted.</p>
        </div>

        <div class="summary-card">
            <div class="summary-header">
                <h2>Reservation Details</h2>
                <div class="ref-container">
                    <span class="ref-label">REFERENCE NUMBER</span>
                    <span class="ref-number">#<?php echo $res_id; ?></span>
                    <span class="ref-note">Please remember this Reference Number to check your reservation status.</span>
                </div>
            </div>

            <div class="summary-body">
                <h3>Customer Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <span><?php echo htmlspecialchars($reservation['full_name'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email Address</label>
                        <span><?php echo htmlspecialchars($reservation['email'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Phone Number</label>
                        <span><?php echo htmlspecialchars($reservation['phone'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Company/Organization</label>
                        <span><?php echo htmlspecialchars($reservation['company'] ?: 'Personal'); ?></span>
                    </div>
                </div>

                <h3>Event Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Event Date</label>
                        <span><?php echo date('F j, Y', strtotime($reservation['booking_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Event Type</label>
                        <span><?php echo htmlspecialchars($reservation['event_type']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Time Slot</label>
                        <span><?php echo date('h:i A', strtotime($reservation['start_time'])) . ' - ' . date('h:i A', strtotime($reservation['end_time'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Number of Guests</label>
                        <span><?php echo $reservation['persons']; ?> pax</span>
                    </div>
                    <div class="info-item">
                        <label>Payment Method</label>
                        <span><?php echo $reservation['payment_method']; ?></span>
                    </div>
                </div>

                <div class="price-breakdown">
                    <h3>Cost Summary</h3>
                    <?php
                    $total = floatval($reservation['total_price']);
                    $addons_sum = 0;
                    
                    // 1. Base Rate (Event Name)
                    // We calculate it as total - addons - surcharge
                    foreach ($addons_booked as $id => $qty) {
                        if (isset($addon_info[$id])) {
                            $price = $addon_info[$id]['price'] * (is_numeric($qty) ? intval($qty) : 1);
                            $addons_sum += $price;
                        }
                    }
                    $base_rate = $total - $addons_sum - $surcharge;
                    ?>
                    
                    <div class="price-row">
                        <span>Base Rate (<?php echo htmlspecialchars($reservation['event_base_name']); ?>)</span>
                        <span>P<?php echo number_format($base_rate); ?></span>
                    </div>

                    <?php foreach ($addons_booked as $id => $qty): ?>
                        <?php if (isset($addon_info[$id])): ?>
                            <?php $price = $addon_info[$id]['price'] * (is_numeric($qty) ? intval($qty) : 1); ?>
                            <div class="price-row">
                                <span><?php echo htmlspecialchars($addon_info[$id]['name']); ?> <?php echo is_numeric($qty) ? "(x$qty)" : ""; ?></span>
                                <span>P<?php echo number_format($price); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($surcharge > 0): ?>
                        <div class="price-row" style="color: #e11d48;">
                            <span>Weekend/Holiday Surcharge</span>
                            <span>P<?php echo number_format($surcharge); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="price-row total">
                        <span>Grand Total</span>
                        <span>P<?php echo number_format($total); ?></span>
                    </div>
                    <div class="price-row downpayment">
                        <span>Required Downpayment (50%)</span>
                        <span>P<?php echo number_format($total * 0.5); ?></span>
                    </div>
                </div>

                <div class="payment-instructions">
                    <h3><i class="fas fa-info-circle"></i> Next Steps</h3>
                    <p>Please upload your payment receipt below to confirm your booking.</p>
                </div>

                <div class="uploader-section" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                    <h3 style="text-align: center; color: #333;"><i class="fas fa-file-invoice"></i> Upload Payment Receipt</h3>
                    <?php 
                    $_GET['res_id'] = $res_id;
                    include '../uploader/index.php'; 
                    ?>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-print" style="background: #292929; color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-print"></i> Print Summary
            </button>
            <a href="check_status.php" class="btn" style="background:#3b82f6; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: 600;">
                <i class="fas fa-search"></i> Check Status
            </a>
            <a href="/index.php" class="btn" style="background: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: 600;">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</body>

</html>
