# Changelog

All notable changes to this project are documented here.
Format: [version] — date — description

---

## [0.1.0] — 2026-04-22 — Project Initialized

- Created repository and folder structure
- Added README, .gitignore, .env.example
- Began Phase 1: Static frontend

## [2.0.0] — 2026-06-19

### Added — Phase 2 Complete (Backend)
- Full MySQL database schema — 14 tables covering users, students, subjects,
  results, result_scores, news, gallery, events, timetables, admissions,
  subscribers, contact_messages, hall_of_fame, prefects
- Seed data — 9 staff users across all role types, 10 students, 25 subjects,
  sample results, news, events, gallery items, prefects, admissions enquiries
- PDO database connection layer (src/config/database.php) with .env config
  loading, grade calculator, and safe output escaping
- check_result.php — live result checker API, replacing demo JS data with
  real database queries
- submit_contact.php — contact form API with full server-side validation,
  saves to contact_messages table
- submit_admission.php — admissions enquiry API with validation, saves to
  admissions table
- subscribe.php — newsletter signup API with duplicate detection and
  resubscription handling
- All four frontend forms (results, contact, admissions, newsletter) wired
  to live APIs via fetch()
- Printable result sheet now pulls live form teacher and principal comments
  from the database

### Fixed
- Result checker silent failure caused by missing variable declaration in
  renderResultFull()
- MySQL port 3306 conflict between XAMPP and a standalone MySQL80 service
- Multi-line anchor tags in contact.php map section breaking HTML render

## [1.0.0] — 2026-05-31

### Added — Phase 1 Complete
- All 11 public pages built and fully functional
- Shared CSS foundation (style.css — 1,037 lines)
- Shared PHP header and footer with BASE_PATH auto-detection
- Shared JavaScript (nav, scroll reveal, back to top, anchor nav)
- Homepage with hero carousel and live result checker demo
- About page with history, timeline, both principals, facilities
- Hall of Fame with alumni, academic stars, sports, prefects
- Academics with departments, downloadable timetables (6 PDFs), staff directory
- Results page with full checker, grading system, printable result sheet
- Admissions page with requirements, steps, fees, application form
- Contact page with Google Maps embed and department contacts
- News page with featured article, filterable grid, announcements
- Gallery with filterable categories, lightbox, keyboard navigation
- Students page with prefects, alumni, scholarships
- Events page with featured, upcoming list, past events
- Role-based admin architecture (10 roles, SS/JS section separation)
- Placeholder timetable PDFs for all 6 class levels