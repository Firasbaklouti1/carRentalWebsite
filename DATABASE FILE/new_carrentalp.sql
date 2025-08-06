-- Drop database if exists and create new
DROP DATABASE IF EXISTS carrentalp;
CREATE DATABASE carrentalp;
USE carrentalp;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create cars table
CREATE TABLE IF NOT EXISTS cars (
    car_id INT PRIMARY KEY AUTO_INCREMENT,
    car_name VARCHAR(100) NOT NULL,
    car_type VARCHAR(50) NOT NULL,
    car_image VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    car_availability ENUM('yes', 'no') NOT NULL DEFAULT 'yes'
);

-- Create bookings table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    return_date DATE NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    notes TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (car_id) REFERENCES cars(car_id)
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admins (username, password, name, email, phone) 
VALUES ('admin', '$2y$12$IsfzktCkg9/LdIjaHqF2NOgugfd4VabRGlexCsq3xnBpK0gzNggQ6', 'Administrator', 'admin@example.com', '1234567890');

-- Insert sample cars
INSERT INTO cars (car_name, car_type, car_image, price, car_availability) VALUES
('Toyota Camry', 'Sedan', 'assets/img/cars/camry.jpg', 50.00, 'yes'),
('Honda CR-V', 'SUV', 'assets/img/cars/crv.jpg', 65.00, 'yes'),
('BMW 3 Series', 'Luxury', 'assets/img/cars/bmw3.jpg', 85.00, 'yes'),
('Ford Mustang', 'Sports', 'assets/img/cars/mustang.jpg', 100.00, 'yes');

