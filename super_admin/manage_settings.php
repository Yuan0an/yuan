<?php
// super_admin/manage_settings.php
require_once 'auth_check.php';
require_once '../form/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
    }
    $message = "Settings updated successfully!";
}

// Fetch current settings
$settings = [];
$res = $conn->query("SELECT * FROM site_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .settings-container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 2rem; }
        label { display: block; margin-bottom: 0.8rem; font-weight: 600; font-size: 1.1rem; color: #1e293b; }
        textarea, input { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        .btn { padding: 0.8rem 2rem; border-radius: 8px; cursor: pointer; border: none; font-weight: 600; background: var(--sa-primary); color: white; font-size: 1rem; }
        .section-title { border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 2rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.9rem; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header style="margin-bottom: 2rem;">
            <h1>Site Settings</h1>
            <p>Manage Terms, Policies, and Footer Content.</p>
        </header>

        <?php if ($message): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" class="settings-container">
            <div class="section-title">Legal & Policies</div>
            
            <div class="form-group">
                <label>Terms & Conditions</label>
                <textarea name="settings[terms_conditions]" rows="6"><?php echo htmlspecialchars($settings['terms_conditions'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Cancellation Policy</label>
                <textarea name="settings[cancellation_policy]" rows="6"><?php echo htmlspecialchars($settings['cancellation_policy'] ?? ''); ?></textarea>
            </div>

            <div class="section-title">Special Pricing</div>

            <div class="form-group">
                <label>Special Dates / Holidays (Comma separated YYYY-MM-DD)</label>
                <input type="text" name="settings[special_dates]" value="<?php echo htmlspecialchars($settings['special_dates'] ?? ''); ?>" placeholder="e.g. 2024-12-25, 2025-01-01">
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">The system will automatically add P1,000 for these dates and every Fri/Sat/Sun.</p>
            </div>

            <div class="section-title">Footer Content</div>

            <div class="form-group">
                <label>About Us (Footer)</label>
                <textarea name="settings[footer_about]" rows="3"><?php echo htmlspecialchars($settings['footer_about'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="settings[footer_address]" value="<?php echo htmlspecialchars($settings['footer_address'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Contact Info</label>
                <input type="text" name="settings[footer_contact]" value="<?php echo htmlspecialchars($settings['footer_contact'] ?? ''); ?>">
            </div>

            <div style="margin-top: 3rem;">
                <button type="submit" class="btn">Save All Changes</button>
            </div>
        </form>
    </main>
</body>
</html>
