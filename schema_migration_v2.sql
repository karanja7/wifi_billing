-- WiFi Billing System - Captive Portal with M-PESA Integration
-- Version 2.0 - Complete Redesign
-- Created: 2024-12-08

-- ============================================
-- DATABASE SETUP
-- ============================================
DROP DATABASE IF EXISTS wifi_billing;
CREATE DATABASE IF NOT EXISTS wifi_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wifi_billing;

-- ============================================
-- 1. ADMINS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    INDEX idx_username (username),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. PLANS TABLE (NEW - Updated Plans)
-- ============================================
CREATE TABLE IF NOT EXISTS plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_hours INT NOT NULL,
    duration_minutes INT NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. USERS TABLE (Simplified - Phone-based)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100),
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_purchase DATETIME,
    INDEX idx_phone (phone_number),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. DEVICES TABLE (MAC Address Tracking)
-- ============================================
CREATE TABLE IF NOT EXISTS devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    mac_address VARCHAR(17) UNIQUE NOT NULL,
    device_name VARCHAR(100),
    device_type VARCHAR(50),
    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mac (mac_address),
    INDEX idx_user (user_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. PAYMENTS TABLE (M-PESA Integration)
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    mpesa_checkout_request_id VARCHAR(100) UNIQUE,
    mpesa_merchant_request_id VARCHAR(100),
    status ENUM('pending', 'success', 'failed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'mpesa',
    mpesa_response JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id),
    INDEX idx_user (user_id),
    INDEX idx_phone (phone_number),
    INDEX idx_status (status),
    INDEX idx_mpesa_id (mpesa_checkout_request_id),
    INDEX idx_paid_at (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. SESSIONS TABLE (Active WiFi Sessions)
-- ============================================
CREATE TABLE IF NOT EXISTS sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    payment_id INT NOT NULL,
    mac_address VARCHAR(17) NOT NULL,
    ip_address VARCHAR(45),
    plan_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    data_used_mb DECIMAL(10,2) DEFAULT 0,
    router_status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    cancelled_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (mac_address) REFERENCES devices(mac_address),
    FOREIGN KEY (plan_id) REFERENCES plans(id),
    INDEX idx_user (user_id),
    INDEX idx_mac (mac_address),
    INDEX idx_status (status),
    INDEX idx_end_time (end_time),
    INDEX idx_active (status, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ACCESS_LOGS TABLE (Audit Trail)
-- ============================================
CREATE TABLE IF NOT EXISTS access_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    mac_address VARCHAR(17),
    ip_address VARCHAR(45),
    action VARCHAR(100),
    status VARCHAR(50),
    details JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mac (mac_address),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. ROUTER_CONFIG TABLE (Router Integration)
-- ============================================
CREATE TABLE IF NOT EXISTS router_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    router_type VARCHAR(50) NOT NULL COMMENT 'mikrotik, coovachilli, nodogsplash, pfsense, unifi',
    router_ip VARCHAR(45) NOT NULL,
    router_username VARCHAR(100),
    router_password VARCHAR(255),
    router_port INT,
    api_token VARCHAR(500),
    is_active BOOLEAN DEFAULT 1,
    last_sync DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (router_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. M-PESA CONFIG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS mpesa_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    consumer_key VARCHAR(255) NOT NULL,
    consumer_secret VARCHAR(255) NOT NULL,
    business_shortcode VARCHAR(20) NOT NULL,
    passkey VARCHAR(255) NOT NULL,
    callback_url VARCHAR(255) NOT NULL,
    test_mode BOOLEAN DEFAULT 1,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Insert Plans (1hr - 10 Ksh, 3hr - 20 Ksh, 6hr - 40 Ksh, 12hr - 70 Ksh, 24hr - 120 Ksh)
INSERT INTO plans (id, name, description, price, duration_hours, duration_minutes, is_active) VALUES
(1, '1 Hour', 'Fast hourly access', 10.00, 1, 60, 1),
(2, '3 Hours', 'Extended access', 20.00, 3, 180, 1),
(3, '6 Hours', 'Half-day access', 40.00, 6, 360, 1),
(4, '12 Hours', 'Full day access', 70.00, 12, 720, 1),
(5, '24 Hours', 'Premium daily access', 120.00, 24, 1440, 1);

-- Insert Default Admin (username: admin, password: admin123)
INSERT INTO admins (id, username, email, password, is_active) VALUES
(1, 'admin', 'admin@wifibilling.local', '$2y$10$O2YBZLVj1k3PwqZ.5qZf..XvPH2j8rNBqfNn0T5yPWzGHlqmz2mZi', 1);

-- Insert M-PESA Default Config (PLACEHOLDER - Update with real credentials)
INSERT INTO mpesa_config (consumer_key, consumer_secret, business_shortcode, passkey, callback_url, test_mode, is_active) VALUES
('YOUR_CONSUMER_KEY', 'YOUR_CONSUMER_SECRET', '174379', 'YOUR_PASSKEY', 'https://yourdomain.com/api/mpesa/callback', 1, 1);

-- Insert Router Config (PLACEHOLDER - Update with actual router details)
INSERT INTO router_config (router_type, router_ip, router_username, router_password, router_port, is_active) VALUES
('mikrotik', '192.168.1.1', 'admin', 'password', 8728, 0);

-- ============================================
-- END OF MIGRATION
-- ============================================
