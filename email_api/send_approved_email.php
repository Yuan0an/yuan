<?php
require_once 'email_config.php';

function sendApprovedEmail($email, $ref, $event_title, $event_type, $tour_type, $guests, $checkin, $checkout, $status = 'Approved') {
    require_once 'send_booking_receipt.php';
    return sendBookingReceipt($email, $ref, $event_title, $event_type, $tour_type, $guests, $checkin, $checkout, $status);
}
