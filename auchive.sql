-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 07:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `auchive`
--

-- --------------------------------------------------------

--
-- Table structure for table `bubbles`
--

CREATE TABLE `bubbles` (
  `bubble_id` int(11) NOT NULL,
  `chapter_id` int(11) NOT NULL,
  `bubble_text` text DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `theme` varchar(100) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `position` enum('left','right','center') DEFAULT 'left',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bubble_image`
--

CREATE TABLE `bubble_image` (
  `bubble_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `display_order` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chapters`
--

CREATE TABLE `chapters` (
  `chapter_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `chapter_title` varchar(200) NOT NULL,
  `chapter_text` longtext DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `chapter_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `comment_like` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followers`
--

CREATE TABLE `followers` (
  `follow_id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `followed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `genre_id` int(11) NOT NULL,
  `genre_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`genre_id`, `genre_name`) VALUES
(1, 'Mystery');

-- --------------------------------------------------------

--
-- Table structure for table `image_chat`
--

CREATE TABLE `image_chat` (
  `image_id` int(11) NOT NULL,
  `img_link` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library`
--

CREATE TABLE `library` (
  `library_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_stories`
--

CREATE TABLE `library_stories` (
  `library_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_chapter_id` int(11) DEFAULT NULL,
  `progress_percent` decimal(5,2) DEFAULT 0.00,
  `is_complete` tinyint(1) DEFAULT 0,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE `stories` (
  `story_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `genre_id` int(11) NOT NULL,
  `status` enum('ongoing','completed','hiatus') DEFAULT 'ongoing',
  `total_views` int(11) DEFAULT 0,
  `total_likes` int(11) DEFAULT 0,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cover` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stories`
--

INSERT INTO `stories` (`story_id`, `user_id`, `title`, `description`, `genre_id`, `status`, `total_views`, `total_likes`, `published_at`, `cover`) VALUES
(2, 1, 'Mama', 'I love you', 1, 'ongoing', 0, 0, '2026-05-09 17:24:13', 'Uploads/covers/cover_69ff6dbd0c857.png');

-- --------------------------------------------------------

--
-- Table structure for table `story_tags`
--

CREATE TABLE `story_tags` (
  `story_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `profile_ban` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `name`, `email`, `password`, `profile_pic`, `profile_ban`, `bio`, `birth_date`, `role`, `created_at`) VALUES
(1, 'testuser', 'Test User', 'test@test.com', '12345', NULL, NULL, NULL, NULL, 'user', '2026-05-09 17:24:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bubbles`
--
ALTER TABLE `bubbles`
  ADD PRIMARY KEY (`bubble_id`),
  ADD KEY `fk_bubbles_chapter` (`chapter_id`);

--
-- Indexes for table `bubble_image`
--
ALTER TABLE `bubble_image`
  ADD PRIMARY KEY (`bubble_id`,`image_id`),
  ADD KEY `fk_bubble_image_image` (`image_id`);

--
-- Indexes for table `chapters`
--
ALTER TABLE `chapters`
  ADD PRIMARY KEY (`chapter_id`),
  ADD KEY `fk_chapters_story` (`story_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_comments_user` (`user_id`),
  ADD KEY `fk_comments_chapter` (`chapter_id`);

--
-- Indexes for table `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`follow_id`),
  ADD UNIQUE KEY `follower_id` (`follower_id`,`following_id`),
  ADD KEY `fk_followers_following` (`following_id`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`genre_id`),
  ADD UNIQUE KEY `genre_name` (`genre_name`);

--
-- Indexes for table `image_chat`
--
ALTER TABLE `image_chat`
  ADD PRIMARY KEY (`image_id`);

--
-- Indexes for table `library`
--
ALTER TABLE `library`
  ADD PRIMARY KEY (`library_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `library_stories`
--
ALTER TABLE `library_stories`
  ADD PRIMARY KEY (`library_id`,`story_id`),
  ADD KEY `fk_library_stories_story` (`story_id`),
  ADD KEY `fk_library_stories_chapter` (`last_read_chapter_id`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`story_id`),
  ADD KEY `fk_stories_user` (`user_id`),
  ADD KEY `fk_stories_genre` (`genre_id`);

--
-- Indexes for table `story_tags`
--
ALTER TABLE `story_tags`
  ADD PRIMARY KEY (`story_id`,`tag_id`),
  ADD KEY `fk_story_tags_tag` (`tag_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `tag_name` (`tag_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bubbles`
--
ALTER TABLE `bubbles`
  MODIFY `bubble_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chapters`
--
ALTER TABLE `chapters`
  MODIFY `chapter_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `followers`
--
ALTER TABLE `followers`
  MODIFY `follow_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `genre_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `image_chat`
--
ALTER TABLE `image_chat`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library`
--
ALTER TABLE `library`
  MODIFY `library_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `story_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bubbles`
--
ALTER TABLE `bubbles`
  ADD CONSTRAINT `fk_bubbles_chapter` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`chapter_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bubble_image`
--
ALTER TABLE `bubble_image`
  ADD CONSTRAINT `fk_bubble_image_bubble` FOREIGN KEY (`bubble_id`) REFERENCES `bubbles` (`bubble_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bubble_image_image` FOREIGN KEY (`image_id`) REFERENCES `image_chat` (`image_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chapters`
--
ALTER TABLE `chapters`
  ADD CONSTRAINT `fk_chapters_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_chapter` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`chapter_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `fk_followers_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_followers_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `library`
--
ALTER TABLE `library`
  ADD CONSTRAINT `fk_library_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `library_stories`
--
ALTER TABLE `library_stories`
  ADD CONSTRAINT `fk_library_stories_chapter` FOREIGN KEY (`last_read_chapter_id`) REFERENCES `chapters` (`chapter_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_library_stories_library` FOREIGN KEY (`library_id`) REFERENCES `library` (`library_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_library_stories_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stories`
--
ALTER TABLE `stories`
  ADD CONSTRAINT `fk_stories_genre` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`genre_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `story_tags`
--
ALTER TABLE `story_tags`
  ADD CONSTRAINT `fk_story_tags_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_story_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
