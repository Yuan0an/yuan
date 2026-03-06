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

$addons = json_decode($reservation['addons_json'], true) ?: [];
$addon_prices = [
    'lpg'      => 250,
    'butane'   => 150,
    'bonfire'  => 500,
    'pet'      => 200,
    'darts'    => 250,
    'billiard' => 500
];
$addon_names = [
    'lpg'      => 'LPGas',
    'butane'   => 'Butane',
    'bonfire'  => 'Bonfire',
    'pet'      => 'Pet Fee',
    'darts'    => 'Darts Game',
    'billiard' => 'Billiard'
];
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-header h2 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }

        .ref-number {
            font-weight: bold;
            color: #4CAF50;
            font-size: 18px;
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

        .payment-instructions p {
            margin-bottom: 5px;
            color: #856404;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .btn-print {
            background: #292929ff;
        }

        .btn-home {
            background: #4CAF50;
        }

        @media print {

            .thank-you-banner,
            .action-buttons {
                display: none;
            }

            .success-wrapper {
                margin: 0;
                width: 100%;
            }

            .summary-card {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>

<body>
    <div class="success-wrapper">
        <div class="thank-you-banner">
            <i class="fas fa-check-circle"></i>
            <h1>Thank You!</h1>
            <p>Your reservation request for <strong>
                    <?php echo htmlspecialchars($reservation['event_title'] ?? ''); ?>
                </strong> has been submitted.</p>
        </div>

        <div class="summary-card">
            <div class="summary-header">
                <h2>Reservation Details</h2>
                <div class="ref-number">Ref: #
                    <?php echo $res_id; ?>
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
                        <label>Alt. Phone</label>
                        <span><?php echo htmlspecialchars($reservation['alt_phone'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Company/Organization</label>
                        <span><?php echo htmlspecialchars($reservation['company'] ?: 'Personal'); ?></span>
                    </div>
                </div>

                <h3>Event Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Event Title</label>
                        <span><?php echo htmlspecialchars($reservation['event_title']); ?></span>
                    </div>
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
                    // Calculate base rate again for display or just show total if we didn't store base separately
                    // Since we have total_price, let's show breakdown
                    $total = floatval($reservation['total_price']);
                    $addons = json_decode($reservation['addons_json'], true) ?: [];
                    $addons_sum = 0;
                    foreach ($addons as $key => $qty) {
                        $p = is_numeric($qty) ? intval($qty) * $addon_prices[$key] : $addon_prices[$key];
                        $addons_sum += $p;
                        echo '<div class="price-row">
                                <span>' . $addon_names[$key] . ' ' . (is_numeric($qty) ? '(x' . $qty . ')' : '') . '</span>
                                <span>P' . number_format($p) . '</span>
                             </div>';
                    }
                    $base_rate = $total - $addons_sum;
                    ?>
                    <div class="price-row">
                        <span>Base Rate</span>
                        <span>P
                            <?php echo number_format($base_rate); ?>
                        </span>
                    </div>
                    <div class="price-row total">
                        <span>Total Price</span>
                        <span>P
                            <?php echo number_format($total); ?>
                        </span>
                    </div>
                    <div class="price-row downpayment">
                        <span>Required Downpayment (50%)</span>
                        <span>P
                            <?php echo number_format($total * 0.5); ?>
                        </span>
                    </div>
                </div>

                <div class="payment-instructions">
                    <h3><i class="fas fa-info-circle"></i> Next Steps</h3>
                    <?php if ($reservation['payment_method'] === 'GCash'): ?>
                        <p>Please send your downpayment to <strong>0917-123-4567</strong> (GCash).</p>
                    <?php else: ?>
                        <p>Please transfer your downpayment to <strong>BPI: 1234-5678-90</strong>.</p>
                    <?php endif; ?>
                    <p>Once paid, please upload a screenshot of your receipt below for verification.</p>
                </div>

                <div class="uploader-section" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                    <h3 style="text-align: center; color: #333;"><i class="fas fa-file-invoice"></i> Upload Payment Receipt</h3>
                    <?php 
                    // Set the reservation ID for the uploader
                    $_GET['res_id'] = $res_id;
                    include '../uploader/index.php'; 
                    ?>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Print Summary</button>
            <a href="check_status.php" class="btn btn-home" style="background:#3b82f6"><i class="fas fa-search"></i> Check Status</a>
            <a href="/index.php" class="btn btn-home"><i class="fas fa-home"></i> Back to Home</a>
        </div>
    </div>
</body>

</html>