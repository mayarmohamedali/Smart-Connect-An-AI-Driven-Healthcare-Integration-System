-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2026 at 12:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_connect`
--

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE `hospitals` (
  `hospital_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `license_number` varchar(60) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospitals`
--

INSERT INTO `hospitals` (`hospital_id`, `name`, `license_number`, `address`, `phone`, `created_at`) VALUES
(1, 'El Shifa Hospital', NULL, NULL, NULL, '2026-01-30 05:50:37'),
(2, 'Cleopatra Hospital', NULL, NULL, NULL, '2026-01-30 05:50:37'),
(3, 'Air Force Hospital', NULL, NULL, NULL, '2026-01-30 05:50:37'),
(4, 'Nasaeem Hospital', NULL, NULL, NULL, '2026-01-30 05:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `medical_insurances`
--

CREATE TABLE `medical_insurances` (
  `insurance_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `regulatory_id` varchar(60) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_insurances`
--

INSERT INTO `medical_insurances` (`insurance_id`, `name`, `regulatory_id`, `address`, `phone`, `created_at`) VALUES
(1, 'AXA', NULL, NULL, NULL, '2026-01-30 05:52:23'),
(2, 'MetLife', NULL, NULL, NULL, '2026-01-30 05:52:23'),
(3, 'Bupa', NULL, NULL, NULL, '2026-01-30 05:52:23'),
(4, 'Allianz', NULL, NULL, NULL, '2026-01-30 05:52:23');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `age` int(11) DEFAULT NULL,
  `checkin_date` date DEFAULT NULL,
  `checkout_date` date DEFAULT NULL,
  `cbc_hb1` decimal(5,2) DEFAULT NULL,
  `cbc_tlc1` decimal(7,2) DEFAULT NULL,
  `cbc_plat1` decimal(10,2) DEFAULT NULL,
  `blood_uria1` decimal(7,2) DEFAULT NULL,
  `blood_creatinine1` decimal(7,2) DEFAULT NULL,
  `cbc_hb2` decimal(5,2) DEFAULT NULL,
  `cbc_tlc2` decimal(7,2) DEFAULT NULL,
  `cbc_plat2` decimal(10,2) DEFAULT NULL,
  `blood_uria2` decimal(7,2) DEFAULT NULL,
  `blood_creatinine2` decimal(7,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `glucose` decimal(7,2) DEFAULT NULL,
  `systolic_bp` int(11) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `admission_count` int(11) DEFAULT NULL,
  `avg_creatinine` decimal(7,2) DEFAULT NULL,
  `avg_urea` decimal(7,2) DEFAULT NULL,
  `avg_hb` decimal(5,2) DEFAULT NULL,
  `avg_tlc` decimal(7,2) DEFAULT NULL,
  `avg_platelets` decimal(10,2) DEFAULT NULL,
  `length_of_stay` int(11) DEFAULT NULL,
  `smoking_status` enum('Non-Smoker','Smoker','Former Smoker') DEFAULT NULL,
  `physical_activity_level` enum('Low','Moderate','High') DEFAULT NULL,
  `has_diabetes` tinyint(1) NOT NULL DEFAULT 0,
  `has_hypertension` tinyint(1) NOT NULL DEFAULT 0,
  `has_kidney_disease` tinyint(1) NOT NULL DEFAULT 0,
  `has_heart_disease` tinyint(1) NOT NULL DEFAULT 0,
  `diagnosis` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `patient_id`, `age`, `checkin_date`, `checkout_date`, `cbc_hb1`, `cbc_tlc1`, `cbc_plat1`, `blood_uria1`, `blood_creatinine1`, `cbc_hb2`, `cbc_tlc2`, `cbc_plat2`, `blood_uria2`, `blood_creatinine2`, `bmi`, `glucose`, `systolic_bp`, `month`, `admission_count`, `avg_creatinine`, `avg_urea`, `avg_hb`, `avg_tlc`, `avg_platelets`, `length_of_stay`, `smoking_status`, `physical_activity_level`, `has_diabetes`, `has_hypertension`, `has_kidney_disease`, `has_heart_disease`, `diagnosis`, `created_at`) VALUES
(1, 10, 30, '2026-01-28', '2026-02-01', 3.00, 4.00, 4.00, 6.00, 6.00, 2.00, 4.00, 6.00, 6.00, 2.00, 12.00, 23.00, 4, 1, 6, 5.00, 6.00, 7.00, 8.00, 6.00, 5, '', 'Moderate', 1, 0, 0, 0, 'diabetes', '2026-02-02 09:45:38'),
(3, 11, 40, '2026-02-01', '2026-02-03', 3.00, 4.00, 4.00, 6.00, 6.00, 2.00, 5.00, 7.00, 8.00, 12.00, 12.00, 23.00, 5, 2, 6, 5.00, 6.00, 7.00, 8.00, 6.00, 5, 'Non-Smoker', 'Low', 1, 0, 0, 0, 'hypertensi', '2026-02-02 10:48:28');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `national_id` varchar(20) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('M','F') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `added_by_hospital_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `full_name`, `national_id`, `phone`, `date_of_birth`, `gender`, `address`, `added_by_hospital_id`, `is_active`, `created_at`) VALUES
(6, 'Ahmed Hassan', '30304050100987', '01012345678', NULL, 'M', 'Cairo', 1, 1, '2026-01-30 05:58:47'),
(7, 'Sara Mohamed', '29802022345678', '01198765432', NULL, 'F', 'Giza', 2, 1, '2026-01-30 05:58:47'),
(8, 'Omar Khaled', '30003033456789', '01234567890', NULL, 'M', 'Alexandria', 3, 1, '2026-01-30 05:58:47'),
(9, 'Mona Adel', '29704044567890', '01511223344', NULL, 'F', 'Nasr City', 4, 1, '2026-01-30 05:58:47'),
(10, 'Maya Mohamed', '40404070100464', '01003147823', NULL, 'F', 'fifth settlement', 1, 1, '2026-02-02 09:18:33'),
(11, 'ahmed amr', '50507010200345', '01003458799', NULL, 'M', 'nasr city', 1, 1, '2026-02-02 10:37:51');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `role_name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'ADMIN'),
(2, 'HOSPITAL_STAFF'),
(3, 'INSURANCE_STAFF');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role_id`, `full_name`, `username`, `email`, `phone`, `password_hash`, `is_active`, `created_at`) VALUES
(1, 2, 'El Shifa Staff', 'elshifa1', 'elshifa@hospital.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:05:40'),
(2, 2, 'Cleopatra Staff', 'cleo1', 'cleo@hospital.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:05:40'),
(3, 2, 'Air Force Staff', 'airforce1', 'airforce@hospital.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:05:40'),
(4, 2, 'Nasaeem Staff', 'nasaeem1', 'nasaeem@hospital.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:05:40'),
(5, 3, 'AXA Officer', 'axa1', 'axa@insurance.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:06:31'),
(6, 3, 'MetLife Officer', 'metlife1', 'metlife@insurance.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:06:31'),
(7, 3, 'Bupa Officer', 'bupa1', 'bupa@insurance.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:06:31'),
(8, 3, 'Allianz Officer', 'allianz1', 'allianz@insurance.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:06:31'),
(9, 1, 'System Admin', 'admin', 'admin@smartconnect.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 06:06:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`hospital_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `medical_insurances`
--
ALTER TABLE `medical_insurances`
  ADD PRIMARY KEY (`insurance_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `idx_medrec_patient` (`patient_id`),
  ADD KEY `idx_medrec_checkin` (`checkin_date`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `idx_patients_phone` (`phone`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_roles` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `hospital_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_insurances`
--
ALTER TABLE `medical_insurances`
  MODIFY `insurance_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `fk_medrec_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
