<?php
require_once 'email_config.php';

function sendBookingReceipt($email, $ref, $event_title, $event_type, $tour_type, $guests, $checkin, $checkout, $status) {

    $mail = getMailer();

    try {
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Booking Reservation Confirmation";

        $body = "<strong>Booking Reservation</strong><br><br>";
        $body .= "Thank you for booking with us.<br><br>";
        $body .= "<strong>Booking Reference Number:</strong> [$ref]<br><br>";
        $body .= "<strong>Booking Details:</strong><br>";
        $body .= "Event Type: [$event_type]<br>";
        $body .= "Tour Type: [$tour_type]<br>";
        $body .= "Expected Guests: [$guests]<br><br>";
        
        $body .= "<strong>Reservation Schedule:</strong><br>";
        $body .= "Check-in Date: [$checkin]<br>";
        $body .= "Check-out Date: [$checkout]<br><br>";
        
        $body .= "<strong>Status:</strong> [$status]<br><br>";
        $body .= "Thank you for choosing our resort.";

        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("[Email API] Failed to send receipt to $email: " . $mail->ErrorInfo);
        return false;
    }
}
