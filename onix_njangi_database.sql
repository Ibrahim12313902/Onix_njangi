-- Create database
CREATE DATABASE IF NOT EXISTS onix_njangi;
USE onix_njangi;

-- Admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: admin123)
INSERT INTO admin_users (username, password, full_name, email) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'admin@onix.com');

-- Member types table
CREATE TABLE member_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_type_number VARCHAR(20) UNIQUE NOT NULL,
    member_type_name VARCHAR(100) NOT NULL,
    description TEXT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Members table
CREATE TABLE members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_number VARCHAR(20) UNIQUE NOT NULL,
    member_type_id INT,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    surname VARCHAR(100) NOT NULL,
    nationality VARCHAR(100) DEFAULT 'Cameroonian',
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Others') DEFAULT 'Male',
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_type_id) REFERENCES member_types(id) ON DELETE SET NULL
);

-- Hand types table
CREATE TABLE hand_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hand_type_number VARCHAR(20) UNIQUE NOT NULL,
    hand_type_name VARCHAR(100) NOT NULL,
    description TEXT,
    default_amount DECIMAL(12,2) DEFAULT 0.00,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hand status table
CREATE TABLE hand_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hand_status_number VARCHAR(20) UNIQUE NOT NULL,
    hand_status_name VARCHAR(100) NOT NULL,
    description TEXT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hands table (Njangi contributions/accounts)
CREATE TABLE hands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hand_number VARCHAR(20) UNIQUE NOT NULL,
    member_id INT,
    hand_type_id INT,
    hand_status_id INT,
    amount DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Amount in FCFA',
    opening_date DATE,
    closing_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (hand_type_id) REFERENCES hand_types(id) ON DELETE SET NULL,
    FOREIGN KEY (hand_status_id) REFERENCES hand_status(id) ON DELETE SET NULL
);

-- Contributions table (for tracking payments)
CREATE TABLE contributions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hand_id INT,
    amount DECIMAL(12,2) NOT NULL COMMENT 'Amount in FCFA',
    contribution_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Mobile Money', 'Bank Transfer', 'Other'),
    reference_number VARCHAR(50),
    notes TEXT,
    recorded_by INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hand_id) REFERENCES hands(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Saved records summary table
CREATE TABLE saved_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_type ENUM('current_month', 'last_quarter', 'achievement', 'piggie_box'),
    title VARCHAR(100),
    amount DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Amount in FCFA',
    description TEXT,
    record_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update the saved_records table structure
ALTER TABLE saved_records MODIFY record_type ENUM('current_month', 'last_quarter', 'achievement', 'piggie_box', 'custom') NOT NULL;

-- Add more fields for better tracking
ALTER TABLE saved_records 
ADD COLUMN `icon` VARCHAR(50) DEFAULT 'fas fa-save',
ADD COLUMN `color` VARCHAR(20) DEFAULT '#667eea',
ADD COLUMN `is_active` TINYINT(1) DEFAULT 1,
ADD COLUMN `updated_by` INT NULL,
ADD COLUMN `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
ADD FOREIGN KEY (`updated_by`) REFERENCES admin_users(`id`) ON DELETE SET NULL;

-- Create saved_records_archive table for history
CREATE TABLE IF NOT EXISTS saved_records_archive (
    id INT PRIMARY KEY AUTO_INCREMENT,
    original_id INT,
    record_type ENUM('current_month', 'last_quarter', 'achievement', 'piggie_box', 'custom'),
    title VARCHAR(100),
    amount DECIMAL(12,2),
    description TEXT,
    record_date DATE,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT,
    action ENUM('UPDATE', 'DELETE', 'ARCHIVE'),
    FOREIGN KEY (archived_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Create piggie_box_transactions table for piggie box details
CREATE TABLE IF NOT EXISTS piggie_box_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_type ENUM('DEPOSIT', 'WITHDRAWAL', 'INTEREST') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    balance_after DECIMAL(12,2),
    reference_number VARCHAR(50),
    recorded_by INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Create achievements table for tracking milestones
CREATE TABLE IF NOT EXISTS achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    achievement_date DATE,
    icon VARCHAR(50),
    color VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO saved_records (record_type, title, amount, description, record_date, icon, color) VALUES
('current_month', 'January 2024 Contributions', 250000, 'Total contributions for January 2024', '2024-01-31', 'fas fa-calendar-alt', '#28a745'),
('current_month', 'February 2024 Contributions', 275000, 'Total contributions for February 2024', '2024-02-29', 'fas fa-calendar-alt', '#28a745'),
('last_quarter', 'Q4 2023 Summary', 850000, 'October - December 2023 total contributions', '2023-12-31', 'fas fa-chart-line', '#17a2b8'),
('last_quarter', 'Q1 2024 Summary', 925000, 'January - March 2024 total contributions', '2024-03-31', 'fas fa-chart-line', '#17a2b8'),
('achievement', '50 Members Milestone', NULL, 'Reached 50 active members', '2024-01-15', 'fas fa-trophy', '#ffc107'),
('achievement', '1 Million FCFA Total Savings', 1000000, 'Accumulated savings reached 1 million', '2024-02-20', 'fas fa-star', '#ffc107'),
('piggie_box', 'Emergency Fund', 500000, 'Emergency savings for group', '2024-03-01', 'fas fa-piggy-bank', '#dc3545');

INSERT INTO piggie_box_transactions (transaction_type, amount, description, transaction_date, balance_after) VALUES
('DEPOSIT', 100000, 'Initial deposit', '2024-01-01', 100000),
('DEPOSIT', 150000, 'Monthly savings', '2024-02-01', 250000),
('DEPOSIT', 150000, 'Monthly savings', '2024-03-01', 400000),
('INTEREST', 5000, 'Monthly interest', '2024-03-31', 405000),
('WITHDRAWAL', 50000, 'Emergency withdrawal - Member medical', '2024-02-15', 355000),
('DEPOSIT', 95000, 'Replenishment', '2024-02-20', 450000);

INSERT INTO achievements (title, description, achievement_date, icon, color) VALUES
('First Member', 'First member registered in the system', '2024-01-01', 'fas fa-user-plus', '#28a745'),
('10 Members', 'Reached 10 active members', '2024-01-10', 'fas fa-users', '#17a2b8'),
('25 Members', 'Reached 25 active members', '2024-01-20', 'fas fa-users', '#17a2b8'),
('50 Members', 'Reached 50 active members', '2024-02-15', 'fas fa-trophy', '#ffc107'),
('First Hand', 'First hand opened', '2024-01-05', 'fas fa-hand-holding-heart', '#28a745'),
('10 Hands', '10 hands opened', '2024-01-25', 'fas fa-hands-helping', '#17a2b8'),
('1M Savings', 'Total savings reached 1 million', '2024-02-28', 'fas fa-star', '#ffc107');