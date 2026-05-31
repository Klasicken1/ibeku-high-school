-- ============================================================
-- IBEKU HIGH SCHOOL — SEED DATA
-- File: database/seed.sql
-- Database: ibeku_school
--
-- Realistic test data for development and testing.
-- Import AFTER schema.sql has been imported.
--
-- Import via phpMyAdmin: select ibeku_school → Import → choose this file
-- Or via CLI: mysql -u root -p ibeku_school < database/seed.sql
--
-- CONTAINS:
--   - 1 superadmin user
--   - 6 staff users (one per key role)
--   - 10 students (across JSS and SSS)
--   - 15 subjects
--   - Results for 2 students (First Term 2024/2025)
--   - 6 news articles
--   - 6 events
--   - 6 gallery items
--   - 6 hall of fame entries
--   - 4 prefects
--   - 3 admissions enquiries
--   - 3 contact messages
--   - 5 subscribers
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- USERS
-- Passwords are bcrypt hashes of 'Password123!'
-- Change all passwords immediately after import.
-- ============================================================
INSERT INTO users
  (full_name, email, password, role, section, department, class_assigned, is_active)
VALUES
  -- Superadmin
  ('System Administrator',
   'admin@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'superadmin', 'both', NULL, NULL, 1),

  -- SS Principal
  ('[SS Principal Name]',
   'principal.ss@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'principal', 'ss', NULL, NULL, 1),

  -- JS Principal
  ('[JS Principal Name]',
   'principal.js@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'principal', 'js', NULL, NULL, 1),

  -- Dean of Studies SS
  ('[Dean SS Name]',
   'dean.ss@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'dean', 'ss', NULL, NULL, 1),

  -- Dean of Studies JS
  ('[Dean JS Name]',
   'dean.js@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'dean', 'js', NULL, NULL, 1),

  -- HOD Sciences
  ('[HOD Sciences Name]',
   'hod.sciences@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'hod', 'ss', 'Sciences', NULL, 1),

  -- Subject Teacher — Mathematics
  ('[Maths Teacher Name]',
   'teacher.maths@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'subject_teacher', 'ss', 'Mathematics', NULL, 1),

  -- Form Teacher — SSS2A
  ('[Form Teacher SSS2A Name]',
   'formteacher.sss2a@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'form_teacher', 'ss', NULL, 'SSS2', 1),

  -- VP Academics SS (position currently unoccupied)
  ('[VP Academics SS]',
   'vp.academics.ss@ibekuhighschool.edu.ng',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'vp_academics', 'ss', NULL, NULL, 0);


-- ============================================================
-- STUDENTS
-- ============================================================
INSERT INTO students
  (admission_number, first_name, last_name, other_name,
   gender, date_of_birth, section, current_class, arm,
   department, date_admitted, is_active,
   parent_name, parent_phone, parent_email)
VALUES
  -- Demo student 1 — used in results.php demo
  ('IHS/2024/0421', 'Adaeze', 'Okonkwo', 'Chisom',
   'female', '2008-03-14', 'ss', 'SSS2', 'A',
   'sciences', '2022-09-05', 1,
   'Mrs Ngozi Okonkwo', '+2348031234567', 'ngozi.okonkwo@gmail.com'),

  -- Demo student 2 — used in results.php demo
  ('IHS/2024/0105', 'Chukwuemeka', 'Nwosu', 'Tochukwu',
   'male', '2011-07-22', 'js', 'JSS3', 'A',
   'general', '2021-09-06', 1,
   'Mr Emmanuel Nwosu', '+2348055678901', 'emmanuel.nwosu@gmail.com'),

  -- Additional SSS students
  ('IHS/2024/0312', 'Obiageli', 'Eze', NULL,
   'female', '2007-11-03', 'ss', 'SSS3', 'B',
   'arts', '2021-09-06', 1,
   'Mr Paulinus Eze', '+2348067890123', NULL),

  ('IHS/2024/0089', 'Emeka', 'Nwachukwu', 'Ifeanyi',
   'male', '2009-05-18', 'ss', 'SSS1', 'A',
   'commercial', '2023-09-04', 1,
   'Mrs Ada Nwachukwu', '+2348012345678', NULL),

  ('IHS/2024/0278', 'Chidinma', 'Mbah', NULL,
   'female', '2008-09-25', 'ss', 'SSS2', 'A',
   'sciences', '2022-09-05', 1,
   'Dr Ikenna Mbah', '+2348098765432', 'ikenna.mbah@yahoo.com'),

  -- JSS students
  ('IHS/2024/0514', 'Oluchukwu', 'Obi', 'Emmanuel',
   'male', '2012-01-10', 'js', 'JSS2', 'A',
   'general', '2022-09-05', 1,
   'Mrs Chinwe Obi', '+2348023456789', NULL),

  ('IHS/2024/0623', 'Adanna', 'Igwe', NULL,
   'female', '2013-06-30', 'js', 'JSS1', 'B',
   'general', '2023-09-04', 1,
   'Mr Chidi Igwe', '+2348034567890', NULL),

  ('IHS/2024/0198', 'Kenechukwu', 'Anya', 'Paul',
   'male', '2011-12-05', 'js', 'JSS3', 'A',
   'general', '2021-09-06', 1,
   'Mrs Uju Anya', '+2348045678901', NULL),

  ('IHS/2024/0445', 'Ngozichukwuka', 'Orji', NULL,
   'female', '2010-04-17', 'ss', 'SSS1', 'A',
   'sciences', '2023-09-04', 1,
   'Mr Bartholomew Orji', '+2348056789012', NULL),

  ('IHS/2024/0367', 'Chibuike', 'Nnadi', 'Solomon',
   'male', '2009-08-08', 'ss', 'SSS2', 'B',
   'commercial', '2022-09-05', 1,
   'Mrs Patience Nnadi', '+2348078901234', NULL);


-- ============================================================
-- SUBJECTS
-- ============================================================
INSERT INTO subjects (name, code, department, section) VALUES

  -- Both sections
  ('English Language',    'ENG', 'all',       'both'),
  ('Mathematics',         'MTH', 'all',       'both'),
  ('Civic Education',     'CIV', 'all',       'both'),
  ('Physical Education',  'PHE', 'all',       'both'),
  ('Computer Studies',    'CMP', 'all',       'both'),

  -- JSS General Studies
  ('Basic Science',       'BSC', 'general',   'js'),
  ('Basic Technology',    'BTY', 'general',   'js'),
  ('Social Studies',      'SST', 'general',   'js'),
  ('Business Studies',    'BUS', 'general',   'js'),
  ('French',              'FRN', 'general',   'js'),
  ('Cultural & Creative Arts', 'CCA', 'general', 'js'),

  -- SSS Sciences
  ('Physics',             'PHY', 'sciences',  'ss'),
  ('Chemistry',           'CHM', 'sciences',  'ss'),
  ('Biology',             'BIO', 'sciences',  'ss'),
  ('Further Mathematics', 'FMT', 'sciences',  'ss'),
  ('Agricultural Science','AGR', 'sciences',  'ss'),

  -- SSS Arts
  ('Literature in English','LIT', 'arts',     'ss'),
  ('Government',          'GOV', 'arts',      'ss'),
  ('History',             'HST', 'arts',      'ss'),
  ('Christian Religious Studies', 'CRS', 'arts', 'ss'),
  ('Fine Art',            'FAR', 'arts',      'ss'),

  -- SSS Commercial
  ('Economics',           'ECO', 'commercial','ss'),
  ('Accounting',          'ACC', 'commercial','ss'),
  ('Commerce',            'COM', 'commercial','ss'),
  ('Office Practice',     'OFP', 'commercial','ss');


-- ============================================================
-- RESULTS — First Term 2024/2025 for demo students
-- ============================================================
INSERT INTO results
  (student_id, session, term, class, arm,
   total_students, position, average_score, total_score,
   form_teacher_comment, principal_comment,
   next_term_resumption, is_published, published_at, published_by)
VALUES
  -- Student 1: Adaeze Okonkwo — SSS2A
  (1, '2024/2025', 'first', 'SSS2', 'A',
   28, 3, 76.33, 458.00,
   'Adaeze is a hardworking and focused student. Her performance this term is commendable. She should maintain this momentum.',
   'An outstanding student with great potential. Keep it up.',
   '2025-01-13', 1, NOW(), 1),

  -- Student 2: Chukwuemeka Nwosu — JSS3A
  (2, '2024/2025', 'first', 'JSS3', 'A',
   32, 1, 83.50, 501.00,
   'Chukwuemeka is the best student in his class this term. Excellent performance across all subjects.',
   'A brilliant student. His parents should be proud. Continue to excel.',
   '2025-01-13', 1, NOW(), 1);


-- ============================================================
-- RESULT SCORES — subject scores for the two demo results
-- ============================================================

-- Adaeze Okonkwo (result_id = 1) — SSS Sciences subjects
-- Subject IDs: English=1, Maths=2, Physics=12, Chemistry=13, Biology=14, Economics=22
INSERT INTO result_scores
  (result_id, subject_id, ca1_score, ca2_score, exam_score, grade, remark, uploaded_by)
VALUES
  (1, 1,  12, 12, 54, 'B3', 'Good',      7),  -- English: 78
  (1, 2,  13, 12, 60, 'A1', 'Excellent', 7),  -- Maths:   85
  (1, 12, 11, 11, 50, 'B3', 'Good',      7),  -- Physics: 72
  (1, 13, 12, 12, 56, 'A1', 'Excellent', 7),  -- Chem:    80
  (1, 14, 11, 11, 52, 'B2', 'Very Good', 7),  -- Biology: 74
  (1, 22, 10, 11, 48, 'B3', 'Good',      7);  -- Econ:    69

-- Chukwuemeka Nwosu (result_id = 2) — JSS General subjects
-- Subject IDs: English=1, Maths=2, BasicSci=6, SocStudies=8, CivEd=3, BusStudies=9
INSERT INTO result_scores
  (result_id, subject_id, ca1_score, ca2_score, exam_score, grade, remark, uploaded_by)
VALUES
  (2, 1, 14, 14, 64, 'A1', 'Excellent', 7),  -- English: 92
  (2, 2, 13, 13, 62, 'A1', 'Excellent', 7),  -- Maths:   88
  (2, 6, 13, 13, 59, 'A1', 'Excellent', 7),  -- BasSci:  85
  (2, 8, 12, 12, 55, 'B2', 'Very Good', 7),  -- SocSt:   79
  (2, 3, 12, 12, 57, 'A1', 'Excellent', 7),  -- CivEd:   81
  (2, 9, 11, 12, 53, 'B2', 'Very Good', 7);  -- BusSt:   76


-- ============================================================
-- NEWS
-- ============================================================
INSERT INTO news
  (title, slug, excerpt, body, category, featured,
   is_published, published_at, author_id)
VALUES
  ('IHS Wins Abia State Science Quiz Championship for Third Consecutive Year',
   'science-quiz-championship-2024',
   'The Ibeku High School science quiz team has won the Abia State Secondary School Science Quiz Championship for the third consecutive year.',
   '<p>The Ibeku High School science quiz team has once again made the school proud, winning the Abia State Secondary School Science Quiz Championship for the third consecutive year — an unprecedented achievement in the history of the competition.</p><p>The team, made up of SSS 2 students, defeated schools from across all local government areas of Abia State to retain the title. The victory was celebrated with a school assembly and commendations from the SS Principal.</p>',
   'achievement', 1, 1, '2024-12-10 10:00:00', 2),

  ('First Term 2024/2025 Results Now Available Online',
   'first-term-results-available',
   'First Term examination results for the 2024/2025 academic session are now available on the school website.',
   '<p>Ibeku High School is pleased to announce that First Term examination results for the 2024/2025 academic session are now available online. Students can check their results using their Admission Number on the <a href="/results.php">Results page</a>.</p><p>Any discrepancies should be reported to the relevant subject teacher or the school office within two weeks of publication.</p>',
   'academic', 0, 1, '2024-12-22 09:00:00', 2),

  ('Computer Lab Fully Refurbished Through Alumni Donation',
   'computer-lab-refurbishment',
   'The school computer laboratory has been fully refurbished with new desktop computers and internet connectivity.',
   '<p>Ibeku High School is grateful to the IHS Old Students Association for funding the complete refurbishment of the school computer laboratory. The lab now features new desktop computers, reliable internet connectivity, and updated software.</p><p>The refurbishment was officially commissioned on November 15, 2024, in a brief ceremony attended by the Principal, staff, and OSA representatives.</p>',
   'ict', 0, 1, '2024-11-15 11:00:00', 2),

  ('IHS Football Team Wins Umuahia Zonal Championship',
   'football-zonal-championship',
   'The Ibeku High School football team has won the Umuahia Zonal Secondary School Football Championship.',
   '<p>The Ibeku High School football team put in a remarkable performance to win the Umuahia Zonal Secondary School Football Championship, defeating seven other schools across three rounds of competition.</p><p>The team was congratulated by the VP General Duties and the Sports Prefect at a special assembly.</p>',
   'sports', 0, 1, '2024-11-05 14:00:00', 2),

  ('Annual Cultural Day Celebration Holds December 6th',
   'cultural-day-2024',
   'The annual Ibeku High School Cultural Day celebration is scheduled for Friday, December 6, 2024.',
   '<p>Students are invited to attend and participate in the annual IHS Cultural Day celebration on Friday, December 6, 2024. Students are encouraged to come in their cultural attires and to participate in the drama, dance, and music competitions.</p>',
   'culture', 0, 1, '2024-11-25 08:00:00', 2),

  ('Second Term Resumption Date Announced',
   'second-term-resumption-date',
   'The Second Term of the 2024/2025 academic session will resume on Monday, January 13, 2025.',
   '<p>The school management wishes to inform all students, parents, and guardians that the Second Term of the 2024/2025 academic session will resume on <strong>Monday, January 13, 2025</strong>. All students are expected to report by 8:00 AM on the resumption date.</p><p>Fee payments for Second Term should be completed before resumption.</p>',
   'announcement', 0, 1, '2024-12-18 09:00:00', 2);


-- ============================================================
-- EVENTS
-- ============================================================
INSERT INTO events
  (title, description, category, event_date, start_time, end_time,
   venue, is_featured, is_published, created_by)
VALUES
  ('Second Term Resumption',
   'Second Term of the 2024/2025 academic session resumes. All students must report by 8:00 AM.',
   'academic', '2025-01-13', '08:00:00', NULL,
   'IHS Main Campus', 0, 1, 1),

  ('First Term Examinations Begin',
   'First Term examinations commence for all classes from JSS 1 to SSS 3. Timetables available from form teachers.',
   'examination', '2025-01-20', '08:00:00', '14:00:00',
   'All Classrooms — IHS Main Campus', 1, 1, 1),

  ('Inter-House Cultural Competition',
   'Annual inter-house cultural competition featuring drama, poetry, traditional dance, and music performances.',
   'culture', '2025-01-24', '10:00:00', '15:00:00',
   'School Assembly Hall', 1, 1, 1),

  ('Inter-House Sports Day',
   'Annual inter-house sports competition — athletics, football, basketball, and field events.',
   'sports', '2025-01-28', '08:30:00', '16:00:00',
   'School Sports Field', 1, 1, 1),

  ('Parents-Teachers Association Meeting',
   'The First Term PTA meeting for parents and guardians of all students.',
   'meeting', '2025-02-01', '09:00:00', '13:00:00',
   'School Assembly Hall', 0, 1, 1),

  ('First Term Ends — School Closes',
   'End of First Term 2024/2025. Students dismissed after closing assembly.',
   'holiday', '2025-02-14', '12:00:00', NULL,
   'IHS Main Campus', 0, 1, 1);


-- ============================================================
-- GALLERY
-- ============================================================
INSERT INTO gallery
  (title, category, filename, caption, is_published, sort_order, uploaded_by)
VALUES
  ('Science Quiz Championship 2024 — Winners',
   'sports', 'placeholder-sports-1.jpg',
   'The IHS science quiz team celebrating their third consecutive championship victory.',
   1, 1, 1),

  ('Cultural Day 2024 — Traditional Attire Display',
   'culture', 'placeholder-culture-1.jpg',
   'Students in their cultural attires during the annual IHS Cultural Day 2024.',
   1, 2, 1),

  ('SSS 3 Graduation Ceremony 2024',
   'graduation', 'placeholder-graduation-1.jpg',
   'The Class of 2024 at their graduation ceremony.',
   1, 3, 1),

  ('Students in the Newly Refurbished Computer Lab',
   'ict', 'placeholder-ict-1.jpg',
   'JSS students during an ICT lesson in the newly refurbished computer laboratory.',
   1, 4, 1),

  ('Morning Assembly — Flag Raising',
   'assembly', 'placeholder-assembly-1.jpg',
   'Students at the weekly Monday morning assembly and flag raising ceremony.',
   1, 5, 1),

  ('Inter-House Sports Day — Track Events',
   'sports', 'placeholder-sports-2.jpg',
   'Athletes competing in the 100m sprint event at Inter-House Sports Day 2024.',
   1, 6, 1);


-- ============================================================
-- HALL OF FAME
-- ============================================================
INSERT INTO hall_of_fame
  (full_name, category, class_year, field, achievement, is_published, sort_order)
VALUES
  ('[Distinguished Alumnus 1]',
   'alumni', 'Class of 2005',
   'Medicine & Public Health',
   'A distinguished medical professional who rose to prominence in public health across Abia State and Nigeria. First-class graduate of the University of Nigeria, Nsukka.',
   1, 1),

  ('[Distinguished Alumnus 2]',
   'alumni', 'Class of 1998',
   'Law & Public Service',
   'A respected legal practitioner who has held significant positions in the Nigerian judiciary and public service sector.',
   1, 2),

  ('[Science Excellence Award 2024]',
   'academic', '2024',
   'Sciences',
   'Best graduating SSS 3 student in the Sciences, 2024. Achieved straight A1s in all science subjects in WAEC.',
   1, 3),

  ('[Sports Achievement 2024]',
   'sports', '2024',
   'Athletics',
   'Won gold in the 100m sprint at the Abia State Secondary Schools Athletics Championship 2024.',
   1, 4),

  ('[Head Boy 2023/2024]',
   'prefect', '2023/2024',
   'Student Leadership',
   'Served as Head Boy of Ibeku High School for the 2023/2024 academic session with distinction.',
   1, 5),

  ('[Long-Serving Staff Member]',
   'staff', NULL,
   'Teaching',
   'Dedicated member of the IHS teaching staff who served the school for over 25 years with distinction.',
   1, 6);


-- ============================================================
-- PREFECTS
-- ============================================================
INSERT INTO prefects
  (full_name, role, section, session, quote, is_active, sort_order)
VALUES
  ('[Head Boy Name]',
   'Head Boy', 'ss', '2024/2025',
   'It is an honour to serve Ibeku High School. My goal is to bridge the gap between students and the administration.',
   1, 1),

  ('[Head Girl Name]',
   'Head Girl', 'ss', '2024/2025',
   'Leadership is not about authority — it is about service. I am committed to making IHS better for every student.',
   1, 2),

  ('[JS Head Boy Name]',
   'JS Head Boy', 'js', '2024/2025',
   'I will represent the Junior Secondary students with integrity and dedication.',
   1, 3),

  ('[JS Head Girl Name]',
   'JS Head Girl', 'js', '2024/2025',
   'Serving as JS Head Girl is a privilege I take very seriously.',
   1, 4);


-- ============================================================
-- ADMISSIONS
-- ============================================================
INSERT INTO admissions
  (parent_first, parent_last, parent_email, parent_phone,
   student_first, student_last, date_of_birth, gender,
   entry_class, session, previous_school, message, status)
VALUES
  ('Ngozi', 'Achike',
   'ngozi.achike@gmail.com', '+2348031112233',
   'Chisom', 'Achike',
   '2012-05-14', 'female',
   'JSS1', '2025/2026',
   'Government Primary School Umuahia',
   'My daughter performed excellently in her primary leaving examination and I believe IHS is the right school for her.',
   'new'),

  ('Emmanuel', 'Obiechina',
   'emma.obiechina@yahoo.com', '+2348044445566',
   'Tobechukwu', 'Obiechina',
   '2009-08-22', 'male',
   'SSS1', '2025/2026',
   'Community Secondary School Aba',
   'My son has his BECE result with 6 credits. We are relocating to Umuahia and would like him to transfer.',
   'contacted'),

  ('Adaku', 'Nwofor',
   'adaku.nwofor@gmail.com', '+2348077778899',
   'Uchechi', 'Nwofor',
   '2012-11-30', 'female',
   'JSS1', '2025/2026',
   NULL,
   NULL,
   'new');


-- ============================================================
-- CONTACT MESSAGES
-- ============================================================
INSERT INTO contact_messages
  (first_name, last_name, email, phone, subject, message, is_read)
VALUES
  ('Chukwudi', 'Okafor',
   'chukwudi.okafor@gmail.com', '+2348031234567',
   'Result Enquiry',
   'Good morning. I am trying to check my son\'s result using his admission number IHS/2024/0312 but I am getting an error. Please help.',
   0),

  ('Adaora', 'Nweze',
   'adaora.nweze@yahoo.com', NULL,
   'Admissions Enquiry',
   'Please I would like to know the cut-off mark for the JSS 1 entrance examination for the 2025/2026 session and when forms will be available.',
   1),

  ('Obinna', 'Uzor',
   'obinna.uzor@gmail.com', '+2348055566677',
   'General Enquiry',
   'I am an alumnus of IHS (Class of 2010). I would like to get in touch with the Old Students Association. Can you provide their contact details?',
   0);


-- ============================================================
-- SUBSCRIBERS
-- ============================================================
INSERT INTO subscribers (email, is_active) VALUES
  ('parent1@gmail.com',     1),
  ('parent2@yahoo.com',     1),
  ('alumni2005@gmail.com',  1),
  ('teacher.ext@gmail.com', 1),
  ('old.unsub@gmail.com',   0);


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SEED DATA
-- ============================================================