<?php
// super_admin/manage_addons.php
require_once 'auth_check.php';
require_once '../form/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $type = $_POST['type'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO addons (name, price, type, is_active) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdsi", $name, $price, $type, $is_active);
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE addons SET name=?, price=?, type=?, is_active=? WHERE id=?");
            $stmt->bind_param("sdsii", $name, $price, $type, $is_active, $id);
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM addons WHERE id=?");
            $stmt->bind_param("i", $id);
        }

        if ($stmt->execute()) {
            $message = "Add-on updated successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

$addons = $conn->query("SELECT * FROM addons");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Add-ons</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .addon-card { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; border: none; font-weight: 600; }
        .btn-primary { background: var(--sa-primary); color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 2rem; border-radius: 12px; width: 400px; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        .status-badge { padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>Manage Add-ons</h1>
            <button class="btn btn-primary" onclick="openModal('add')"><i class="fas fa-plus"></i> Add Add-on</button>
        </header>

        <?php if ($message): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="addon-list">
            <?php while($row = $addons->fetch_assoc()): ?>
                <div class="addon-card">
                    <div>
                        <h3 style="margin:0;"><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p style="margin:5px 0; color:#64748b;">
                            Price: P<?php echo number_format($row['price'], 2); ?> | Type: <?php echo ucfirst($row['type']); ?>
                            <span class="status-badge <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary" onclick='editAddon(<?php echo json_encode($row); ?>)'>Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete this add-on?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <div id="addonModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Add-on</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="addonId">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="addonName" required>
                </div>
                <div class="form-group">
                    <label>Price (PHP)</label>
                    <input type="number" step="0.01" name="price" id="addonPrice" required>
                </div>
                <div class="form-group">
                    <label>UI Type</label>
                    <select name="type" id="addonType">
                        <option value="counter">Counter (Quantity)</option>
                        <option value="checkbox">Checkbox (Yes/No)</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_active" id="addonActive" style="width: auto;" checked>
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
            document.getElementById('addonModal').style.display = 'flex';
            document.getElementById('formAction').value = action;
            if (action === 'add') {
                document.getElementById('addonId').value = '';
                document.getElementById('addonName').value = '';
                document.getElementById('addonPrice').value = '';
                document.getElementById('addonType').value = 'counter';
                document.getElementById('addonActive').checked = true;
            }
        }
        function closeModal() { document.getElementById('addonModal').style.display = 'none'; }
        function editAddon(data) {
            openModal('edit');
            document.getElementById('addonId').value = data.id;
            document.getElementById('addonName').value = data.name;
            document.getElementById('addonPrice').value = data.price;
            document.getElementById('addonType').value = data.type;
            document.getElementById('addonActive').checked = data.is_active == 1;
        }
    </script>
</body>
</html>
