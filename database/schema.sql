-- ============================================================
-- IBEKU HIGH SCHOOL — DATABASE SCHEMA
-- File: database/schema.sql
-- Database: ibeku_school
-- Charset: utf8mb4_unicode_ci
--
-- Run this file once to create all tables.
-- Import via phpMyAdmin: select ibeku_school → Import → choose this file
-- Or via CLI: mysql -u root -p ibeku_school < database/schema.sql
--
-- TABLE ORDER (respects foreign key dependencies):
--   1. users
--   2. students
--   3. subjects
--   4. results
--   5. result_scores
--   6. news
--   7. gallery
--   8. events
--   9. timetables
--  10. admissions
--  11. subscribers
--  12. contact_messages
--  13. hall_of_fame
--  14. prefects
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- 1. USERS — admin panel staff accounts
--    Covers all roles across both SS and JS sections.
--    is_active = 0 means position temporarily unoccupied.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(150)    NOT NULL,
  email         VARCHAR(150)    NOT NULL,
  password      VARCHAR(255)    NOT NULL COMMENT 'bcrypt hash — never plaintext',
  role          ENUM(
                  'superadmin',
                  'principal',
                  'vp_admin',
                  'vp_academics',
                  'vp_general',
                  'dean',
                  'counselor',
                  'hod',
                  'form_teacher',
                  'subject_teacher'
                )               NOT NULL,
  section       ENUM('ss','js','both') NOT NULL DEFAULT 'both',
  department    VARCHAR(100)    NULL COMMENT 'For hod and subject_teacher',
  class_assigned VARCHAR(20)   NULL COMMENT 'e.g. JSS2A — for form_teacher',
  is_active     TINYINT(1)      NOT NULL DEFAULT 1,
  last_login    TIMESTAMP       NULL,
  created_by    INT UNSIGNED    NULL,
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role    (role),
  KEY idx_users_section (section),
  KEY idx_users_active  (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. STUDENTS — student records
--    admission_number is the unique public identifier.
--    pin is used for result checking (hashed).
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
  id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  admission_number VARCHAR(30)    NOT NULL COMMENT 'e.g. IHS/2024/0421',
  first_name       VARCHAR(100)   NOT NULL,
  last_name        VARCHAR(100)   NOT NULL,
  other_name       VARCHAR(100)   NULL,
  gender           ENUM('male','female') NOT NULL,
  date_of_birth    DATE           NOT NULL,
  section          ENUM('ss','js') NOT NULL,
  current_class    ENUM('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') NOT NULL,
  arm              VARCHAR(5)     NULL COMMENT 'e.g. A, B, C',
  department       ENUM('sciences','arts','commercial','general') NOT NULL DEFAULT 'general',
  date_admitted    DATE           NOT NULL,
  is_active        TINYINT(1)     NOT NULL DEFAULT 1 COMMENT '0 = graduated or left',
  parent_name      VARCHAR(150)   NULL,
  parent_phone     VARCHAR(20)    NULL,
  parent_email     VARCHAR(150)   NULL,
  address          TEXT           NULL,
  photo            VARCHAR(255)   NULL COMMENT 'Path to student photo',
  created_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_students_admission (admission_number),
  KEY idx_students_class   (current_class),
  KEY idx_students_section (section),
  KEY idx_students_active  (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. SUBJECTS — master list of all subjects
-- ============================================================
CREATE TABLE IF NOT EXISTS subjects (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  name         VARCHAR(100)   NOT NULL,
  code         VARCHAR(20)    NULL COMMENT 'e.g. ENG, MTH, PHY',
  department   ENUM('sciences','arts','commercial','general','all') NOT NULL DEFAULT 'all',
  section      ENUM('ss','js','both') NOT NULL DEFAULT 'both',
  is_active    TINYINT(1)     NOT NULL DEFAULT 1,
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_subjects_name (name),
  KEY idx_subjects_dept    (department),
  KEY idx_subjects_section (section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. RESULTS — one record per student per term per session
--    This is the result header. Scores live in result_scores.
-- ============================================================
CREATE TABLE IF NOT EXISTS results (
  id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  student_id       INT UNSIGNED   NOT NULL,
  session          VARCHAR(12)    NOT NULL COMMENT 'e.g. 2024/2025',
  term             ENUM('first','second','third') NOT NULL,
  class            ENUM('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') NOT NULL,
  arm              VARCHAR(5)     NULL,
  total_students   SMALLINT       NOT NULL DEFAULT 0 COMMENT 'Number in class',
  position         SMALLINT       NULL COMMENT 'Position in class',
  average_score    DECIMAL(5,2)   NULL,
  total_score      DECIMAL(7,2)   NULL,
  form_teacher_comment  TEXT      NULL,
  principal_comment     TEXT      NULL,
  next_term_resumption  DATE      NULL,
  is_published     TINYINT(1)     NOT NULL DEFAULT 0 COMMENT '0=draft, 1=published',
  published_at     TIMESTAMP      NULL,
  published_by     INT UNSIGNED   NULL,
  created_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_results_student_term (student_id, session, term),
  KEY idx_results_session    (session),
  KEY idx_results_term       (term),
  KEY idx_results_class      (class),
  KEY idx_results_published  (is_published),
  CONSTRAINT fk_results_student
    FOREIGN KEY (student_id) REFERENCES students (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_results_publisher
    FOREIGN KEY (published_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. RESULT_SCORES — individual subject scores per result
--    1st test max 15, 2nd test max 15, exam max 70, total 100.
-- ============================================================
CREATE TABLE IF NOT EXISTS result_scores (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  result_id    INT UNSIGNED   NOT NULL,
  subject_id   INT UNSIGNED   NOT NULL,
  ca1_score    DECIMAL(4,1)   NOT NULL DEFAULT 0 COMMENT '1st test — max 15',
  ca2_score    DECIMAL(4,1)   NOT NULL DEFAULT 0 COMMENT '2nd test — max 15',
  exam_score   DECIMAL(4,1)   NOT NULL DEFAULT 0 COMMENT 'Exam — max 70',
  total_score  DECIMAL(5,1)   GENERATED ALWAYS AS (ca1_score + ca2_score + exam_score) STORED,
  grade        VARCHAR(3)     NULL COMMENT 'A1, B2 ... F9 — computed on save',
  remark       VARCHAR(20)    NULL COMMENT 'Excellent, Very Good ... Fail',
  uploaded_by  INT UNSIGNED   NULL COMMENT 'Subject teacher who uploaded',
  uploaded_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_scores_result_subject (result_id, subject_id),
  KEY idx_scores_result  (result_id),
  KEY idx_scores_subject (subject_id),
  CONSTRAINT fk_scores_result
    FOREIGN KEY (result_id) REFERENCES results (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_scores_subject
    FOREIGN KEY (subject_id) REFERENCES subjects (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_scores_uploader
    FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. NEWS — articles and announcements
-- ============================================================
CREATE TABLE IF NOT EXISTS news (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  title        VARCHAR(300)   NOT NULL,
  slug         VARCHAR(320)   NOT NULL,
  excerpt      TEXT           NULL,
  body         LONGTEXT       NOT NULL,
  category     ENUM('achievement','academic','ict','sports','announcement','culture','general') NOT NULL DEFAULT 'general',
  featured     TINYINT(1)     NOT NULL DEFAULT 0,
  image        VARCHAR(255)   NULL COMMENT 'Path to featured image',
  is_published TINYINT(1)     NOT NULL DEFAULT 0,
  published_at TIMESTAMP      NULL,
  author_id    INT UNSIGNED   NULL,
  views        INT UNSIGNED   NOT NULL DEFAULT 0,
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_news_slug      (slug),
  KEY idx_news_category        (category),
  KEY idx_news_published       (is_published),
  KEY idx_news_featured        (featured),
  KEY idx_news_published_at    (published_at),
  CONSTRAINT fk_news_author
    FOREIGN KEY (author_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. GALLERY — photo uploads
-- ============================================================
CREATE TABLE IF NOT EXISTS gallery (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  title        VARCHAR(255)   NOT NULL,
  category     ENUM('sports','events','classrooms','graduation','culture','assembly','ict','general') NOT NULL DEFAULT 'general',
  filename     VARCHAR(255)   NOT NULL COMMENT 'Stored filename on server',
  original_name VARCHAR(255)  NULL COMMENT 'Original upload filename',
  caption      TEXT           NULL,
  is_published TINYINT(1)     NOT NULL DEFAULT 1,
  sort_order   SMALLINT       NOT NULL DEFAULT 0,
  uploaded_by  INT UNSIGNED   NULL,
  uploaded_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_gallery_category  (category),
  KEY idx_gallery_published (is_published),
  KEY idx_gallery_sort      (sort_order),
  CONSTRAINT fk_gallery_uploader
    FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. EVENTS — school events calendar
-- ============================================================
CREATE TABLE IF NOT EXISTS events (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  title        VARCHAR(255)   NOT NULL,
  description  TEXT           NULL,
  category     ENUM('academic','sports','culture','examination','meeting','holiday','general') NOT NULL DEFAULT 'general',
  event_date   DATE           NOT NULL,
  start_time   TIME           NULL,
  end_time     TIME           NULL,
  venue        VARCHAR(255)   NULL,
  is_featured  TINYINT(1)     NOT NULL DEFAULT 0,
  is_published TINYINT(1)     NOT NULL DEFAULT 1,
  created_by   INT UNSIGNED   NULL,
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_events_date      (event_date),
  KEY idx_events_category  (category),
  KEY idx_events_published (is_published),
  KEY idx_events_featured  (is_featured),
  CONSTRAINT fk_events_creator
    FOREIGN KEY (created_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. TIMETABLES — tracks uploaded PDF timetables
-- ============================================================
CREATE TABLE IF NOT EXISTS timetables (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  class        ENUM('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3') NOT NULL,
  session      VARCHAR(12)    NOT NULL COMMENT 'e.g. 2024/2025',
  term         ENUM('first','second','third') NOT NULL DEFAULT 'first',
  filename     VARCHAR(255)   NOT NULL COMMENT 'Stored filename — fixed per class',
  original_name VARCHAR(255)  NULL,
  uploaded_by  INT UNSIGNED   NULL,
  uploaded_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_timetables_class_session_term (class, session, term),
  KEY idx_timetables_class   (class),
  KEY idx_timetables_session (session),
  CONSTRAINT fk_timetables_uploader
    FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. ADMISSIONS — enquiry form submissions
-- ============================================================
CREATE TABLE IF NOT EXISTS admissions (
  id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  parent_first     VARCHAR(100)   NOT NULL,
  parent_last      VARCHAR(100)   NOT NULL,
  parent_email     VARCHAR(150)   NOT NULL,
  parent_phone     VARCHAR(20)    NOT NULL,
  student_first    VARCHAR(100)   NOT NULL,
  student_last     VARCHAR(100)   NOT NULL,
  date_of_birth    DATE           NULL,
  gender           ENUM('male','female') NULL,
  entry_class      ENUM('JSS1','SSS1') NOT NULL,
  session          VARCHAR(12)    NOT NULL,
  previous_school  VARCHAR(200)   NULL,
  message          TEXT           NULL,
  status           ENUM('new','contacted','assessed','admitted','declined') NOT NULL DEFAULT 'new',
  notes            TEXT           NULL COMMENT 'Internal admin notes',
  created_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_admissions_status  (status),
  KEY idx_admissions_class   (entry_class),
  KEY idx_admissions_session (session),
  KEY idx_admissions_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. SUBSCRIBERS — newsletter email subscriptions
-- ============================================================
CREATE TABLE IF NOT EXISTS subscribers (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  email        VARCHAR(150)   NOT NULL,
  is_active    TINYINT(1)     NOT NULL DEFAULT 1,
  subscribed_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  unsubscribed_at TIMESTAMP   NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_subscribers_email (email),
  KEY idx_subscribers_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. CONTACT_MESSAGES — contact form submissions
-- ============================================================
CREATE TABLE IF NOT EXISTS contact_messages (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  first_name   VARCHAR(100)   NOT NULL,
  last_name    VARCHAR(100)   NOT NULL,
  email        VARCHAR(150)   NOT NULL,
  phone        VARCHAR(20)    NULL,
  subject      VARCHAR(200)   NOT NULL,
  message      TEXT           NOT NULL,
  is_read      TINYINT(1)     NOT NULL DEFAULT 0,
  read_at      TIMESTAMP      NULL,
  read_by      INT UNSIGNED   NULL,
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_contact_read    (is_read),
  KEY idx_contact_created (created_at),
  CONSTRAINT fk_contact_reader
    FOREIGN KEY (read_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. HALL_OF_FAME — distinguished alumni and achievers
-- ============================================================
CREATE TABLE IF NOT EXISTS hall_of_fame (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  full_name    VARCHAR(150)   NOT NULL,
  category     ENUM('alumni','academic','sports','prefect','staff') NOT NULL,
  class_year   VARCHAR(12)    NULL COMMENT 'e.g. Class of 2005',
  field        VARCHAR(150)   NULL COMMENT 'e.g. Medicine, Law, Engineering',
  achievement  TEXT           NOT NULL,
  photo        VARCHAR(255)   NULL,
  is_published TINYINT(1)     NOT NULL DEFAULT 1,
  sort_order   SMALLINT       NOT NULL DEFAULT 0,
  nominated_by VARCHAR(150)   NULL,
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_hof_category  (category),
  KEY idx_hof_published (is_published),
  KEY idx_hof_sort      (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 14. PREFECTS — student leaders per session
-- ============================================================
CREATE TABLE IF NOT EXISTS prefects (
  id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  full_name    VARCHAR(150)   NOT NULL,
  role         VARCHAR(100)   NOT NULL COMMENT 'e.g. Head Boy, Sports Prefect',
  section      ENUM('ss','js') NOT NULL,
  session      VARCHAR(12)    NOT NULL COMMENT 'e.g. 2024/2025',
  quote        TEXT           NULL,
  photo        VARCHAR(255)   NULL,
  is_active    TINYINT(1)     NOT NULL DEFAULT 1,
  sort_order   SMALLINT       NOT NULL DEFAULT 0,
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_prefects_section (section),
  KEY idx_prefects_session (session),
  KEY idx_prefects_active  (is_active),
  KEY idx_prefects_sort    (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SCHEMA
-- Total tables: 14
-- ============================================================