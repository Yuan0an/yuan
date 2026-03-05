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

function checkConflict($eventId, $date, $testName, $shouldBeBlocked)
{
    $postData = http_build_query([
        'date' => $date,
        'event_id' => $eventId,
        'start_time' => '00:00:00',
        'end_time' => '00:00:00',
        'is_overnight' => 'false'
    ]);

    // Run wrapper in separate process
    $cmd = "php test_wrapper.php " . escapeshellarg($postData);
    $output = shell_exec($cmd);

    // JSON is at the end of output (ignoring PHP warnings)
    // Find last '{'
    $jsonStart = strrpos($output, '{');
    if ($jsonStart !== false) {
        $json = substr($output, $jsonStart);
        $response = json_decode($json, true);

        $isBlocked = isset($response['has_approved']) && $response['has_approved'] === true;

        $pass = ($isBlocked === $shouldBeBlocked);
        $status = $pass ? "PASS" : "FAIL";
        $actual = $isBlocked ? "BLOCKED" : "AVAILABLE";

        echo sprintf("%-50s [%s] Actual: %s\n", $testName, $status, $actual);

        if (!$pass) {
            echo "Debug Output: $output\n";
        }
    } else {
        echo "$testName: FAIL (Invalid JSON) Output: $output\n";
    }
}

echo "Starting Expanded Verification (Process Isolated)...\n\n";

// Scenario 1: Night Tour (ID 2) Approved
insertReservation(2, '2027-02-01', 'test1@conflict.com');
echo "--- Scenario 1: Night Tour Approved ---\n";
checkConflict(3, '2027-02-01', "Check Overnight 9AM (ID 3) [Exp: Blocked]", true);
checkConflict(4, '2027-02-01', "Check Overnight 2PM (ID 4) [Exp: Blocked]", true);
checkConflict(5, '2027-02-01', "Check Overnight 7PM (ID 5) [Exp: Blocked]", true);
checkConflict(1, '2027-02-01', "Check Day Tour (ID 1) [Exp: Available]", false);

// Cleanup S1
$conn->query("DELETE FROM reservations WHERE email = 'test1@conflict.com'");

// Scenario 2: Overnight 7PM (ID 5) Approved
insertReservation(5, '2027-02-02', 'test2@conflict.com');
echo "\n--- Scenario 2: Overnight 7PM Approved ---\n";
checkConflict(2, '2027-02-02', "Check Night Tour (ID 2) [Exp: Blocked]", true);
checkConflict(3, '2027-02-02', "Check Overnight 9AM (ID 3) [Exp: Blocked]", true);
checkConflict(4, '2027-02-02', "Check Overnight 2PM (ID 4) [Exp: Blocked]", true);
checkConflict(1, '2027-02-02', "Check Day Tour (ID 1) [Exp: Available]", false);

// Cleanup All
$conn->query("DELETE FROM reservations WHERE email LIKE 'test%@conflict.com'");
?>