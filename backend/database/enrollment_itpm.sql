-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 29, 2025 at 02:55 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `enrollment_itpm`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_account`
--

CREATE TABLE `tbl_account` (
  `acc_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Student') NOT NULL,
  `enrollment_status` enum('Enrolled','Not Enrolled','Dropped Out','Newly Registered','Pending') NOT NULL DEFAULT 'Newly Registered',
  `reg_acc_status` int(11) DEFAULT 1,
  `date_registered` datetime DEFAULT current_timestamp(),
  `date_enrolled` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_account`
--

INSERT INTO `tbl_account` (`acc_id`, `username`, `email`, `password`, `role`, `enrollment_status`, `reg_acc_status`, `date_registered`, `date_enrolled`, `email_verified`) VALUES
(1, 'admin', 'jcdavid@gmail.com', '$2y$10$Z5JPFdmQq5vRcI10R0UqbOpjx9lAmjgl74Le3scj53aRAodx9a2IO', 'Admin', 'Not Enrolled', 2, '2025-09-12 20:35:58', NULL, 0),
(2, 'juancruz', 'jcdavid2@gmail.com', 'stud123', 'Student', 'Not Enrolled', 1, '2025-09-12 20:35:58', NULL, 0),
(3, 'anasantos', 'jcdavid3@gmail.com', 'stud123', 'Student', 'Not Enrolled', 1, '2025-09-12 20:35:58', NULL, 0),
(1002, 'chesca', 'chesca@gmail.com', '$2y$10$XN3M5MPJ8j/J99zrNQrlAuAz.coMsB3n.LMjmcG0KnfapIK/ysDIW', 'Student', 'Enrolled', 2, '2025-09-12 20:35:58', '2025-09-22 18:01:24', 0),
(1010, 'joshivan', 'jcdavid123c@gmail.com', '$2y$10$gpZu.yQi6LBq3rk.1MG29uRYtGs07M6XXH8vZ9.Ph0aG1tpMbWg4O', 'Student', 'Enrolled', 2, '2025-09-15 23:41:30', '2025-09-27 12:21:24', 0),
(1011, 'nicarosales', 'rosaleschesca1@gmail.com', '$2y$10$aOKQ0dmEVGsBBpGcx07Hz.ejxZr20cg5N.jyK3sBWHaBxXgKdKSMK', 'Student', 'Enrolled', 2, '2025-09-29 15:45:25', '2025-09-29 16:04:15', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_audit_log`
--

CREATE TABLE `tbl_audit_log` (
  `log_id` int(11) NOT NULL,
  `acc_id` int(11) NOT NULL,
  `activity` varchar(100) NOT NULL,
  `log_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_audit_log`
--

INSERT INTO `tbl_audit_log` (`log_id`, `acc_id`, `activity`, `log_date`) VALUES
(1, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:01:24'),
(2, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:02:20'),
(3, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:03:27'),
(4, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:03:39'),
(5, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:11:18'),
(6, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:14:04'),
(7, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:25:01'),
(8, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:25:18'),
(9, 1, 'Updated student information for account ID: 1002', '2025-09-22 18:25:33'),
(10, 1, 'Added new section: Kinder 1 - Rizal (Level ID: 1, Capacity: 32)', '2025-09-27 13:56:18'),
(11, 1, 'Updated section (ID: 15): Kinder 1 - Rizal (Level ID: 2, Capacity: 32)', '2025-09-27 13:57:09'),
(12, 1, 'Deleted section: Kinder 1 - Rizal (ID: 15)', '2025-09-27 13:57:26'),
(13, 1, 'Updated payment ID 6 with amount ₱2,000.00', '2025-09-27 15:13:08'),
(14, 1, 'Updated payment ID 4 with amount ₱18,000.00', '2025-09-27 15:13:19'),
(15, 1, 'Updated payment ID 6 with amount ₱2,000.00', '2025-09-27 15:16:24'),
(16, 1, 'Updated payment ID 6 with amount ₱2,000.00', '2025-09-27 15:16:33'),
(17, 1, 'Updated payment ID 6 with amount ₱2,000.00', '2025-09-27 15:16:41'),
(18, 1, 'Updated payment ID 6 with amount ₱2,000.00', '2025-09-27 15:16:48'),
(19, 1, 'Updated payment ID 6 with amount ₱2,000.00', '2025-09-27 20:58:55'),
(20, 1, 'Transferred student (Acc ID: 1002) from section ID 3 to section ID 4. Reason: Section transfer', '2025-09-27 21:53:10'),
(21, 1, 'Transferred student (Acc ID: 1002) from section ID 4 to section ID 3. Reason: Section transfer', '2025-09-27 21:53:29'),
(22, 1, 'Updated registration status for student (Acc ID: 1011) from 1 to 2', '2025-09-29 15:46:54'),
(23, 1, 'Added new section: Kinder 1 - Rizal (Level ID: 1, Capacity: 25)', '2025-09-29 16:03:02'),
(24, 1, 'Deleted section: Kinder 1 - Rizal (ID: 16)', '2025-09-29 16:03:15');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_dropout_transfer_reasons`
--

CREATE TABLE `tbl_dropout_transfer_reasons` (
  `reason_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `reason_type` enum('Dropped Out','Transferred') NOT NULL,
  `reason_detail` text NOT NULL,
  `transfer_school` varchar(100) DEFAULT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_email_verification`
--

CREATE TABLE `tbl_email_verification` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_verified` tinyint(1) DEFAULT 0,
  `temp_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`temp_data`)),
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_enrollments`
--

CREATE TABLE `tbl_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('Pending','Enrolled','Cancelled','Completed') DEFAULT 'Pending',
  `total_fee` decimal(10,2) DEFAULT NULL,
  `current_level_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_enrollments`
--

INSERT INTO `tbl_enrollments` (`enrollment_id`, `student_id`, `school_year`, `enrollment_date`, `status`, `total_fee`, `current_level_id`) VALUES
(3, 1002, '2025-2026', '2025-09-16', 'Completed', 18000.00, 2),
(5, 1002, '2025-2026', '2025-09-16', 'Pending', 18000.00, 3),
(7, 1003, '2025-2026', '2025-09-22', 'Pending', 18000.00, 1),
(8, 1004, '2025-2026', '2025-09-29', 'Pending', 18000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_fees`
--

CREATE TABLE `tbl_fees` (
  `fee_id` int(11) NOT NULL,
  `level` enum('Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6') NOT NULL,
  `registration_fee` decimal(10,2) NOT NULL,
  `miscellaneous_fee` decimal(10,2) NOT NULL,
  `books_fee` decimal(10,2) NOT NULL,
  `tuition_fee` decimal(10,2) NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_fees`
--

INSERT INTO `tbl_fees` (`fee_id`, `level`, `registration_fee`, `miscellaneous_fee`, `books_fee`, `tuition_fee`, `monthly_fee`) VALUES
(1, 'Kinder 1', 2500.00, 2500.00, 3000.00, 10000.00, 1000.00),
(2, 'Kinder 2', 2500.00, 2500.00, 3000.00, 10000.00, 1000.00),
(3, 'Grade 1', 2500.00, 2500.00, 5000.00, 10000.00, 1000.00),
(4, 'Grade 2', 2500.00, 2500.00, 5000.00, 10000.00, 1000.00),
(5, 'Grade 3', 2500.00, 2500.00, 5000.00, 10000.00, 1000.00),
(6, 'Grade 4', 2500.00, 2500.00, 5000.00, 10000.00, 1000.00),
(7, 'Grade 5', 2500.00, 2500.00, 5000.00, 10000.00, 1000.00),
(8, 'Grade 6', 2500.00, 2500.00, 5000.00, 10000.00, 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_new_old_students`
--

CREATE TABLE `tbl_new_old_students` (
  `std_id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `level_id` int(11) NOT NULL,
  `student_image` varchar(255) DEFAULT NULL,
  `parents_valid_id` varchar(255) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_new_old_students`
--

INSERT INTO `tbl_new_old_students` (`std_id`, `personal_id`, `level_id`, `student_image`, `parents_valid_id`, `section_id`) VALUES
(3, 1003, 1, 'photo_68c8fe437bfb7.jpg', 'id_68c8fe437c0ba.jpg', 1),
(4, 1004, 1, 'photo_68da39e274fbe.jpg', 'id_68da39e2751ba.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_parents_details`
--

CREATE TABLE `tbl_parents_details` (
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `parent_full_name` varchar(50) NOT NULL,
  `contact_num` varchar(20) NOT NULL,
  `relationship` enum('Mother','Father','Guardian') NOT NULL,
  `fb_account` varchar(255) DEFAULT 'No provided link',
  `parent_temp_id` varchar(255) DEFAULT 'No image uploaded yet'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_parents_details`
--

INSERT INTO `tbl_parents_details` (`parent_id`, `child_id`, `parent_full_name`, `contact_num`, `relationship`, `fb_account`, `parent_temp_id`) VALUES
(1, 1, 'Maria Cruz', '09171234567', 'Mother', 'No provided link', ''),
(2, 2, 'Jose Santos', '09182345678', 'Father', 'No provided link', ''),
(3, 1002, 'Nida Rosales', '09762096892', 'Mother', 'No provided link', ''),
(4, 1003, 'Josh Ivan', '09565535401', 'Father', 'https://www.facebook.com/jcdiff123', '68c8339b9c9a7_1757950875.jpeg'),
(5, 1004, 'Nida Franco', '09565535401', 'Mother', '', '68da38edb64e5_1759131885.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payments`
--

CREATE TABLE `tbl_payments` (
  `payment_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `method` enum('Cash','Bank','GCash','Online','Other') NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_payments`
--

INSERT INTO `tbl_payments` (`payment_id`, `enrollment_id`, `amount`, `payment_date`, `method`, `remarks`) VALUES
(4, 3, 18000.00, '2025-09-15 22:30:00', 'Cash', 'Payment'),
(6, 5, 2000.00, '2025-09-16 14:45:00', 'Cash', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_details`
--

CREATE TABLE `tbl_payment_details` (
  `payment_detail_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `fee_type` enum('Registration','Miscellaneous','Books','Tuition','Monthly') NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_payment_details`
--

INSERT INTO `tbl_payment_details` (`payment_detail_id`, `payment_id`, `fee_type`, `amount`) VALUES
(5, 4, 'Monthly', 18000.00),
(7, 6, 'Miscellaneous', 2000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_personal_details`
--

CREATE TABLE `tbl_personal_details` (
  `personal_id` int(11) NOT NULL,
  `acc_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_personal_details`
--

INSERT INTO `tbl_personal_details` (`personal_id`, `acc_id`, `first_name`, `middle_name`, `last_name`, `date_of_birth`, `gender`, `address`) VALUES
(1, 2, 'Juan', 'Dela', 'Cruz', '2017-05-12', 'Male', 'Quezon City'),
(2, 3, 'Ana', 'Lopez', 'Santos', '2015-09-21', 'Female', 'Manila City'),
(1002, 1002, 'Chesca', NULL, 'Rosales', '2022-04-21', 'Female', 'Kaligayahan QC'),
(1003, 1010, 'Ivan', '', 'Inocencio', '2022-05-21', 'Male', 'Bahay'),
(1004, 1011, 'Nica', '', 'Rosales', '2020-01-19', 'Female', 'Bistekville');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_requirements`
--

CREATE TABLE `tbl_requirements` (
  `requirement_id` int(11) NOT NULL,
  `requirement_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_requirements`
--

INSERT INTO `tbl_requirements` (`requirement_id`, `requirement_name`) VALUES
(1, 'PSA / Birth Certificate'),
(2, 'Report Card'),
(3, 'Good Moral Certificate'),
(4, '2x2 ID Picture'),
(5, 'Form 137');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sections`
--

CREATE TABLE `tbl_sections` (
  `sec_id` int(11) NOT NULL,
  `sec_name` varchar(50) NOT NULL,
  `level_id` int(11) DEFAULT NULL,
  `sec_capacity` int(11) NOT NULL,
  `sec_adviser` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_sections`
--

INSERT INTO `tbl_sections` (`sec_id`, `sec_name`, `level_id`, `sec_capacity`, `sec_adviser`) VALUES
(1, 'Kinder 1 - Rose', 1, 25, NULL),
(2, 'Kinder 2 - Sunflower', 2, 25, NULL),
(3, 'Grade 1 - Mabini', 3, 30, NULL),
(4, 'Grade 1 - Rizal', 3, 30, NULL),
(5, 'Grade 2 - Bonifacio', 4, 30, NULL),
(6, 'Grade 2 - Luna', 4, 30, NULL),
(7, 'Grade 3 - Jacinto', 5, 30, NULL),
(8, 'Grade 3 - Del Pilar', 5, 30, NULL),
(9, 'Grade 4 - Aguinaldo', 6, 32, NULL),
(10, 'Grade 4 - Mabuhay', 6, 32, NULL),
(11, 'Grade 5 - Lapu-Lapu', 7, 32, NULL),
(12, 'Grade 5 - Magallanes', 7, 32, NULL),
(13, 'Grade 6 - Einstein', 8, 32, NULL),
(14, 'Grade 6 - Newton', 8, 32, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_student_requirements`
--

CREATE TABLE `tbl_student_requirements` (
  `std_rq_id` int(11) NOT NULL,
  `acc_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `requirement_status` enum('Pending','Verifying','Accepted','Declined') DEFAULT 'Pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_student_requirements`
--

INSERT INTO `tbl_student_requirements` (`std_rq_id`, `acc_id`, `requirement_id`, `requirement_status`, `submitted_at`) VALUES
(1, 2, 1, 'Accepted', '2025-09-12 05:47:00'),
(2, 2, 2, 'Verifying', '2025-09-12 05:47:00'),
(3, 2, 1, 'Pending', '2025-09-12 05:47:00'),
(4, 3, 5, 'Pending', '2025-09-12 05:47:00'),
(9, 1010, 4, 'Accepted', '2025-09-24 13:14:34'),
(10, 1002, 4, 'Accepted', '2025-09-24 13:15:06');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_student_transferee`
--

CREATE TABLE `tbl_student_transferee` (
  `std_id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `prev_school` varchar(255) NOT NULL,
  `prev_address_school` varchar(255) NOT NULL,
  `prev_id_school_file` varchar(255) NOT NULL,
  `prev_school_card` varchar(255) NOT NULL,
  `level_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_student_transferee`
--

INSERT INTO `tbl_student_transferee` (`std_id`, `personal_id`, `prev_school`, `prev_address_school`, `prev_id_school_file`, `prev_school_card`, `level_id`, `section_id`) VALUES
(1, 1002, 'Bayan Glori Elementary School', 'Bayan Glori', 'id_68c9035bd5c28.jpg', 'school_card_68c9035bd5e81.jpg', 3, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_account`
--
ALTER TABLE `tbl_account`
  ADD PRIMARY KEY (`acc_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `tbl_audit_log`
--
ALTER TABLE `tbl_audit_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `tbl_dropout_transfer_reasons`
--
ALTER TABLE `tbl_dropout_transfer_reasons`
  ADD PRIMARY KEY (`reason_id`),
  ADD KEY `fk_reason_student` (`student_id`),
  ADD KEY `fk_reason_enrollment` (`enrollment_id`);

--
-- Indexes for table `tbl_email_verification`
--
ALTER TABLE `tbl_email_verification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_enrollments`
--
ALTER TABLE `tbl_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tbl_fees`
--
ALTER TABLE `tbl_fees`
  ADD PRIMARY KEY (`fee_id`);

--
-- Indexes for table `tbl_new_old_students`
--
ALTER TABLE `tbl_new_old_students`
  ADD PRIMARY KEY (`std_id`);

--
-- Indexes for table `tbl_parents_details`
--
ALTER TABLE `tbl_parents_details`
  ADD PRIMARY KEY (`parent_id`),
  ADD KEY `fk_child_id` (`child_id`);

--
-- Indexes for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `tbl_payment_details`
--
ALTER TABLE `tbl_payment_details`
  ADD PRIMARY KEY (`payment_detail_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `tbl_personal_details`
--
ALTER TABLE `tbl_personal_details`
  ADD PRIMARY KEY (`personal_id`),
  ADD KEY `fk_acc_idPd` (`acc_id`);

--
-- Indexes for table `tbl_requirements`
--
ALTER TABLE `tbl_requirements`
  ADD PRIMARY KEY (`requirement_id`);

--
-- Indexes for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  ADD PRIMARY KEY (`sec_id`),
  ADD KEY `fk_level_id_sec` (`level_id`);

--
-- Indexes for table `tbl_student_requirements`
--
ALTER TABLE `tbl_student_requirements`
  ADD PRIMARY KEY (`std_rq_id`),
  ADD KEY `fk_acc_idRq` (`acc_id`),
  ADD KEY `fk_requirement_id` (`requirement_id`);

--
-- Indexes for table `tbl_student_transferee`
--
ALTER TABLE `tbl_student_transferee`
  ADD PRIMARY KEY (`std_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_account`
--
ALTER TABLE `tbl_account`
  MODIFY `acc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1012;

--
-- AUTO_INCREMENT for table `tbl_audit_log`
--
ALTER TABLE `tbl_audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tbl_dropout_transfer_reasons`
--
ALTER TABLE `tbl_dropout_transfer_reasons`
  MODIFY `reason_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_email_verification`
--
ALTER TABLE `tbl_email_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tbl_enrollments`
--
ALTER TABLE `tbl_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_fees`
--
ALTER TABLE `tbl_fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_new_old_students`
--
ALTER TABLE `tbl_new_old_students`
  MODIFY `std_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_parents_details`
--
ALTER TABLE `tbl_parents_details`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_payment_details`
--
ALTER TABLE `tbl_payment_details`
  MODIFY `payment_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_personal_details`
--
ALTER TABLE `tbl_personal_details`
  MODIFY `personal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;

--
-- AUTO_INCREMENT for table `tbl_requirements`
--
ALTER TABLE `tbl_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  MODIFY `sec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tbl_student_requirements`
--
ALTER TABLE `tbl_student_requirements`
  MODIFY `std_rq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_student_transferee`
--
ALTER TABLE `tbl_student_transferee`
  MODIFY `std_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_dropout_transfer_reasons`
--
ALTER TABLE `tbl_dropout_transfer_reasons`
  ADD CONSTRAINT `fk_reason_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `tbl_enrollments` (`enrollment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reason_student` FOREIGN KEY (`student_id`) REFERENCES `tbl_personal_details` (`personal_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_enrollments`
--
ALTER TABLE `tbl_enrollments`
  ADD CONSTRAINT `tbl_enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tbl_personal_details` (`personal_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_parents_details`
--
ALTER TABLE `tbl_parents_details`
  ADD CONSTRAINT `fk_child_id` FOREIGN KEY (`child_id`) REFERENCES `tbl_personal_details` (`personal_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  ADD CONSTRAINT `tbl_payments_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `tbl_enrollments` (`enrollment_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_payment_details`
--
ALTER TABLE `tbl_payment_details`
  ADD CONSTRAINT `tbl_payment_details_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `tbl_payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_personal_details`
--
ALTER TABLE `tbl_personal_details`
  ADD CONSTRAINT `fk_acc_idPd` FOREIGN KEY (`acc_id`) REFERENCES `tbl_account` (`acc_id`);

--
-- Constraints for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  ADD CONSTRAINT `fk_level_id_sec` FOREIGN KEY (`level_id`) REFERENCES `tbl_fees` (`fee_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_student_requirements`
--
ALTER TABLE `tbl_student_requirements`
  ADD CONSTRAINT `fk_acc_idRq` FOREIGN KEY (`acc_id`) REFERENCES `tbl_account` (`acc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_requirement_id` FOREIGN KEY (`requirement_id`) REFERENCES `tbl_requirements` (`requirement_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
