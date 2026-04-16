-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2026 at 11:28 PM
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
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`) VALUES
(1, 'Normal'),
(2, 'VIP');

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `claim_id` int(10) UNSIGNED NOT NULL,
  `record_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `insurance_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED DEFAULT NULL,
  `treatment_cost` decimal(10,2) DEFAULT NULL,
  `coverage_percentage` decimal(5,2) DEFAULT NULL,
  `cost_coverage_ratio` decimal(8,6) DEFAULT NULL,
  `claim_amount` decimal(10,2) DEFAULT NULL,
  `avg_claim` decimal(12,6) DEFAULT NULL,
  `claim_status` enum('Approved','Rejected','Pending') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`claim_id`, `record_id`, `patient_id`, `insurance_id`, `service_id`, `treatment_cost`, `coverage_percentage`, `cost_coverage_ratio`, `claim_amount`, `avg_claim`, `claim_status`, `created_at`) VALUES
(1, 5, 13, 1, 1, 500.00, NULL, NULL, 500.00, NULL, 'Pending', '2026-04-08 08:28:32'),
(2, 5, 13, 1, 4, 2000.00, NULL, NULL, 2000.00, NULL, 'Pending', '2026-04-08 08:29:40'),
(3, 5, 13, 1, 1, 500.00, NULL, NULL, 500.00, NULL, 'Pending', '2026-04-08 08:30:20');

-- --------------------------------------------------------

--
-- Table structure for table `customer_type`
--

CREATE TABLE `customer_type` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_type`
--

INSERT INTO `customer_type` (`id`, `name`) VALUES
(1, 'Individual'),
(2, 'Company');

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE `hospitals` (
  `hospital_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `license_number` varchar(60) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospitals`
--

INSERT INTO `hospitals` (`hospital_id`, `name`, `license_number`, `created_at`) VALUES
(1, 'El Shifa Hospital', NULL, '2026-01-30 05:50:37'),
(2, 'Cleopatra Hospital', NULL, '2026-01-30 05:50:37'),
(3, 'Air Force Hospital', NULL, '2026-01-30 05:50:37'),
(4, 'Nasaeem Hospital', NULL, '2026-01-30 05:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_hospitals`
--

CREATE TABLE `insurance_hospitals` (
  `id` int(11) NOT NULL,
  `insurance_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insurance_hospitals`
--

INSERT INTO `insurance_hospitals` (`id`, `insurance_id`, `hospital_id`) VALUES
(10, 1, 1),
(6, 1, 2),
(2, 1, 3),
(14, 1, 4),
(12, 2, 1),
(8, 2, 2),
(4, 2, 3),
(16, 2, 4),
(11, 3, 1),
(7, 3, 2),
(3, 3, 3),
(15, 3, 4),
(9, 4, 1),
(5, 4, 2),
(1, 4, 3),
(13, 4, 4);

-- --------------------------------------------------------

--
-- Table structure for table `insurance_plan`
--

CREATE TABLE `insurance_plan` (
  `id` int(10) UNSIGNED NOT NULL,
  `insurance_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `customer_type_id` int(10) UNSIGNED NOT NULL,
  `plan_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insurance_plan`
--

INSERT INTO `insurance_plan` (`id`, `insurance_id`, `category_id`, `customer_type_id`, `plan_name`) VALUES
(7, 1, 1, 1, 'Plan 1-1-1'),
(8, 1, 2, 1, 'Plan 1-2-1'),
(9, 1, 1, 2, 'Plan 1-1-2'),
(31, 1, 2, 2, 'Plan 1-2-2');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `policy_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_insurances`
--

INSERT INTO `medical_insurances` (`insurance_id`, `name`, `regulatory_id`, `address`, `phone`, `created_at`, `policy_completed`) VALUES
(1, 'AXA', NULL, NULL, NULL, '2026-01-30 05:52:23', 1),
(2, 'MetLife', NULL, NULL, NULL, '2026-01-30 05:52:23', 0),
(3, 'Bupa', NULL, NULL, NULL, '2026-01-30 05:52:23', 0),
(4, 'Allianz', NULL, NULL, NULL, '2026-01-30 05:52:23', 0);

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
  `cholesterol_level` decimal(7,2) DEFAULT NULL,
  `systolic_bp` int(11) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `day_of_week` varchar(10) DEFAULT NULL,
  `admission_count` int(11) DEFAULT NULL,
  `avg_creatinine` decimal(7,2) DEFAULT NULL,
  `avg_urea` decimal(7,2) DEFAULT NULL,
  `avg_hb` decimal(5,2) DEFAULT NULL,
  `avg_tlc` decimal(7,2) DEFAULT NULL,
  `avg_platelets` decimal(10,2) DEFAULT NULL,
  `delta_hb` decimal(7,3) DEFAULT NULL,
  `delta_tlc` decimal(7,3) DEFAULT NULL,
  `delta_plat` decimal(10,3) DEFAULT NULL,
  `delta_uria` decimal(7,3) DEFAULT NULL,
  `delta_creatinine` decimal(7,3) DEFAULT NULL,
  `length_of_stay` int(11) DEFAULT NULL,
  `avg_length_stay` decimal(10,6) DEFAULT NULL,
  `smoking_status` tinyint(1) DEFAULT NULL,
  `physical_activity_level` enum('Low','Moderate','High') DEFAULT NULL,
  `diet_quality` enum('Poor','Average','Good') DEFAULT NULL,
  `alcohol_consumption` tinyint(1) DEFAULT NULL,
  `sleep_hours` decimal(4,1) DEFAULT NULL,
  `stress_level` decimal(7,4) DEFAULT NULL,
  `family_history` decimal(7,4) DEFAULT NULL,
  `medications_count` decimal(7,4) DEFAULT NULL,
  `risk_score` decimal(10,8) DEFAULT NULL,
  `symptom_burden` decimal(10,8) DEFAULT NULL,
  `seasonal_weight` decimal(10,8) DEFAULT NULL,
  `has_diabetes` tinyint(1) NOT NULL DEFAULT 0,
  `has_hypertension` tinyint(1) NOT NULL DEFAULT 0,
  `has_kidney_disease` tinyint(1) NOT NULL DEFAULT 0,
  `has_heart_disease` tinyint(1) NOT NULL DEFAULT 0,
  `fever` decimal(7,4) DEFAULT NULL,
  `cough` decimal(7,4) DEFAULT NULL,
  `fatigue` decimal(7,4) DEFAULT NULL,
  `chest_pain` tinyint(1) DEFAULT NULL,
  `shortness_of_breath` decimal(7,4) DEFAULT NULL,
  `headache` tinyint(1) DEFAULT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `disease_category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `patient_id`, `age`, `checkin_date`, `checkout_date`, `cbc_hb1`, `cbc_tlc1`, `cbc_plat1`, `blood_uria1`, `blood_creatinine1`, `cbc_hb2`, `cbc_tlc2`, `cbc_plat2`, `blood_uria2`, `blood_creatinine2`, `bmi`, `glucose`, `cholesterol_level`, `systolic_bp`, `month`, `year`, `day_of_week`, `admission_count`, `avg_creatinine`, `avg_urea`, `avg_hb`, `avg_tlc`, `avg_platelets`, `delta_hb`, `delta_tlc`, `delta_plat`, `delta_uria`, `delta_creatinine`, `length_of_stay`, `avg_length_stay`, `smoking_status`, `physical_activity_level`, `diet_quality`, `alcohol_consumption`, `sleep_hours`, `stress_level`, `family_history`, `medications_count`, `risk_score`, `symptom_burden`, `seasonal_weight`, `has_diabetes`, `has_hypertension`, `has_kidney_disease`, `has_heart_disease`, `fever`, `cough`, `fatigue`, `chest_pain`, `shortness_of_breath`, `headache`, `diagnosis`, `disease_category`, `created_at`) VALUES
(1, 10, 30, '2026-01-28', '2026-02-01', 3.00, 4.00, 4.00, 6.00, 6.00, 2.00, 4.00, 6.00, 6.00, 2.00, 12.00, 23.00, NULL, 4, 1, NULL, NULL, 6, 5.00, 6.00, 7.00, 8.00, 6.00, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, 'Moderate', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'diabetes', NULL, '2026-02-02 09:45:38'),
(3, 11, 40, '2026-02-01', '2026-02-03', 3.00, 4.00, 4.00, 6.00, 6.00, 2.00, 5.00, 7.00, 8.00, 12.00, 12.00, 23.00, NULL, 5, 2, NULL, NULL, 6, 5.00, 6.00, 7.00, 8.00, 6.00, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, 'Low', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'hypertensi', NULL, '2026-02-02 10:48:28'),
(4, 12, 21, '2026-02-03', '2026-02-04', 3.00, 4.00, 4.00, 6.00, 6.00, 2.00, 5.00, 7.00, 8.00, 12.00, 12.00, 23.00, NULL, 5, 2, NULL, NULL, 6, 5.00, 6.00, 7.00, 8.00, 6.00, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, 'Moderate', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'diabetes', NULL, '2026-02-04 00:14:58'),
(5, 13, 22, '2026-02-01', '2026-02-02', 3.00, 4.00, 4.00, 6.00, 6.00, 4.00, 4.00, 6.00, 6.00, 12.00, 12.00, 23.00, NULL, 5, 2, NULL, NULL, 6, 5.00, 6.00, 7.00, 8.00, 6.00, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, 'Low', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'diabetes', NULL, '2026-02-04 03:28:26'),
(6, 19, 81, '2026-02-02', '2026-02-05', 3.00, 4.00, 4.00, 7.00, 10.00, 2.00, 4.00, 7.00, 8.00, 12.00, 12.00, 23.00, NULL, 5, 2, NULL, NULL, 6, 5.00, 6.00, 7.00, 8.00, 6.00, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, 'Low', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'diabetes', NULL, '2026-02-06 05:28:14'),
(7, 20, 20, '2026-03-31', '2026-04-05', 3.00, 4.00, 6.00, 6.00, 6.00, 2.00, 5.00, 7.00, 8.00, 12.00, 12.00, 23.00, 7.00, 120, 3, 2026, 'Monday', 1, 9.00, 7.00, 2.50, 4.50, 6.50, -1.000, 1.000, 1.000, 2.000, 6.000, 6, 4.000000, 1, 'Moderate', 'Poor', 1, 7.5, 7.0000, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, 1.0000, 1.0000, 1.0000, 1, 1.0000, 1, 'diabetes', 'Healthy', '2026-04-07 10:38:47'),
(8, 21, 16, '2026-04-01', '2026-04-07', 3.00, 4.00, 6.00, 7.00, 10.00, 2.00, 5.00, 7.00, 6.00, 12.00, 12.00, 23.00, 7.00, 5, 4, 2026, 'Wednesday', 6, 11.00, 6.50, 2.50, 4.50, 6.50, -1.000, 1.000, 1.000, -1.000, 2.000, 6, 6.000000, NULL, 'High', 'Good', 0, 8.0, 10.0000, 0.0000, 0.0000, 0.00000000, 0.00000000, 50.00000000, 0, 0, 0, 0, 1.0000, 1.0000, 1.0000, NULL, 1.0000, 1, 'headache', NULL, '2026-04-08 06:30:38');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `national_id` varchar(20) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `gender` enum('M','F') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `insurance_id` int(10) UNSIGNED DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `full_name`, `national_id`, `phone`, `gender`, `address`, `insurance_id`, `category_id`, `is_active`, `created_at`) VALUES
(13, 'Mayar Mohamed', '30304070100464', '01003147822', 'F', 'fifth settlement', 1, NULL, 1, '2026-02-04 02:47:36'),
(14, 'merna mohamed', '50506070200489', '01095463987', 'F', 'fifth settlement', 2, NULL, 1, '2026-02-04 02:48:59'),
(15, 'mariam mohamed', '40405060700987', '01003147833', 'F', 'fifth settlement', 2, NULL, 1, '2026-02-04 04:01:24'),
(16, 'mahy moatasem', '27312090134570', '01001720391', 'F', 'fifth settlement', 1, NULL, 1, '2026-02-05 07:12:31'),
(17, 'mohamed farouk', '27312090345690', '01095347822', 'M', 'fifth settlement', 1, NULL, 1, '2026-02-05 07:25:37'),
(18, 'heba ayman', '27602150348290', '01006789906', 'F', 'maadi', 1, NULL, 1, '2026-02-05 09:17:46'),
(19, 'khaled zaky', '24502010123456', '01009876544', 'M', 'fifth settlement', 1, NULL, 1, '2026-02-06 05:25:28'),
(20, 'zaky khaled mohamed', '50506070200487', '01009874522', 'M', 'nasr city', 1, NULL, 1, '2026-04-07 10:03:35'),
(21, 'merna mohamed farouk', '30907190103323', '01095239935', 'F', 'first settlement', 1, NULL, 1, '2026-04-08 06:27:34');

-- --------------------------------------------------------

--
-- Table structure for table `patient_policy`
--

CREATE TABLE `patient_policy` (
  `patient_policy_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `insurance_id` int(10) UNSIGNED NOT NULL,
  `insurance_plan_id` int(10) UNSIGNED NOT NULL,
  `policy_number` varchar(60) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_policy`
--

INSERT INTO `patient_policy` (`patient_policy_id`, `patient_id`, `insurance_id`, `insurance_plan_id`, `policy_number`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(0, 10, 1, 9, 'AXA-3941', '2026-02-08', '2027-02-04', 'active', '2026-02-04 00:49:21'),
(1, 11, 1, 8, 'AXA-3940', '2026-02-02', NULL, 'active', '2026-02-03 07:51:25'),
(0, 12, 1, 9, 'AXA-3960', '2026-02-03', '2026-03-08', 'active', '2026-02-04 00:17:13'),
(0, 13, 1, 9, 'AXA-3987', '2026-02-01', '2027-02-02', 'active', '2026-02-04 04:04:49'),
(0, 16, 1, 7, 'AXA-3990', '2026-02-05', '2027-02-05', 'active', '2026-02-05 07:13:04'),
(0, 18, 1, 8, 'AXA-3988', '2026-02-04', '2026-02-10', 'active', '2026-02-06 09:02:28'),
(0, 19, 1, 9, 'AXA-3991', '2026-02-06', '2027-02-06', 'active', '2026-02-06 05:26:04');

-- --------------------------------------------------------

--
-- Table structure for table `plan_service_coverage`
--

CREATE TABLE `plan_service_coverage` (
  `id` int(10) UNSIGNED NOT NULL,
  `insurance_plan_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `coverage_percent` decimal(5,2) DEFAULT NULL,
  `deductible_egp` decimal(10,2) NOT NULL DEFAULT 0.00,
  `threshold_egp` decimal(10,2) NOT NULL DEFAULT 0.00,
  `copayment_percent` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`id`, `name`) VALUES
(1, 'Checkup'),
(4, 'Dental'),
(3, 'Maternity'),
(2, 'Operations'),
(5, 'Optical'),
(0, 'Surgery');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `hospital_id` int(10) UNSIGNED DEFAULT NULL,
  `insurance_id` int(10) UNSIGNED DEFAULT NULL,
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

INSERT INTO `users` (`user_id`, `role_id`, `hospital_id`, `insurance_id`, `full_name`, `username`, `email`, `phone`, `password_hash`, `is_active`, `created_at`) VALUES
(1, 2, 1, NULL, 'El Shifa Staff', 'elshifa1', 'elshifa@hospital.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:05:40'),
(2, 2, 2, NULL, 'Cleopatra Staff', 'cleo1', 'cleo@hospital.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:05:40'),
(3, 2, 3, NULL, 'Air Force Staff', 'airforce1', 'airforce@hospital.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:05:40'),
(4, 2, 4, NULL, 'Nasaeem Staff', 'nasaeem1', 'nasaeem@hospital.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:05:40'),
(5, 3, NULL, 1, 'AXA Officer', 'axa1', 'axa@insurance.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:06:31'),
(6, 3, NULL, 2, 'MetLife Officer', 'metlife1', 'metlife@insurance.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:06:31'),
(7, 3, NULL, 3, 'Bupa Officer', 'bupa1', 'bupa@insurance.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:06:31'),
(8, 3, NULL, 4, 'Allianz Officer', 'allianz1', 'allianz@insurance.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:06:31'),
(9, 1, NULL, NULL, 'System Admin', 'admin', 'admin@smartconnect.com', NULL, '$2y$10$j4qq3ojBPZ4SC47oOmdV0.6Q9d04jna16.9a32eYHCXRYaUGU3b7u', 1, '2026-01-30 04:06:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`claim_id`),
  ADD KEY `fk_claim_record` (`record_id`),
  ADD KEY `fk_claim_patient` (`patient_id`),
  ADD KEY `fk_claim_insurance` (`insurance_id`),
  ADD KEY `fk_claim_service` (`service_id`);

--
-- Indexes for table `customer_type`
--
ALTER TABLE `customer_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`hospital_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `insurance_hospitals`
--
ALTER TABLE `insurance_hospitals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ins_hosp` (`insurance_id`,`hospital_id`);

--
-- Indexes for table `insurance_plan`
--
ALTER TABLE `insurance_plan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plan` (`insurance_id`,`category_id`,`customer_type_id`),
  ADD KEY `fk_plan_category` (`category_id`),
  ADD KEY `fk_plan_customer_type` (`customer_type_id`);

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
  ADD KEY `idx_patients_phone` (`phone`),
  ADD KEY `idx_patients_insurance` (`insurance_id`),
  ADD KEY `fk_patients_category` (`category_id`);

--
-- Indexes for table `patient_policy`
--
ALTER TABLE `patient_policy`
  ADD UNIQUE KEY `uq_patient_one_policy` (`patient_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_roles` (`role_id`),
  ADD KEY `fk_users_hospital` (`hospital_id`),
  ADD KEY `fk_users_insurance` (`insurance_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `claim_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_type`
--
ALTER TABLE `customer_type`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `hospital_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `insurance_hospitals`
--
ALTER TABLE `insurance_hospitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `insurance_plan`
--
ALTER TABLE `insurance_plan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `medical_insurances`
--
ALTER TABLE `medical_insurances`
  MODIFY `insurance_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `fk_claim_insurance` FOREIGN KEY (`insurance_id`) REFERENCES `medical_insurances` (`insurance_id`),
  ADD CONSTRAINT `fk_claim_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `fk_claim_record` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`),
  ADD CONSTRAINT `fk_claim_service` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_patients_insurance` FOREIGN KEY (`insurance_id`) REFERENCES `medical_insurances` (`insurance_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`hospital_id`),
  ADD CONSTRAINT `fk_users_insurance` FOREIGN KEY (`insurance_id`) REFERENCES `medical_insurances` (`insurance_id`),
  ADD CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
