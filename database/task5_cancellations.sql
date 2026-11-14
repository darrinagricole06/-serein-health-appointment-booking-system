-- Track who cancelled an appointment (patient, doctor or administrator).
-- Run this once on an existing Serein Health database.

USE `serein_health`;

ALTER TABLE `appointments`
  ADD COLUMN `cancelled_by` VARCHAR(120) DEFAULT NULL AFTER `proposed_time`,
  ADD COLUMN `cancelled_by_role` ENUM('patient', 'doctor', 'admin') DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL AFTER `cancelled_by_role`,
  ADD COLUMN `cancellation_reason` TEXT AFTER `cancelled_at`;
