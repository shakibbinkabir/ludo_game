-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2024 at 02:51 PM
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
-- Database: `ludo_game`
--

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_code` varchar(10) NOT NULL,
  `status` enum('waiting','ongoing','completed') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_turn` int(10) UNSIGNED DEFAULT NULL,
  `last_roll` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `game_code`, `status`, `created_at`, `current_turn`, `last_roll`) VALUES
(1, '5548C9', 'waiting', '2024-10-26 11:51:32', NULL, NULL),
(2, '7CB5CA', 'waiting', '2024-10-26 11:56:58', NULL, NULL),
(3, '9DA048', 'ongoing', '2024-10-26 12:39:23', 3, NULL),
(4, '6292DB', 'ongoing', '2024-10-26 12:49:12', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `game_moves`
--

CREATE TABLE `game_moves` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `player_id` int(10) UNSIGNED NOT NULL,
  `move` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_players`
--

CREATE TABLE `game_players` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `color` enum('red','blue','green','yellow') NOT NULL,
  `position` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_players`
--

INSERT INTO `game_players` (`id`, `game_id`, `user_id`, `color`, `position`, `created_at`) VALUES
(1, 1, 1, 'red', 0, '2024-10-26 11:51:32'),
(2, 2, 1, 'red', 0, '2024-10-26 11:56:58'),
(3, 3, 1, 'red', 0, '2024-10-26 12:39:23'),
(4, 3, 2, 'blue', 0, '2024-10-26 12:39:46'),
(5, 4, 1, 'red', 0, '2024-10-26 12:49:12'),
(6, 4, 2, 'blue', 0, '2024-10-26 12:49:12');

-- --------------------------------------------------------

--
-- Table structure for table `game_tokens`
--

CREATE TABLE `game_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `player_id` int(10) UNSIGNED NOT NULL,
  `color` enum('red','blue','green','yellow') NOT NULL,
  `position` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_tokens`
--

INSERT INTO `game_tokens` (`id`, `game_id`, `player_id`, `color`, `position`) VALUES
(1, 3, 3, 'red', 0),
(2, 3, 3, 'red', 0),
(3, 3, 3, 'red', 0),
(4, 3, 3, 'red', 0),
(5, 3, 4, 'blue', 0),
(6, 3, 4, 'blue', 0),
(7, 3, 4, 'blue', 0),
(8, 3, 4, 'blue', 0),
(9, 4, 5, 'red', 0),
(10, 4, 5, 'red', 0),
(11, 4, 5, 'red', 0),
(12, 4, 5, 'red', 0),
(13, 4, 6, 'blue', 0),
(14, 4, 6, 'blue', 0),
(15, 4, 6, 'blue', 0),
(16, 4, 6, 'blue', 0);

-- --------------------------------------------------------

--
-- Table structure for table `matchmaking_queue`
--

CREATE TABLE `matchmaking_queue` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `wins` int(10) UNSIGNED DEFAULT 0,
  `losses` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `wins`, `losses`) VALUES
(1, 'shakib', 'shakibbinkabir@gmail.com', '$2y$10$F295OxMaS6Gphz6xMj8WSuibw00iBxUjTkPdGu/ZKm.nzL3kBsHla', '2024-10-26 11:51:26', 0, 0),
(2, 'akib', 'AKIBBINKABIRBD@GMAIL.COM', '$2y$10$10yO7yUl3kCdkYifdkWxAO2pULkzg8jV9xdauRGt4.MCu3DfM2xPK', '2024-10-26 12:39:40', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_code` (`game_code`);

--
-- Indexes for table `game_moves`
--
ALTER TABLE `game_moves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `game_players`
--
ALTER TABLE `game_players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `game_tokens`
--
ALTER TABLE `game_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `matchmaking_queue`
--
ALTER TABLE `matchmaking_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `game_moves`
--
ALTER TABLE `game_moves`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_players`
--
ALTER TABLE `game_players`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `game_tokens`
--
ALTER TABLE `game_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `matchmaking_queue`
--
ALTER TABLE `matchmaking_queue`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `game_moves`
--
ALTER TABLE `game_moves`
  ADD CONSTRAINT `game_moves_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_moves_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `game_players` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_players`
--
ALTER TABLE `game_players`
  ADD CONSTRAINT `game_players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_players_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_tokens`
--
ALTER TABLE `game_tokens`
  ADD CONSTRAINT `game_tokens_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_tokens_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `game_players` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `matchmaking_queue`
--
ALTER TABLE `matchmaking_queue`
  ADD CONSTRAINT `matchmaking_queue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
