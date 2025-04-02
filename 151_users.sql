-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2025 at 09:42 PM
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
-- Database: `151_users`
--

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `created_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `title`, `description`, `status`, `priority`, `created_by`, `assigned_to`, `created_at`, `updated_at`) VALUES
(3, 'Alles kaputt', 'HalliHallo\r\nHabe Word in Pink bitte ändern\r\nTel. 0800 800 80 80\r\nUnd ich habe das Internet gelöscht - Kann nicht mehr googlen\r\n\r\nDringender Rückruf sofort Ich bezahle nicht für nichts\r\n\r\nLG Ciao Ciao', 'in_progress', 'critical', 2, 1, '2025-04-02 08:37:26', '2025-04-02 08:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_comments`
--

CREATE TABLE `ticket_comments` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `ticket_comments`
--

INSERT INTO `ticket_comments` (`id`, `ticket_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 1, 1, 'People are like bananas...', '2025-04-02 05:17:39'),
(2, 1, 2, 'Hello', '2025-04-02 08:27:37');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_history`
--

CREATE TABLE `ticket_history` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `field_changed` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `ticket_history`
--

INSERT INTO `ticket_history` (`id`, `ticket_id`, `user_id`, `field_changed`, `old_value`, `new_value`, `changed_at`) VALUES
(1, 1, 1, 'created', NULL, 'Ticket created', '2025-04-01 19:47:55'),
(2, 1, 2, 'assigned_to', 'Unassigned', 'WantedHipster', '2025-04-01 20:00:06'),
(3, 2, 1, 'created', NULL, 'Ticket created', '2025-04-02 08:26:39'),
(4, 2, 2, 'status', 'open', 'closed', '2025-04-02 08:27:52'),
(5, 3, 2, 'created', NULL, 'Ticket created', '2025-04-02 08:37:26'),
(6, 3, 2, 'status', 'open', 'in_progress', '2025-04-02 08:37:57'),
(7, 3, 2, 'assigned_to', 'Unassigned', 'Unassigned', '2025-04-02 08:37:58'),
(8, 3, 2, 'status', 'in_progress', 'open', '2025-04-02 08:38:08'),
(9, 3, 2, 'assigned_to', 'Unassigned', 'WantedHipster', '2025-04-02 08:38:12'),
(10, 3, 2, 'status', 'open', 'in_progress', '2025-04-02 08:38:22'),
(11, 1, 2, 'status', 'open', 'in_progress', '2025-04-02 09:10:14'),
(12, 1, 2, 'assigned_to', 'WantedHipster', 'WantedHipsterA', '2025-04-02 09:10:14'),
(13, 1, 2, 'assigned_to', 'WantedHipsterA', 'WantedHipster', '2025-04-02 09:10:18'),
(14, 4, 3, 'created', NULL, 'Ticket created', '2025-04-02 14:03:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `firstname` varchar(30) NOT NULL,
  `lastname` varchar(30) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'Robert', 'Nemcsok', 'WantedHipster', '$2y$10$b0mtbYIA9rkeH09mNYajaunrK6KSaI3qB15t33MIXGnnrwhcnbH7G', 'robikajatek@gmail.com', 'user', '2025-04-02 08:34:23'),
(2, 'Robert', 'Nemcsok', 'WantedHipsterA', '$2y$10$BvZrcyICa4PSndcPRJvSdOLH.eL3YpJIUDAfH1oHTrgubWKtY.LEW', 'robikajatek@gmail.com', 'admin', '2025-04-02 08:34:23'),
(3, 'Jerome', 'Chesworth', 'chesworth', '$2y$10$bhP6U7DXtD34xPMrKIsQ8eCRAnnY4ljohb2JTSd1tcLGRuzymF7QC', 'JEROME.CHESWORTH@BBZBL-IT.CH', 'admin', '2025-04-02 14:02:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ticket_history`
--
ALTER TABLE `ticket_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
