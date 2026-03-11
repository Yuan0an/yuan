<?php
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$dbname = getenv('MYSQL_DATABASE') ?: (getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: (getenv('MYSQLHOST') ? 'railway' : 'resort_db')));
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    // 🛡️ SELF-HEALING DATABASE: Ensure critical columns and tables exist.
    // This handles cases where migrations were not run or failed on Railway.
    
    // 1. Check/Add columns to existing tables
    $ensureColumn = function($table, $column, $definition) use ($conn) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE `$table` ADD COLUMN $column $definition");
        }
    };

    $ensureColumn('bookings', 'addons_json', 'TEXT');
    $ensureColumn('bookings', 'reservation_id', 'VARCHAR(10) UNIQUE');
    $ensureColumn('payments', 'receipt_data', 'LONGTEXT');
    $ensureColumn('payments', 'time_uploaded', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    $ensureColumn('admins', 'role', "VARCHAR(20) DEFAULT 'admin' AFTER email");
    $ensureColumn('events', 'pricing_logic', "TEXT AFTER is_overnight");

    // 2. Create new tables if missing
    $conn->query("CREATE TABLE IF NOT EXISTS addons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        type ENUM('counter', 'checkbox') DEFAULT 'counter',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50),
        details TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Seed default data if empty
    $check_addons = $conn->query("SELECT id FROM addons LIMIT 1");
    if ($check_addons && $check_addons->num_rows === 0) {
        $conn->query("INSERT INTO addons (name, price, type) VALUES ('LPGas', 250, 'counter'), ('Butane', 150, 'counter'), ('Bonfire', 500, 'counter'), ('Pet Fee', 200, 'counter'), ('Darts Game', 250, 'checkbox'), ('Billiard', 500, 'checkbox')");
    }

    $check_pm = $conn->query("SELECT id FROM payment_methods LIMIT 1");
    if ($check_pm && $check_pm->num_rows === 0) {
        $conn->query("INSERT INTO payment_methods (name, icon, details) VALUES ('GCash', 'fas fa-mobile-alt', '0917-123-4567\nEvent Venue Booking'), ('Bank Transfer', 'fas fa-university', 'BPI: 1234-5678-90\nEvent Venue Booking')");
    }

    // 4. Ensure Super Admin exists with correct credentials and role
    $sa_user = 'superadmin';
    $sa_pass_plain = 'Superadmin@ckresort1';
    
    $check_sa = $conn->query("SELECT id, password FROM admins WHERE username = '$sa_user' LIMIT 1");
    if ($check_sa && $check_sa->num_rows > 0) {
        $admin = $check_sa->fetch_assoc();
        // If password verification fails, force update the password hash
        if (!password_verify($sa_pass_plain, $admin['password'])) {
            $new_hash = password_hash($sa_pass_plain, PASSWORD_BCRYPT);
            $conn->query("UPDATE admins SET password = '$new_hash', role = 'superadmin' WHERE username = '$sa_user'");
        }
    } else {
        // Create it if it doesn't exist
        $new_hash = password_hash($sa_pass_plain, PASSWORD_BCRYPT);
        $conn->query("INSERT INTO admins (username, password, full_name, email, role) 
                      VALUES ('$sa_user', '$new_hash', 'Super Administrator', 'superadmin@example.com', 'superadmin')");
    }

} catch (mysqli_sql_exception $e) {
    die("Database Connection Error: " . $e->getMessage() . "<br><br><b>Troubleshooting Tip:</b> Ensure you have configured the production database credentials.");
}

session_start();
?>