-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 01:59 AM
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
-- Database: `museo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', 'password123', '2026-03-25 01:11:59');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `image_path` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `image_path`, `description`, `created_at`) VALUES
(3, 'Local History Books', 'books.jpg', 'Books documenting the rich history of Labo', '2026-03-25 01:11:59'),
(4, 'Old Lamps', '238f30d987b072e5475d3fc5cb007cfb.jpg', 'Various old lamps used by forefathers', '2026-03-25 01:11:59'),
(5, 'OLD WOODEN CANE AND HAT', '637157823_926852483074221_5622699732933520824_n.jpg', 'Colonial-era symbols of authority', '2026-03-25 01:11:59'),
(6, 'Camera', 'd1cee0a9bd9adff6604c500fcbe368e0.jpg', 'Historical cameras from Labo', '2026-03-25 01:11:59'),
(7, 'Clock', '69c359a9860bd_1774410153.jpg', '', '2026-03-25 03:42:33');

-- --------------------------------------------------------

--
-- Table structure for table `exhibits`
--

CREATE TABLE `exhibits` (
  `id` int(11) NOT NULL,
  `title` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image_path` varchar(300) DEFAULT NULL,
  `donated_by` varchar(200) DEFAULT NULL,
  `artifact_year` varchar(100) DEFAULT NULL,
  `origin` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exhibits`
--

INSERT INTO `exhibits` (`id`, `title`, `description`, `category_id`, `image_path`, `donated_by`, `artifact_year`, `origin`, `created_at`) VALUES
(5, 'KASAYSAY PAMANA NG LAHI VOL. 1', 'The first ever local history books of Labo from 1591-1998 with special feature of Centennial celebration of Philippine Independence in town on June 12, 1998.', 3, '1book.jpg', 'Briones Family', 'June 12, 1998', 'Labo', '2026-03-25 01:11:59'),
(6, 'KASAYSAY PAMANA NG LAHI VOL. 2', 'After 25 years the updated version of Kasaysayan Vol. 1 covering 1570-2023, many special events that happened in the town are already included.', 3, '2book.jpg', 'Malagueño', '1570-2023', 'Labo', '2026-03-25 01:11:59'),
(7, 'OLD LAMPS', 'Different sizes and design of old lamps used by our forefathers for various purposes donated by local populace.', 4, '639087160_1992852244773915_1201390354977356726_n.jpg', 'Elon', '1994', 'Labo', '2026-03-25 01:11:59'),
(8, 'OLD WOODEN CANE AND HAT', 'During Spanish times these were the common things carried by local leaders and encargado that symbolized authority and power in the community.', 5, '637157823_926852483074221_5622699732933520824_n.jpg', 'Garen', '1993', 'Labo', '2026-03-25 01:11:59'),
(9, 'COMPUR KODAK CAMERA', '(1910) owned by Mr. William Paguirillo (Ching Studio) is the oldest studio camera in Labo, Camarines Norte and still existing today.', 6, '638496593_3821753597961247_6027973135724181512_n.jpg', 'Paguirillo', '1890', 'Labo', '2026-03-25 01:11:59'),
(10, 'Wooden Clock', 'bagong donate', 7, '69c359df370b0_1774410207.jpg', 'Ms. Jaime Palado', '1946', 'Brgy. Fundado', '2026-03-25 03:43:27');

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `guest_name` varchar(200) NOT NULL,
  `visitor_type` enum('Individual','Group') DEFAULT 'Individual',
  `organization` varchar(200) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `residence` varchar(200) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Filipino',
  `headcount` int(11) DEFAULT 1,
  `male_count` int(11) DEFAULT 0,
  `female_count` int(11) DEFAULT 0,
  `num_days` int(11) DEFAULT 1,
  `purpose` varchar(200) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `visit_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`id`, `guest_name`, `visitor_type`, `organization`, `gender`, `residence`, `nationality`, `headcount`, `male_count`, `female_count`, `num_days`, `purpose`, `contact_no`, `visit_date`, `created_at`) VALUES
(1, 'Mark Joseph D. Cruz', 'Group', 'OLLCF', 'Male', 'Labo', 'Filipino', 13, 6, 7, 1, 'Information', '+639214567321', '2026-03-26', '2026-03-26 00:23:47');

-- --------------------------------------------------------

--
-- Table structure for table `news_events`
--

CREATE TABLE `news_events` (
  `id` int(11) NOT NULL,
  `title` varchar(300) NOT NULL,
  `content` text NOT NULL,
  `type` enum('news','event') DEFAULT 'news',
  `event_date` date DEFAULT NULL,
  `date_posted` date DEFAULT curdate(),
  `image_path` varchar(300) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news_events`
--

INSERT INTO `news_events` (`id`, `title`, `content`, `type`, `event_date`, `date_posted`, `image_path`, `is_archived`, `created_at`) VALUES
(1, 'Birthday ni Jonel', 'Handaan sa Bonoks', 'event', '2026-07-17', '2026-03-25', '69c34ef95a619_1774407417.png', 0, '2026-03-25 02:56:57'),
(2, 'Independence Day', 'Every year on June 12, the Philippines comes alive with vibrant celebrations, as Filipinos commemorate Independence Day. This national holiday marks the anniversary of the country’s declaration of independence from Spanish rule in 1898. But beyond the festivities, this day is a profound reminder of the nation’s rich history, diverse culture, and resilient spirit. Let’s delve into the past, present, and future significance of Philippine Independence Day.', 'event', '2026-06-12', '2026-03-25', '69c355d8431c1_1774409176.jpg', 0, '2026-03-25 03:26:16'),
(3, 'Lukban Day', 'Vicente Lukban was born in Labo, Camarines Norte on February 11, 1860.\r\n\r\nAfter his elementary education at Escuela Pia Publica in his hometown, he proceeded to Manila. He enrolled to get his Law Degree at the Ateneo de Manila and then at the Colegio de San Juan de Letran.\r\n\r\nHe became an Oficial Criminalista in the court of first instance in Quiapo, in the company of Marcelo H. del Pilar and Doroteo Jose. But he returned to Labo where he served as Delegado Municipal and Juez de Paz.\r\n\r\nIn 1894, he was inducted into the Masonic Lodge Luz del Oriente. Together with Juan Miguel, he founded Lodge Bicol in Camarines.\r\n\r\nAt the outbreak of the revolution, he was devoting himself to agriculture and commerce, founding an agrigultural society, La Cooperativa Popular. By 1896, his reach had widened because he was considered influential even as far as Tayabas province, where conspiratorical exertions were noted. Attending a meeting of the agricultural society in Manila, he was arrested on September 29. He was tortured and incarcerated in Bilibid Prison until May 17, 1897, when he was released together with many political prisioners upon being pardoned by the governor-general.', 'event', '2026-02-11', '2026-03-25', '69c35640a8f72_1774409280.jpeg', 0, '2026-03-25 03:28:00'),
(4, 'New Donated Artifact: Wooden Clock', 'A newly donated artifact has been added to the museum collection.\n\nDonated by: Ms. Jaime Palado.\n\nDepartment: Clock.\n\nYear/Period: 1946.\n\nOrigin: Brgy. Fundado.\n\nDescription: bagong donate', 'news', NULL, '2026-03-25', '69c359df370b0_1774410207.jpg', 0, '2026-03-25 03:43:27');

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
-- Indexes for table `news_events`
--
ALTER TABLE `news_events`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `exhibits`
--
ALTER TABLE `exhibits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `news_events`
--
ALTER TABLE `news_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exhibits`
--
ALTER TABLE `exhibits`
  ADD CONSTRAINT `exhibits_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
