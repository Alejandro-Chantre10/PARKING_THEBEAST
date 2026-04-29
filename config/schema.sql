-- =============================================
-- PARKING THE BEASTS - Database Schema
-- Based on Entity-Relationship Diagram
-- =============================================

CREATE DATABASE IF NOT EXISTS parqueadero_db;
USE parqueadero_db;

-- =============================================
-- ROLES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS roles (
    id_roles BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    INDEX idx_roles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles
INSERT INTO roles (code, name) VALUES 
    ('ADMIN', 'Administrador'),
    ('USER', 'Usuario'),
    ('EMPLOYEE', 'Empleado')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id_users BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_rol BIGINT(20) UNSIGNED NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_roles) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_users_email (email),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- FACILITIES TABLE (Parking Locations)
-- =============================================
CREATE TABLE IF NOT EXISTS facilities (
    id_facilities BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    address VARCHAR(180) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_facilities_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default facility
INSERT INTO facilities (name, address, is_active) VALUES 
    ('Parking The Beasts - Principal', 'Calle Principal #123, Ciudad', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =============================================
-- VEHICLE_TYPES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS vehicle_types (
    id_vehicle_types BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    INDEX idx_vehicle_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default vehicle types
INSERT INTO vehicle_types (code, name) VALUES 
    ('CAR', 'Carro'),
    ('MOTO', 'Moto'),
    ('BIKE', 'Bicicleta')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =============================================
-- PARKING_CAPACITY TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS parking_capacity (
    id_facilities BIGINT(20) UNSIGNED NOT NULL,
    id_vehicle_types BIGINT(20) UNSIGNED NOT NULL,
    capacity INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (id_facilities, id_vehicle_types),
    FOREIGN KEY (id_facilities) REFERENCES facilities(id_facilities) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_vehicle_types) REFERENCES vehicle_types(id_vehicle_types) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default capacities
INSERT INTO parking_capacity (id_facilities, id_vehicle_types, capacity) VALUES 
    (1, 1, 50),  -- 50 cars
    (1, 2, 30),  -- 30 motorcycles
    (1, 3, 20)   -- 20 bicycles
ON DUPLICATE KEY UPDATE capacity = VALUES(capacity);

-- =============================================
-- RATES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS rates (
    id_rates BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_facilities BIGINT(20) UNSIGNED NOT NULL,
    id_vehicle_types BIGINT(20) UNSIGNED NOT NULL,
    price_per_hour DECIMAL(12, 2) NOT NULL,
    min_minutes INT(11) NOT NULL DEFAULT 15,
    rounding_minutes INT(11) NOT NULL DEFAULT 15,
    grace_minutes INT(11) NOT NULL DEFAULT 10,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_facilities) REFERENCES facilities(id_facilities) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_vehicle_types) REFERENCES vehicle_types(id_vehicle_types) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_rates_active (is_active),
    UNIQUE KEY unique_facility_vehicle (id_facilities, id_vehicle_types)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default rates
INSERT INTO rates (id_facilities, id_vehicle_types, price_per_hour, min_minutes, rounding_minutes, grace_minutes) VALUES 
    (1, 1, 5000.00, 15, 15, 10),  -- Cars: $5000/hour
    (1, 2, 2500.00, 15, 15, 10),  -- Motorcycles: $2500/hour
    (1, 3, 1000.00, 15, 15, 10)   -- Bicycles: $1000/hour
ON DUPLICATE KEY UPDATE price_per_hour = VALUES(price_per_hour);

-- =============================================
-- RESERVATIONS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS reservations (
    id_reservations BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_facilities BIGINT(20) UNSIGNED NOT NULL,
    id_users BIGINT(20) UNSIGNED NOT NULL,
    id_vehicle_types BIGINT(20) UNSIGNED NOT NULL,
    vehicle_plate VARCHAR(20) NOT NULL,
    vehicle_description VARCHAR(120),
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    status ENUM('PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED') NOT NULL DEFAULT 'PENDING',
    price DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    notes VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_facilities) REFERENCES facilities(id_facilities) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_users) REFERENCES users(id_users) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_vehicle_types) REFERENCES vehicle_types(id_vehicle_types) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_reservations_status (status),
    INDEX idx_reservations_user (id_users),
    INDEX idx_reservations_dates (start_at, end_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- PAYMENTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS payments (
    id_payments BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_reservations BIGINT(20) UNSIGNED NOT NULL,
    id_users BIGINT(20) UNSIGNED NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'COP',
    method VARCHAR(30) NOT NULL,
    status ENUM('PENDING', 'PAID', 'FAILED', 'REFUNDED') NOT NULL DEFAULT 'PENDING',
    gateway_reference VARCHAR(120),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME,
    FOREIGN KEY (id_reservations) REFERENCES reservations(id_reservations) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_users) REFERENCES users(id_users) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_payments_status (status),
    INDEX idx_payments_reservation (id_reservations)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- PAYMENT_NOTIFICATIONS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS payment_notifications (
    id_payment_notifications BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_payments BIGINT(20) UNSIGNED NOT NULL,
    id_reservations BIGINT(20) UNSIGNED NOT NULL,
    id_users BIGINT(20) UNSIGNED NOT NULL,
    channel ENUM('INTERNAL', 'EMAIL', 'SMS', 'WHATSAPP') NOT NULL DEFAULT 'INTERNAL',
    message VARCHAR(255) NOT NULL,
    notification_status ENUM('CREATED', 'SENT', 'ERROR') NOT NULL DEFAULT 'CREATED',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME,
    FOREIGN KEY (id_payments) REFERENCES payments(id_payments) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_reservations) REFERENCES reservations(id_reservations) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_users) REFERENCES users(id_users) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_notifications_status (notification_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
