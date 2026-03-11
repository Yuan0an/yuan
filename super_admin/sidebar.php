<!-- super_admin/sidebar.php -->
<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/logo.jpg" alt="Logo">
        <span>Super Admin</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="manage_events.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_events.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> Event Types
        </a>
        <a href="manage_addons.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_addons.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i> Add-ons
        </a>
        <a href="manage_payments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_payments.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i> Payment Methods
        </a>
        <hr>
        <a href="manage_admins.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i> Admin Credentials
        </a>
        <a href="manage_settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Site Settings
        </a>
        <a href="system_reset.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'system_reset.php' ? 'active' : ''; ?>">
            <i class="fas fa-sync-alt"></i> Reset Reservations
        </a>
        <hr>
        <a href="../auth/logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<style>
:root {
    --sa-primary: #1e3a8a;
    --sa-secondary: #3b82f6;
    --sa-bg: #f8fafc;
    --sa-sidebar-bg: #0f172a;
    --sa-sidebar-text: #94a3b8;
    --sa-sidebar-active: #ffffff;
    --sa-sidebar-hover: #1e293b;
}

body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    display: flex;
    background-color: var(--sa-bg);
}

.sidebar {
    width: 260px;
    height: 100vh;
    background-color: var(--sa-sidebar-bg);
    color: var(--sa-sidebar-text);
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0;
    top: 0;
}

.sidebar-header {
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-header img {
    width: 40px;
    height: 40px;
    border-radius: 8px;
}

.sidebar-header span {
    font-weight: 700;
    color: white;
    font-size: 1.2rem;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    padding: 1rem;
    gap: 5px;
}

.sidebar-nav a {
    color: var(--sa-sidebar-text);
    text-decoration: none;
    padding: 0.8rem 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.2s;
}

.sidebar-nav a:hover {
    background-color: var(--sa-sidebar-hover);
    color: var(--sa-sidebar-active);
}

.sidebar-nav a.active {
    background-color: var(--sa-primary);
    color: white;
}

.sidebar-nav hr {
    border: 0;
    border-top: 1px solid #1e293b;
    margin: 1rem 0;
}

.logout-link:hover {
    background-color: #991b1b !important;
}

.main-content {
    margin-left: 260px;
    padding: 2rem;
    width: calc(100% - 260px);
}
</style>
