<?php
// uploader/upload.php - Backend processing
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt'])) {
    $res_id = isset($_POST['res_id']) ? intval($_POST['res_id']) : 0;
    $file = $_FILES['receipt'];
    
    $upload_dir = 'uploaded-image/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($file_ext, $allowed_exts)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
        exit;
    }

    // Limit size to 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large. Max 5MB allowed.']);
        exit;
    }

    $new_filename = 'receipt_' . $res_id . '_' . time() . '.' . $file_ext;
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Update database with image details and set payment_status to paid
        require_once '../form/config.php';
        $stmt = $conn->prepare("UPDATE payments SET payment_proof = ?, time_uploaded = NOW(), payment_status = 'paid' WHERE booking_id = ?");
        $db_path = 'uploader/' . $target_path;
        $stmt->bind_param("si", $db_path, $res_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'filename' => $new_filename]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file received.']);
}
?>