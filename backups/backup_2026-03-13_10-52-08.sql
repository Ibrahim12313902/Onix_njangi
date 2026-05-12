-- ONIX Njangi System Backup
-- Generated: 2026-03-13 10:52:08



CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO activity_logs VALUES ('1', '1', 'Updated system settings', 'Updated display settings', '::1', NULL, '2026-03-13 11:46:31');
INSERT INTO activity_logs VALUES ('2', '1', 'Updated system settings', 'Updated notifications settings', '::1', NULL, '2026-03-13 11:49:03');


CREATE TABLE `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO admin_users VALUES ('1', 'admin', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'admin@onix.com', '2026-01-30 17:43:01');


CREATE TABLE `backup_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(255) NOT NULL,
  `backup_file` varchar(500) NOT NULL,
  `backup_size` int DEFAULT NULL,
  `backup_type` enum('manual','automatic','scheduled') DEFAULT 'manual',
  `status` enum('success','failed','in_progress') DEFAULT 'success',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `restored_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



CREATE TABLE `contributions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hand_id` int DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL COMMENT 'Amount in FCFA',
  `contribution_date` date NOT NULL,
  `payment_method` enum('Cash','Mobile Money','Bank Transfer','Other') DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text,
  `recorded_by` int DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hand_id` (`hand_id`),
  KEY `recorded_by` (`recorded_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



CREATE TABLE `hand_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hand_status_number` varchar(20) NOT NULL,
  `hand_status_name` varchar(100) NOT NULL,
  `description` text,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hand_status_number` (`hand_status_number`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO hand_status VALUES ('1', 'HS20260303633', 'actif', 'actif', '2026-03-03 11:28:39');


CREATE TABLE `hand_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hand_type_number` varchar(20) NOT NULL,
  `hand_type_name` varchar(100) NOT NULL,
  `description` text,
  `default_amount` decimal(12,2) DEFAULT '0.00',
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hand_type_number` (`hand_type_number`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO hand_types VALUES ('1', 'HT20260303601', 'monthly', 'wdiegedwfhwufehwu', '2000.00', '2026-03-03 11:04:41');


CREATE TABLE `hands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hand_number` varchar(20) NOT NULL,
  `member_id` int DEFAULT NULL,
  `hand_type_id` int DEFAULT NULL,
  `hand_status_id` int DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT '0.00' COMMENT 'Amount in FCFA',
  `opening_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hand_number` (`hand_number`),
  KEY `member_id` (`member_id`),
  KEY `hand_type_id` (`hand_type_id`),
  KEY `hand_status_id` (`hand_status_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO hands VALUES ('1', 'H202603031133', '1', '1', '1', '10000.00', '2026-03-03', NULL, 'huhgjk', '2026-03-03 11:31:07', '2026-03-03 11:31:07');


CREATE TABLE `member_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_type_number` varchar(20) NOT NULL,
  `member_type_name` varchar(100) NOT NULL,
  `description` text,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_type_number` (`member_type_number`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO member_types VALUES ('2', 'MT20260303635', 'President', 'Head of Contributions', '2026-03-03 11:20:45', '2026-03-03 11:20:45');
INSERT INTO member_types VALUES ('3', 'MT20260309688', 'secretory', 'actif', '2026-03-09 12:06:33', '2026-03-09 12:06:33');


CREATE TABLE `members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_number` varchar(20) NOT NULL,
  `member_type_id` int DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `nationality` varchar(100) DEFAULT 'Cameroonian',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Others') DEFAULT 'Male',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_number` (`member_number`),
  KEY `member_type_id` (`member_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO members VALUES ('1', 'M202603032321', '2', 'Fonji Elad', '', 'King K', 'Cameroonian', '2006-01-25', 'Male', '+237670810307', 'abbamaster2005@gmail.com', 'pk8', '2026-03-03 11:21:56', '2026-03-03 11:22:48');


CREATE TABLE `piggie_box_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_type` enum('DEPOSIT','WITHDRAWAL','INTEREST') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` text,
  `transaction_date` date NOT NULL,
  `balance_after` decimal(12,2) DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recorded_by` (`recorded_by`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO piggie_box_transactions VALUES ('1', 'DEPOSIT', '100000.00', 'Initial deposit', '2024-01-01', '100000.00', NULL, NULL, '2026-03-12 13:39:17');
INSERT INTO piggie_box_transactions VALUES ('2', 'DEPOSIT', '150000.00', 'Monthly savings', '2024-02-01', '250000.00', NULL, NULL, '2026-03-12 13:39:17');
INSERT INTO piggie_box_transactions VALUES ('3', 'INTEREST', '5000.00', 'Monthly interest', '2024-02-28', '255000.00', NULL, NULL, '2026-03-12 13:39:17');
INSERT INTO piggie_box_transactions VALUES ('4', 'DEPOSIT', '2000001.00', '1', '2026-03-12', '2255001.00', '690538767', '1', '2026-03-12 14:24:49');


CREATE TABLE `saved_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_type` enum('current_month','last_quarter','achievement','piggie_box') DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT '0.00' COMMENT 'Amount in FCFA',
  `description` text,
  `icon` varchar(50) DEFAULT 'fas fa-save',
  `color` varchar(20) DEFAULT '#667eea',
  `is_active` tinyint(1) DEFAULT '1',
  `record_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO saved_records VALUES ('1', 'current_month', 'March 2026 Contributions', '20000.00', 'me', 'fas fa-calendar-alt', '#28a745', '1', '2026-03-12', '2026-03-12 14:22:27', NULL);


CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('text','number','boolean','color','select') DEFAULT 'text',
  `setting_group` varchar(50) DEFAULT 'general',
  `description` text,
  `options` text,
  `is_editable` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO system_settings VALUES ('1', 'site_name', 'ONIX - Njangi Management System', 'text', 'general', 'System name displayed throughout the application', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('2', 'site_url', 'http://localhost/onix_njangi/', 'text', 'general', 'Base URL of the application', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('3', 'admin_email', 'admin@onix.com', 'text', 'general', 'Primary administrator email', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('4', 'timezone', 'Africa/Douala', 'select', 'general', 'System timezone', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('5', 'date_format', 'd/m/Y', 'select', 'general', 'Date display format', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('6', 'currency', 'FCFA', 'text', 'general', 'Currency symbol', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('7', 'currency_position', 'right', 'select', 'general', 'Currency symbol position (left/right)', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('8', 'email_notifications', '1', 'boolean', 'notifications', 'Enable email notifications', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:49:03');
INSERT INTO system_settings VALUES ('9', 'sms_notifications', '1', 'boolean', 'notifications', 'Enable SMS notifications', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:49:03');
INSERT INTO system_settings VALUES ('10', 'notify_on_new_member', '1', 'boolean', 'notifications', 'Notify when new member registers', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:49:03');
INSERT INTO system_settings VALUES ('11', 'notify_on_new_hand', '1', 'boolean', 'notifications', 'Notify when new hand is opened', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:49:03');
INSERT INTO system_settings VALUES ('12', 'notify_on_contribution', '1', 'boolean', 'notifications', 'Notify on contributions', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:49:03');
INSERT INTO system_settings VALUES ('13', 'items_per_page', '10', 'number', 'display', 'Number of items per page', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:46:30');
INSERT INTO system_settings VALUES ('14', 'theme_color', '#667eea', 'color', 'display', 'Primary theme color', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:46:31');
INSERT INTO system_settings VALUES ('15', 'sidebar_collapsed', '1', 'boolean', 'display', 'Sidebar collapsed by default', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:46:31');
INSERT INTO system_settings VALUES ('16', 'show_logo', '1', 'boolean', 'display', 'Show logo in header', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:46:31');
INSERT INTO system_settings VALUES ('17', 'dashboard_layout', 'grid', 'select', 'display', 'Dashboard layout (grid/list)', NULL, '1', '2026-03-12 16:11:50', '2026-03-13 11:46:31');
INSERT INTO system_settings VALUES ('18', 'session_timeout', '30', 'number', 'security', 'Session timeout in minutes', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('19', 'password_expiry', '90', 'number', 'security', 'Password expiry in days', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('20', 'max_login_attempts', '5', 'number', 'security', 'Maximum login attempts before lockout', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('21', 'two_factor_auth', '0', 'boolean', 'security', 'Enable two-factor authentication', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('22', 'maintenance_mode', '0', 'boolean', 'security', 'Put system in maintenance mode', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('23', 'auto_backup', '1', 'boolean', 'backup', 'Enable automatic backups', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('24', 'backup_frequency', 'daily', 'select', 'backup', 'Backup frequency (daily/weekly/monthly)', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('25', 'backup_time', '02:00', 'text', 'backup', 'Backup execution time', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('26', 'backup_retention', '30', 'number', 'backup', 'Number of days to keep backups', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('27', 'backup_location', '../backups/', 'text', 'backup', 'Backup storage location', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('28', 'default_hand_amount', '5000', 'number', 'njangi', 'Default hand contribution amount', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('29', 'min_contribution', '100', 'number', 'njangi', 'Minimum contribution amount', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('30', 'max_contribution', '1000000', 'number', 'njangi', 'Maximum contribution amount', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('31', 'allow_partial_payment', '1', 'boolean', 'njangi', 'Allow partial payments', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('32', 'enable_interest', '0', 'boolean', 'njangi', 'Enable interest on savings', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('33', 'interest_rate', '5', 'number', 'njangi', 'Annual interest rate (%)', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('34', 'grace_period', '7', 'number', 'njangi', 'Grace period for contributions (days)', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('35', 'late_fee', '1000', 'number', 'njangi', 'Late contribution fee (FCFA)', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('36', 'member_registration', '1', 'boolean', 'registration', 'Allow member registration', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('37', 'require_approval', '1', 'boolean', 'registration', 'Require admin approval for new members', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('38', 'default_member_type', '1', 'number', 'registration', 'Default member type for new registrations', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('39', 'require_phone', '1', 'boolean', 'registration', 'Require phone number', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('40', 'require_email', '1', 'boolean', 'registration', 'Require email address', NULL, '1', '2026-03-12 16:11:50', NULL);
INSERT INTO system_settings VALUES ('41', 'require_address', '0', 'boolean', 'registration', 'Require physical address', NULL, '1', '2026-03-12 16:11:50', NULL);
