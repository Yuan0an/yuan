<?php
require 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Auto-mark past events before handling actions or fetching data
$conn->query("UPDATE bookings SET status = 'completed' WHERE status = 'approved' AND 
    DATE_ADD(CONCAT(booking_date, ' ', end_time), INTERVAL IF(end_time <= start_time, 1, 0) DAY) <= NOW() - INTERVAL 1 HOUR");
$conn->query("UPDATE bookings SET status = 'rejected' WHERE status = 'pending' AND 
    DATE_ADD(CONCAT(booking_date, ' ', end_time), INTERVAL IF(end_time <= start_time, 1, 0) DAY) <= NOW() - INTERVAL 1 HOUR");

// Auto-delete old cancelled and rejected reservations
$conn->query("DELETE FROM bookings WHERE status = 'cancelled' AND booking_date <= CURDATE() - INTERVAL 7 DAY");
$conn->query("DELETE FROM bookings WHERE status = 'rejected' AND booking_date <= CURDATE() - INTERVAL 3 DAY");

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    $admin_id = $_SESSION['admin_id'];

    switch ($action) {
        case 'approve':
            // Check if it's paid first (in payments table)
            $check = $conn->prepare("SELECT payment_status FROM payments WHERE booking_id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $p_res = $check->get_result()->fetch_assoc();
            
            if ($p_res && $p_res['payment_status'] !== 'paid') {
                $_SESSION['error'] = 'Cannot approve an unpaid reservation. Please mark as paid first.';
                header('Location: reservations.php' . (isset($_GET['id']) ? '?id=' . $id : ''));
                exit;
            }

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE bookings SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                $stmt->bind_param("ii", $admin_id, $id);
                $stmt->execute();

                $stmt2 = $conn->prepare("UPDATE payments SET payment_status = 'paid' WHERE booking_id = ?");
                $stmt2->bind_param("i", $id);
                $stmt2->execute();

                $conn->commit();

                // ── Auto-resolve competing reservations (same event, same date) ──
                // Get the event_id and booking_date of the just-approved booking
                $approved_info = $conn->prepare("SELECT event_id, booking_date FROM bookings WHERE id = ?");
                $approved_info->bind_param("i", $id);
                $approved_info->execute();
                $approved_row = $approved_info->get_result()->fetch_assoc();

                if ($approved_row) {
                    $ev_id    = $approved_row['event_id'];
                    $bk_date  = $approved_row['booking_date'];

                    // Find all OTHER pending bookings for the same event + date
                    $rivals = $conn->prepare("
                        SELECT b.id, p.payment_status
                        FROM bookings b
                        JOIN payments p ON p.booking_id = b.id
                        WHERE b.event_id = ?
                          AND b.booking_date = ?
                          AND b.status = 'pending'
                          AND b.id != ?
                    ");
                    $rivals->bind_param("isi", $ev_id, $bk_date, $id);
                    $rivals->execute();
                    $rivals_result = $rivals->get_result();

                    $refund_ids   = [];
                    $rejected_ids = [];

                    while ($rival = $rivals_result->fetch_assoc()) {
                        if ($rival['payment_status'] === 'paid') {
                            // Case #1: paid → for refund
                            $refund_ids[] = $rival['id'];
                        } else {
                            // Case #2: unpaid → rejected
                            $rejected_ids[] = $rival['id'];
                        }
                    }

                    // Bulk-update for_refund
                    if (!empty($refund_ids)) {
                        $placeholders = implode(',', array_fill(0, count($refund_ids), '?'));
                        $types = str_repeat('i', count($refund_ids));
                        $upd = $conn->prepare("UPDATE bookings SET status = 'for_refund' WHERE id IN ($placeholders)");
                        $upd->bind_param($types, ...$refund_ids);
                        $upd->execute();
                    }

                    // Bulk-update rejected
                    if (!empty($rejected_ids)) {
                        $placeholders = implode(',', array_fill(0, count($rejected_ids), '?'));
                        $types = str_repeat('i', count($rejected_ids));
                        $upd = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id IN ($placeholders)");
                        $upd->bind_param($types, ...$rejected_ids);
                        $upd->execute();
                    }
                }
                // ──────────────────────────────────────────────────────────────────

                // ── Send Approval Notification Email ──────────────────────────
                // Fetch details for the email
                $details_stmt = $conn->prepare("
                    SELECT b.reservation_id, b.booking_date, b.start_time, b.event_title, c.email, c.full_name 
                    FROM bookings b 
                    JOIN customers c ON b.customer_id = c.id 
                    WHERE b.id = ?
                ");
                $details_stmt->bind_param("i", $id);
                $details_stmt->execute();
                $details = $details_stmt->get_result()->fetch_assoc();

                if ($details) {
                    require_once __DIR__ . '/../auto_email/send_booking_email.php';
                        sendBookingApprovalEmail(
                            $details['reservation_id'],
                            $details['email'],
                            $details['full_name'],
                            $details['event_title'],
                            $details['booking_date'],
                            $details['start_time'],
                            false,
                            10 // 10s timeout for admin approval
                        );
                }
                // ─────────────────────────────────────────────────────────────

                $refund_count   = count($refund_ids ?? []);
                $rejected_count = count($rejected_ids ?? []);
                $msg = 'Reservation approved successfully.';
                if ($refund_count > 0)   $msg .= " $refund_count competing paid reservation(s) moved to For Refund.";
                if ($rejected_count > 0) $msg .= " $rejected_count unpaid reservation(s) auto-rejected.";
                $_SESSION['message'] = $msg;
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'Error approving reservation: ' . $e->getMessage();
            }
            break;

        case 'mark_paid':
            $stmt = $conn->prepare("UPDATE payments SET payment_status = 'paid' WHERE booking_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['message'] = 'Reservation marked as paid';
            break;

        case 'reject':
            $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ? AND status = 'pending'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['message'] = 'Reservation rejected';
            break;

        case 'cancel':
            $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['message'] = 'Reservation cancelled';
            break;

        case 'mark_refund':
            $stmt = $conn->prepare("UPDATE bookings SET status = 'for_refund' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['message'] = 'Reservation marked for refund';
            break;

        case 'refund_done':
        case 'mark_refunded':
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $stmt2 = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
                
                $conn->commit();
                $_SESSION['message'] = 'Reservation marked as refunded';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'Error processing refund: ' . $e->getMessage();
            }
            break;
    }

    header('Location: reservations.php' . (isset($_GET['id']) && !isset($_GET['action']) ? '?id=' . $id : ''));
    exit;
}

// Handle form submission for notes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_notes'])) {
    $id = intval($_POST['id']);
    $notes = $conn->real_escape_string(trim($_POST['admin_notes']));

    $stmt = $conn->prepare("UPDATE bookings SET admin_notes = ? WHERE id = ?");
    $stmt->bind_param("si", $notes, $id);
    $stmt->execute();

    $_SESSION['message'] = 'Notes updated successfully';
    header('Location: reservations.php?id=' . $id);
    exit;
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "
    SELECT b.*, c.full_name, c.email, c.phone, 
           p.payment_method, p.payment_status, p.total_price,
           e.name as event_name, a.full_name as admin_name
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN payments p ON p.booking_id = b.id
    JOIN events e ON b.event_id = e.id
    LEFT JOIN admins a ON b.approved_by = a.id
    WHERE 1=1
";

$params = [];
$types = '';

if ($status == 'refunded') {
    $query .= " AND p.payment_status = 'refunded'";
} elseif ($status != 'all') {
    $query .= " AND b.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($event_id > 0) {
    $query .= " AND b.event_id = ?";
    $params[] = $event_id;
    $types .= 'i';
}

if (!empty($date_from)) {
    $query .= " AND b.booking_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $query .= " AND b.booking_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Search by ID/Name/Email
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $query .= " AND (b.reservation_id LIKE ? OR c.full_name LIKE ? OR c.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$query .= " ORDER BY b.booking_date ASC, b.start_time ASC";

// Get reservations
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reservations = $stmt->get_result();

// Get all events for filter
$events = $conn->query("SELECT * FROM events ORDER BY name");

// Get single reservation for view mode
$single_reservation = null;
if (isset($_GET['id']) && !isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("
        SELECT b.*, c.full_name, c.email, c.phone, c.alt_phone, c.company,
               p.payment_method, p.payment_status, p.time_uploaded, p.total_price,
               p.payment_proof, p.receipt_data,
               e.name as event_name, e.max_persons, 
               a.full_name as admin_name, a.email as admin_email
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN payments p ON p.booking_id = b.id
        JOIN events e ON b.event_id = e.id
        LEFT JOIN admins a ON b.approved_by = a.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $single_reservation = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Admin</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Admin Image Viewer Styles */
        .receipt-thumbnail-container {
            margin-top: 10px;
            position: relative;
            display: inline-block;
        }
        .receipt-thumbnail {
            max-width: 200px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .receipt-thumbnail:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .receipt-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: 8px;
            pointer-events: none;
        }
        .receipt-thumbnail-container:hover .receipt-overlay {
            opacity: 1;
        }

        /* Modal Styles */
        .receipt-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9);
            padding: 40px;
            box-sizing: border-box;
            justify-content: center;
            align-items: center;
        }
        .receipt-modal.show { display: flex; }
        .modal-content {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 4px;
        }
        .modal-close {
            position: absolute;
            top: 20px; right: 30px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-caption {
            position: absolute;
            bottom: 20px;
            color: #ccc;
            text-align: center;
            width: 100%;
        }

        /* Alignment Fixes */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .action-buttons-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-back {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .btn-back:hover {
            color: var(--accent);
        }

        /* Flash Message / Toast Styles */
        .alert-flash {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            background: #fff;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-left: 4px solid var(--cs-success);
            max-width: 400px;
        }

        .alert-flash.alert-error {
            border-left-color: var(--danger);
        }

        .alert-flash i {
            font-size: 1.25rem;
            color: var(--cs-success);
        }

        .alert-flash.alert-error i {
            color: var(--danger);
        }

        .alert-flash .message-content {
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.9375rem;
        }

        .alert-flash.fade-out {
            animation: fadeOutDown 0.5s ease forwards;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes fadeOutDown {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(20px); opacity: 0; }
        }
    </style>
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
            <a href="reservations.php" class="active">
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
                <h1>
                    <i class="fas fa-list"></i>
                    <?php echo isset($single_reservation) ? 'Reservation Details' : 'Manage Reservations'; ?>
                </h1>
            </div>
            <div class="topbar-right">
                <span class="admin-info">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['admin_name']; ?>
                </span>
            </div>
        </header>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-flash alert-success">
                <i class="fas fa-check-circle"></i>
                <div class="message-content"><?php echo $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-flash alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div class="message-content"><?php echo $_SESSION['error']; ?></div>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>


        <?php if (isset($single_reservation)): ?>
            <!-- Single Reservation View -->
            <div class="reservation-detail">
                <div class="detail-header">
                    <div class="header-left">
                        <span class="status-badge <?php echo $single_reservation['status']; ?>">
                            <?php echo ucfirst($single_reservation['status'] == 'for_refund' ? 'Refund Pending' : $single_reservation['status']); ?>
                        </span>
                        <?php if (in_array($single_reservation['status'], ['cancelled', 'rejected']) && $single_reservation['payment_status'] == 'paid'): ?>
                            <span class="status-badge refund-pending">
                                Refund Pending
                            </span>
                        <?php endif; ?>
                        <h2>Reservation #<?php echo $single_reservation['reservation_id']; ?></h2>
                        <p><i class="far fa-clock"></i> Booked on <?php echo date('F j, Y', strtotime($single_reservation['created_at'])); ?></p>
                    </div>
                    <div class="header-right">
                        <a href="reservations.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <div class="action-buttons-group">
                            <?php if ($single_reservation['status'] == 'pending'): ?>
                                <?php if ($single_reservation['payment_status'] !== 'paid'): ?>
                                    <a href="?action=mark_paid&id=<?php echo $single_reservation['id']; ?>" class="dropdown-item approve"
                                        onclick="return confirm('Mark this reservation as paid?')">
                                        <i class="fas fa-money-bill-wave"></i> Mark as Paid
                                    </a>
                                <?php else: ?>
                                    <a href="?action=approve&id=<?php echo $single_reservation['id']; ?>" class="dropdown-item approve"
                                        onclick="return confirm('Approve this reservation?')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                <?php endif; ?>
                                <a href="?action=reject&id=<?php echo $single_reservation['id']; ?>" class="dropdown-item reject"
                                    onclick="return confirm('Reject this reservation?')">
                                    <i class="fas fa-times"></i> Reject
                                </a>
                            <?php endif; ?>
                            <?php if ($single_reservation['status'] == 'approved'): ?>
                                <a href="?action=cancel&id=<?php echo $single_reservation['id']; ?>" class="dropdown-item cancel"
                                    onclick="return confirm('Cancel this reservation?')">
                                    <i class="fas fa-ban"></i> Cancel
                                </a>
                            <?php endif; ?>
                            <?php if (($single_reservation['status'] == 'for_refund') || (in_array($single_reservation['status'], ['cancelled', 'rejected']) && $single_reservation['payment_status'] == 'paid')): ?>
                                <a href="?action=mark_refunded&id=<?php echo $single_reservation['id']; ?>" class="dropdown-item refund"
                                    onclick="return confirm('Mark this reservation as refunded?')">
                                    <i class="fas fa-undo"></i> Mark as Refunded
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="detail-cards">
                    <div class="detail-card">
                        <h3><i class="fas fa-user"></i> Customer Information</h3>
                        <div class="detail-content">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($single_reservation['full_name'] ?? ''); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($single_reservation['email'] ?? ''); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($single_reservation['phone'] ?? ''); ?></p>
                            <?php if (!empty($single_reservation['alt_phone'])): ?>
                                <p><strong>Alt Phone:</strong> <?php echo htmlspecialchars($single_reservation['alt_phone'] ?? ''); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($company = $single_reservation['company'] ?? '')): ?>
                                <p><strong>Company:</strong> <?php echo htmlspecialchars($company); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h3><i class="fas fa-calendar-check"></i> Event Details</h3>
                        <div class="detail-content">
                            <p><strong>Event:</strong> <?php echo htmlspecialchars($single_reservation['event_name'] ?? ''); ?>
                            </p>
                            <p><strong>Event Title:</strong>
                                <?php echo htmlspecialchars($single_reservation['event_title'] ?? ''); ?></p>
                            <p><strong>Event Type:</strong>
                                <?php echo htmlspecialchars($single_reservation['event_type'] ?? ''); ?></p>
                            <p><strong>Date:</strong>
                                <?php echo date('F j, Y', strtotime($single_reservation['booking_date'])); ?></p>
                            <p><strong>Time:</strong>
                                <?php echo date('g:i A', strtotime($single_reservation['start_time'])); ?> to
                                <?php echo date('g:i A', strtotime($single_reservation['end_time'])); ?>
                            </p>
                            <p><strong>Guests:</strong> <?php echo $single_reservation['persons']; ?> persons</p>
                            <p><strong>Capacity:</strong> <?php echo $single_reservation['max_persons']; ?> persons max</p>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                        <div class="detail-content">
                            <p><strong>Payment Method:</strong> <?php echo $single_reservation['payment_method']; ?></p>
                            <p><strong>Payment Status:</strong>
                                <span class="payment-status <?php echo $single_reservation['payment_status']; ?>">
                                    <?php echo ucfirst($single_reservation['payment_status']); ?>
                                </span>
                            </p>
                            <?php
                            // Receipt image: prefer base64 data URI (Railway-safe), fall back to legacy path
                            $receipt_src = '';
                            if (!empty($single_reservation['receipt_data']) && strpos($single_reservation['receipt_data'], 'data:image') === 0) {
                                $receipt_src = $single_reservation['receipt_data'];
                            } elseif (!empty($single_reservation['payment_proof'])) {
                                // Legacy path fallback (only works on localhost)
                                $receipt_src = '/' . ltrim($single_reservation['payment_proof'], '/');
                            }
                            if ($receipt_src !== ''):
                            ?>
                                <p><strong>Payment Receipt:</strong></p>
                                <div class="receipt-thumbnail-container" onclick="openReceiptModal('<?php echo htmlspecialchars($receipt_src, ENT_QUOTES); ?>', 'Receipt #<?php echo $single_reservation['id']; ?>')">
                                    <img src="<?php echo htmlspecialchars($receipt_src, ENT_QUOTES); ?>" alt="Receipt" class="receipt-thumbnail">
                                    <div class="thumbnail-overlay">
                                        <i class="fas fa-search-plus"></i>
                                    </div>
                                </div>
                                <p style="font-size: 11px; color: #888; margin-top: 5px;">Uploaded on <?php echo date('F j, Y g:i A', strtotime($single_reservation['time_uploaded'])); ?></p>
                            <?php else: ?>
                                <p><strong>Payment Proof:</strong> <span class="text-muted">No proof uploaded yet</span></p>
                            <?php endif; ?>
                            <?php if ($single_reservation['status'] == 'approved'): ?>
                                <p><strong>Approved By:</strong> <?php echo $single_reservation['admin_name'] ?: 'N/A'; ?></p>
                                <p><strong>Approved At:</strong>
                                    <?php echo $single_reservation['approved_at'] ? date('F j, Y g:i A', strtotime($single_reservation['approved_at'])) : 'N/A'; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="detail-notes">
                    <h3><i class="fas fa-sticky-note"></i> Admin Notes</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $single_reservation['id']; ?>">
                        <textarea name="admin_notes" rows="4"
                            placeholder="Add notes about this reservation..."><?php echo htmlspecialchars($single_reservation['admin_notes'] ?? ''); ?></textarea>
                        <button type="submit" name="update_notes" class="btn-save">
                            <i class="fas fa-save"></i> Save Notes
                        </button>
                    </form>
                </div>

                <div class="detail-timestamps">
                    <p><strong>Created:</strong>
                        <?php echo date('F j, Y g:i A', strtotime($single_reservation['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong>
                        <?php echo date('F j, Y g:i A', strtotime($single_reservation['updated_at'])); ?></p>
                </div>
            </div>

        <?php else: ?>
            <!-- Status Tabs -->
            <div class="status-tabs">
                <a href="reservations.php" class="status-tab <?php echo $status == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Reservations
                </a>
                <a href="reservations.php?status=pending" class="status-tab pending-tab <?php echo $status == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="reservations.php?status=approved" class="status-tab approved-tab <?php echo $status == 'approved' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Approved
                </a>
                <a href="reservations.php?status=rejected" class="status-tab rejected-tab <?php echo $status == 'rejected' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Rejected
                </a>
                <a href="reservations.php?status=cancelled" class="status-tab cancelled-tab <?php echo $status == 'cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-ban"></i> Cancelled
                </a>
                <a href="reservations.php?status=completed" class="status-tab completed-tab <?php echo $status == 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-flag-checkered"></i> Completed
                </a>
                <a href="reservations.php?status=for_refund" class="status-tab refund-tab <?php echo $status == 'for_refund' ? 'active' : ''; ?>">
                    <i class="fas fa-undo-alt"></i> For Refund
                </a>
                <a href="reservations.php?status=refunded" class="status-tab refunded-tab <?php echo $status == 'refunded' ? 'active' : ''; ?>">
                    <i class="fas fa-undo"></i> Refunded
                </a>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="status" value="<?php echo $status; ?>">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label><i class="fas fa-calendar-alt"></i> Event</label>
                            <select name="event_id" onchange="this.form.submit()">
                                <option value="0">All Events</option>
                                <?php while ($event = $events->fetch_assoc()): ?>
                                    <option value="<?php echo $event['id']; ?>" <?php echo $event_id == $event['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['name'] ?? ''); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> Date From</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>"
                                onchange="this.form.submit()">
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> Date To</label>
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>" onchange="this.form.submit()">
                        </div>

                        <div class="filter-group" style="flex: 1; min-width: 200px;">
                            <label><i class="fas fa-search"></i> Search ID/Guest</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Reservation ID, Name, or Email" onchange="this.form.submit()">
                        </div>

                        <div class="filter-group">
                            <button type="button" onclick="window.location.href='reservations.php'" class="btn-reset">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Reservations Grouped by Date -->
            <div class="reservations-by-date">
                <?php
                // Group reservations by booking_date
                $grouped = [];
                while ($reservation = $reservations->fetch_assoc()) {
                    $date_key = $reservation['booking_date'];
                    if (!isset($grouped[$date_key])) {
                        $grouped[$date_key] = [];
                    }
                    $grouped[$date_key][] = $reservation;
                }

                // Sort by date (ascending — soonest first)
                ksort($grouped);

                if (empty($grouped)):
                ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No reservations found</h3>
                        <p>There are no reservations matching your current filters.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($grouped as $date_key => $date_reservations):
                        $date_obj = new DateTime($date_key);
                        $today = new DateTime(date('Y-m-d'));
                        $tomorrow = (clone $today)->modify('+1 day');
                        $is_today = ($date_obj->format('Y-m-d') === $today->format('Y-m-d'));
                        $is_tomorrow = ($date_obj->format('Y-m-d') === $tomorrow->format('Y-m-d'));
                        $is_past = ($date_obj < $today);

                        // Count statuses for this date
                        $date_pending = 0;
                        $date_approved = 0;
                        $date_completed = 0;
                        $date_rejected = 0;
                        $date_cancelled = 0;
                        $date_refund = 0;
                        $date_refunded = 0;
                        foreach ($date_reservations as $r) {
                            if ($r['status'] === 'pending') $date_pending++;
                            elseif ($r['status'] === 'approved') $date_approved++;
                            elseif ($r['status'] === 'completed') $date_completed++;
                            elseif ($r['status'] === 'rejected') $date_rejected++;
                            elseif ($r['status'] === 'cancelled' && $r['payment_status'] !== 'refunded') $date_cancelled++;
                            elseif ($r['status'] === 'for_refund') $date_refund++;
                            elseif ($r['payment_status'] === 'refunded') $date_refunded++;
                        }
                    ?>
                        <div class="date-group <?php echo $is_today ? 'today' : ($is_past ? 'past' : ''); ?>">
                            <div class="date-header">
                                <div class="date-info">
                                    <span class="date-day"><?php echo $date_obj->format('d'); ?></span>
                                    <div class="date-meta">
                                        <span class="date-weekday"><?php echo $date_obj->format('l'); ?></span>
                                        <span class="date-full"><?php echo $date_obj->format('F Y'); ?></span>
                                    </div>
                                    <?php if ($is_today): ?>
                                        <span class="date-badge today-badge">Today</span>
                                    <?php elseif ($is_tomorrow): ?>
                                        <span class="date-badge tomorrow-badge">Tomorrow</span>
                                    <?php elseif ($is_past): ?>
                                        <span class="date-badge past-badge">Past</span>
                                    <?php endif; ?>
                                </div>
                                <div class="date-stats">
                                    <span class="date-count"><?php echo count($date_reservations); ?> reservation<?php echo count($date_reservations) > 1 ? 's' : ''; ?></span>
                                    <?php if ($date_pending > 0): ?>
                                        <span class="mini-badge pending-mini"><?php echo $date_pending; ?> pending</span>
                                    <?php endif; ?>
                                    <?php if ($date_approved > 0): ?>
                                        <span class="mini-badge approved-mini"><?php echo $date_approved; ?> approved</span>
                                    <?php endif; ?>
                                    <?php if ($date_completed > 0): ?>
                                        <span class="mini-badge completed-mini"><?php echo $date_completed; ?> completed</span>
                                    <?php endif; ?>
                                    <?php if ($date_rejected > 0): ?>
                                        <span class="mini-badge rejected-mini"><?php echo $date_rejected; ?> rejected</span>
                                    <?php endif; ?>
                                    <?php if ($date_cancelled > 0): ?>
                                        <span class="mini-badge cancelled-mini"><?php echo $date_cancelled; ?> cancelled</span>
                                    <?php endif; ?>
                                    <?php if ($date_refund > 0): ?>
                                        <span class="mini-badge refund-mini"><?php echo $date_refund; ?> for refund</span>
                                    <?php endif; ?>
                                    <?php if ($date_refunded > 0): ?>
                                        <span class="mini-badge refunded-mini"><?php echo $date_refunded; ?> refunded</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="date-reservations">
                                <?php foreach ($date_reservations as $res):
                                    $start_time = date('g:i A', strtotime($res['start_time']));
                                    $end_time = date('g:i A', strtotime($res['end_time']));
                                ?>
                                    <div class="reservation-card status-<?php echo $res['status']; ?>">
                                        <div class="res-card-left">
                                            <div class="res-status-indicator <?php echo $res['status']; ?>"></div>
                                            <div class="res-card-info">
                                                <div class="res-card-top">
                                                    <span class="res-id">#<?php echo $res['reservation_id']; ?></span>
                                                    <span class="status-badge <?php echo $res['status']; ?>">
                                                        <?php echo ucfirst($res['status'] == 'for_refund' ? 'refund pending' : $res['status']); ?>
                                                    </span>
                                                    <?php if (in_array($res['status'], ['cancelled', 'rejected']) && $res['payment_status'] == 'paid'): ?>
                                                        <span class="status-badge refund-pending">
                                                            Refund Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h4 class="res-customer"><?php echo htmlspecialchars($res['full_name'] ?? ''); ?></h4>
                                                <div class="res-details-row">
                                                    <span class="res-detail">
                                                        <i class="fas fa-envelope"></i>
                                                        <?php echo htmlspecialchars($res['email'] ?? ''); ?>
                                                    </span>
                                                    <span class="res-detail">
                                                        <i class="fas fa-phone"></i>
                                                        <?php echo htmlspecialchars($res['phone'] ?? ''); ?>
                                                    </span>
                                                </div>
                                                <div class="res-details-row">
                                                    <span class="res-detail">
                                                        <i class="fas fa-calendar-check"></i>
                                                        <?php echo htmlspecialchars($res['event_name'] ?? ''); ?>
                                                        <?php if (!empty($res['event_title'])): ?>
                                                            — <?php echo htmlspecialchars($res['event_title'] ?? ''); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="res-detail">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo $start_time; ?> – <?php echo $end_time; ?>
                                                    </span>
                                                    <span class="res-detail">
                                                        <i class="fas fa-users"></i>
                                                        <?php echo $res['persons']; ?> guest<?php echo $res['persons'] > 1 ? 's' : ''; ?>
                                                    </span>
                                                </div>
                                                <div class="res-details-row">
                                                    <span class="res-detail">
                                                        <i class="fas fa-credit-card"></i>
                                                        <?php echo $res['payment_method']; ?>
                                                        <span class="payment-status <?php echo $res['payment_status']; ?>">
                                                            <?php echo ucfirst($res['payment_status']); ?>
                                                        </span>
                                                    </span>
                                                    <?php if (($res['status'] == 'approved' || $res['status'] == 'completed') && !empty($res['admin_name'])): ?>
                                                        <span class="res-detail">
                                                            <i class="fas fa-user-check"></i>
                                                            Approved by <?php echo $res['admin_name']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="res-card-actions">
                                            <a href="?id=<?php echo $res['id']; ?>" class="btn-card-action view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($res['status'] == 'pending'): ?>
                                                <?php if ($res['payment_status'] !== 'paid'): ?>
                                                    <a href="?action=mark_paid&id=<?php echo $res['id']; ?>" class="btn-card-action approve" title="Mark as Paid">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?action=approve&id=<?php echo $res['id']; ?>" class="btn-card-action approve" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?action=reject&id=<?php echo $res['id']; ?>" class="btn-card-action reject" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (($res['status'] == 'for_refund') || (in_array($res['status'], ['cancelled', 'rejected']) && $res['payment_status'] == 'paid')): ?>
                                                <a href="?action=mark_refunded&id=<?php echo $res['id']; ?>" class="btn-card-action approve" title="Mark as Refunded"
                                                   onclick="return confirm('Mark this refund as completed?')">
                                                    <i class="fas fa-check-double"></i>
                                                </a>
                                            <?php elseif ($res['status'] == 'approved' || ($res['status'] == 'pending' && $res['payment_status'] == 'paid')): ?>
                                                <a href="?action=cancel&id=<?php echo $res['id']; ?>" class="btn-card-action cancel" title="Cancel & Refund Later">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Dropdown toggle
        function toggleDropdown(button) {
            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu !== button.nextElementSibling) {
                    menu.classList.remove('show');
                }
            });
            button.nextElementSibling.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.btn-actions') && !event.target.closest('.btn-actions')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        }

        // Auto-hide success message after 5 seconds with fade animation
        setTimeout(function () {
            var alerts = document.querySelectorAll('.alert-flash');
            alerts.forEach(function (alert) {
                alert.classList.add('fade-out');
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // Modal Logic
        function openReceiptModal(src, caption) {
            const modal = document.getElementById('receiptModal');
            const modalImg = document.getElementById('modalImage');
            const modalCaption = document.getElementById('modalCaption');
            
            modal.classList.add('show');
            modalImg.src = src;
            modalCaption.textContent = caption;
            document.body.style.overflow = 'hidden'; // Prevent scroll
        }

        function closeReceiptModal() {
            const modal = document.getElementById('receiptModal');
            modal.classList.remove('show');
            document.body.style.overflow = ''; 
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeReceiptModal();
        });
    </script>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="receipt-modal" onclick="closeReceiptModal()">
        <span class="modal-close">&times;</span>
        <img class="modal-content" id="modalImage">
        <div id="modalCaption" class="modal-caption"></div>
    </div>
</body>

</html>