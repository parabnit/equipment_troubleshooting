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

INSERT INTO `employee_org` (`id`, `name`, `post`, `email`, `mobile`, `team`, `team_status`, `faculty_incharge`, `job_profile`, `reporting_to`, `photo`, `created_at`, `updated_at`) VALUES
(1, 'Dipankar Saha', 'Principal Investigator', 'dipankar.saha@iitb.ac.in', '9000000001', 'Management', 'Active', 'NULL', 'Overall leadership and strategy', 1234, NULL, '2026-01-15 05:29:13', '2026-01-15 05:29:13'),
(3, 'Mallika Moolya', 'Principal Investigator', 'mallika.moolya@gmail.com', '9000000001', 'Management', 'Active', 'NULL', 'Overall leadership and strategy', 1234, NULL, '2026-01-15 05:29:13', '2026-01-15 05:29:13'),
(4, 'Pushpa Jadhav', 'Senior Project Assistant', 'pushpa.jadhav@gmail.com', '9000000003', 'Administration', 'Active', 'Prof. Maryam', 'Payments and reimbursements', 1, NULL, '2026-01-15 05:34:38', '2026-01-15 05:34:38'),
(5, 'Bindya B', 'Senior Project Assistant', 'bindya.b@gmail.com', '9000000004', 'Administration', 'Active', 'Prof. Sandip Mondal', 'Finance and purchase handling', 2, NULL, '2026-01-15 05:34:38', '2026-01-15 05:34:38'),
(6, 'Rahul Sharma', 'IT Engineer', 'rahul.sharma@iitb.ac.in', '9000000005', 'IT', 'Active', 'Prof. Dipankar Saha', 'Network and system maintenance', 1, NULL, '2026-01-15 05:34:38', '2026-01-15 05:34:38'),
(7, 'Snehal Singh', 'Administrative Assistant', 'snehal.singh@gmail.com', '9000000006', 'IT', 'Active', 'Prof. Dipankar Saha', 'IT admin support', 5, NULL, '2026-01-15 05:34:38', '2026-01-15 05:34:38'),
(8, 'Amit Verma', 'EMT Engineer', 'amit.verma@iitb.ac.in', '9000000007', 'EMT', 'Active', 'Prof. Ashwin Tulapurkar', 'Tool maintenance and troubleshooting', 1, NULL, '2026-01-15 05:34:38', '2026-01-15 05:34:38'),
(9, 'Neha Kulkarni', 'Technical Assistant', 'neha.kulkarni@iitb.ac.in', '9000000008', 'EMT', 'Active', 'Prof. Ashwin Tulapurkar', 'Lab equipment support', 7, NULL, '2026-01-15 05:34:38', '2026-01-15 05:34:38'),
(10, 'Deepti Rukade', 'Assistant Lab Manager', 'deepti.rukade@gmail.com', '9000000009', 'HR', 'Active', 'Prof. Swaroop Ganguly', 'HR operations and payroll', 1, NULL, '2026-01-15 05:34:38', '2026-01-15 05:34:38'),
(11, 'Sarannya T S', 'HR Executive', 'sarannya.hr@iitb.ac.in', '9000000010', 'HR', 'Active', 'Prof. Swaroop Ganguly', 'Recruitment and attendance', 9, NULL, '2026-01-15 05:34:38', '2026-01-15 05:34:38');

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
