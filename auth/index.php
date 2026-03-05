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
    $stmt = $conn->prepare("SELECT id, username, password, full_name, email FROM admins WHERE username = ?");
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

            header('Location: dashboard.php');
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

                    <div class="options-row">
                        <label class="remember-me">
                            <input type="checkbox"> Remember me
                        </label>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                    
                    <a href="#" class="forgot-link">Forgot Password or Username?</a>
                </form>
            </div>

            <div class="login-footer">
                <a href="#">FAQS</a>
                <a href="#">TERMS OF USE</a>
                <a href="#">PRIVACY POLICY</a>
            </div>
        </div>

        <!-- Right Section -->
        <div class="login-side-image"></div>
    </div>

    <script>
        function togglePassword() {
            const pwdInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>