-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 03:33 PM
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
-- Database: `krishna_pump`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'omvi', '3103');

-- --------------------------------------------------------

--
-- Table structure for table `advance_salary`
--

CREATE TABLE `advance_salary` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `date_advance` date NOT NULL DEFAULT curdate(),
  `amount` decimal(10,2) NOT NULL,
  `cash_gpay` enum('cash','gpay') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advance_salary`
--

INSERT INTO `advance_salary` (`id`, `worker_id`, `date_advance`, `amount`, `cash_gpay`) VALUES
(1, 1, '2025-08-05', 1500.00, 'cash');

-- --------------------------------------------------------

--
-- Table structure for table `customer_bill`
--

CREATE TABLE `customer_bill` (
  `id` int(11) NOT NULL,
  `work_no` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `contact_no` varchar(15) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `bill_amount` decimal(10,2) NOT NULL,
  `margin` decimal(10,2) GENERATED ALWAYS AS (`bill_amount` - `cost`) STORED,
  `date` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_bill`
--

INSERT INTO `customer_bill` (`id`, `work_no`, `customer_name`, `contact_no`, `cost`, `bill_amount`, `date`) VALUES
(1, '', 'Rakeshbhai', '9985746325', 0.00, 2000.00, '2025-08-05'),
(2, '', 'Rakeshbhai', '9985746325', 0.00, 1500.00, '2025-08-05');

-- --------------------------------------------------------

--
-- Table structure for table `customer_motor_work`
--

CREATE TABLE `customer_motor_work` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_motor_work`
--

INSERT INTO `customer_motor_work` (`id`, `bill_id`, `part_id`) VALUES
(1, 1, 5),
(2, 1, 5),
(3, 1, 9),
(4, 1, 36),
(5, 2, 9),
(6, 2, 9),
(7, 2, 39);

-- --------------------------------------------------------

--
-- Table structure for table `motor_groups`
--

CREATE TABLE `motor_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motor_groups`
--

INSERT INTO `motor_groups` (`id`, `group_name`, `image_path`) VALUES
(1, 'BEARING', 'images/bearing.jpg'),
(2, 'SEAL', 'images/seal.jpg'),
(3, 'REWINDING', 'images/revindind.jpg'),
(4, 'CAPACITOR', 'images/capacitor.jpg'),
(5, 'FAN', 'images/fan.jpg'),
(6, 'LOTER', 'images/loter.jpg'),
(7, 'DABRA SET', 'images/dabraset.jpg'),
(8, 'NUT AND STUD', 'images/nut and stud.jpg'),
(9, 'BUSSHING', 'images/busshing.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `motor_parts`
--

CREATE TABLE `motor_parts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motor_parts`
--

INSERT INTO `motor_parts` (`id`, `name`, `cost`, `group_id`) VALUES
(1, 'REVINDING 1 HP (2800 RPM)', 500.00, 3),
(2, 'REVINDING 0.75 HP (2800 RPM)', 450.00, 3),
(3, 'REVINDING 0.05 HP (2800 RPM)', 450.00, 3),
(4, 'REVINDING 1 HP (1400 RPM)', 700.00, 3),
(5, 'BEARING 6202', 30.00, 1),
(6, 'BEARING 6203', 40.00, 1),
(7, 'BEARING 6204', 60.00, 1),
(8, 'BEARING 6304', 60.00, 1),
(9, 'SEAL 12 MM WITH CERAMIC', 30.00, 2),
(10, 'SEAL 12 MM WITHOUT CERAMIC', 30.00, 2),
(11, 'SEAL 16 MM WITH CERAMIC', 40.00, 2),
(12, 'SEAL 16 MM WITHOUT CERAMIC', 40.00, 2),
(13, 'CAPACITOR 12.5 MFD', 35.00, 4),
(14, 'CAPACITOR 15 MFD', 40.00, 4),
(15, 'CAPACITOR 20 MFD', 60.00, 4),
(16, 'CAPACITOR 25 MFD', 70.00, 4),
(17, 'FAN 12 MM', 10.00, 5),
(18, 'FAN 16 MM', 10.00, 5),
(19, 'DABRA SET 82 MM DABRA', 350.00, 7),
(20, 'DABRA SET 85 MM DABRA', 350.00, 7),
(21, 'DABRA SET 95 MM DABRA', 430.00, 7),
(22, 'LOTER 82 MM LOTER', 150.00, 6),
(23, 'LOTER 85 MM LOTER', 160.00, 6),
(24, 'LOTER 95 MM LOTER', 230.00, 6),
(28, 'Connection', 100.00, 3),
(29, 'SEAL 18MM', 50.00, 2),
(30, 'SEAL 19MM', 50.00, 2),
(31, 'Connection', 100.00, NULL),
(34, 'PUMP', 0.00, 3),
(35, 'NUT BAULT', 5.00, 8),
(36, 'STUD', 5.00, 8),
(37, '30.40.30', 189.00, 9),
(38, 'CAPACITOR 50 MFD', 115.00, 4),
(39, '18-28-28', 116.00, 9),
(40, '22-30-30', 110.00, 9);

-- --------------------------------------------------------

--
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workers`
--

INSERT INTO `workers` (`id`, `name`) VALUES
(1, 'Rahulbhai');

-- --------------------------------------------------------

--
-- Table structure for table `worker_motor_work`
--

CREATE TABLE `worker_motor_work` (
  `id` int(11) NOT NULL,
  `work_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_motor_work`
--

INSERT INTO `worker_motor_work` (`id`, `work_id`, `part_id`) VALUES
(1, 1, 5),
(2, 1, 19),
(3, 2, 9),
(4, 2, 22),
(5, 2, 34),
(6, 3, 9),
(7, 3, 22),
(8, 4, 9),
(9, 4, 22),
(10, 5, 9),
(11, 5, 5),
(12, 5, 5),
(13, 5, 34),
(14, 6, 5),
(15, 6, 5),
(16, 6, 19),
(17, 6, 36),
(18, 6, 36),
(19, 6, 36),
(20, 7, 5),
(21, 7, 9),
(22, 7, 9),
(23, 7, 24),
(24, 8, 5),
(25, 8, 5);

-- --------------------------------------------------------

--
-- Table structure for table `worker_work`
--

CREATE TABLE `worker_work` (
  `id` int(11) NOT NULL,
  `work_no` varchar(50) NOT NULL,
  `work_date` date NOT NULL DEFAULT curdate(),
  `cost` decimal(10,2) NOT NULL,
  `bill` decimal(10,2) NOT NULL,
  `margin` decimal(10,2) GENERATED ALWAYS AS (`bill` - `cost`) STORED,
  `salary` decimal(10,2) GENERATED ALWAYS AS ((`bill` - `cost`) * 0.5) STORED,
  `worker_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_work`
--

INSERT INTO `worker_work` (`id`, `work_no`, `work_date`, `cost`, `bill`, `worker_id`) VALUES
(1, 'WK20250805-5563', '2025-08-04', 500.00, 1500.00, 1),
(2, 'WK20250805-9615', '2025-08-04', 200.00, 1500.00, 1),
(3, 'WK20250805-6629', '2025-08-05', 180.00, 1500.00, 1),
(4, 'WK20250805-1630', '2025-08-05', 180.00, 1500.00, 1),
(5, 'WK20250805-5120', '2025-08-05', 200.00, 800.00, 1),
(6, 'WK20250805-8220', '2025-08-05', 450.00, 900.00, 1),
(7, 'WK20250805-4801', '2025-08-05', 350.00, 800.00, 1),
(8, 'WK20250805-8193', '2025-08-05', 80.00, 150.00, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `advance_salary`
--
ALTER TABLE `advance_salary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `worker_id` (`worker_id`);

--
-- Indexes for table `customer_bill`
--
ALTER TABLE `customer_bill`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_motor_work`
--
ALTER TABLE `customer_motor_work`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `motor_groups`
--
ALTER TABLE `motor_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `motor_parts`
--
ALTER TABLE `motor_parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `worker_motor_work`
--
ALTER TABLE `worker_motor_work`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_id` (`work_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `worker_work`
--
ALTER TABLE `worker_work`
  ADD PRIMARY KEY (`id`),
  ADD KEY `worker_id` (`worker_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `advance_salary`
--
ALTER TABLE `advance_salary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_bill`
--
ALTER TABLE `customer_bill`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_motor_work`
--
ALTER TABLE `customer_motor_work`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `motor_groups`
--
ALTER TABLE `motor_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `motor_parts`
--
ALTER TABLE `motor_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `worker_motor_work`
--
ALTER TABLE `worker_motor_work`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `worker_work`
--
ALTER TABLE `worker_work`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advance_salary`
--
ALTER TABLE `advance_salary`
  ADD CONSTRAINT `advance_salary_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_motor_work`
--
ALTER TABLE `customer_motor_work`
  ADD CONSTRAINT `customer_motor_work_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `customer_bill` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_motor_work_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `motor_parts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `motor_parts`
--
ALTER TABLE `motor_parts`
  ADD CONSTRAINT `motor_parts_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `motor_groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `worker_motor_work`
--
ALTER TABLE `worker_motor_work`
  ADD CONSTRAINT `worker_motor_work_ibfk_1` FOREIGN KEY (`work_id`) REFERENCES `worker_work` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `worker_motor_work_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `motor_parts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `worker_work`
--
ALTER TABLE `worker_work`
  ADD CONSTRAINT `worker_work_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
