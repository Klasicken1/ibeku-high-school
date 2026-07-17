-- ============================================================
-- IBEKU HIGH SCHOOL — DATABASE SCHEMA
-- File: database/schema.sql
--
-- Structure-only export (no data) — recreates the empty
-- database schema on a fresh server. Import this into a new
-- MariaDB/MySQL database to set up all 33 tables, indexes,
-- and foreign key constraints in one pass.
--
-- Usage:
--   1. Create an empty database (e.g. `ibeku_school`)
--   2. Import this file via phpMyAdmin, or:
--        mysql -u root -p ibeku_school < database/schema.sql
--   3. Copy src/config/vapid.php.example to vapid.php and
--      fill in real VAPID keys (see that file for generation
--      instructions)
--   4. Configure src/config/database.php with DB credentials
--
-- This file intentionally contains NO data — only structure.
-- Regenerate it after any schema change via phpMyAdmin's
-- Export tab (Custom export, Structure only, uncheck every
-- table's Data checkbox).
-- ============================================================

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2026 at 11:26 PM
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
-- Database: `ibeku_school`
--

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_first` varchar(100) NOT NULL,
  `parent_last` varchar(100) NOT NULL,
  `parent_email` varchar(150) NOT NULL,
  `parent_phone` varchar(20) NOT NULL,
  `student_first` varchar(100) NOT NULL,
  `student_last` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `entry_class` enum('JSS1','SSS1') NOT NULL,
  `session` varchar(12) NOT NULL,
  `previous_school` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','assessed','admitted','declined') NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL COMMENT 'Internal admin notes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alumni`
--

CREATE TABLE `alumni` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `class_year` varchar(12) DEFAULT NULL,
  `field` varchar(150) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `awards`
--

CREATE TABLE `awards` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `year_label` varchar(50) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `badge_text` varchar(100) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_arms`
--

CREATE TABLE `class_arms` (
  `id` int(10) UNSIGNED NOT NULL,
  `grade_level` enum('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') NOT NULL,
  `class` varchar(5) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `patron` varchar(150) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `read_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `corps_clearance`
--

CREATE TABLE `corps_clearance` (
  `id` int(10) UNSIGNED NOT NULL,
  `corps_member_id` int(10) UNSIGNED NOT NULL,
  `month` tinyint(3) UNSIGNED NOT NULL COMMENT '1=January to 12=December',
  `year` year(4) NOT NULL,
  `is_cleared` tinyint(1) NOT NULL DEFAULT 0,
  `cleared_by` int(10) UNSIGNED DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `corps_members`
--

CREATE TABLE `corps_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `state_code` varchar(20) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `state_of_origin` varchar(100) NOT NULL,
  `batch` varchar(20) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `course_studied` varchar(255) NOT NULL,
  `cds_group` varchar(100) DEFAULT NULL,
  `cds_day` varchar(20) DEFAULT NULL,
  `subject_taught` varchar(150) DEFAULT NULL,
  `section` enum('js','ss','both') NOT NULL DEFAULT 'both',
  `class_arms` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` enum('active','passed_out','deleted') NOT NULL DEFAULT 'active',
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `status_changed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `corps_messages`
--

CREATE TABLE `corps_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `corps_member_id` int(10) UNSIGNED NOT NULL,
  `sender_type` enum('admin','corps') NOT NULL,
  `sender_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'users.id if admin, NULL if corps',
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL for new thread, parent message id for reply',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('academic','sports','culture','examination','meeting','holiday','general') NOT NULL DEFAULT 'general',
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` enum('sports','events','classrooms','graduation','culture','assembly','ict','general') NOT NULL DEFAULT 'general',
  `filename` varchar(255) NOT NULL COMMENT 'Stored filename on server',
  `original_name` varchar(255) DEFAULT NULL COMMENT 'Original upload filename',
  `caption` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hall_of_fame`
--

CREATE TABLE `hall_of_fame` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `category` enum('alumni','academic','sports','prefect','staff') NOT NULL,
  `class_year` varchar(12) DEFAULT NULL COMMENT 'e.g. Class of 2005',
  `field` varchar(150) DEFAULT NULL COMMENT 'e.g. Medicine, Law, Engineering',
  `achievement` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `nominated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hall_of_fame_nominations`
--

CREATE TABLE `hall_of_fame_nominations` (
  `id` int(10) UNSIGNED NOT NULL,
  `nominator_name` varchar(150) NOT NULL,
  `nominator_email` varchar(150) NOT NULL,
  `nominee_name` varchar(150) NOT NULL,
  `nominee_class_year` varchar(12) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('new','reviewed','converted','declined') NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `milestones`
--

CREATE TABLE `milestones` (
  `id` int(10) UNSIGNED NOT NULL,
  `era_label` varchar(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(300) NOT NULL,
  `slug` varchar(320) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `meta_title` varchar(300) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `body` longtext NOT NULL,
  `category` enum('achievement','academic','ict','sports','announcement','culture','general') NOT NULL DEFAULT 'general',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL COMMENT 'Path to featured image',
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `author_id` int(10) UNSIGNED DEFAULT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prefects`
--

CREATE TABLE `prefects` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` varchar(100) NOT NULL COMMENT 'e.g. Head Boy, Sports Prefect',
  `section` enum('ss','js') NOT NULL,
  `session` varchar(12) NOT NULL COMMENT 'e.g. 2024/2025',
  `quote` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `push_broadcast_log`
--

CREATE TABLE `push_broadcast_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `sent_to` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `auth` varchar(100) NOT NULL,
  `p256dh` varchar(200) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `corps_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `session` varchar(12) NOT NULL COMMENT 'e.g. 2024/2025',
  `term` enum('first','second','third') NOT NULL,
  `grade_level` enum('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') NOT NULL,
  `class` varchar(5) DEFAULT NULL,
  `class_total_students` int(10) UNSIGNED DEFAULT NULL,
  `grade_level_total_students` int(10) UNSIGNED DEFAULT NULL,
  `class_position` int(10) UNSIGNED DEFAULT NULL,
  `grade_level_position` int(10) UNSIGNED DEFAULT NULL,
  `average_score` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(7,2) DEFAULT NULL,
  `form_teacher_comment` text DEFAULT NULL,
  `principal_comment` text DEFAULT NULL,
  `next_term_resumption` date DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=draft, 1=published',
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `published_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_scores`
--

CREATE TABLE `result_scores` (
  `id` int(10) UNSIGNED NOT NULL,
  `result_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `ca1_score` decimal(4,1) NOT NULL DEFAULT 0.0 COMMENT '1st test — max 15',
  `ca2_score` decimal(4,1) NOT NULL DEFAULT 0.0 COMMENT '2nd test — max 15',
  `exam_score` decimal(4,1) NOT NULL DEFAULT 0.0 COMMENT 'Exam — max 70',
  `total_score` decimal(5,1) GENERATED ALWAYS AS (`ca1_score` + `ca2_score` + `exam_score`) STORED,
  `grade` varchar(3) DEFAULT NULL COMMENT 'A1, B2 ... F9 — computed on save',
  `remark` varchar(20) DEFAULT NULL COMMENT 'Excellent, Very Good ... Fail',
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'Subject teacher who uploaded',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `reviewer_name` varchar(150) NOT NULL,
  `reviewer_email` varchar(150) NOT NULL,
  `relationship` enum('parent','student','alumnus','staff','visitor') NOT NULL DEFAULT 'visitor',
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `review_text` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `verification_token` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `eligibility` text DEFAULT NULL,
  `contact_info` varchar(200) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `color_theme` enum('purple','blue','gold') NOT NULL DEFAULT 'purple',
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `section` enum('ss','js','both') NOT NULL DEFAULT 'both',
  `category` enum('administration','sciences','arts','commercial','support') NOT NULL DEFAULT 'support',
  `bio` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_messages`
--

CREATE TABLE `staff_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED DEFAULT NULL,
  `recipient_id` int(10) UNSIGNED NOT NULL,
  `type` enum('direct','system','results_workflow') NOT NULL DEFAULT 'direct',
  `subject` varchar(200) DEFAULT NULL,
  `body` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `admission_number` varchar(30) NOT NULL COMMENT 'e.g. IHS/2024/0421',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `date_of_birth` date NOT NULL,
  `section` enum('ss','js') NOT NULL,
  `grade_level` enum('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') NOT NULL,
  `class` varchar(5) NOT NULL,
  `department` enum('sciences','arts','commercial','general') NOT NULL DEFAULT 'general',
  `date_admitted` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = graduated or left',
  `status` enum('active','expelled','graduated','deceased','transferred') NOT NULL DEFAULT 'active',
  `status_reason` text DEFAULT NULL,
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `status_changed_by` int(10) UNSIGNED DEFAULT NULL,
  `parent_name` varchar(150) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `parent_email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL COMMENT 'Path to student photo',
  `password` varchar(255) DEFAULT NULL,
  `portal_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `portal_blocked_reason` text DEFAULT NULL,
  `portal_blocked_by` int(10) UNSIGNED DEFAULT NULL,
  `portal_blocked_at` timestamp NULL DEFAULT NULL,
  `results_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `results_blocked_reason` text DEFAULT NULL,
  `results_blocked_by` int(10) UNSIGNED DEFAULT NULL,
  `results_blocked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_history`
--

CREATE TABLE `student_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `event_type` enum('promotion','retention','demotion','expulsion','graduation','reinstatement') NOT NULL,
  `from_grade_level` enum('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') DEFAULT NULL,
  `from_class` varchar(5) DEFAULT NULL,
  `to_grade_level` enum('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') DEFAULT NULL,
  `to_class` varchar(5) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `type` enum('suspension','expulsion','promotion','demotion','retention','behavioural_remark') NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `issued_by` int(10) UNSIGNED NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL COMMENT 'e.g. ENG, MTH, PHY',
  `department` enum('sciences','arts','commercial','general','all') NOT NULL DEFAULT 'all',
  `section` enum('ss','js','both') NOT NULL DEFAULT 'both',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class_assignments`
--

CREATE TABLE `teacher_class_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `grade_level` enum('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') NOT NULL,
  `class` varchar(5) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int(10) UNSIGNED NOT NULL,
  `class` enum('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') NOT NULL,
  `session` varchar(12) NOT NULL COMMENT 'e.g. 2024/2025',
  `term` enum('first','second','third') NOT NULL DEFAULT 'first',
  `filename` varchar(255) NOT NULL COMMENT 'Stored filename — fixed per class',
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'bcrypt hash — never plaintext',
  `role` enum('superadmin','principal','vp_admin','vp_academics','vp_general','dean','counselor','hod','form_teacher','subject_teacher') NOT NULL,
  `section` enum('ss','js','both') NOT NULL DEFAULT 'both',
  `department` varchar(100) DEFAULT NULL COMMENT 'For hod and subject_teacher',
  `class_assigned` varchar(20) DEFAULT NULL COMMENT 'e.g. JSS2A — for form_teacher',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `closed_reason` enum('retired','transferred','deceased','expelled','graduated','other') DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` int(10) UNSIGNED DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admissions_status` (`status`),
  ADD KEY `idx_admissions_class` (`entry_class`),
  ADD KEY `idx_admissions_session` (`session`),
  ADD KEY `idx_admissions_created` (`created_at`);

--
-- Indexes for table `alumni`
--
ALTER TABLE `alumni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `is_published` (`is_published`);

--
-- Indexes for table `awards`
--
ALTER TABLE `awards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_arms`
--
ALTER TABLE `class_arms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_class_arm` (`grade_level`,`class`),
  ADD KEY `idx_class_arms_class` (`grade_level`),
  ADD KEY `idx_class_arms_active` (`is_active`);

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_read` (`is_read`),
  ADD KEY `idx_contact_created` (`created_at`),
  ADD KEY `fk_contact_reader` (`read_by`);

--
-- Indexes for table `corps_clearance`
--
ALTER TABLE `corps_clearance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clearance` (`corps_member_id`,`month`,`year`),
  ADD KEY `idx_corps` (`corps_member_id`);

--
-- Indexes for table `corps_members`
--
ALTER TABLE `corps_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_state_code` (`state_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_batch` (`batch`);

--
-- Indexes for table `corps_messages`
--
ALTER TABLE `corps_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_corps` (`corps_member_id`,`is_read`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_date` (`event_date`),
  ADD KEY `idx_events_category` (`category`),
  ADD KEY `idx_events_published` (`is_published`),
  ADD KEY `idx_events_featured` (`is_featured`),
  ADD KEY `fk_events_creator` (`created_by`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gallery_category` (`category`),
  ADD KEY `idx_gallery_published` (`is_published`),
  ADD KEY `idx_gallery_sort` (`sort_order`),
  ADD KEY `fk_gallery_uploader` (`uploaded_by`);

--
-- Indexes for table `hall_of_fame`
--
ALTER TABLE `hall_of_fame`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hof_category` (`category`),
  ADD KEY `idx_hof_published` (`is_published`),
  ADD KEY `idx_hof_sort` (`sort_order`);

--
-- Indexes for table `hall_of_fame_nominations`
--
ALTER TABLE `hall_of_fame_nominations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `milestones`
--
ALTER TABLE `milestones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_news_slug` (`slug`),
  ADD KEY `idx_news_category` (`category`),
  ADD KEY `idx_news_published` (`is_published`),
  ADD KEY `idx_news_featured` (`featured`),
  ADD KEY `idx_news_published_at` (`published_at`),
  ADD KEY `fk_news_author` (`author_id`);

--
-- Indexes for table `prefects`
--
ALTER TABLE `prefects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prefects_section` (`section`),
  ADD KEY `idx_prefects_session` (`session`),
  ADD KEY `idx_prefects_active` (`is_active`),
  ADD KEY `idx_prefects_sort` (`sort_order`);

--
-- Indexes for table `push_broadcast_log`
--
ALTER TABLE `push_broadcast_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_endpoint` (`endpoint`(255)),
  ADD KEY `idx_push_user` (`user_id`),
  ADD KEY `idx_push_corps` (`corps_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_results_student_term` (`student_id`,`session`,`term`),
  ADD KEY `idx_results_session` (`session`),
  ADD KEY `idx_results_term` (`term`),
  ADD KEY `idx_results_class` (`grade_level`),
  ADD KEY `idx_results_published` (`is_published`),
  ADD KEY `fk_results_publisher` (`published_by`),
  ADD KEY `fk_results_approved_by` (`approved_by`);

--
-- Indexes for table `result_scores`
--
ALTER TABLE `result_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_scores_result_subject` (`result_id`,`subject_id`),
  ADD KEY `idx_scores_result` (`result_id`),
  ADD KEY `idx_scores_subject` (`subject_id`),
  ADD KEY `fk_scores_uploader` (`uploaded_by`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `is_verified` (`is_verified`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section` (`section`),
  ADD KEY `category` (`category`),
  ADD KEY `is_published` (`is_published`);

--
-- Indexes for table `staff_messages`
--
ALTER TABLE `staff_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`,`is_read`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_students_admission` (`admission_number`),
  ADD KEY `idx_students_class` (`grade_level`),
  ADD KEY `idx_students_section` (`section`),
  ADD KEY `idx_students_active` (`is_active`),
  ADD KEY `fk_students_status_changed_by` (`status_changed_by`);

--
-- Indexes for table `student_history`
--
ALTER TABLE `student_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sh_student` (`student_id`),
  ADD KEY `fk_sh_recorded_by` (`recorded_by`);

--
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`,`is_read`),
  ADD KEY `idx_issued` (`issued_by`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subjects_name` (`name`),
  ADD KEY `idx_subjects_dept` (`department`),
  ADD KEY `idx_subjects_section` (`section`);

--
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subscribers_email` (`email`),
  ADD KEY `idx_subscribers_active` (`is_active`);

--
-- Indexes for table `teacher_class_assignments`
--
ALTER TABLE `teacher_class_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teacher_class` (`teacher_id`,`grade_level`,`class`),
  ADD KEY `idx_tca_teacher` (`teacher_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_timetables_class_session_term` (`class`,`session`,`term`),
  ADD KEY `idx_timetables_class` (`class`),
  ADD KEY `idx_timetables_session` (`session`),
  ADD KEY `fk_timetables_uploader` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_section` (`section`),
  ADD KEY `idx_users_active` (`is_active`),
  ADD KEY `fk_users_closed_by` (`closed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alumni`
--
ALTER TABLE `alumni`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `awards`
--
ALTER TABLE `awards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_arms`
--
ALTER TABLE `class_arms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `corps_clearance`
--
ALTER TABLE `corps_clearance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `corps_members`
--
ALTER TABLE `corps_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `corps_messages`
--
ALTER TABLE `corps_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hall_of_fame`
--
ALTER TABLE `hall_of_fame`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hall_of_fame_nominations`
--
ALTER TABLE `hall_of_fame_nominations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prefects`
--
ALTER TABLE `prefects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_broadcast_log`
--
ALTER TABLE `push_broadcast_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result_scores`
--
ALTER TABLE `result_scores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_messages`
--
ALTER TABLE `staff_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_history`
--
ALTER TABLE `student_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_class_assignments`
--
ALTER TABLE `teacher_class_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `fk_contact_reader` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `corps_clearance`
--
ALTER TABLE `corps_clearance`
  ADD CONSTRAINT `fk_clearance_corps` FOREIGN KEY (`corps_member_id`) REFERENCES `corps_members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `corps_messages`
--
ALTER TABLE `corps_messages`
  ADD CONSTRAINT `fk_message_corps` FOREIGN KEY (`corps_member_id`) REFERENCES `corps_members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `fk_gallery_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `fk_results_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_results_publisher` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_results_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `result_scores`
--
ALTER TABLE `result_scores`
  ADD CONSTRAINT `fk_scores_result` FOREIGN KEY (`result_id`) REFERENCES `results` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scores_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scores_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_messages`
--
ALTER TABLE `staff_messages`
  ADD CONSTRAINT `staff_messages_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_status_changed_by` FOREIGN KEY (`status_changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_history`
--
ALTER TABLE `student_history`
  ADD CONSTRAINT `fk_sh_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_sh_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_class_assignments`
--
ALTER TABLE `teacher_class_assignments`
  ADD CONSTRAINT `fk_tca_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `fk_timetables_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;