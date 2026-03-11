<?php
// super_admin/manage_admins.php
require_once 'auth_check.php';
require_once '../form/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];

        if ($_POST['action'] === 'add') {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO admins (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $password, $full_name, $email, $role);
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE admins SET username=?, password=?, full_name=?, email=?, role=? WHERE id=?");
                $stmt->bind_param("sssssi", $username, $password, $full_name, $email, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET username=?, full_name=?, email=?, role=? WHERE id=?");
                $stmt->bind_param("ssssi", $username, $full_name, $email, $role, $id);
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            // Prevent self-deletion
            if ($id == $_SESSION['admin_id']) {
                $error = "You cannot delete your own account.";
            } else {
                $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
                $stmt->bind_param("i", $id);
            }
        }

        if (isset($stmt)) {
            if ($stmt->execute()) {
                $message = "Admin account updated successfully!";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

$admins = $conn->query("SELECT * FROM admins");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admin Credentials</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-card { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; border: none; font-weight: 600; }
        .btn-primary { background: var(--sa-primary); color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 2rem; border-radius: 12px; width: 400px; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        .role-badge { padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .role-super { background: #fee2e2; color: #991b1b; }
        .role-admin { background: #eff6ff; color: #1e40af; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>Manage Admin Credentials</h1>
            <button class="btn btn-primary" onclick="openModal('add')"><i class="fas fa-user-plus"></i> Add Admin</button>
        </header>

        <?php if ($message): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-list">
            <?php while($row = $admins->fetch_assoc()): ?>
                <div class="admin-card">
                    <div>
                        <h3 style="margin:0;"><?php echo htmlspecialchars($row['full_name']); ?> (@<?php echo htmlspecialchars($row['username']); ?>)</h3>
                        <p style="margin:5px 0; color:#64748b;">
                            <?php echo htmlspecialchars($row['email']); ?> | 
                            <span class="role-badge <?php echo $row['role'] === 'superadmin' ? 'role-super' : 'role-admin'; ?>">
                                <?php echo ucfirst($row['role']); ?>
                            </span>
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary" onclick='editAdmin(<?php echo json_encode($row); ?>)'>Edit</button>
                        <?php if ($row['id'] != $_SESSION['admin_id']): ?>
                        <form method="POST" onsubmit="return confirm('Confirm deletion?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <div id="adminModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Admin</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="adminId">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="adminFullName" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="adminUsername" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="adminEmail" required>
                </div>
                <div class="form-group">
                    <label>Password <span id="passHint" style="font-weight:normal; font-size:0.8rem; color:#64748b;">(Leave blank to keep current)</span></label>
                    <input type="password" name="password" id="adminPassword">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="adminRole">
                        <option value="admin">Standard Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
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
            document.getElementById('adminModal').style.display = 'flex';
            document.getElementById('formAction').value = action;
            document.getElementById('passHint').style.display = action === 'add' ? 'none' : 'inline';
            if (action === 'add') {
                document.getElementById('adminId').value = '';
                document.getElementById('adminFullName').value = '';
                document.getElementById('adminUsername').value = '';
                document.getElementById('adminEmail').value = '';
                document.getElementById('adminRole').value = 'admin';
                document.getElementById('adminPassword').required = true;
            } else {
                document.getElementById('adminPassword').required = false;
            }
        }
        function closeModal() { document.getElementById('adminModal').style.display = 'none'; }
        function editAdmin(data) {
            openModal('edit');
            document.getElementById('adminId').value = data.id;
            document.getElementById('adminFullName').value = data.full_name;
            document.getElementById('adminUsername').value = data.username;
            document.getElementById('adminEmail').value = data.email;
            document.getElementById('adminRole').value = data.role;
        }
    </script>
</body>
</html>
