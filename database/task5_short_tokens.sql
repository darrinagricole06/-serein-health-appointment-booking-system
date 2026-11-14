-- Change existing appointment access tokens from 64 characters to 10 characters.
-- Run this once on a database that still uses the old 64-character tokens.

USE `serein_health`;

UPDATE `appointments`
SET `access_token` = SUBSTRING(SHA2(CONCAT(UUID(), `id`, RAND()), 256), 1, 10)
WHERE `access_token` IS NOT NULL;

ALTER TABLE `appointments`
  MODIFY COLUMN `access_token` VARCHAR(10) DEFAULT NULL;
