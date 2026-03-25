-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 27, 2026 at 06:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `museum_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'password123');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `image_path`) VALUES
(3, 'Local History Books', 'books.jpg'),
(4, 'Old Lamps', '238f30d987b072e5475d3fc5cb007cfb.jpg'),
(5, 'OLD WOODEN CANE AND HAT', '637157823_926852483074221_5622699732933520824_n.jpg'),
(6, 'Camera', 'd1cee0a9bd9adff6604c500fcbe368e0.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `exhibits`
--

CREATE TABLE `exhibits` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `donated_by` varchar(255) DEFAULT NULL,
  `artifact_year` varchar(50) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exhibits`
--

INSERT INTO `exhibits` (`id`, `title`, `description`, `image_url`, `category_id`, `image_path`, `donated_by`, `artifact_year`, `origin`) VALUES
(5, 'KASAYSAY PAMANA NG LAHI: ALAY SA MGA TAGA LABO VOL. 1', 'The first ever local history books of Labo from 1591 - 1998 with special feature of Centennial celebration of Philippine Independence in town on June 12, 1998.', NULL, 3, '1book.jpg', 'Briones Family', 'June 12, 1998', 'Labo'),
(6, 'KASAYSAY PAMANA NG LAHI: ALAY SA MGA TAGA LABO VOL. 2', 'After 25 years the updated version of Kasaysayan Vol. 1 is produced which cover from 1570 – 2023, many special events that happened in the town are already included in the newest version.', NULL, 3, '2book.jpg', 'Malagueño', '1570 - 2023', 'Labo'),
(7, ' OLD LAMPS', 'Different sizes and design of old lamps used by our forefathers for various purposes donated by local populace.', NULL, 4, '639087160_1992852244773915_1201390354977356726_n.jpg', 'Elon', '1994', 'Labo'),
(8, 'OLD WOODEN CANE AND HAT', 'During Spanish times these were the common things carried by local leaders and “encargado” that symbolized authority and power in the community.', NULL, 5, '637157823_926852483074221_5622699732933520824_n.jpg', 'Garen', '1993', 'Labo'),
(9, 'COMPUR KODAK CAMERA', '(1910) owned by Mr. William Paguirillo (Ching Studio) is the oldest studio camera in Labo, Camarines Norte and still existing today.', NULL, 6, '638496593_3821753597961247_6027973135724181512_n.jpg', 'Paguirillo', '1890', 'Labo');

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `guest_name` varchar(100) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `residence` varchar(255) NOT NULL,
  `nationality` varchar(100) NOT NULL,
  `num_days` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `contact_no` varchar(50) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `visit_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`id`, `guest_name`, `gender`, `residence`, `nationality`, `num_days`, `purpose`, `contact_no`, `status`, `visit_date`) VALUES
(2, 'jonel ramos', 'Male', 'Labo', 'Filipino', 1, 'Information', '09929139222', 'approved', '2026-02-27 04:55:46'),
(3, 'Eric John Kenneth Briones', 'Male', 'Labo', 'Filipino', 3, 'Information', '0912345678', 'approved', '2026-02-27 05:11:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exhibits`
--
ALTER TABLE `exhibits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exhibits`
--
ALTER TABLE `exhibits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exhibits`
--
ALTER TABLE `exhibits`
  ADD CONSTRAINT `exhibits_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
