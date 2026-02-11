
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Feb 09, 2026 at 06:05 PM
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
-- Database: `temb2c`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `created_at`) VALUES
(1, 'admin@gmail.com', '$2y$10$1W.eh1MpNzRTxERlaYTAyuZjrcb0ifFh8idFrA672HkS.43..ryfy', '2026-02-07 06:38:09');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `payment_for` enum('PSYCHOMETRIC','GROUP','ONE_TO_ONE') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` enum('RAZORPAY') DEFAULT 'RAZORPAY',
  `razorpay_order_id` varchar(120) DEFAULT NULL,
  `razorpay_payment_id` varchar(120) DEFAULT NULL,
  `payment_status` enum('PENDING','PAID','FAILED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  `auto_pay` enum('YES','NO') DEFAULT 'YES',
  `scheduled_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `student_id`, `payment_for`, `amount`, `gateway`, `razorpay_order_id`, `razorpay_payment_id`, `payment_status`, `created_at`, `paid_at`, `auto_pay`, `scheduled_date`) VALUES
(11, 17, 6, 'PSYCHOMETRIC', 799.00, 'RAZORPAY', 'order_SDyjZtUjSDqoZy', NULL, 'PENDING', '2026-02-09 08:18:42', NULL, 'YES', NULL),
(12, 17, 6, 'PSYCHOMETRIC', 799.00, 'RAZORPAY', 'order_SDykVVRj8Harhf', NULL, 'PENDING', '2026-02-09 08:19:35', NULL, 'YES', NULL),
(13, 17, 6, 'PSYCHOMETRIC', 799.00, 'RAZORPAY', 'order_SDyoZPQdlKgQ4f', 'pay_SDyodikWWXkqxW', 'PAID', '2026-02-09 08:23:26', '2026-02-09 13:53:44', 'YES', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_logs`
--

CREATE TABLE `payment_logs` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `razorpay_order_id` varchar(120) DEFAULT NULL,
  `razorpay_payment_id` varchar(120) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `payment_type` enum('TOKEN','FINAL') DEFAULT 'TOKEN',
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_payload`)),
  `status` enum('INITIATED','SUCCESS','FAILED') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_logs`
--

INSERT INTO `payment_logs` (`id`, `booking_id`, `student_id`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `payment_type`, `raw_payload`, `status`, `created_at`) VALUES
(4, 17, 6, 'order_SDydXqHy5Kw1W8', 'pay_SDydch3GrdtrTW', 'c499637f9f69a6c37114a3e314748e2f77397dff2035af6b1db07075c8171348', 'TOKEN', '{\"razorpay_payment_id\":\"pay_SDydch3GrdtrTW\",\"razorpay_order_id\":\"order_SDydXqHy5Kw1W8\",\"razorpay_signature\":\"c499637f9f69a6c37114a3e314748e2f77397dff2035af6b1db07075c8171348\"}', 'SUCCESS', '2026-02-09 08:13:19'),
(5, 17, 6, 'order_SDyeUpxb1tEugW', NULL, NULL, 'FINAL', '{}', 'INITIATED', '2026-02-09 08:13:54'),
(6, 17, 6, 'order_SDyjZtUjSDqoZy', NULL, NULL, 'FINAL', '{}', 'INITIATED', '2026-02-09 08:18:42'),
(7, 17, 6, 'order_SDykVVRj8Harhf', NULL, NULL, 'FINAL', '{}', 'INITIATED', '2026-02-09 08:19:35'),
(8, 17, 6, 'order_SDyoZPQdlKgQ4f', NULL, NULL, 'FINAL', '{}', 'INITIATED', '2026-02-09 08:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_master`
--

CREATE TABLE `schedule_master` (
  `id` int(11) NOT NULL,
  `event_name` varchar(150) DEFAULT NULL,
  `psychometric_date1` date DEFAULT NULL,
  `psychometric_date2` date DEFAULT NULL,
  `block1_session1` varchar(150) DEFAULT NULL,
  `block1_date1` date DEFAULT NULL,
  `block1_session2` varchar(150) DEFAULT NULL,
  `block1_date2` date DEFAULT NULL,
  `block2_session1` varchar(150) DEFAULT NULL,
  `block2_date1` date DEFAULT NULL,
  `block2_session2` varchar(150) DEFAULT NULL,
  `block2_date2` date DEFAULT NULL,
  `counselling_from` date DEFAULT NULL,
  `counselling_to` date DEFAULT NULL,
  `counsellors_name` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_master`
--

INSERT INTO `schedule_master` (`id`, `event_name`, `psychometric_date1`, `psychometric_date2`, `block1_session1`, `block1_date1`, `block1_session2`, `block1_date2`, `block2_session1`, `block2_date1`, `block2_session2`, `block2_date2`, `counselling_from`, `counselling_to`, `counsellors_name`, `created_at`) VALUES
(3, 'February 2026 Public Counselling Event', '2026-02-11', '2026-02-12', 'Group Session 1 ', '2026-02-13', 'Group Session 2', '2026-02-14', 'Group Session 3', '2026-03-15', 'Group Session 4', '2026-03-16', '2026-02-17', '2026-02-28', 'David Ipe Sir and Medhavi Mam', '2026-02-09 05:11:05');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `std` varchar(20) NOT NULL,
  `school_name` varchar(200) NOT NULL,
  `address` text NOT NULL,
  `dob` date NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `username`, `email`, `contact_number`, `gender`, `std`, `school_name`, `address`, `dob`, `password`, `created_at`) VALUES
(4, 'surya sundar', 'surya1@gmail.com', '9768332600', 'Male', '10_any', 'Holy Family High School', 'meghwahi jogeshwari east\r\nmegwadi', '2010-11-08', '$2y$10$OYK2NJOJd3K2cam6uxpnl.QFWsTyOIuW7lO/URtM3dR.uQfgKpzNO', '2026-02-09 05:05:20'),
(5, 'surya sundar 2', 'surya2@gmail.com', '8104608876', 'Male', '11_sci_medi', 'VALIA COLLEGE', 'Praesentium voluptat', '2008-12-12', '$2y$10$tBBnQuXSg1E3UnAgDw0lUOQ9WTN/el/ToaiyFitgiKM9rj2fAwUre', '2026-02-09 06:31:57'),
(6, 'Anurag', 'anurag1@gmail.com', '8104608876', 'Male', '8-9_cbse', 'St. Arnold High school', 'mas', '2012-12-12', '$2y$10$LgtheitqTLuesDZZ1aPM4uf1UmbK9FE5nbLcooH3F03ZDj1QQXrXK', '2026-02-09 07:40:14');

-- --------------------------------------------------------

--
-- Table structure for table `student_bookings`
--

CREATE TABLE `student_bookings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `selected_psychometric_date` date NOT NULL,
  `group_session1` varchar(150) NOT NULL,
  `group_session1_date` date DEFAULT NULL,
  `group_session2` varchar(150) NOT NULL,
  `group_session2_date` date DEFAULT NULL,
  `one_to_one_slot` varchar(20) NOT NULL,
  `booked_date` date NOT NULL,
  `counsellor_name` varchar(120) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `txnid` varchar(120) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `razorpay_customer_id` varchar(120) DEFAULT NULL,
  `razorpay_mandate_id` varchar(120) DEFAULT NULL,
  `mandate_status` enum('ACTIVE','CANCELLED') DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_bookings`
--

INSERT INTO `student_bookings` (`id`, `student_id`, `schedule_id`, `selected_psychometric_date`, `group_session1`, `group_session1_date`, `group_session2`, `group_session2_date`, `one_to_one_slot`, `booked_date`, `counsellor_name`, `payment_status`, `txnid`, `amount`, `created_at`, `razorpay_customer_id`, `razorpay_mandate_id`, `mandate_status`) VALUES
(17, 6, 3, '2026-02-11', 'Group Session 1 ', '2026-02-13', 'Group Session 2', '2026-02-14', '10:00', '2026-02-17', 'David Ipe', 'paid', 'pay_SDydch3GrdtrTW', 1.00, '2026-02-09 08:12:59', NULL, NULL, 'ACTIVE');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule_master`
--
ALTER TABLE `schedule_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student_bookings`
--
ALTER TABLE `student_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booked_date` (`booked_date`,`one_to_one_slot`),
  ADD UNIQUE KEY `student_id` (`student_id`,`schedule_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payment_logs`
--
ALTER TABLE `payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schedule_master`
--
ALTER TABLE `schedule_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_bookings`
--
ALTER TABLE `student_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
