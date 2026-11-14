-- Add profile details shown on the public "View profile" modal.
-- Run this once on an existing Serein Health database.

USE `serein_health`;

ALTER TABLE `doctors`
  ADD COLUMN `languages` VARCHAR(255) DEFAULT NULL AFTER `image`,
  ADD COLUMN `bio` TEXT DEFAULT NULL AFTER `languages`;
