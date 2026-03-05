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

// Enable Debugging in PHPMailer via global if needed, but since we use getMailer(),
// we'll temporarily hack a debug toggle here by re-getting the mailer or modifying the existing one.
function getDebugMailer() {
    require_once 'auto_email/config.php';
    $mail = getMailer();
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->Debugoutput = 'html';
    return $mail;
}

// Custom call instead of sendBookingConfirmationEmail to see output
try {
    $mail = getDebugMailer();
    $mail->addAddress($test_email, $guest_name);
    $mail->Subject = 'Debug Delivery Test';
    $mail->Body    = "This is a test email with full SMTP debug enabled.\n\n" . 
                     "Reference: $booking_id\n" .
                     "Date: $booking_date";
    
    echo "<h4>📡 SMTP Debug Output:</h4><div style='background:#eee;padding:10px;border:1px solid #ccc;'>";
    $result = $mail->send();
    echo "</div>";
} catch (Exception $e) {
    echo "</div><h4 style='color:red;'>Caught Exception: " . $e->getMessage() . "</h4>";
    $result = false;
}

if ($result) {
    echo "<h3 style='color:green;'>✅ Success! Check your inbox/spam folder.</h3>";
} else {
    echo "<h3 style='color:red;'>❌ Failed! Check PHP error logs.</h3>";
    echo "<p>Common issues: Incorrect App Password, SMTP blocked by firewall, or SSL/TLS version mismatch.</p>";
}
?>
