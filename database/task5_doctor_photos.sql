-- Add website photos for doctor cards.
-- Run this once on a database created before doctor photos were supported.

USE `serein_health`;

ALTER TABLE `doctors`
  ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `phone`;

UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-1.jpg'  WHERE `name` = 'Dr. Elena Rossi';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-5.jpg'  WHERE `name` = 'Dr. Priya Nair';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-2.jpg'  WHERE `name` = 'Dr. Arjun Mehta';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-6.jpg'  WHERE `name` = 'Dr. Samuel Labiche';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-4.jpg'  WHERE `name` = 'Dr. Lucas Ferreira';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-7.jpg'  WHERE `name` = 'Dr. Claire Antat';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-3.jpg'  WHERE `name` = 'Dr. Maya Chen';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-8.jpg'  WHERE `name` = 'Dr. Nadia Payet';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-9.jpg'  WHERE `name` = 'Dr. Amara Hoareau';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-10.jpg' WHERE `name` = 'Dr. Theo Barbier';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-11.jpg' WHERE `name` = 'Dr. Jean Morel';
UPDATE `doctors` SET `image` = 'assets/img/doctors/doctor-12.jpg' WHERE `name` = 'Dr. Elodie Fanchette';
