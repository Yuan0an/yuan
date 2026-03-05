<?php
require_once 'auto_email/send_booking_email.php';

// Test data
$booking_id = 999999;
$test_email = 'calmayuan0@gmail.com'; // Testing to the same sender first
$guest_name = 'Test User';
$event_title = 'Debug Test Event';
$event_type = 'Testing';
$tour_type = 'Day Tour';
$guests = 5;
$booking_date = date('Y-m-d');
$start_time = '09:00:00';
$end_time = '17:00:00';
$is_overnight = false;

echo "<h2>📧 Email Delivery Test</h2>";
echo "Attempting to send test email to: <strong>$test_email</strong>...<br>";

// We'll temporarily modify getMailer() in config.php to show debug output if needed, 
// but first let's just try the call.
$result = sendBookingConfirmationEmail(
    $booking_id,
    $test_email,
    $guest_name,
    $event_title,
    $event_type,
    $tour_type,
    $guests,
    $booking_date,
    $start_time,
    $end_time,
    $is_overnight
);

if ($result) {
    echo "<h3 style='color:green;'>✅ Success! Check your inbox/spam folder.</h3>";
} else {
    echo "<h3 style='color:red;'>❌ Failed! Check PHP error logs.</h3>";
    echo "<p>Common issues: Incorrect App Password, SMTP blocked by firewall, or SSL/TLS version mismatch.</p>";
}
?>
