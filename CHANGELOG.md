# Changelog

All notable changes to the Ibeku High School website project are recorded here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

Developer: **Nweke Kenneth Nnaemeka** — NYSC CDS Project, AB/25C/0245
Institution: **Ibeku High School**, Umuahia, Abia State

---

## [Unreleased]
- Corps Member module (Phase 7) — public directory, individual profiles, clearance status, PDF clearance letter, admin CRUD
- PWA manifest screenshot images for install prompt
- Open Graph image for social sharing

---

## [6.4.0] — July 2026

### Added
- `public/portal/change-password.php` — student password change page; verifies current password (handles both hashed and default admission-number password); enforces 6-character minimum; blocks reuse of admission number or same password; live strength meter (Very Weak to Very Strong); show/hide toggle on all three fields; success screen with dashboard redirect
- `public/unsubscribe.php` — branded newsletter unsubscribe page; accepts `?email=` GET parameter; handles four states: success, already unsubscribed, not found (with manual form), server error; auto-wired from newsletter broadcast emails
- `public/assets/images/students/.gitkeep` — ensures student photo upload directory exists on fresh cPanel deployment

### Changed
- `public/portal/profile.php` — added Change Password button linking to change-password.php; switched to `fetchAll(PDO::FETCH_ASSOC)` to prevent PDOStatement-as-array error; added null-safe `??` operators throughout
- `src/includes/portal-nav.php` — renamed PDO query variable from `$s` to `$_navStmt` to prevent silent overwrite of the student record in profile.php scope
- `public/admin/newsletter-admin.php` — unsubscribe link in broadcast email template wired to real `/unsubscribe.php?email=` endpoint
- `src/includes/header.php` — added `API_PATH` constant; injected `window.IHS_BASE` and `window.IHS_API` JS globals into `<head>` so external JS files can resolve paths without PHP
- `public/assets/js/main.js` — SW registration updated to use `window.IHS_BASE + 'sw.js'`; desktop dropdown nav rewritten to remove JS `preventDefault` on parent links — CSS `:hover` now handles desktop dropdowns; JS only handles mobile tap-to-toggle
- `public/assets/js/pages/results.js` — fetch call updated to use `window.IHS_API + 'check_result.php'`
- `public/manifest.json` — `start_url`, `scope`, and shortcut URLs updated from `/ibeku-high-school/public/` to `/` for production
- `public/index.php`, `public/admissions.php`, `public/contact.php`, `public/hall-of-fame.php`, `public/news.php` — all inline JS fetch() calls updated from hardcoded `/ibeku-high-school/src/api/` to `<?php echo API_PATH; ?>`
- `public/admin/messages.php` — push notification URL updated from hardcoded path to `BASE_PATH . 'admin/messages.php'`
- `public/admin/push-notifications.php` — icon/badge URLs updated to use `getPushIconUrl()` from push-helper
- `src/includes/push-helper.php` — added `getPushIconUrl()` function that builds absolute icon URL dynamically from `$_SERVER['HTTP_HOST']` and `BASE_PATH`; added `BASE_PATH` fallback guard

### Fixed
- `public/portal/results.php` — query rewritten to join `results` to `result_scores` to `subjects` correctly; was querying non-existent `r.subject_id` and `r.status` columns; now uses `r.is_published = 1`; results table now shows CA1 and CA2 as separate columns
- `public/portal/dashboard.php` — fixed `AND status = 'published'` to `AND is_published = 1` to match actual results table schema
- `public/portal/profile.php` — fixed BOM encoding corruption; removed emoji characters causing garbled display
- `public/portal/blocked.php` — phone numbers now pulled from `$_site['school_phone']` settings instead of hardcoded placeholders

### Deployment notes
- `manifest.json` `start_url` and `scope` are now set to `/` — correct for production cPanel
- `BASE_PATH` and `API_PATH` auto-detect localhost vs production — no manual changes needed on deploy
- `src/config/vapid.php` remains gitignored — must be manually uploaded to cPanel
- `public/assets/images/students/` directory now tracked via `.gitkeep`

---

## [6.3.0] — July 2026

### Added
- `public/admin/student-notices.php` — compose and send official notices to one or multiple students; notice types: Suspension, Expulsion, Promotion, Demotion, Retention, Behavioural Remark; role restrictions enforced per type; quick-fill title templates per type; real-time student search and multi-select recipient picker; recent notices log with read status
- `public/admin/student-portal.php` — per-student portal and results access control; lock/unlock portal login; block/unblock results access with reason; password reset to admission number; bulk select and bulk actions across filtered student list; audit columns (blocked_by, blocked_at) recorded on every action; reason modal for all destructive actions
- `src/includes/admin-sidebar.php` — added Portal Access and Student Notices nav links with correct role restrictions

### Changed
- `public/admin/students-create.php` — admission number is now an editable field (pre-filled with auto-generated value, overrideable before save); duplicate admission number validation added; default portal password hashed and saved on student creation; photo save path corrected to `assets/images/students/`
- `public/portal/dashboard.php` — photo path corrected from `assets/images/staff/` to `assets/images/students/`
- `public/portal/profile.php` — photo path corrected from `assets/images/staff/` to `assets/images/students/`

### Database
- `students` table: added `password`, `portal_blocked`, `portal_blocked_reason`, `portal_blocked_by`, `portal_blocked_at`, `results_blocked`, `results_blocked_reason`, `results_blocked_by`, `results_blocked_at` columns
- New table: `student_notifications` — stores all official notices issued to students by admin staff

---

## [6.2.0] — July 2026

### Added
- `public/portal/login.php` — student login by admission number; default password is the admission number; branded login page
- `public/portal/logout.php` — secure session destruction
- `public/portal/dashboard.php` — student home with welcome card, quick links, results blocked warning, recent notices preview
- `public/portal/profile.php` — read-only student profile with personal, academic, and parent/guardian details
- `public/portal/results.php` — term-by-term academic results viewer; term selector; summary stats; results table with CA1, CA2, exam, total, grade, remark; grade key; respects `results_blocked` flag
- `public/portal/notifications.php` — student notices inbox with two-panel layout; unread dot and badge; mark-as-read on open
- `public/portal/blocked.php` — access blocked page; displays reason if provided; school phone from settings; contact form
- `src/includes/auth.php` — full student portal auth library
- `src/includes/portal-nav.php` — shared student portal navigation with unread badge and mobile burger menu
- `public/assets/css/portal.css` — complete student portal stylesheet using school design tokens
- `public/portal/.htaccess` — portal security rules

---

## [6.1.0] — July 2026

### Added
- `src/includes/push-helper.php` — extracted all VAPID/Web Push functions into a shared helper; `sendPushToUser()` for targeted push by user_id; `ensurePushUserIdColumn()` safe migration; `sendStaffMessageEmail()` branded HTML email to staff

### Changed
- `public/admin/messages.php` — on message send fires targeted push notification and branded HTML email to recipient simultaneously; purple subscribe banner for admins who have not subscribed; JSON POST handler for subscription saving
- `push_subscriptions` table: `user_id` column added via safe migration

---

## [5.0.0] — June-July 2026

### Added
- `public/manifest.json` — Web App Manifest with shortcuts to Results and News
- `public/sw.js` — service worker with three-cache strategy: static shell (cache-first, pre-cached), pages (network-first with offline fallback), images (cache-first as visited); admin and portal routes excluded
- `public/offline.php` — branded offline fallback page; lists cacheable public pages; auto-reloads on connection restore
- `public/assets/js/pwa.js` — SW registration; update detection; custom install banner (Android/Chrome); iOS Safari install nudge; online/offline status bar
- `src/includes/header.php` — PWA head block; pwa.js script tag using BASE_PATH
- Push notification broadcast admin page with self-contained pure PHP VAPID implementation using OpenSSL

---

## [4.0.0] — May-June 2026

### Added
- Full CMS admin panel with role-based access control across 8 roles
- Admin login, logout, session management
- Results entry, approval, and publishing workflow across 3-stage pipeline
- Timetable PDF upload pages for SS and JS
- News create and edit with TinyMCE rich text editor
- Events, gallery, student, staff, Hall of Fame, admissions, alumni, clubs, awards, scholarships, prefects management
- Review moderation
- Internal staff messaging with unread badge
- Newsletter subscriber management with broadcast email
- Site-wide settings page
- Admin sidebar with role-filtered navigation

### Fixed
- `public/admin/timetables-ss.php` and `timetables-js.php` — added missing `require_once database.php`
- `public/admin/newsletter-admin.php` — corrected `created_at` column references to `subscribed_at`
- `public/admin/gallery.php` — `onerror` attribute fixed with `this.onerror=null` pattern

---

## [3.0.0] — April-May 2026

### Added
- `public/results.php` — public-facing exam result lookup; AJAX-powered
- `public/gallery.php` — categorised photo gallery with lightbox
- `public/students.php` — prefects, alumni, scholarships, Hall of Fame
- `public/hall-of-fame.php` — Hall of Fame with nomination form
- `public/news.php` and `public/news-single.php` — dynamic news listing and article pages
- `public/events.php` — upcoming and past events
- `public/verify-review.php` — email-based review verification
- All src/api/ endpoints: check_result, submit_contact, submit_admission, submit_review, subscribe, push-subscribe

---

## [2.0.0] — March-April 2026

### Added
- `public/about.php` — history, vision, anthem, rules, principal messages
- `public/academics.php` — departments, timetable downloads, staff directory, clubs, awards
- `public/admissions.php` — process, requirements, application form
- `public/contact.php` — contact form, map, office hours
- `src/includes/footer.php` — shared footer with settings-driven contact details
- `src/config/database.php` — PDO connection with `getDB()` and `getSettings()`

---

## [1.0.0] — March 2026

### Added
- Project initialised — repository created, directory structure established
- `database/schema.sql` — full database schema
- `database/seed.sql` — demo/seed data
- `public/index.php` — homepage with hero carousel, stats, departments, staff preview, news, YouTube embed, testimonials, admissions CTA, newsletter subscription
- `src/includes/header.php` — shared public header with BASE_PATH auto-detection, full responsive navigation
- `public/assets/css/style.css` — complete public site stylesheet
- `public/assets/js/main.js` — navigation, announcement bar, scroll reveal, carousel
- Design system locked: purple #3d1a6e, blue #4a90d9, gold #e8a020; Playfair Display + DM Sans