-- Upgrade an existing Serein Health database for the doctor/admin workflow.
-- Run this once if the database was created before Task 5 was extended.

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

ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin', 'user', 'doctor') NOT NULL DEFAULT 'user';

ALTER TABLE `users`
  ADD COLUMN `doctor_id` INT UNSIGNED DEFAULT NULL AFTER `role`;

ALTER TABLE `users`
  ADD KEY `idx_users_doctor` (`doctor_id`),
  ADD CONSTRAINT `fk_users_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

ALTER TABLE `appointments`
  ADD COLUMN `access_token` VARCHAR(10) DEFAULT NULL AFTER `id`,
  ADD COLUMN `doctor_id` INT UNSIGNED DEFAULT NULL AFTER `doctor`,
  ADD COLUMN `status` ENUM('pending', 'confirmed', 'rescheduled', 'cancelled', 'completed') NOT NULL DEFAULT 'pending' AFTER `notes`,
  ADD COLUMN `doctor_message` TEXT AFTER `status`,
  ADD COLUMN `proposed_date` DATE DEFAULT NULL AFTER `doctor_message`,
  ADD COLUMN `proposed_time` VARCHAR(10) DEFAULT NULL AFTER `proposed_date`,
  ADD COLUMN `cancelled_by` VARCHAR(120) DEFAULT NULL AFTER `proposed_time`,
  ADD COLUMN `cancelled_by_role` ENUM('patient', 'doctor', 'admin') DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL AFTER `cancelled_by_role`,
  ADD COLUMN `cancellation_reason` TEXT AFTER `cancelled_at`,
  ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `appointments`
  ADD UNIQUE KEY `uniq_appointment_token` (`access_token`),
  ADD KEY `idx_appointments_doctor` (`doctor_id`),
  ADD CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL;

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
