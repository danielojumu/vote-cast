-- =======================================================
-- BUI Beta Set Alumni Election Voting System (VCAST)
-- Database Schema for MySQL 8+
-- Target Host: Hostinger Business Web Hosting
-- Base URL: https://vote.buibetaset.org
-- =======================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS voters;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS admins;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------
-- Table 1: admins
-- -------------------------------------------------------
CREATE TABLE `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('Super Admin', 'Electoral Commissioner', 'Auditor') NOT NULL DEFAULT 'Electoral Commissioner',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table 2: registrations
-- -------------------------------------------------------
CREATE TABLE `registrations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `graduation_year` INT UNSIGNED NOT NULL,
  `id_type` ENUM('Student ID', 'Passport', 'National ID', 'Personal Code') NOT NULL,
  `id_value` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_reg_email` (`email`),
  INDEX `idx_reg_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table 3: voters
-- -------------------------------------------------------
CREATE TABLE `voters` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `registration_id` INT UNSIGNED NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `voting_token` VARCHAR(32) NOT NULL UNIQUE,
  `token_sent` TINYINT(1) NOT NULL DEFAULT 1,
  `token_sent_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `has_voted` TINYINT(1) NOT NULL DEFAULT 0,
  `voted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`) ON DELETE CASCADE,
  INDEX `idx_voter_token` (`voting_token`),
  INDEX `idx_voter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table 4: positions
-- -------------------------------------------------------
CREATE TABLE `positions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table 5: candidates
-- -------------------------------------------------------
CREATE TABLE `candidates` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `position_id` INT UNSIGNED NOT NULL,
  `fullname` VARCHAR(150) NOT NULL,
  `manifesto` TEXT NOT NULL,
  `profile` TEXT NULL,
  `university_photo` VARCHAR(255) NULL,
  `recent_photo` VARCHAR(255) NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE,
  INDEX `idx_candidate_position` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table 6: votes
-- -------------------------------------------------------
CREATE TABLE `votes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `voter_id` INT UNSIGNED NOT NULL,
  `candidate_id` INT UNSIGNED NOT NULL,
  `position_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_voter_position` (`voter_id`, `position_id`),
  FOREIGN KEY (`voter_id`) REFERENCES `voters`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table 7: audit_logs
-- -------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user` VARCHAR(150) NOT NULL DEFAULT 'System',
  `action` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- SEED DATA
-- =======================================================

-- Default Admin Account (password: Admin@2026!)
INSERT INTO `admins` (`fullname`, `email`, `password`, `role`) VALUES
('Electoral Chairman', 'admin@vote.buibetaset.org', '$2y$10$fV3GsnT1zT2QvjN9YxHq/eXmY7wY09kZkC8Q.8C3eL/xG3C2q3b/S', 'Super Admin');

-- Seed Executive Positions
INSERT INTO `positions` (`id`, `name`, `display_order`, `active`) VALUES
(1, 'President', 1, 1),
(2, 'Vice President', 2, 1),
(3, 'Secretary', 3, 1),
(4, 'Treasurer', 4, 1);

-- Seed Candidates
INSERT INTO `candidates` (`position_id`, `fullname`, `manifesto`, `profile`, `university_photo`, `recent_photo`, `display_order`, `active`) VALUES
(1, 'Dr. Samuel O. Adebayo', 'Strengthening alumni global networks, driving mentorship programs for recent graduates, and establishing the BUI Beta Innovation Endowment Fund.', 'Class of 2008 Computer Science graduate. Former Student Union President with 14 years experience in tech leadership.', '/uploads/candidates/samuel_uni.jpg', '/uploads/candidates/samuel_recent.jpg', 1, 1),
(1, 'Engr. Fatima Bello', 'Building transparent digital governance, fostering alumni startup grants, and organizing annual international homecoming conventions.', 'Class of 2007 Electrical Engineering graduate. Senior Engineering Director & STEM advocate.', '/uploads/candidates/fatima_uni.jpg', '/uploads/candidates/fatima_recent.jpg', 2, 1),

(2, 'Michael Chen', 'Expanding regional alumni chapters across North America, Europe, and Asia, and introducing professional development webinars.', 'Class of 2009 Business Administration graduate. VP of Operations at Global Logistics Corp.', '/uploads/candidates/michael_uni.jpg', '/uploads/candidates/michael_recent.jpg', 1, 1),
(2, 'Dr. Amina K. Yusuf', 'Enhancing healthcare support initiatives for alumni members and launching the Beta Set Mentorship Portal.', 'Class of 2008 Biochemistry graduate. Consultant Physician and Health Systems Specialist.', '/uploads/candidates/amina_uni.jpg', '/uploads/candidates/amina_recent.jpg', 2, 1),

(3, 'Chidi O. Nwosu', 'Automating alumni record management, launching monthly newsletters, and maintaining an open-access meeting archive.', 'Class of 2007 Mass Communication graduate. Corporate Communications Lead & Tech Editor.', '/uploads/candidates/chidi_uni.jpg', '/uploads/candidates/chidi_recent.jpg', 1, 1),
(3, 'Grace E. Taylor', 'Ensuring prompt communication, digital voter registration for future polls, and structured committee reporting.', 'Class of 2009 Law graduate. Principal Partner at Taylor & Co. Legal Chambers.', '/uploads/candidates/grace_uni.jpg', '/uploads/candidates/grace_recent.jpg', 2, 1),

(4, 'Oluwaseun Vance', 'Quarterly audited financial reporting, high-yield treasury management, and automated alumni dues tracking.', 'Class of 2008 Accounting graduate. Senior Chartered Accountant & Financial Analyst.', '/uploads/candidates/seun_uni.jpg', '/uploads/candidates/seun_recent.jpg', 1, 1),
(4, 'David K. Mensah', 'Implementing zero-fee transaction portals, transparent budget allocations, and welfare assistance funds.', 'Class of 2007 Economics graduate. Chief Financial Officer at Zenith Capital.', '/uploads/candidates/david_uni.jpg', '/uploads/candidates/david_recent.jpg', 2, 1);

-- Initial Audit Log
INSERT INTO `audit_logs` (`user`, `action`, `ip_address`) VALUES
('System', 'Database initialized and seeded with default positions and candidates.', '127.0.0.1');
