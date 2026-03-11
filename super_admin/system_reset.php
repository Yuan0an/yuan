<?php
// super_admin/system_reset.php
require_once 'auth_check.php';
require_once '../form/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];

    // Verify superadmin password
    $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        // Perform reset
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("TRUNCATE TABLE payments");
        $conn->query("TRUNCATE TABLE bookings");
        $conn->query("TRUNCATE TABLE customers");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        $message = "All reservations, payments, and customer records have been successfully reset.";
    } else {
        $error = "Invalid password. System reset aborted.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reset</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .reset-container { background: white; padding: 2.5rem; border-radius: 12px; max-width: 600px; margin: 2rem auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #fee2e2; }
        .warning-icon { font-size: 3rem; color: #ef4444; margin-bottom: 1rem; text-align: center; display: block; }
        h1 { color: #1e293b; text-align: center; margin-bottom: 0.5rem; }
        p { color: #64748b; text-align: center; line-height: 1.5; margin-bottom: 2rem; }
        .btn { width: 100%; padding: 0.8rem; border-radius: 6px; cursor: pointer; border: none; font-weight: 700; font-size: 1rem; transition: all 0.2s; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-1px); }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1e293b; }
        input { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; text-align: center; font-size: 1.1rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; text-align: center; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="reset-container">
            <i class="fas fa-exclamation-triangle warning-icon"></i>
            <h1>System Reset</h1>
            <p>This action will <strong>permanently delete all reservations, payments, and customer data</strong> from the database. This cannot be undone.</p>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" onsubmit="return confirm('ARE YOU ABSOLUTELY SURE? THIS ACTION IS PERMANENT.')">
                <div class="form-group">
                    <label>Confirm Super Admin Password</label>
                    <input type="password" name="password" required placeholder="Enter your password here">
                </div>
                <button type="submit" class="btn btn-danger">Wipe All Reservation Data</button>
            </form>
        </div>
    </main>
</body>
</html>
