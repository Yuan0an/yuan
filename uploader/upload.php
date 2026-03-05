<?php
// uploader/upload.php — stores receipt image as base64 in the database
// (Railway has an ephemeral filesystem; local files are wiped on every restart)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt'])) {
    $res_id = isset($_POST['res_id']) ? intval($_POST['res_id']) : 0;

    if ($res_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid reservation ID.']);
        exit;
    }

    $file = $_FILES['receipt'];

    // Validate extension
    $file_ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_ext, $allowed_exts)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
        exit;
    }

    // Validate size (max 5 MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large. Max 5MB allowed.']);
        exit;
    }

    // Read file and encode as base64 data-URI (persisted in DB, not local disk)
    $file_data = file_get_contents($file['tmp_name']);
    if ($file_data === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to read uploaded file.']);
        exit;
    }

    $mime_map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $mime     = $mime_map[$file_ext] ?? 'image/jpeg';
    $data_uri = 'data:' . $mime . ';base64,' . base64_encode($file_data);

    // Connect to DB and save
    require_once '../form/config.php';

    // Add receipt_data column if it doesn't exist (idempotent)
    $conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_data LONGTEXT");

    $ref_name = 'receipt_' . $res_id . '.' . $file_ext; // human-readable label only

    $stmt = $conn->prepare("
        UPDATE payments
        SET payment_proof  = ?,
            receipt_data   = ?,
            time_uploaded  = NOW(),
            payment_status = 'paid'
        WHERE booking_id = ?
    ");
    $stmt->bind_param("ssi", $ref_name, $data_uri, $res_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'filename' => $ref_name]);

} else {
    echo json_encode(['success' => false, 'error' => 'No file received.']);
}
?>