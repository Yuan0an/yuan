<?php
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli('localhost', 'root', '', 'resort_db');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

echo "Connected to resort_db.<br>";

// Add approved_by column
echo "Adding approved_by column... ";
$sql = "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS approved_by INT NULL";
if ($conn->query($sql)) {
    echo "OK.<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Add approved_at column (also missing likely)
echo "Adding approved_at column... ";
$sql = "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL";
if ($conn->query($sql)) {
    echo "OK.<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Add admin_notes column (seen in code)
echo "Adding admin_notes column... ";
$sql = "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS admin_notes TEXT NULL";
if ($conn->query($sql)) {
    echo "OK.<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Add FK
echo "Adding foreign key... ";
$sql = "ALTER TABLE reservations ADD CONSTRAINT fk_reservations_admin FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL";
if ($conn->query($sql)) {
    echo "OK.<br>";
} else {
    echo "Info: " . $conn->error . " (likely already exists)<br>";
}

echo "Database Fix Completed.";
?>
