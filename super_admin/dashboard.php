<?php
// super_admin/dashboard.php
require_once 'auth_check.php';
require_once '../form/config.php';

// Fetch some quick stats
$admin_count = $conn->query("SELECT COUNT(*) as count FROM admins")->fetch_assoc()['count'];
$event_count = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$addon_count = $conn->query("SELECT COUNT(*) as count FROM addons")->fetch_assoc()['count'];
$payment_count = $conn->query("SELECT COUNT(*) as count FROM payment_methods")->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="content-header">
            <h1>Welcome, Super Admin</h1>
            <p>Manage your resort's booking engine and system settings here.</p>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-calendar-alt"></i>
                <div class="stat-info">
                    <h3><?php echo $event_count; ?></h3>
                    <p>Event Types</p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-plus-circle"></i>
                <div class="stat-info">
                    <h3><?php echo $addon_count; ?></h3>
                    <p>Add-ons</p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-credit-card"></i>
                <div class="stat-info">
                    <h3><?php echo $payment_count; ?></h3>
                    <p>Payment Methods</p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users-cog"></i>
                <div class="stat-info">
                    <h3><?php echo $admin_count; ?></h3>
                    <p>Total Admins</p>
                </div>
            </div>
        </div>

        <section class="quick-actions">
            <h2>Quick Shortcuts</h2>
            <div class="shortcut-grid">
                <a href="manage_events.php" class="shortcut-btn">
                    <i class="fas fa-edit"></i> Edit Event Pricing
                </a>
                <a href="manage_addons.php" class="shortcut-btn">
                    <i class="fas fa-plus"></i> Manage Add-ons
                </a>
                <a href="system_reset.php" class="shortcut-btn reset-btn">
                    <i class="fas fa-exclamation-triangle"></i> Reset System
                </a>
            </div>
        </section>
    </main>

    <style>
    .content-header {
        margin-bottom: 2rem;
    }
    .content-header h1 {
        margin: 0;
        color: #1e293b;
    }
    .content-header p {
        color: #64748b;
        margin-top: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .stat-card i {
        font-size: 2.5rem;
        color: var(--sa-primary);
        opacity: 0.8;
    }

    .stat-info h3 {
        margin: 0;
        font-size: 1.8rem;
        color: #1e293b;
    }

    .stat-info p {
        margin: 0;
        color: #64748b;
        font-weight: 500;
    }

    .quick-actions h2 {
        font-size: 1.25rem;
        color: #1e293b;
        margin-bottom: 1rem;
    }

    .shortcut-grid {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .shortcut-btn {
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        color: #1e293b;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .shortcut-btn:hover {
        border-color: var(--sa-primary);
        color: var(--sa-primary);
        transform: translateY(-2px);
    }

    .shortcut-btn.reset-btn:hover {
        border-color: #ef4444;
        color: #ef4444;
    }
    </style>
</body>
</html>
