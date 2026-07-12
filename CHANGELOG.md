# Changelog

All notable changes to the Ibeku High School website project are recorded here.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

Developer: **Nweke Kenneth Nnaemeka** — NYSC CDS Project, AB/25C/0245  
Institution: **Ibeku High School**, Umuahia, Abia State

---

## [Unreleased]
- Student portal password change page
- Dedicated announcements table with admin CRUD
- PWA screenshot images for manifest
- Open Graph image for social sharing
- Newsletter unsubscribe endpoint
- Pre-production path audit (manifest.json, sw.js, API fetch paths)

---

## [6.3.0] — July 2026

### Added
- `public/admin/student-notices.php` — compose and send official notices to one or multiple students; notice types: Suspension, Expulsion, Promotion, Demotion, Retention, Behavioural Remark; role restrictions enforced per type; quick-fill title templates per type; real-time student search and multi-select recipient picker; recent notices log with read status
- `public/admin/student-portal.php` — per-student portal and results access control; lock/unlock portal login; block/unblock results access with reason; password reset to admission number; bulk select and bulk actions across filtered student list; audit columns (blocked_by, blocked_at) recorded on every action; reason modal for all destructive actions
- `src/includes/admin-sidebar.php` — added Portal Access (🔐) and Student Notices (📢) nav links with correct role restrictions

### Changed
- `public/admin/students-create.php` — admission number is now an editable field (pre-filled with auto-generated value, overrideable before save); duplicate admission number validation added; default portal password hashed and saved on student creation; password note shown in form; photo save path corrected to `assets/images/students/`
- `public/portal/dashboard.php` — photo path corrected from `assets/images/staff/` to `assets/images/students/`
- `public/portal/profile.php` — photo path corrected from `assets/images/staff/` to `assets/images/students/`

### Database
- `students` table: added `password`, `portal_blocked`, `portal_blocked_reason`, `portal_blocked_by`, `portal_blocked_at`, `results_blocked`, `results_blocked_reason`, `results_blocked_by`, `results_blocked_at` columns
- New table: `student_notifications` — stores all official notices issued to students by admin staff

---

## [6.2.0] — July 2026

### Added
- `public/portal/login.php` — student login by admission number; default password is the admission number; branded login page matching school design system
- `public/portal/logout.php` — secure session destruction
- `public/portal/dashboard.php` — student home page with welcome card (name, class, photo), quick links, results blocked warning, recent notices preview
- `public/portal/profile.php` — read-only student profile with personal, academic, and parent/guardian details; photo displayed from `assets/images/students/`
- `public/portal/results.php` — term-by-term academic results viewer; term selector; summary stats (subjects, average, highest, lowest); results table with CA score, exam score, total, grade, and remark; grade key; respects `results_blocked` flag with reason message
- `public/portal/notifications.php` — student notices inbox with two-panel layout (list + open view); unread dot and badge; mark-as-read on open; all notice types with colour-coded badges
- `public/portal/blocked.php` — access blocked page shown when `portal_blocked = 1`; displays reason if provided; school contact numbers from `$_site` settings; contact form submits to `contact_messages` table; auto-reloads when admin unblocks
- `src/includes/auth.php` — full student portal auth library: `portalSessionStart()`, `currentStudent()`, `studentLoggedIn()`, `requireStudentLogin()`, `loginStudent()`, `refreshStudentSession()`, `logoutStudent()`, `gradeLabel()`, `sectionLabel()`
- `src/includes/portal-nav.php` — shared student portal navigation with unread badge, mobile burger menu, and active state
- `public/assets/css/portal.css` — complete student portal stylesheet using school design tokens (purple #3d1a6e, blue #4a90d9, gold #e8a020); responsive at 680px and 700px breakpoints; covers header, dashboard, quick links, profile, results, notifications, blocked, empty states
- `public/portal/.htaccess` — portal security rules: no directory listing, hidden file blocking, security headers

---

## [6.1.0] — July 2026

### Added
- `src/includes/push-helper.php` — extracted all VAPID/Web Push functions into a shared helper; `base64url_encode()`, `buildVapidHeader()`, `derToRawSignature()`, `sendWebPushNotification()`, `sendPushToUser()` (targeted push by user_id), `ensurePushUserIdColumn()` (safe ALTER TABLE migration), `sendStaffMessageEmail()` (branded HTML email to staff)
- All functions wrapped in `function_exists()` guards for safe co-inclusion with `push-notifications.php`

### Changed
- `public/admin/messages.php` — on message send: fires targeted push notification to recipient's subscribed browser(s) and branded HTML email to recipient's email address simultaneously; push + email status reported in success alert; purple "Enable Notifications" banner shown to admin users who haven't subscribed; subscribe flow saves subscription to `push_subscriptions` with `user_id` for targeted delivery; JSON POST handler for subscription saving; already-subscribed admins see green confirmation note instead of banner
- `push_subscriptions` table: `user_id` column added via `ensurePushUserIdColumn()` safe migration (run on first messages.php load)

---

## [5.0.0] — June–July 2026

### Added
- `public/manifest.json` — Web App Manifest with name, short name, icons (192px, 512px, SVG), theme colour (#3d1a6e), display mode standalone, shortcuts to Results and News
- `public/sw.js` — Service worker with three-cache strategy: `ihs-static-v1` (CSS/JS/icons, cache-first, pre-cached on install), `ihs-pages-v1` (public pages, network-first with offline fallback), `ihs-images-v1` (images, cache-first as visited); admin and portal routes explicitly excluded from all caching; cache versioning and old-cache cleanup on activate; `skipWaiting()` + `clients.claim()` for immediate activation
- `public/offline.php` — branded offline fallback page served when network fails and no cache match; lists all cacheable public pages; auto-reloads on connection restore; school design system styled
- `public/assets/js/pwa.js` — service worker registration; SW update detection with refresh banner; custom install banner (Android/Chrome, fires after 3 seconds, respects 7-day dismiss); iOS Safari install nudge (Share → Add to Home Screen instruction); online/offline status bar; all banners use school design tokens; `urlBase64ToUint8Array()` for VAPID key conversion
- `src/includes/header.php` — PWA head block: manifest link, theme-color, mobile-web-app-capable, apple-mobile-web-app-capable, apple-mobile-web-app-status-bar-style, apple-mobile-web-app-title, apple-touch-icon; pwa.js script tag using BASE_PATH; resolves correctly on both localhost and production
- Push notification broadcast admin page (`push-notifications.php`) with VAPID-based self-contained pure PHP implementation using OpenSSL — no Firebase, no third-party services
- `push_subscriptions` table for storing browser push subscriptions
- `push_broadcast_log` table for broadcast history

---

## [4.0.0] — May–June 2026

### Added
- Full CMS admin panel with role-based access control across 8 roles
- Admin login, logout, session management (`src/includes/admin-auth.php`)
- Dashboard with metrics
- Results entry, approval, and publishing workflow across 3-stage pipeline
- Timetable PDF upload pages for SS and JS deans (`timetables-ss.php`, `timetables-js.php`)
- News create and edit with TinyMCE rich text editor
- Events create and management
- Gallery management with bulk publish/unpublish and category filtering; onerror infinite loop protection on missing images
- Student management pages — list, create, edit, promote
- Staff directory management
- Hall of Fame nominations and approvals
- Admissions enquiry management
- Alumni, clubs, awards, scholarships, prefects management
- Review moderation (approve/reject public testimonials)
- Internal staff messaging — inbox, sent, compose, reply, delete, mark read/unread, unread badge on sidebar bell
- Newsletter subscriber management — view, search, filter, export CSV, bulk delete, broadcast email
- Site-wide settings page (announcement bar, principal messages, YouTube video, social links, school contact details, current session)
- Manage Users, Manage Classes, Manage Subjects pages
- Admin sidebar with role-filtered navigation, unread message badge

### Fixed
- `public/admin/timetables-ss.php` — added missing `require_once database.php` (fixes `getDB() undefined` fatal error)
- `public/admin/timetables-js.php` — same fix
- `public/admin/newsletter-admin.php` — corrected all three `created_at` column references to `subscribed_at` to match actual `subscribers` table schema; fixed broken duplicate count query
- `public/admin/gallery.php` — `onerror` attribute on gallery images changed from recursive loop to `this.onerror=null` pattern; falls back to school icon as placeholder

---

## [3.0.0] — April–May 2026

### Added
- `public/results.php` — public-facing exam result lookup by admission number, class, and term; AJAX-powered via `src/api/check_result.php`
- `public/gallery.php` — categorised photo gallery with lightbox and lazy loading
- `public/students.php` — prefects listing, alumni section, scholarships, Hall of Fame teaser
- `public/hall-of-fame.php` — dedicated Hall of Fame with nomination form
- `public/news.php` + `public/news-single.php` — dynamic news listing and article pages; TinyMCE HTML sanitised before display
- `public/events.php` — upcoming and past events
- `public/verify-review.php` — email-based review verification flow
- `src/api/submit_review.php` — review submission with honeypot spam protection and email verification token
- `src/api/submit_contact.php` — contact form handler
- `src/api/submit_admission.php` — admissions enquiry handler
- `src/api/subscribe.php` — newsletter subscription endpoint
- `src/api/push-subscribe.php` + `push-unsubscribe.php` — push notification opt-in/out endpoints

---

## [2.0.0] — March–April 2026

### Added
- `public/about.php` — school history, vision & mission, anthem, rules and regulations, SS and JS principal messages
- `public/academics.php` — departments and subjects, timetable PDF downloads, staff directory, clubs & societies, awards, learning resources
- `public/admissions.php` — step-by-step admissions process, requirements, fee structure, application form
- `public/contact.php` — contact form, school map, office hours
- `src/includes/footer.php` — shared footer with settings-driven contact details, social links, newsletter form, quick links
- `src/config/database.php` — PDO connection wrapper with `getDB()` and `getSettings()` functions; auto-detects localhost vs production for BASE_PATH

---

## [1.0.0] — March 2026

### Added
- Project initialised — repository created, directory structure established
- `database/schema.sql` — full database schema for all tables
- `database/seed.sql` — demo/seed data for local development
- `public/index.php` — homepage with hero carousel (3 slides), quick links strip, stats band, about section, principal's message, departments (Sciences, Arts, Commercial), why-choose-us benefits, online result checker widget, staff preview, news section, YouTube embed, testimonials with star rating form, admissions CTA with mini application form, newsletter subscription
- `src/includes/header.php` — shared public header with BASE_PATH auto-detection, dynamic settings integration, full responsive navigation with dropdowns, announcement bar
- `public/assets/css/style.css` — complete public site stylesheet
- `public/assets/js/main.js` — navigation, announcement bar dismiss, scroll reveal animations, carousel
- Design system locked: primary purple `#3d1a6e`, primary blue `#4a90d9`, gold accent `#e8a020`; Playfair Display (headings) + DM Sans (body)