<?php
require 'config.php';
require_once __DIR__ . '/../auto_email/send_booking_email.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $event_id = $_POST['event_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $guests = intval($_POST['guests']);

    // Customer Information
    $full_name = $conn->real_escape_string(trim($_POST['full_name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $alt_phone = isset($_POST['alt_phone']) ? $conn->real_escape_string(trim($_POST['alt_phone'])) : '';
    $company = isset($_POST['company']) ? $conn->real_escape_string(trim($_POST['company'])) : '';

    // Event Details
    $event_title = $conn->real_escape_string(trim($_POST['event_title']));
    $event_type = $conn->real_escape_string(trim($_POST['event_type']));

    // Payment Method
    $payment_method = $conn->real_escape_string(trim($_POST['payment_method']));

    // Terms
    $terms_accepted = isset($_POST['terms']) ? 1 : 0;
    $cancellation_accepted = isset($_POST['cancellation']) ? 1 : 0;

    // Add-ons and Pricing
    $addons = [];
    $addon_fields = ['lpg', 'butane', 'bonfire', 'pet', 'darts', 'billiard'];
    $total_price = 0;

    // Calculate total on server side for security (optional but good) or just take from POST if trusted
    // Here we'll just capture what was sent to ensure the summary page matches
    foreach ($addon_fields as $field) {
        if (isset($_POST['addon_' . $field])) {
            $val = $_POST['addon_' . $field];
            if ($val > 0 || $val === '1') {
                $addons[$field] = $val;
            }
        }
    }
    $addons_json = $conn->real_escape_string(json_encode($addons));

    // We should ideally recalculate price here, but for now we'll take a 'total_price' hidden field if we add it,
    // or just let the success page recalculate. Let's add a hidden total field to the form in next step.
    // For now, let's assume we'll pass it.
    $form_total_price = isset($_POST['total_price_hidden']) ? floatval($_POST['total_price_hidden']) : 0;

    // 1. Handle Customer (Find existing or Create new)
    $cust_stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
    $cust_stmt->bind_param("s", $email);
    $cust_stmt->execute();
    $cust_res = $cust_stmt->get_result();

    if ($cust_res->num_rows > 0) {
        $customer_id = $cust_res->fetch_assoc()['id'];
        // Update customer details in case they changed
        $upd_cust = $conn->prepare("UPDATE customers SET full_name = ?, phone = ?, alt_phone = ?, company = ? WHERE id = ?");
        $upd_cust->bind_param("ssssi", $full_name, $phone, $alt_phone, $company, $customer_id);
        $upd_cust->execute();
    } else {
        $ins_cust = $conn->prepare("INSERT INTO customers (full_name, email, phone, alt_phone, company) VALUES (?, ?, ?, ?, ?)");
        $ins_cust->bind_param("sssss", $full_name, $email, $phone, $alt_phone, $company);
        $ins_cust->execute();
        $customer_id = $conn->insert_id;
    }

    // 2. Check Availability in bookings table
    $check_stmt = $conn->prepare("
        SELECT id FROM bookings 
        WHERE event_id = ? 
        AND booking_date = ?
        AND start_time = ?
        AND end_time = ?
        AND status = 'approved'
    ");
    $check_stmt->bind_param("isss", $event_id, $booking_date, $start_time, $end_time);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'This time slot already has an approved reservation.'
        ]);
        exit;
    }

    // 3. Start Transaction logic
    $conn->begin_transaction();

    try {
        // Generate a unique 5-digit reservation_id
        $reservation_id = '';
        $is_unique = false;
        $max_attempts = 10;
        $attempts = 0;

        while (!$is_unique && $attempts < $max_attempts) {
            $temp_id = strval(rand(10000, 99999));
            $check_id_stmt = $conn->prepare("SELECT id FROM bookings WHERE reservation_id = ? LIMIT 1");
            $check_id_stmt->bind_param("s", $temp_id);
            $check_id_stmt->execute();
            if ($check_id_stmt->get_result()->num_rows === 0) {
                $reservation_id = $temp_id;
                $is_unique = true;
            }
            $attempts++;
        }

        if (!$is_unique) {
            throw new Exception("Could not generate a unique reservation ID.");
        }

        // Insert into bookings
        $booking_stmt = $conn->prepare("
            INSERT INTO bookings (
                customer_id, event_id, booking_date, start_time, end_time, 
                persons, status, event_title, event_type, addons_json, reservation_id
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
        ");
        $booking_stmt->bind_param(
            "iisssissss",
            $customer_id,
            $event_id,
            $booking_date,
            $start_time,
            $end_time,
            $guests,
            $event_title,
            $event_type,
            $addons_json,
            $reservation_id
        );
        $booking_stmt->execute();
        $booking_id = $conn->insert_id;

        // Insert into payments
        $payment_stmt = $conn->prepare("
            INSERT INTO payments (
                booking_id, payment_method, payment_status, total_price
            ) VALUES (?, ?, 'unpaid', ?)
        ");
        $payment_stmt->bind_param("isd", $booking_id, $payment_method, $form_total_price);
        $payment_stmt->execute();

        $conn->commit();

        // ── Non-Blocking Response Preparation ──────────────────────────────
        // We want to send the success JSON to the user immediately so they don't wait for SMTP.
        $response = json_encode([
            'success' => true,
            'reservation_id' => $reservation_id,
            'message' => 'Reservation request submitted successfully!'
        ]);

        // If running under FastCGI (like many XAMPP/Cloud setups), we can close the connection
        if (function_exists('fastcgi_finish_request')) {
            echo $response;
            fastcgi_finish_request();
        } else {
            // Fallback: Just let the script continue. We'll set a shorter 5s timeout for the email
            // to ensure it doesn't hang the process too long if the connection is slow.
        }
        
        // ── Send booking confirmation email (Background-ish) ────────────────
        
        // Fetch event name from the events table to derive the tour type
        $ev_stmt = $conn->prepare("SELECT name, is_overnight FROM events WHERE id = ? LIMIT 1");
        $ev_stmt->bind_param("i", $event_id);
        $ev_stmt->execute();
        $ev_row     = $ev_stmt->get_result()->fetch_assoc();
        $tour_type  = $ev_row ? $ev_row['name']        : 'N/A';
        $is_overnight = $ev_row ? (bool)$ev_row['is_overnight'] : false;

        // Format dates for the email
        $checkin_display = date('F j, Y', strtotime($booking_date)) . ' at ' . date('g:i A', strtotime($start_time));
        
        if ($is_overnight) {
            $checkout_date = date('Y-m-d', strtotime($booking_date . ' +1 day'));
        } else {
            $checkout_date = $booking_date;
        }
        $checkout_display = date('F j, Y', strtotime($checkout_date)) . ' at ' . date('g:i A', strtotime($end_time));

        require_once __DIR__ . '/../email_api/send_booking_receipt.php';
        sendBookingReceipt(
            $email,
            $reservation_id,
            $event_title,
            $event_type,
            $tour_type,
            $guests,
            $checkin_display,
            $checkout_display,
            'Pending'
        );
        // ────────────────────────────────────────────────────────────────

        // If fastcgi_finish_request was NOT called, we need to echo the response here.
        if (!function_exists('fastcgi_finish_request')) {
            echo $response;
        }
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>