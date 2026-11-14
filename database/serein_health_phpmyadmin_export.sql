-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 14, 2026 at 12:29 PM
-- Server version: 8.0.40
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `serein_health`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `access_token` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_id` int UNSIGNED DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','confirmed','rescheduled','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `doctor_message` text COLLATE utf8mb4_unicode_ci,
  `proposed_date` date DEFAULT NULL,
  `proposed_time` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_by` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_by_role` enum('patient','doctor','admin') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_appointment_token` (`access_token`),
  KEY `idx_appointments_doctor` (`doctor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `access_token`, `first_name`, `last_name`, `email`, `phone`, `service`, `doctor`, `doctor_id`, `appointment_date`, `appointment_time`, `notes`, `status`, `doctor_message`, `proposed_date`, `proposed_time`, `cancelled_by`, `cancelled_by_role`, `cancelled_at`, `cancellation_reason`, `created_at`, `updated_at`) VALUES
(1, 'FBGU7TEUAW', 'Darrin', 'Agricole', 'darrinagricole06@gmail.com', '2587092', 'dental', 'Dr. Arjun Mehta', 3, '2026-11-13', '15:30', '', 'cancelled', 'We are sorry, but we need to cancel this appointment. Please contact us to rebook.', NULL, NULL, 'Dr. Arjun Mehta', 'doctor', '2026-11-12 14:04:33', NULL, '2026-11-10 17:21:35', '2026-11-12 14:47:14'),
(2, 'RXCN5VTD5H', 'Darrin', 'Agricole', 'darrinagricole06@gmail.com', '2587092', 'skin', 'Dr. Nadia Payet', 8, '2026-12-09', '16:00', '', 'confirmed', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-11 17:26:27', '2026-11-12 14:47:14'),
(6, 'HEFRYJSHPW', 'Darrin', 'Agricole', 'darrinagricole06@gmail.com', '2587092', 'dental', 'Dr. Samuel Labiche', 4, '2026-11-13', '15:30', '', 'confirmed', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 12:39:24', '2026-11-12 15:43:17'),
(8, '3Z6JQ88YFP', 'Darrin', 'Agricole', 'darrinagricole06@gmail.com', '2587092', 'mental', 'Dr. Theo Barbier', 10, '2026-11-24', '15:00', '', 'confirmed', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 14:06:46', '2026-11-12 14:47:14'),
(14, 'WVDFGHZHJ9', 'Darrin', 'Agricole', 'darrinagricole06@gmail.com', '2587092', 'mental', 'Dr. Theo Barbier', 10, '2026-11-19', '09:00', '', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 15:03:51', '2026-11-12 15:03:51'),
(15, '2WZRAUE65D', 'Darrin', 'Agricole', 'darrinagricole06@gmail.com', '2587092', 'physio', 'Dr. Claire Antat', 6, '2026-11-27', '14:00', '', 'confirmed', '', NULL, NULL, 'Darrin Agricole', 'patient', '2026-11-12 15:26:02', 'Busy on that day', '2026-11-12 15:25:23', '2026-11-12 15:28:29'),
(17, '5WM43RLDZN', 'Lucas', 'Johnson', 'Lj@yahoo.com', '2735770', 'physio', 'Dr. Claire Antat', 6, '2026-11-17', '10:00', '.', 'cancelled', NULL, NULL, NULL, 'Lucas Johnson', 'patient', '2026-11-12 16:03:52', 'New date request', '2026-11-12 16:02:04', '2026-11-12 16:03:52');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `languages` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_doctor_name` (`name`),
  UNIQUE KEY `uniq_doctor_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `name`, `specialty`, `email`, `phone`, `image`, `languages`, `bio`, `created_at`) VALUES
(1, 'Dr. Elena Rossi', 'General medicine', 'elena.rossi@serein.sc', '+248 438 8001', 'assets/img/doctors/doctor-1.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(2, 'Dr. Priya Nair', 'General medicine', 'priya.nair@serein.sc', '+248 438 8002', 'assets/img/doctors/doctor-5.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(3, 'Dr. Arjun Mehta', 'Dental surgery', 'arjun.mehta@serein.sc', '+248 438 8003', 'assets/img/doctors/doctor-2.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(4, 'Dr. Samuel Labiche', 'Dental surgery', 'samuel.labiche@serein.sc', '+248 438 8004', 'assets/img/doctors/doctor-6.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(5, 'Dr. Lucas Ferreira', 'Physiotherapy', 'lucas.ferreira@serein.sc', '+248 438 8005', 'assets/img/doctors/doctor-4.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(6, 'Dr. Claire Antat', 'Physiotherapy', 'claire.antat@serein.sc', '+248 438 8006', 'assets/img/doctors/doctor-7.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(7, 'Dr. Maya Chen', 'Dermatology', 'maya.chen@serein.sc', '+248 438 8007', 'assets/img/doctors/doctor-3.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(8, 'Dr. Nadia Payet', 'Dermatology', 'nadia.payet@serein.sc', '+248 438 8008', 'assets/img/doctors/doctor-8.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(9, 'Dr. Amara Hoareau', 'Mental wellness', 'amara.hoareau@serein.sc', '+248 438 8009', 'assets/img/doctors/doctor-9.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(10, 'Dr. Theo Barbier', 'Mental wellness', 'theo.barbier@serein.sc', '+248 438 8010', 'assets/img/doctors/doctor-10.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(11, 'Dr. Jean Morel', 'Nutrition & dietetics', 'jean.morel@serein.sc', '+248 438 8011', 'assets/img/doctors/doctor-11.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(12, 'Dr. Elodie Fanchette', 'Nutrition & dietetics', 'elodie.fanchette@serein.sc', '+248 438 8012', 'assets/img/doctors/doctor-12.jpg', NULL, NULL, '2026-11-10 13:59:49'),
(16, 'Dr. Photo Now', 'Dermatology', 'photo.now@serein.sc', '123', 'assets/img/doctors/uploads/doctor-16-8c9a7c546446.jpg', NULL, NULL, '2026-11-10 14:49:12');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `subscribed_at`) VALUES
(1, 'darrinagricole06@gmail.com', '2026-11-10 15:21:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user','doctor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `doctor_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_user_email` (`email`),
  KEY `idx_users_doctor` (`doctor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `doctor_id`, `created_at`) VALUES
(1, 'admin', 'admin@serein.sc', '$2y$10$Ty1X8xenxbzEYA3V97Tqi.IfA9DM3A7AxL2wAwaQLxsJr4Z0lVPni', 'admin', NULL, '2026-11-10 13:24:48'),
(2, 'patient', 'patient@example.com', '$2y$10$UXIZsl4500CIlBL6IaD7OegGgcMphtkKubRLW8i2TnrjURSia/aEC', 'user', NULL, '2026-11-10 13:24:48'),
(3, 'elena', 'elena.rossi@serein.sc', '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 1, '2026-11-10 13:59:49'),
(4, 'priya', 'priya.nair@serein.sc', '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 2, '2026-11-10 13:59:49'),
(5, 'arjun', 'arjun.mehta@serein.sc', '$2y$10$Vlr4YjbYzMvIr8.e7s/NPeHSxRUHRQDgE5MHUDO9Jr3uEZIoS.JBe', 'doctor', 3, '2026-11-10 13:59:49'),
(6, 'samuel', 'samuel.labiche@serein.sc', '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 4, '2026-11-10 13:59:49'),
(7, 'lucas', 'lucas.ferreira@serein.sc', '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 5, '2026-11-10 13:59:49'),
(8, 'claire', 'claire.antat@serein.sc', '$2y$10$CHnCCBxVYf9QnjMUynOilOiAtwXtxmigLxc//N4np16DIXZYiHM6O', 'doctor', 6, '2026-11-10 13:59:49'),
(9, 'maya', 'maya.chen@serein.sc', '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 7, '2026-11-10 13:59:49'),
(10, 'nadia', 'nadia.payet@serein.sc', '$2y$10$lASR3/MKSJOKvnv3DzHrxune3SviZh16nk/8NguZF98nCNCfEV80q', 'doctor', 8, '2026-11-10 13:59:49'),
(11, 'amara', 'amara.hoareau@serein.sc', '$2y$10$XXCMrj40kuBca7Qn0.4RrezRSt2lDy1lRDo5jfBzjFbsbr81QHCUK', 'doctor', 9, '2026-11-10 13:59:49'),
(12, 'theo', 'theo.barbier@serein.sc', '$2y$10$PMOj5JHZBURXeKc.k2nmkOrXy92CNxUBEoczqmrNeb1WFQ5GZR.kK', 'doctor', 10, '2026-11-10 13:59:49'),
(13, 'jean', 'jean.morel@serein.sc', '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 11, '2026-11-10 13:59:49'),
(14, 'elodie', 'elodie.fanchette@serein.sc', '$2y$10$qKgBY7gWWePFjNRdgXAAVOA3JwksS.6gtHjCfPVma.AzRAe6hRs9i', 'doctor', 12, '2026-11-10 13:59:49'),
(18, 'photnow', 'photo.now@serein.sc', '$2y$10$olTBpKLqT3LiIBaWHufwo.i4qCp2J/f7b5bgMHvw0yMmcbcwUr4Q.', 'doctor', 16, '2026-11-10 14:49:12');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
