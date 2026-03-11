<?php
// super_admin/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: ../auth/index.php');
    exit;
}
?>
