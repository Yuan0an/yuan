<?php
require 'config.php';

// Cleanup
$conn->query("DELETE FROM reservations WHERE email LIKE 'test%@conflict.com'");

function insertReservation($eventId, $date, $email)
{
    global $conn;
    $sql = "INSERT INTO reservations (event_id, booking_date, start_time, end_time, persons, status, full_name, email, phone, event_title, event_type, payment_method) 
            VALUES ($eventId, '$date', '00:00:00', '00:00:00', 10, 'approved', 'Test Conflict', '$email', '123', 'Test Event', 'Corporate', 'GCash')";
    if (!$conn->query($sql)) {
        die("Failed to insert reservation: " . $conn->error . "\n");
    }
}

function checkCalendar($eventId, $month, $year, $targetDateStr, $testName, $expectedLabel)
{
    // Wrapper for get_calendar.php
    $postData = http_build_query([
        'month' => $month,
        'year' => $year,
        'event_id' => $eventId
    ]);

    // Create temp wrapper if not exists
    if (!file_exists('cal_wrapper.php')) {
        file_put_contents('cal_wrapper.php', '<?php ini_set("session.use_cookies", 0); parse_str($argv[1], $_POST); ob_start(); require "get_calendar.php"; ob_end_flush(); ?>');
    }

    $cmd = "php cal_wrapper.php " . escapeshellarg($postData);
    $output = shell_exec($cmd);

    $jsonStart = strrpos($output, '{');
    if ($jsonStart !== false) {
        $json = substr($output, $jsonStart);
        $response = json_decode($json, true);
        $html = $response['calendar'];

        $statusFound = "Not Found";
        // Search cell with date and get the text inside day-availability div
        if (preg_match("/<td[^>]*data-date='$targetDateStr'[^>]*>.*?<div class='day-availability'>(.*?)<\/div>.*?<\/td>/s", $html, $matches)) {
            $statusFound = trim($matches[1]);
        }

        $pass = ($statusFound === $expectedLabel);

        $status = $pass ? "PASS" : "FAIL";
        echo sprintf("%-60s [%s] Exp: %s | Act: %s\n", $testName, $status, $expectedLabel, $statusFound);

        if (!$pass) {
            echo "HTML Snippet near Target: " . substr($html, 0, 1000) . "...\n";
        }
    } else {
        echo "$testName: FAIL (Invalid JSON)\n";
    }
}

echo "Starting Label Verification...\n\n";

insertReservation(5, '2027-02-01', 'test1@conflict.com');
echo "--- Scenario: Overnight 7PM (ID 5) booked Yesterday ---\n";

// Check Conflict (Day Tour View)
checkCalendar(1, 2, 2027, '2027-02-02', "Day Tour (ID 1) View Feb 2", "Unavailable");

// Check Direct Booking (Overnight 7PM View)
checkCalendar(5, 2, 2027, '2027-02-01', "Overnight 7PM (ID 5) View Feb 1", "Booked");

$conn->query("DELETE FROM reservations WHERE email LIKE 'test%@conflict.com'");
if (file_exists('cal_wrapper.php'))
    unlink('cal_wrapper.php');
?>