-- Vehicle Rental Management System - Database
-- Import this file in phpMyAdmin (or: mysql -u root -p < database.sql)

CREATE DATABASE IF NOT EXISTS vehicle_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vehicle_rental;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(60) NOT NULL,
    type ENUM('Car','SUV','Bike','Scooter','Van','Truck') NOT NULL,
    fuel ENUM('Petrol','Diesel','Electric','CNG','Hybrid') NOT NULL,
    transmission ENUM('Manual','Automatic') NOT NULL,
    seats TINYINT NOT NULL,
    reg_number VARCHAR(20) NOT NULL UNIQUE,
    price_per_day DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    description TEXT,
    status ENUM('available','maintenance') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    pickup_location VARCHAR(150) NOT NULL,
    status ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default accounts
-- Admin: admin@rental.com / admin123
-- User : user@rental.com  / user123
INSERT INTO users (name, email, phone, password, role) VALUES
('Administrator', 'admin@rental.com', '9876543210', '$2y$10$B1CL8tNWR35ujSGhTDxEcOMm4Xy4nEycXpVqYnmzWrL07D/UcGM/m', 'admin'),
('Rahul Sharma', 'user@rental.com', '9123456780', '$2y$10$1J6byw1PuH8LsHwW7m0At.fyxhvTvS10fIfesgCPW0jgAV/XRu4Uy', 'user');

INSERT INTO vehicles (name, brand, type, fuel, transmission, seats, reg_number, price_per_day, description, status) VALUES
('Swift', 'Maruti Suzuki', 'Car', 'Petrol', 'Manual', 5, 'DL01AB1234', 1500.00, 'Compact, fuel-efficient hatchback ideal for city drives.', 'available'),
('City', 'Honda', 'Car', 'Petrol', 'Automatic', 5, 'DL02CD5678', 2500.00, 'Comfortable sedan with spacious cabin and smooth automatic gearbox.', 'available'),
('Creta', 'Hyundai', 'SUV', 'Diesel', 'Automatic', 5, 'DL03EF9012', 3200.00, 'Feature-loaded SUV, great for highways and family trips.', 'available'),
('Innova Crysta', 'Toyota', 'SUV', 'Diesel', 'Manual', 7, 'DL04GH3456', 4000.00, 'Reliable 7-seater for group travel and long journeys.', 'available'),
('Nexon EV', 'Tata', 'SUV', 'Electric', 'Automatic', 5, 'DL05IJ7890', 3000.00, 'All-electric SUV with 300+ km range. Zero emissions.', 'available'),
('Thar', 'Mahindra', 'SUV', 'Diesel', 'Manual', 4, 'DL06KL1122', 3800.00, 'Iconic off-roader for adventure trips.', 'maintenance'),
('Classic 350', 'Royal Enfield', 'Bike', 'Petrol', 'Manual', 2, 'DL07MN3344', 900.00, 'Classic cruiser motorcycle for weekend rides.', 'available'),
('Pulsar NS200', 'Bajaj', 'Bike', 'Petrol', 'Manual', 2, 'DL08OP5566', 700.00, 'Sporty street bike with sharp handling.', 'available'),
('Activa 6G', 'Honda', 'Scooter', 'Petrol', 'Automatic', 2, 'DL09QR7788', 400.00, 'Easy-to-ride scooter for daily commuting.', 'available'),
('450X', 'Ather', 'Scooter', 'Electric', 'Automatic', 2, 'DL10ST9900', 550.00, 'Smart electric scooter with touchscreen dashboard.', 'available'),
('Eeco', 'Maruti Suzuki', 'Van', 'CNG', 'Manual', 7, 'DL11UV1212', 1800.00, 'Economical van for cargo or passengers.', 'available'),
('Ace', 'Tata', 'Truck', 'Diesel', 'Manual', 2, 'DL12WX3434', 2200.00, 'Mini truck for shifting and goods transport.', 'available');

INSERT INTO bookings (user_id, vehicle_id, start_date, end_date, total_days, total_amount, pickup_location, status) VALUES
(2, 1, CURDATE() + INTERVAL 2 DAY, CURDATE() + INTERVAL 4 DAY, 3, 4500.00, 'Connaught Place, Delhi', 'pending'),
(2, 7, CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 8 DAY, 3, 2700.00, 'Karol Bagh, Delhi', 'completed');

INSERT INTO messages (name, email, subject, message) VALUES
('Priya Verma', 'priya@example.com', 'Long-term rental', 'Do you offer discounts for monthly SUV rentals?');
