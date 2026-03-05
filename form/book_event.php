<?php
require 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = $_POST['event_id'];
    $date = $_POST['booking_date'];
    $persons = $_POST['persons'];
    
    // Simple validation
    if($persons <= 0) {
        die("Invalid number of persons");
    }
    
    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (event_id, booking_date, persons) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $event_id, $date, $persons);
    
    if($stmt->execute()) {
        echo "<h2>Booking Successful!</h2>";
        echo "<p>Event booked for " . date('F j, Y', strtotime($date)) . "</p>";
        echo "<p>Number of persons: $persons</p>";
        echo "<a href='index.php'>Make Another Booking</a>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>