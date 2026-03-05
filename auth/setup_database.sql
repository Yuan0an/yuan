-- Database Setup Script for CK Reservation System

-- 1. Create Database and User (Run this as root/sudo)
CREATE DATABASE IF NOT EXISTS resort_db;
CREATE USER IF NOT EXISTS 'reserve_user'@'localhost' IDENTIFIED BY 'reserve_pass123';
CREATE USER IF NOT EXISTS 'reserve_user'@'127.0.0.1' IDENTIFIED BY 'reserve_pass123';
GRANT ALL PRIVILEGES ON resort_db.* TO 'reserve_user'@'localhost';
GRANT ALL PRIVILEGES ON resort_db.* TO 'reserve_user'@'127.0.0.1';
FLUSH PRIVILEGES;

USE resort_db;

-- 2. Create Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    max_persons INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed initial events if empty
INSERT INTO events (name, max_persons)
SELECT 'Day Tour', 50 WHERE NOT EXISTS (SELECT 1 FROM events);
INSERT INTO events (name, max_persons)
SELECT 'Night Tour', 50 WHERE NOT EXISTS (SELECT 1 FROM events WHERE name = 'Night Tour');
INSERT INTO events (name, max_persons)
SELECT 'Overnight Stay', 20 WHERE NOT EXISTS (SELECT 1 FROM events WHERE name = 'Overnight Stay');

-- 3. Create Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin: admin / admin123
INSERT INTO admins (username, password, full_name, email)
SELECT 'admin', '$2y$10$lf1E5/kUKYI1pmT.8jO7BOrsI0PP8OgpZepf08mvTOYqBoe99P77u', 'Administrator', 'admin@example.com'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'admin');

-- 4. Create Reservations Table
CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    event_title VARCHAR(255),
    event_type VARCHAR(100),
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    alt_phone VARCHAR(20),
    company VARCHAR(100),
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    persons INT NOT NULL,
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    payment_proof VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending',
    admin_notes TEXT,
    approved_by INT,
    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (approved_by) REFERENCES admins(id)
);
