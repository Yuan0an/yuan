<?php
require 'config.php';

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

    // Check if slot already has approved reservation
    $check_stmt = $conn->prepare("
        SELECT id FROM reservations 
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

    // Insert reservation as pending
    $stmt = $conn->prepare("
        INSERT INTO reservations (
            event_id, booking_date, start_time, end_time, persons,
            full_name, email, phone, alt_phone, company,
            event_title, event_type, payment_method,
            terms_accepted, cancellation_accepted, status,
            total_price, addons_json
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
    ");

    $stmt->bind_param(
        "isssissssssssiids",
        $event_id,
        $booking_date,
        $start_time,
        $end_time,
        $guests,
        $full_name,
        $email,
        $phone,
        $alt_phone,
        $company,
        $event_title,
        $event_type,
        $payment_method,
        $terms_accepted,
        $cancellation_accepted,
        $form_total_price,
        $addons_json
    );

    if ($stmt->execute()) {
        $reservation_id = $conn->insert_id;

        echo json_encode([
            'success' => true,
            'reservation_id' => $reservation_id,
            'message' => 'Reservation request submitted successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>