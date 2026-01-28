-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 15, 2026 at 11:14 AM
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
-- Database: `iitbnf`
--

-- --------------------------------------------------------

--
-- Table structure for table `employee_org`
--

CREATE TABLE `employee_org` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `post` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `team` varchar(100) DEFAULT NULL,
  `team_status` varchar(100) DEFAULT NULL,
  `faculty_incharge` text DEFAULT NULL,
  `job_profile` text DEFAULT NULL,
  `reporting_to` int(10) UNSIGNED DEFAULT NULL,
  `photo` blob DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_org`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `employee_org`
--
ALTER TABLE `employee_org`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employee_org`
--
ALTER TABLE `employee_org`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
