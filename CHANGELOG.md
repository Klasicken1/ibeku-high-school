# Changelog — Ibeku High School Official Website

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [4.0.0] — 2026-07-01

### Added — Phase 4 Complete (CMS & Content Management)

Full content management system built on top of the Phase 3 admin panel.
Every section of every public page is now database-driven — no hardcoded
placeholder content remains. Administrators manage the entire website from
the admin panel without touching code. Nine new database tables, nine new
admin pages, three new public APIs, and comprehensive rewiring of all
public pages with hardcoded fallbacks throughout.

#### Admin Pages

**Settings & Site Customiser** (`public/admin/settings.php`)
- 7-tab settings interface
- School Info tab — name, tagline, motto, address, phone, email, website,
  days and hours of operation
- Principals tab — SS and JS principal names and welcome messages; wired to
  about.php and index.php
- Academic tab — current session/term, next term resumption date, result
  checker toggle, admissions form toggle
- Announcement tab — site-wide banner with show/hide toggle, text, optional
  link URL and link text, live preview
- Popup tab — intrusive notification popup with title, message, optional
  link, scroll-percentage trigger, time-on-page trigger, live preview
- Media tab — YouTube video ID or full URL for homepage embed with live
  iframe preview; auto-extracts video ID from full YouTube URLs
- System tab — PHP version, server software, current session/term,
  logged-in admin info

**Staff Directory** (`public/admin/staff.php`)
- Full CRUD with photo upload (JPG/PNG/WEBP, max 3MB)
- Fields: full name, role, department, section (SS/JS/Both),
  category (administration/sciences/arts/commercial/support), bio, sort order
- Filter by section and category; publish/unpublish per profile
- Single source of truth — replaces all hardcoded staff on about.php
  and academics.php

**History Milestones** (`public/admin/milestones.php`)
- Full CRUD for the about.php history timeline
- Fields: era label (e.g. "1954", "1960s"), title, description, sort order
- Sort order controls alternating left/right timeline layout

**Clubs & Societies** (`public/admin/clubs.php`)
- Full CRUD for the academics.php clubs section
- Fields: name, emoji icon, description, patron/teacher in charge, sort order

**Competitions & Awards** (`public/admin/awards.php`)
- Full CRUD for the academics.php awards section
- Fields: title, description, year/period label, emoji icon, badge text,
  sort order
- Colour rotation (gold/blue/purple) handled automatically by display index

**Alumni Directory** (`public/admin/alumni.php`)
- Full CRUD with photo upload
- Featured alumni appear on homepage and students.php notable alumni section
- Published alumni appear on the hall-of-fame.php alumni wall
- Filter by featured status; total and featured count stats shown

**Scholarships** (`public/admin/scholarships.php`)
- Full CRUD for scholarship listings shown on students.php
- Fields: title, description, eligibility, contact info, emoji icon,
  colour theme (purple/blue/gold), sort order

**Prefects** (`public/admin/prefects-admin.php`)
- Full CRUD for student leader profiles organised by academic session
- Session tabs auto-generated from DB; current session pre-selected
- Fields: full name, role, section (SS/JS), session, quote, photo, sort order
- Role-restricted to superadmin and principal

**Hall of Fame** (`public/admin/hall-of-fame-admin.php`)
- Full CRUD across 5 categories: Distinguished Alumni, Academic Star,
  Sports Champion, Head Prefect, Legendary Staff
- Photo upload; category filter; stat panel with link to nominations inbox

**Nominations Inbox** (`public/admin/nominations.php`)
- Lists all public nominations by status: New → Reviewed → Converted → Declined
- Detail view with full nomination content and internal notes field
- Status update workflow with notes
- One-click Convert to Hall of Fame Entry — creates an unpublished entry
  for review before going live

**Reviews Management** (`public/admin/reviews.php`)
- Pending / Approved / Rejected tab workflow with badge counts
- Unverified review count shown as a warning (submitter hasn't clicked
  their confirmation link yet)
- Approve, reject, move-to-pending, and delete actions
- Shows reviewer name, email, relationship badge, star rating, review text,
  and who actioned it and when
- Role-restricted to superadmin, principal, and vp_general

#### Public APIs

**Nomination Submission** (`src/api/submit_nomination.php`)
- Validates nominator name/email, nominee name, reason (min 30 chars)
- Honeypot bot-spam protection
- Saves to hall_of_fame_nominations; returns JSON success/error

**Review Submission** (`src/api/submit_review.php`)
- Validates name, email, relationship, rating (1–5), review text (min 20 chars)
- Honeypot bot-spam protection
- Rate limit: 1 review per email per 24 hours
- Generates 64-character hex verification token
- Saves as is_verified=0, status='pending'; returns verification URL in JSON
- No email infrastructure required — token link shown directly to user

**Review Verification** (`public/verify-review.php`)
- Standalone page; reached by clicking the verification link
- Sets is_verified=1, status='pending' on the matching review
- Branded success/error card; self-contained (no header/footer dependency)

#### Database Tables Added (9 new)

| Table | Purpose |
|---|---|
| `settings` | All site-wide configuration key-value pairs |
| `staff` | Staff directory powering about, academics, and homepage |
| `milestones` | History timeline on about.php |
| `clubs` | Clubs & Societies on academics.php |
| `awards` | Competitions & Awards on academics.php |
| `alumni` | Alumni wall (hall-of-fame) and featured cards (students, index) |
| `scholarships` | Scholarship listings on students.php |
| `prefects` | Prefect profiles by academic session on students.php |
| `hall_of_fame` | Hall of Fame inductees across 5 categories |
| `hall_of_fame_nominations` | Public nomination form submissions |
| `reviews` | Visitor reviews with verification and approval workflow |
| `staff_messages` | Internal staff messaging (schema only; UI in Phase 5) |

#### Public Pages Rewired

**index.php (Homepage)**
- Staff preview queries first 4 published staff by sort order; falls back
  to principal names from settings if DB is empty
- Principal name and message pulled from settings
- School motto pulled from settings
- Admissions session label pulled from settings
- YouTube embed section conditionally rendered — only shown when a video ID
  is saved in settings; hidden otherwise
- Testimonials section queries latest 3 approved reviews from DB; falls back
  to 3 hardcoded testimonials when none approved yet
- Review submission form added inline — star picker, relationship selector,
  fetch POST to submit_review.php, shows verification link on success
- Star picker implemented in vanilla JS with hover and click states
- BASE_PATH applied to all internal links (was previously hardcoded to `/`)

**about.php**
- DB connection added
- Timeline section queries milestones table with alternating left/right
  layout driven by index parity; falls back to 6 hardcoded milestones
- Staff directory queries staff table with category filter buttons;
  photo with initials fallback; falls back to empty-state message

**academics.php**
- DB connection and all queries moved to top of file
- Staff directory queries staff table (same source as about.php —
  no duplication)
- Clubs section queries clubs table; falls back to 8 hardcoded clubs
- Awards section queries awards table; falls back to 6 hardcoded awards

**students.php**
- DB connection added
- Prefects section queries prefects table filtered by current_session
  from settings; head prefects (Head Boy/Girl) and other prefects queried
  separately; empty-state message when none added
- Notable alumni queries alumni WHERE is_featured=1 LIMIT 6;
  empty-state fallback
- Scholarships queries scholarships WHERE is_published=1;
  falls back to 6 hardcoded scholarships

**hall-of-fame.php**
- DB connection added
- All 5 category sections query hall_of_fame table grouped by category;
  photo with initials fallback; per-section empty-state messages
- Hero inductee count shows real count from DB
- Alumni wall queries alumni WHERE is_published=1; field filter buttons
  generated dynamically from actual DB field values
- Alumni wall JS filter updated to use data-filter attributes
- Nomination form converted from static HTML to fetch POST with validation,
  loading state, honeypot, success/error display

#### Site Infrastructure Changes

**src/config/database.php**
- getSettings() extended with all new keys: school_hours, popup_show,
  popup_title, popup_text, popup_link, popup_link_text,
  popup_trigger_scroll, popup_trigger_seconds, youtube_video_id,
  youtube_video_title

**src/includes/header.php**
- Topbar removed entirely
- Announcement bar rewritten with sessionStorage-based dismiss
- Popup notification markup rendered from settings with data-scroll-pct
  and data-delay-seconds attributes
- Nav dropdown modernised from display:none/block to
  opacity/visibility/transform fade+slide

**src/includes/footer.php**
- New .footer__contact-strip added above footer grid — shows address,
  hours, email, and phone from settings; replaces the removed topbar

**src/includes/admin-sidebar.php**
- New sidebar links added: Staff Directory, History Timeline,
  Clubs & Societies, Awards, Alumni Directory, Scholarships,
  Prefects (role-restricted), Hall of Fame, Nominations, Reviews
  (role-restricted)

**public/assets/js/main.js**
- initSitePopup() — fires on scroll% OR time-on-page, whichever first;
  dismissed per session via sessionStorage
- initNav() — rewritten: desktop first-click opens dropdown (prevents
  accidental navigation), second click navigates, click outside closes;
  mobile tap-only toggle
- Announcement bar sessionStorage dismiss replaces CSS class toggle

---

## [3.0.0] — 2026-06-25

### Added — Phase 3 Complete (Admin Panel)

Full role-based admin panel with authentication, results entry and
publishing workflow, timetable PDF uploads, and student management.

- Role-based access control — 6 roles: superadmin, principal,
  vp_admin, vp_academics, vp_general, form_teacher
- SS/JS section separation throughout admin panel
- Admin authentication with session management (admin-auth.php)
- Shared admin sidebar with role-restricted link visibility
- Dashboard with quick-action cards per role
- Results entry — form teachers enter subject scores per student per term
- Results publishing — VP Academics approves and publishes results
- Timetable upload — PDF uploads for all 6 class levels with meta.json
  tracking last-updated timestamps per class
- Manage Users — superadmin creates and edits admin accounts
- Admin layout CSS (admin-layout.css) separate from public styles
- Admin JS (admin.js) for panel-specific interactions

---

## [2.0.0] — 2026-06-19

### Added — Phase 2 Complete (Backend)

- Full MySQL database schema — 14 tables covering users, students,
  subjects, results, result_scores, news, gallery, events, timetables,
  admissions, subscribers, contact_messages, hall_of_fame, prefects
- Seed data — 9 staff users across all role types, 10 students,
  25 subjects, sample results, news, events, gallery items, prefects,
  admissions enquiries
- PDO database connection layer (src/config/database.php) with .env
  config loading, grade calculator, and safe output escaping
- check_result.php — live result checker API replacing demo JS data
  with real database queries
- submit_contact.php — contact form API with full server-side
  validation; saves to contact_messages table
- submit_admission.php — admissions enquiry API with validation;
  saves to admissions table
- subscribe.php — newsletter signup API with duplicate detection and
  resubscription handling
- All four frontend forms (results, contact, admissions, newsletter)
  wired to live APIs via fetch()
- Printable result sheet now pulls live form teacher and principal
  comments from the database

### Fixed
- Result checker silent failure caused by missing variable declaration
  in renderResultFull()
- MySQL port 3306 conflict between XAMPP and a standalone MySQL80 service
- Multi-line anchor tags in contact.php map section breaking HTML render

---

## [1.0.0] — 2026-05-31

### Added — Phase 1 Complete (Static Frontend)

- All 11 public pages built and fully functional
- Shared CSS foundation (style.css)
- Shared PHP header and footer with BASE_PATH auto-detection
- Shared JavaScript — nav, scroll reveal, back to top, anchor nav
- Homepage with hero carousel and live result checker demo
- About page — history, timeline, both principals, facilities
- Hall of Fame — alumni, academic stars, sports, prefects
- Academics — departments, downloadable timetables (6 PDFs),
  staff directory
- Results page — full checker, grading system, printable result sheet
- Admissions page — requirements, steps, fees, application form
- Contact page — Google Maps embed and department contacts
- News page — featured article, filterable grid, announcements
- Gallery — filterable categories, lightbox, keyboard navigation
- Students page — prefects, alumni, scholarships
- Events page — featured, upcoming list, past events
- Role-based admin architecture (6 roles, SS/JS section separation)
- Placeholder timetable PDFs for all 6 class levels

---

## [0.1.0] — 2026-04-22

### Added — Project Initialised

- Repository created and folder structure established
- README, .gitignore, .env.example added
- Phase 1 static frontend begun
- Design system locked: #4a90d9 (blue) + #3d1a6e (purple),
  Playfair Display + DM Sans

---

## Planned — Phase 5

- **Staff internal messaging** — bell icon and dropdown in admin header;
  staff_messages table already in DB; notification triggers on results
  entry and form teacher approval events
- **Browser push notifications** — Web Push API with VAPID keys;
  service worker push event handler; admin interface to compose and
  broadcast announcements to subscribed visitors and parents;
  subscription stored per browser with opt-in prompt
- **PWA conversion** — manifest.json, service worker with layered
  caching strategy (cache-first for static assets, network-first for
  public pages), offline fallback page, online/offline banner;
  when installed as a PWA, push notifications delivered as native
  app notifications via the same Web Push / VAPID infrastructure
- **News & announcements CMS** — full CRUD for the news.php page
- **Gallery CMS** — upload and manage gallery images from admin panel
- **Events CMS** — manage the events.php calendar from admin panel
- **Admissions form backend** — PHP handler connecting the admissions
  enquiry form to a notifiable inbox
- **Newsletter broadcast** — subscriber list management and
  announcement emails
- **cPanel production deployment**