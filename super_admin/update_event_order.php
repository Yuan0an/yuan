<?php
require_once 'auth_check.php';
require_once '../auth/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    $order = $_POST['order'];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE events SET sort_order = ? WHERE id = ?");
        foreach ($order as $item) {
            $stmt->bind_param("ii", $item['sort_order'], $item['id']);
            $stmt->execute();
        }
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
