<?php
require_once __DIR__ . '/config.php';

/**
 * sendBookingConfirmationEmail()
 *
 * Sends a Booking Reservation Confirmation email to the guest
 * after a successful reservation is saved to the database.
 *
 * @param string $reservation_id  The 5-digit random reservation ID
 * @param string $guest_email     Guest's email address
 * @param string $guest_name      Guest's full name
 * @param string $event_title     The event/function name entered by the guest
 * @param string $event_type      e.g. Birthday, Wedding, Corporate
 * @param string $tour_type       e.g. Day Tour, Night Tour, Overnight Stay
 * @param int    $guests          Number of expected guests
 * @param string $booking_date    Reservation date (YYYY-MM-DD)
 * @param string $start_time      Session start time (HH:MM:SS)
 * @param string $end_time        Session end time (HH:MM:SS)
 * @param bool   $is_overnight    Whether the event spans overnight
 *
 * @return bool  true on success, false on failure
 */
function sendBookingConfirmationEmail(
    $reservation_id,
    $guest_email,
    $guest_name,
    $event_title,
    $event_type,
    $tour_type,
    $guests,
    $booking_date,
    $start_time,
    $end_time,
    $is_overnight = false,
    $echo_debug = false,
    $timeout = 10
) {
    try {
        $mail = getMailer($echo_debug, $timeout);

        // Recipient
        $mail->addAddress($guest_email, $guest_name);

        // Format dates for display
        $checkin_display  = date('F j, Y', strtotime($booking_date))
                          . ' at ' . date('h:i A', strtotime($start_time));

        // For overnight bookings the checkout date is the next day
        if ($is_overnight) {
            $checkout_date = date('Y-m-d', strtotime($booking_date . ' +1 day'));
        } else {
            $checkout_date = $booking_date;
        }
        $checkout_display = date('F j, Y', strtotime($checkout_date))
                          . ' at ' . date('h:i A', strtotime($end_time));

        $booking_date_display = date('F j, Y', strtotime($booking_date));
        $today_display        = date('F j, Y');

        // Reference number for email
        $ref_number = $reservation_id;

        // ----------------------------------------------------------------
        // Email content - Simplified (No CSS)
        // ----------------------------------------------------------------
        $mail->isHTML(true);
        $mail->Subject = 'Booking Reservation Confirmation';
        
        $body = "Booking Reservation\n\n";
        $body .= "Thank you for booking with us!\n\n";
        $body .= "Booking Reference No: " . $ref_number . "\n\n";
        $body .= "Booking Details:\n";
        $body .= "Event Name: " . htmlspecialchars($event_title) . "\n";
        $body .= "Event Type: " . htmlspecialchars($event_type) . "\n";
        $body .= "Tour Type: " . htmlspecialchars($tour_type) . "\n";
        $body .= "Expected Guests: " . intval($guests) . "\n\n";
        
        $body .= "Reservation Information:\n";
        $body .= "Check-in Date: " . $checkin_display . "\n";
        $body .= "Check-out Date: " . $checkout_display . "\n";
        $body .= "Booking Date: " . $today_display . "\n\n";
        
        $body .= "Thank you for choosing our resort.\n";
        $body .= "We look forward to hosting your event!";

        // Convert newlines to HTML breaks for HTML email readability
        $mail->Body = nl2br($body);
        $mail->AltBody = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        $error_msg = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
        error_log('[CK Resort Email] Failed to send booking confirmation to '
                  . $guest_email . ': ' . $error_msg);
        return false;
    }
}

/**
 * sendBookingApprovalEmail()
 *
 * Sends an Approval Notification email to the guest
 * after an admin approves their reservation.
 */
function sendBookingApprovalEmail(
    $reservation_id,
    $guest_email,
    $guest_name,
    $event_title,
    $booking_date,
    $start_time,
    $echo_debug = false,
    $timeout = 10
) {
    try {
        $mail = getMailer($echo_debug, $timeout);

        // Recipient
        $mail->addAddress($guest_email, $guest_name);

        $mail->isHTML(true);
        $mail->Subject = 'Your Reservation has been APPROVED - CK Resort';
        
        $booking_date_display = date('F j, Y', strtotime($booking_date));
        $time_display = date('h:i A', strtotime($start_time));

        $body = "<h2>Great news, " . htmlspecialchars($guest_name) . "!</h2>";
        $body .= "<p>Your reservation request has been <strong>Approved</strong>.</p>";
        $body .= "<p><strong>Reservation ID:</strong> " . $reservation_id . "<br>";
        $body .= "<strong>Date:</strong> " . $booking_date_display . "<br>";
        $body .= "<strong>Check-in Time:</strong> " . $time_display . "</p>";
        
        $body .= "<p>We have successfully verified your payment. You are now all set for your visit!</p>";
        $body .= "<p>If you have any further questions, feel free to reply to this email or contact us directly.</p>";
        $body .= "<p>We look forward to seeing you!<br><strong>CK Resort Team</strong></p>";

        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

        $mail->send();
        return true;

    } catch (Exception $e) {
        $error_msg = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
        error_log('[CK Resort Email] Failed to send approval notification to '
                  . $guest_email . ': ' . $error_msg);
        return false;
    }
}
