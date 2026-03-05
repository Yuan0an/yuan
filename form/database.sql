CREATE DATABASE reservation_system;
USE reservation_system;

-- Events with specific time slots
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_persons INT NOT NULL,
    is_overnight BOOLEAN DEFAULT FALSE
);

-- Reservations with status
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    persons INT NOT NULL,
    
    -- Reservation status
    status ENUM('pending', 'approved', 'cancelled', 'rejected') DEFAULT 'pending',
    
    -- Customer information
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    alt_phone VARCHAR(20),
    company VARCHAR(100),
    event_title VARCHAR(200) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    
    -- Payment info
    payment_proof VARCHAR(255),
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    
    terms_accepted BOOLEAN DEFAULT FALSE,
    cancellation_accepted BOOLEAN DEFAULT FALSE,

    total_price DECIMAL(10,2) DEFAULT 0.00,
    addons_json TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (event_id) REFERENCES events(id)
);

-- Insert events
INSERT INTO events (name, start_time, end_time, max_persons, is_overnight) VALUES
('Day Tour', '08:00:00', '16:00:00', 50, FALSE),
('Night Tour', '16:00:00', '00:00:00', 50, FALSE),
('Overnight Stay - 9AM to 7AM', '09:00:00', '07:00:00', 70, TRUE),
('Overnight Stay - 2PM to 12NN', '14:00:00', '12:00:00', 70, TRUE),
('Overnight Stay - 7PM to 5PM', '19:00:00', '17:00:00', 70, TRUE);