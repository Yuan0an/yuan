<?php
require 'config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Database authentication
    $stmt = $conn->prepare("SELECT id, username, password, full_name, email, role FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin  = $result->fetch_assoc();
        $verify = password_verify($password, $admin['password']);

        if ($verify) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['id'];
            $_SESSION['admin_name']      = $admin['full_name'];
            $_SESSION['admin_role']      = $admin['role'];

            if ($admin['role'] === 'superadmin') {
                header('Location: ../super_admin/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        }
    }

    $error = 'Invalid username or password';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Event Reservation System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="login-page-container">
        <!-- Left Section -->
        <div class="login-side-form">
            <div>
                <div class="brand-logo">
                    <img src="../assets/images/logo.jpg" alt="Resort Logo">
                    <div class="brand-text">
                        <div class="brand-name">RESORT</div>
                        <div class="brand-sub">Admin Portal</div>
                    </div>
                </div>

                <div class="welcome-section">
                    <h1>Welcome back!</h1>
                    <p>Login to Admin Dashboard</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label for="username">Email or Username</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="form-control" required placeholder="Enter your username" autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                    
                </form>
            </div>

        </div>

        <!-- Right Section -->
        <div class="login-side-image"></div>
    </div>

    <script src="js/login.js"></script>
</body>

</html>