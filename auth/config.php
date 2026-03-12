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
    
    // 🛡️ SELF-HEALING DATABASE: Ensure critical tables and columns exist.
    
    // 1. Create critical tables first if missing
    $conn->query("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        email VARCHAR(100),
        role VARCHAR(20) DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        customer_id INT,
        booking_date DATE,
        start_time TIME,
        end_time TIME,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT,
        amount DECIMAL(10,2),
        payment_status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

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
        qr_code_url VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // 2. Check/Add columns to existing tables
    $ensureColumn = function($table, $column, $definition) use ($conn) {
        try {
            $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($check && $check->num_rows === 0) {
                $conn->query("ALTER TABLE `$table` ADD COLUMN $column $definition");
            }
        } catch (Exception $e) {
            // Silently continue if column check fails
        }
    };

    $ensureColumn('bookings', 'addons_json', 'TEXT');
    $ensureColumn('bookings', 'reservation_id', 'VARCHAR(10) UNIQUE');
    $ensureColumn('payments', 'receipt_data', 'LONGTEXT');
    $ensureColumn('payments', 'time_uploaded', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    $ensureColumn('payments', 'refund_method', 'VARCHAR(50)');
    $ensureColumn('payments', 'refund_proof', 'VARCHAR(255)');
    $ensureColumn('payments', 'refunded_by', 'INT');
    $ensureColumn('payments', 'refunded_at', 'TIMESTAMP NULL');
    $ensureColumn('admins', 'role', "VARCHAR(20) DEFAULT 'admin' AFTER email");
    if ($conn->query("SHOW TABLES LIKE 'events'")->num_rows > 0) {
        $ensureColumn('events', 'pricing_logic', "TEXT AFTER is_overnight");
        $ensureColumn('events', 'sort_order', "INT DEFAULT 0");
    }
    $ensureColumn('addons', 'sort_order', "INT DEFAULT 0");
    $ensureColumn('payment_methods', 'qr_code_url', "VARCHAR(255) AFTER details");

    // 3. Seed default data if empty
    $check_settings = $conn->query("SELECT setting_key FROM site_settings LIMIT 1");
    if ($check_settings && $check_settings->num_rows === 0) {
        $terms = "1. Respect the venue property.\n2. No smoking inside rooms.\n3. Keep noise levels reasonable after 10 PM.";
        $cancel = "1. Full refund if cancelled 7 days before.\n2. 50% refund if cancelled 3 days before.\n3. No refund for same-day cancellations.";
        $footer_about = "Our resort provides a premium booking experience for your special events.";
        $footer_address = "123 Resort Drive, Event City, Philippines";
        $footer_contact = "Email: info@ckresort.com | Phone: +63 912 345 6789";
        
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('terms_conditions', ?), ('cancellation_policy', ?), ('footer_about', ?), ('footer_address', ?), ('footer_contact', ?)");
        $stmt->bind_param("sssss", $terms, $cancel, $footer_about, $footer_address, $footer_contact);
        $stmt->execute();
    }
    
    $check_addons = $conn->query("SELECT id FROM addons LIMIT 1");
    if ($check_addons && $check_addons->num_rows === 0) {
        $conn->query("INSERT INTO addons (name, price, type) VALUES ('LPGas', 250, 'counter'), ('Butane', 150, 'counter'), ('Bonfire', 500, 'counter'), ('Pet Fee', 200, 'counter'), ('Darts Game', 250, 'checkbox'), ('Billiard', 500, 'checkbox')");
    }

    $check_pm = $conn->query("SELECT id FROM payment_methods LIMIT 1");
    if ($check_pm && $check_pm->num_rows === 0) {
        $conn->query("INSERT INTO payment_methods (name, icon, details) VALUES ('GCash', 'fas fa-mobile-alt', '0917-123-4567\nEvent Venue Booking'), ('Bank Transfer', 'fas fa-university', 'BPI: 1234-5678-90\nEvent Venue Booking')");
    }

    // 4. Ensure Super Admin exists
    $sa_user = 'superadmin';
    $sa_pass_plain = 'Superadmin@ckresort1';
    
    $check_sa = $conn->query("SELECT id, password FROM admins WHERE username = '$sa_user' LIMIT 1");
    if ($check_sa && $check_sa->num_rows > 0) {
        $admin = $check_sa->fetch_assoc();
        if (!password_verify($sa_pass_plain, $admin['password'])) {
            $new_hash = password_hash($sa_pass_plain, PASSWORD_BCRYPT);
            $conn->query("UPDATE admins SET password = '$new_hash', role = 'superadmin' WHERE username = '$sa_user'");
        }
    } else {
        $new_hash = password_hash($sa_pass_plain, PASSWORD_BCRYPT);
        $conn->query("INSERT INTO admins (username, password, full_name, email, role) 
                      VALUES ('$sa_user', '$new_hash', 'Super Administrator', 'superadmin@example.com', 'superadmin')");
    }

} catch (mysqli_sql_exception $e) {
    die("Database Connection Error: " . $e->getMessage() . "<br><br><b>Troubleshooting Tip:</b> Ensure you have configured the production database credentials.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>