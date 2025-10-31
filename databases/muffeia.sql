-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2025 at 03:42 AM
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
-- Database: `muffeia`
--

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `created_at`) VALUES
(5, 4, 4, '2025-02-24 14:06:17'),
(12, 48, 4, '2025-03-02 07:03:38'),
(13, 48, 13, '2025-03-02 07:04:05'),
(14, 51, 52, '2025-10-16 06:12:35'),
(15, 4, 52, '2025-10-21 05:03:07'),
(16, 53, 4, '2025-10-28 14:22:50');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `read_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `message_text`, `timestamp`, `is_read`, `read_status`) VALUES
(18, 11, 4, 'hoi', '2025-02-28 15:07:23', 0, 0),
(19, 11, 4, 'po', '2025-02-28 15:07:47', 0, 0),
(20, 11, 12, 'd', '2025-02-28 15:12:11', 0, 0),
(21, 11, 4, 'wsd', '2025-02-28 15:51:08', 0, 0),
(22, 12, 48, 'k', '2025-03-02 07:04:28', 0, 0),
(23, 14, 51, 'kurting', '2025-10-16 06:12:41', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `target_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`, `target_url`) VALUES
(32, 4, 'klein commented on your problem: wr', 1, '2025-03-01 07:41:21', 'view_problem.php?problem_id=36'),
(33, 4, 'klein commented on your problem: wr', 1, '2025-03-01 07:41:33', 'view_problem.php?problem_id=36'),
(34, 13, 'admin commented on your problem: df', 1, '2025-03-01 07:41:55', 'view_problem.php?problem_id=34'),
(35, 13, 'Someone commented on your problem: df', 1, '2025-03-01 07:41:59', 'view_problem.php?problem_id=34'),
(37, 4, 'Your problem has been posted successfully!', 1, '2025-10-21 05:08:24', 'view_problem.php?problem_id=45'),
(38, 53, 'Your problem has been posted successfully!', 0, '2025-10-28 14:23:46', 'view_problem.php?problem_id=46'),
(39, 54, 'Your problem has been posted successfully!', 0, '2025-10-29 00:58:08', 'view_problem.php?problem_id=47'),
(40, 54, 'Your problem has been posted successfully!', 0, '2025-10-29 01:29:11', 'view_problem.php?problem_id=48'),
(41, 54, 'admin commented on your problem: hello world!', 0, '2025-10-29 02:10:33', 'view_problem.php?problem_id=48'),
(42, 54, 'admin commented on your problem: hello world!', 0, '2025-10-29 02:10:45', 'view_problem.php?problem_id=48');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `problem_id`, `user_id`, `created_at`) VALUES
(1, 47, 54, '2025-10-29 02:37:39'),
(2, 48, 54, '2025-10-29 02:37:41'),
(3, 48, 4, '2025-10-29 02:38:03');

-- --------------------------------------------------------

--
-- Table structure for table `post_shares`
--

CREATE TABLE `post_shares` (
  `id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `problems`
--

CREATE TABLE `problems` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `anonymous` tinyint(1) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `views_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `problems`
--

INSERT INTO `problems` (`id`, `user_id`, `title`, `description`, `created_at`, `anonymous`, `image`, `views_count`) VALUES
(33, 13, 'bleh', 'bleh', '2025-03-01 07:35:02', 1, '', 0),
(34, 13, 'df', 'fdfd', '2025-03-01 07:35:30', 0, '', 0),
(36, 4, 'wr', 'erer', '2025-03-01 07:40:43', 0, '', 0),
(37, 48, 'Islaaaaaaaa', 'cutieeeee', '2025-03-01 13:07:13', 0, 'uploads/1740834433_aefa3d38-e6b0-4914-8232-d68b0a7a8f12.jpg', 0),
(38, 4, 'Islaaaaaaaa', 'f', '2025-03-01 14:05:26', 0, '', 0),
(39, 4, 'isla', 'cutie', '2025-03-01 15:21:59', 1, 'uploads/1740842519_aefa3d38-e6b0-4914-8232-d68b0a7a8f12.jpg', 0),
(43, 48, 'sdfds', 'df', '2025-03-02 13:41:03', 0, '', 0),
(45, 4, 'beatch', 'beatchessssssssssssssssssssssss', '2025-10-21 05:08:24', 0, NULL, 0),
(46, 53, 'makauang', 'hello world!', '2025-10-28 14:23:46', 0, NULL, 0),
(47, 54, 'luhh', 'Luhh\r\n', '2025-10-29 00:58:08', 1, NULL, 0),
(48, 54, 'hello world!', '!dlrow olleh', '2025-10-29 01:29:11', 0, NULL, 20);

-- --------------------------------------------------------

--
-- Table structure for table `reactions`
--

CREATE TABLE `reactions` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` enum('like','love','haha','wow','sad','angry') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solutions`
--

CREATE TABLE `solutions` (
  `id` int(11) NOT NULL,
  `problem_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `solution_text` text NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `solutions`
--

INSERT INTO `solutions` (`id`, `problem_id`, `user_id`, `solution_text`, `is_anonymous`, `created_at`) VALUES
(17, 36, 13, 'rerer', 0, '2025-03-01 07:41:21'),
(18, 36, 13, 'erer', 0, '2025-03-01 07:41:33'),
(19, 34, 4, 'rrytryty43t', 0, '2025-03-01 07:41:55'),
(20, 34, 4, 'gdfgfd', 1, '2025-03-01 07:41:59'),
(21, 48, 4, 'hi my world!', 0, '2025-10-29 02:10:33'),
(22, 48, 4, 'oi', 0, '2025-10-29 02:10:45'),
(23, 48, 54, 'thanks', 1, '2025-10-29 02:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `solution_reactions`
--

CREATE TABLE `solution_reactions` (
  `id` int(11) NOT NULL,
  `solution_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `solution_reactions`
--

INSERT INTO `solution_reactions` (`id`, `solution_id`, `user_id`, `reaction_type`, `created_at`) VALUES
(1, 23, 54, 'like', '2025-10-29 02:18:14');

-- --------------------------------------------------------

--
-- Table structure for table `solution_replies`
--

CREATE TABLE `solution_replies` (
  `id` int(11) NOT NULL,
  `solution_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reply_text` text NOT NULL,
  `is_anonymous` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `solution_replies`
--

INSERT INTO `solution_replies` (`id`, `solution_id`, `user_id`, `reply_text`, `is_anonymous`, `created_at`) VALUES
(1, 23, 54, 'hihi', 0, '2025-10-29 02:18:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `csrf_token` varchar(64) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `role` enum('user','eia','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`, `profile_pic`, `csrf_token`, `admin_id`, `role`) VALUES
(1, 'klein', 'muffy@muffy.com', '$argon2id$v=19$m=65536,t=4,p=1$a2k1MHY2RDQyL0V6eVBrQg$YHl8QWecMJheO7wO1HtP928a9xd+unwSomxIre628Bg', '2025-03-01 05:38:47', 'default.png', '', NULL, 'user'),
(2, 'Erica', 'ekang@ekang.com', '$argon2id$v=19$m=65536,t=4,p=1$LldNa1RYblRZQTk0ZzhYUg$uzq4XcKkTBw8grT6qr0/co0qr6Vg1++yqEOTVYXXOO8', '2025-03-01 12:56:41', 'profile_pics/06ee6a27-7689-48a4-9c71-eb574eafc6fe.jpg', '', NULL, 'user'),
(4, 'admin', 'admin@admin.com', '$2y$10$q2wK3l04Cgd/QLnsabTYv.I9Rf3cEZQdJIAxvKf3y8AVCx2S0efle', '2025-02-24 12:55:07', 'profile_pics/2811f686-5d6b-4b37-b7bc-82900001ee4c.jpg', '', 1, 'eia'),
(51, 'mvff', 'kleinricm@gmail.com', '$2y$10$S24ob9TFWzU1DDQ.bDNnaO/bHVkni/U6kYxUUZDs2qIh9K64zf5va', '2025-10-16 06:07:12', 'default.png', '', NULL, 'user'),
(52, 'truk', 'tinaspala.09@gmail.com', '$2y$10$I9P1d4FlG8hhh9aQvhn2Ze3DFHDu88EUkeFQ.HploxvOsosYKpSbC', '2025-10-16 06:12:07', 'default.png', '', NULL, 'user'),
(53, 'mvv', 'mvf@mv.com', '$2y$10$zYbz25WjKoaGDe0tZD05J.XtpblbxUjlkvpLhAjqPIDXhbXKQZYAu', '2025-10-28 14:21:54', 'default.png', '', NULL, 'user'),
(54, 'meee', 'me@me.com', '$2y$10$QWg07pixZBQKRZunQeroC.EIwWBVRCbTpZPvuwNiAwJmVTfwyFRPW', '2025-10-29 00:56:10', 'default.png', '', NULL, 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user1_id` (`user1_id`),
  ADD KEY `user2_id` (`user2_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`problem_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_shares`
--
ALTER TABLE `post_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `problem_id` (`problem_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `problems`
--
ALTER TABLE `problems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `solutions`
--
ALTER TABLE `solutions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `solutions_ibfk_1` (`problem_id`);

--
-- Indexes for table `solution_reactions`
--
ALTER TABLE `solution_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`solution_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `solution_replies`
--
ALTER TABLE `solution_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solution_id` (`solution_id`),
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
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `post_shares`
--
ALTER TABLE `post_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `problems`
--
ALTER TABLE `problems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solutions`
--
ALTER TABLE `solutions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `solution_reactions`
--
ALTER TABLE `solution_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `solution_replies`
--
ALTER TABLE `solution_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`problem_id`) REFERENCES `problems` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_shares`
--
ALTER TABLE `post_shares`
  ADD CONSTRAINT `post_shares_ibfk_1` FOREIGN KEY (`problem_id`) REFERENCES `problems` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_shares_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `reactions_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `solution_reactions`
--
ALTER TABLE `solution_reactions`
  ADD CONSTRAINT `solution_reactions_ibfk_1` FOREIGN KEY (`solution_id`) REFERENCES `solutions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solution_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `solution_replies`
--
ALTER TABLE `solution_replies`
  ADD CONSTRAINT `solution_replies_ibfk_1` FOREIGN KEY (`solution_id`) REFERENCES `solutions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solution_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
