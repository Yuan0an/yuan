
-- -----------------------------
-- ADMINS TABLE
-- -----------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$lf1E5/kUKYI1pmT.8jO7BOrsI0PP8OgpZepf08mvTOYqBoe99P77u', 'Administrator', 'admin@example.com', '2026-02-06 05:32:32');
-- -----------------------------
-- CUSTOMERS TABLE
-- -----------------------------
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    alt_phone VARCHAR(20),
    company VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------
-- EVENTS TABLE
-- -----------------------------
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_persons INT NOT NULL,
    is_overnight BOOLEAN DEFAULT FALSE
);

-- -----------------------------
-- BOOKINGS TABLE
-- -----------------------------
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,
    event_id INT NOT NULL,

    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    persons INT NOT NULL,

    status VARCHAR(50) DEFAULT 'pending',

    event_title VARCHAR(200),
    event_type VARCHAR(50),

    approved_by INT,
    approved_at DATETIME,
    admin_notes TEXT,
    addons_json TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (approved_by) REFERENCES admins(id)
);

-- -----------------------------
-- PAYMENTS TABLE
-- -----------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    booking_id INT NOT NULL,

    payment_method VARCHAR(50),
    payment_status VARCHAR(50),
    payment_proof VARCHAR(255),

    total_price DECIMAL(10,2),
    time_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
