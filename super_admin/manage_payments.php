<?php
// super_admin/manage_payments.php
require_once 'auth_check.php';
require_once '../form/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = $_POST['name'];
        $icon = $_POST['icon'];
        $details = $_POST['details'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $qr_url = $_POST['existing_qr_code'] ?? '';

        if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/qr_codes/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = pathinfo($_FILES['qr_code']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('qr_') . '.' . $file_ext;
            if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $upload_dir . $file_name)) {
                $qr_url = 'uploads/qr_codes/' . $file_name;
            }
        }

        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO payment_methods (name, icon, details, qr_code_url, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $icon, $details, $qr_url, $is_active);
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE payment_methods SET name=?, icon=?, details=?, qr_code_url=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssssii", $name, $icon, $details, $qr_url, $is_active, $id);
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM payment_methods WHERE id=?");
            $stmt->bind_param("i", $id);
        }

        if ($stmt->execute()) {
            $message = "Payment method updated successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

$payments = $conn->query("SELECT * FROM payment_methods");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Payment Methods</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .payment-card { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; border: none; font-weight: 600; }
        .btn-primary { background: var(--sa-primary); color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 2rem; border-radius: 12px; width: 400px; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 0.6rem; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        .status-badge { padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .pm-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #3b82f6; background: #eff6ff; border-radius: 8px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>Manage Payment Methods</h1>
            <button class="btn btn-primary" onclick="openModal('add')"><i class="fas fa-plus"></i> Add Method</button>
        </header>

        <?php if ($message): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="payment-list">
            <?php while($row = $payments->fetch_assoc()): ?>
                <div class="payment-card">
                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                        <div class="pm-icon"><i class="<?php echo htmlspecialchars($row['icon']); ?>"></i></div>
                        <div>
                            <h3 style="margin:0;"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <div style="color:#64748b; font-size:0.9rem; margin-top:4px;">
                                <?php echo nl2br(htmlspecialchars($row['details'])); ?>
                            </div>
                            <?php if ($row['qr_code_url']): ?>
                                <div style="margin-top: 8px;">
                                    <span style="font-size: 0.8rem; color: #3b82f6;"><i class="fas fa-qrcode"></i> QR Code Attached</span>
                                </div>
                            <?php endif; ?>
                            <span class="status-badge <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>" style="display:inline-block; margin-top:8px;">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary" onclick='editPayment(<?php echo json_encode($row); ?>)'>Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete this payment method?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Payment Method</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="paymentId">
                <input type="hidden" name="existing_qr_code" id="paymentExistingQr">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="paymentName" required placeholder="e.g., GCash, BPI">
                </div>
                <div class="form-group">
                    <label>Icon (FontAwesome Class)</label>
                    <input type="text" name="icon" id="paymentIcon" required placeholder="e.g., fas fa-mobile-alt">
                </div>
                <div class="form-group">
                    <label>Details (Displayed to customer)</label>
                    <textarea name="details" id="paymentDetails" rows="4" required placeholder="Account Name: ...\nNumber: ..."></textarea>
                </div>
                <div class="form-group">
                    <label>QR Code (Optional)</label>
                    <input type="file" name="qr_code" accept="image/*">
                    <div id="qrPreview" style="margin-top: 10px; display: none;">
                        <img src="" alt="QR Preview" style="max-width: 100px; border-radius: 4px;">
                    </div>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_active" id="paymentActive" style="width: auto;" checked>
                    <label style="margin:0;">Active?</label>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
                    <button type="button" class="btn" style="flex:1; background: #e2e8f0;" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(action) {
            document.getElementById('paymentModal').style.display = 'flex';
            document.getElementById('formAction').value = action;
            if (action === 'add') {
                document.getElementById('paymentId').value = '';
                document.getElementById('paymentName').value = '';
                document.getElementById('paymentIcon').value = 'fas fa-money-bill';
                document.getElementById('paymentDetails').value = '';
                document.getElementById('paymentActive').checked = true;
            }
        }
        function closeModal() { document.getElementById('paymentModal').style.display = 'none'; }
        function editPayment(data) {
            openModal('edit');
            document.getElementById('paymentId').value = data.id;
            document.getElementById('paymentName').value = data.name;
            document.getElementById('paymentIcon').value = data.icon;
            document.getElementById('paymentDetails').value = data.details;
            document.getElementById('paymentActive').checked = data.is_active == 1;
            document.getElementById('paymentExistingQr').value = data.qr_code_url || '';
            
            if (data.qr_code_url) {
                document.getElementById('qrPreview').style.display = 'block';
                document.getElementById('qrPreview').querySelector('img').src = '../' + data.qr_code_url;
            } else {
                document.getElementById('qrPreview').style.display = 'none';
            }
        }
    </script>
</body>
</html>
