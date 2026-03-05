-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (if not exists)
-- Password is 'admin123'
INSERT INTO admins (username, password, full_name, email)
SELECT 'admin', '$2y$10$lf1E5/kUKYI1pmT.8jO7BOrsI0PP8OgpZepf08mvTOYqBoe99P77u', 'Administrator', 'admin@example.com'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'admin');