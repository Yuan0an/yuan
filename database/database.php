<?php
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$dbname = getenv('MYSQL_DATABASE') ?: (getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: (getenv('MYSQLHOST') ? 'railway' : 'resort_db')));
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);

    // 🛡️ CORE SELF-HEALING: Ensure basic tables exist
    $conn->query("CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        max_persons INT NOT NULL,
        is_overnight BOOLEAN DEFAULT FALSE,
        pricing_logic TEXT,
        sort_order INT DEFAULT 0
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        alt_phone VARCHAR(20),
        company VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT,
        event_id INT,
        booking_date DATE,
        start_time TIME,
        end_time TIME,
        persons INT,
        status VARCHAR(50) DEFAULT 'pending',
        event_title VARCHAR(200),
        event_type VARCHAR(50),
        approved_by INT,
        approved_at DATETIME,
        admin_notes TEXT,
        addons_json TEXT,
        reservation_id VARCHAR(10) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT,
        payment_method VARCHAR(50),
        payment_status VARCHAR(50) DEFAULT 'pending',
        payment_proof VARCHAR(255),
        receipt_data LONGTEXT,
        total_price DECIMAL(10,2),
        refund_method VARCHAR(50),
        refund_proof VARCHAR(255),
        refunded_by INT,
        refunded_at TIMESTAMP NULL,
        time_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed default settings if empty
    $check_settings = $conn->query("SELECT setting_key FROM site_settings LIMIT 1");
    if ($check_settings && $check_settings->num_rows === 0) {
        $terms = "1. Respect the venue property.\n2. No smoking inside rooms.\n3. Keep noise levels reasonable after 10 PM.";
        $cancel = "1. Full refund if cancelled 7 days before.\n2. 50% refund if cancelled 3 days before.\n3. No refund for same-day cancellations.";
        $footer_about = "Our resort provides a premium booking experience for your special events.";
        $footer_address = "123 Resort Drive, San Simon, Pampanga, Philippines";
        $footer_contact = "Email: info@ckresort.com | Phone: +63 920 950 2510";
        
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('terms_conditions', ?), ('cancellation_policy', ?), ('footer_about', ?), ('footer_address', ?), ('footer_contact', ?)");
        $stmt->bind_param("sssss", $terms, $cancel, $footer_about, $footer_address, $footer_contact);
        $stmt->execute();
    }

    // Seed default events if empty
    $check_events = $conn->query("SELECT id FROM events LIMIT 1");
    if ($check_events && $check_events->num_rows === 0) {
        $conn->query("INSERT INTO events (name, start_time, end_time, max_persons, is_overnight, sort_order) VALUES 
            ('Day Tour', '08:00:00', '16:00:00', 50, 0, 1),
            ('Night Tour', '16:00:00', '00:00:00', 50, 0, 2),
            ('Overnight Stay - 9AM to 7AM', '09:00:00', '07:00:00', 70, 1, 3),
            ('Overnight Stay - 2PM to 12NN', '14:00:00', '12:00:00', 70, 1, 4),
            ('Overnight Stay - 7PM to 5PM', '19:00:00', '17:00:00', 70, 1, 5)");
    }

    // 🛡️ Disable strict reporting for the rest of the application to prevent 500 errors on minor queries
    mysqli_report(MYSQLI_REPORT_OFF);

} catch (mysqli_sql_exception $e) {
    // If we can't connect, don't crash the whole site, just disable DB features
    $conn = null;
}
?>