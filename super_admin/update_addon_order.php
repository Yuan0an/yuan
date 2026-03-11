<?php
// super_admin/update_addon_order.php
require_once 'auth_check.php';
require_once '../form/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    foreach ($_POST['order'] as $item) {
        $id = $item['id'];
        $sort_order = $item['sort_order'];
        $stmt = $conn->prepare("UPDATE addons SET sort_order = ? WHERE id = ?");
        $stmt->bind_param("ii", $sort_order, $id);
        $stmt->execute();
    }
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
