-- Add a secure 10-character per-appointment token used by the confirmation email link.
-- Run this once on an existing Serein Health database.

USE `serein_health`;

ALTER TABLE `appointments`
  ADD COLUMN `access_token` VARCHAR(10) DEFAULT NULL AFTER `id`;

UPDATE `appointments`
SET `access_token` = SUBSTRING(SHA2(CONCAT(UUID(), `id`, RAND()), 256), 1, 10)
WHERE `access_token` IS NULL;

ALTER TABLE `appointments`
  ADD UNIQUE KEY `uniq_appointment_token` (`access_token`);
