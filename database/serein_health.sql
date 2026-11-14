-- Serein Health — Appointment Booking System
-- Tasks 4–5: MySQL schema, authentication and doctor workflow

CREATE DATABASE IF NOT EXISTS `serein_health`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `serein_health`;

CREATE TABLE IF NOT EXISTS `doctors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `specialty` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(40) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `languages` VARCHAR(255) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_doctor_name` (`name`),
  UNIQUE KEY `uniq_doctor_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user', 'doctor') NOT NULL DEFAULT 'user',
  `doctor_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_user_email` (`email`),
  KEY `idx_users_doctor` (`doctor_id`),
  CONSTRAINT `fk_users_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `access_token` VARCHAR(10) DEFAULT NULL,
  `first_name` VARCHAR(80) NOT NULL,
  `last_name` VARCHAR(80) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `service` VARCHAR(60) NOT NULL,
  `doctor` VARCHAR(120) DEFAULT NULL,
  `doctor_id` INT UNSIGNED DEFAULT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` VARCHAR(10) NOT NULL,
  `notes` TEXT,
  `status` ENUM('pending', 'confirmed', 'rescheduled', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
  `doctor_message` TEXT,
  `proposed_date` DATE DEFAULT NULL,
  `proposed_time` VARCHAR(10) DEFAULT NULL,
  `cancelled_by` VARCHAR(120) DEFAULT NULL,
  `cancelled_by_role` ENUM('patient', 'doctor', 'admin') DEFAULT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `cancellation_reason` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_appointment_token` (`access_token`),
  KEY `idx_appointments_doctor` (`doctor_id`),
  CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(120) NOT NULL,
  `subscribed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default accounts (change these passwords after first login):
-- admin   / admin123
-- patient / patient123
-- doctors / doctor123
INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@serein.sc', '$2y$10$Ty1X8xenxbzEYA3V97Tqi.IfA9DM3A7AxL2wAwaQLxsJr4Z0lVPni', 'admin'),
('patient', 'patient@example.com', '$2y$10$UXIZsl4500CIlBL6IaD7OegGgcMphtkKubRLW8i2TnrjURSia/aEC', 'user');

INSERT IGNORE INTO `doctors` (`id`, `name`, `specialty`, `email`, `phone`, `image`) VALUES
(1, 'Dr. Elena Rossi', 'General medicine', 'elena.rossi@serein.sc', '+248 438 8001', 'assets/img/doctors/doctor-1.jpg'),
(2, 'Dr. Priya Nair', 'General medicine', 'priya.nair@serein.sc', '+248 438 8002', 'assets/img/doctors/doctor-5.jpg'),
(3, 'Dr. Arjun Mehta', 'Dental surgery', 'arjun.mehta@serein.sc', '+248 438 8003', 'assets/img/doctors/doctor-2.jpg'),
(4, 'Dr. Samuel Labiche', 'Dental surgery', 'samuel.labiche@serein.sc', '+248 438 8004', 'assets/img/doctors/doctor-6.jpg'),
(5, 'Dr. Lucas Ferreira', 'Physiotherapy', 'lucas.ferreira@serein.sc', '+248 438 8005', 'assets/img/doctors/doctor-4.jpg'),
(6, 'Dr. Claire Antat', 'Physiotherapy', 'claire.antat@serein.sc', '+248 438 8006', 'assets/img/doctors/doctor-7.jpg'),
(7, 'Dr. Maya Chen', 'Dermatology', 'maya.chen@serein.sc', '+248 438 8007', 'assets/img/doctors/doctor-3.jpg'),
(8, 'Dr. Nadia Payet', 'Dermatology', 'nadia.payet@serein.sc', '+248 438 8008', 'assets/img/doctors/doctor-8.jpg'),
(9, 'Dr. Amara Hoareau', 'Mental wellness', 'amara.hoareau@serein.sc', '+248 438 8009', 'assets/img/doctors/doctor-9.jpg'),
(10, 'Dr. Theo Barbier', 'Mental wellness', 'theo.barbier@serein.sc', '+248 438 8010', 'assets/img/doctors/doctor-10.jpg'),
(11, 'Dr. Jean Morel', 'Nutrition & dietetics', 'jean.morel@serein.sc', '+248 438 8011', 'assets/img/doctors/doctor-11.jpg'),
(12, 'Dr. Elodie Fanchette', 'Nutrition & dietetics', 'elodie.fanchette@serein.sc', '+248 438 8012', 'assets/img/doctors/doctor-12.jpg');

INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role`, `doctor_id`) VALUES
('elena',  'elena.rossi@serein.sc',      '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 1),
('priya',  'priya.nair@serein.sc',       '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 2),
('arjun',  'arjun.mehta@serein.sc',      '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 3),
('samuel', 'samuel.labiche@serein.sc',   '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 4),
('lucas',  'lucas.ferreira@serein.sc',   '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 5),
('claire', 'claire.antat@serein.sc',     '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 6),
('maya',   'maya.chen@serein.sc',        '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 7),
('nadia',  'nadia.payet@serein.sc',      '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 8),
('amara',  'amara.hoareau@serein.sc',    '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 9),
('theo',   'theo.barbier@serein.sc',     '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 10),
('jean',   'jean.morel@serein.sc',       '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 11),
('elodie', 'elodie.fanchette@serein.sc', '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 12);
