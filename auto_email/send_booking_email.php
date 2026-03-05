<?php
require_once __DIR__ . '/config.php';

/**
 * sendBookingConfirmationEmail()
 *
 * Sends a Booking Reservation Confirmation email to the guest
 * after a successful reservation is saved to the database.
 *
 * @param int    $booking_id     The auto-generated booking ID (reference number)
 * @param string $guest_email    Guest's email address
 * @param string $guest_name     Guest's full name
 * @param string $event_title    The event/function name entered by the guest
 * @param string $event_type     e.g. Birthday, Wedding, Corporate
 * @param string $tour_type      e.g. Day Tour, Night Tour, Overnight Stay
 * @param int    $guests         Number of expected guests
 * @param string $booking_date   Reservation date (YYYY-MM-DD)
 * @param string $start_time     Session start time (HH:MM:SS)
 * @param string $end_time       Session end time (HH:MM:SS)
 * @param bool   $is_overnight   Whether the event spans overnight
 *
 * @return bool  true on success, false on failure
 */
function sendBookingConfirmationEmail(
    $booking_id,
    $guest_email,
    $guest_name,
    $event_title,
    $event_type,
    $tour_type,
    $guests,
    $booking_date,
    $start_time,
    $end_time,
    $is_overnight = false
) {
    try {
        $mail = getMailer();

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

        // Padded reference number, e.g. CK-000042
        $ref_number = 'CK-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);

        // ----------------------------------------------------------------
        // Email content - Simplified (No CSS)
        // ----------------------------------------------------------------
        $mail->isHTML(true);
        $mail->Subject = 'Booking Reservation Confirmation';
        
        $body = "Booking Reservation\n\n";
        $body .= "Thank you for booking with us!\n\n";
        $body .= "Booking Reference No: " . $booking_id . "\n\n";
        $body .= "Event Title: " . htmlspecialchars($event_title) . "\n\n";
        
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
        // Log silently — booking is NOT affected by email failure
        error_log('[CK Resort Email] Failed to send booking confirmation to '
                  . $guest_email . ': ' . $e->getMessage());
        return false;
    }
}
