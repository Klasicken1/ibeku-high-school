# Ibeku High School — Official Website

> **Live domain:** [ibekuhighschool.sch.ng](https://ibekuhighschool.sch.ng)  
> **Developer:** Nweke Kenneth Nnaemeka  
> **Project type:** NYSC Community Development Service (CDS) — Digital Transformation  
> **Institution:** Ibeku High School, Umuahia, Abia State, Nigeria

---

## Overview

This is the official website and digital management platform for **Ibeku High School**, one of the oldest and most respected government secondary schools in South-East Nigeria, founded in 1954.

The project was conceived and delivered as an NYSC CDS initiative by Kenneth Nweke (Corps Member AB/25C/0245), a Digital Technology teacher posted to Ibeku High School. It replaces the school's previous digital absence with a fully functional, database-driven web platform — built entirely from scratch with no frameworks, no third-party CMS, and no external dependencies beyond the PHP standard library.

---

## What It Does

The platform serves three distinct user groups with separate, secured interfaces:

### Public Website
A fully responsive, SEO-optimised public-facing site covering every aspect of school life:
- **Homepage** with hero carousel, stats, departments, staff preview, testimonials, and admissions enquiry form
- **About** — school history, vision & mission, anthem, rules, and principal messages
- **Academics** — departments, subjects, timetable downloads, staff directory, clubs, awards
- **Students** — prefects, Hall of Fame, alumni, scholarships
- **News & Blog** — dynamic articles with rich-text content and featured images
- **Events** — upcoming and past school events
- **Gallery** — categorised photo gallery with lightbox
- **Admissions** — step-by-step process and online enquiry form
- **Contact** — contact form wired to admin inbox
- **Results checker** — public-facing exam result lookup by admission number

### Admin Panel (`/admin`)
A full content management system accessible only to authenticated staff:
- Role-based access control across 8 staff roles (superadmin, principal, VP Admin, VP Academics, VP General, Dean, Form Teacher, Subject Teacher)
- Dashboard with key metrics
- Results entry, approval, and publishing workflow
- Timetable PDF uploads (SS and JS separately)
- News, events, and gallery management
- Student management — registration, promotion, portal access control
- Staff directory management
- Hall of Fame nominations and approvals
- Admissions enquiry management
- Alumni, clubs, awards, scholarships, prefects, milestones
- Review moderation
- Internal staff messaging with push + email notifications
- Newsletter subscriber management with broadcast emails
- Web push notification broadcasts (VAPID-based, no third-party service)
- Site-wide settings (announcement bar, principal messages, social links, etc.)
- Student portal access control — lock/unlock portal login, block/unblock results per student
- Student notices — suspension, expulsion, promotion, demotion, retention, and behavioural remark notices sent directly to student portal inbox

### Student Portal (`/portal`)
A dedicated, secured portal for enrolled students:
- Login by admission number (default password = admission number, changeable)
- Dashboard with welcome card, quick links, and recent notices
- Results viewer — term-by-term academic results with grades and remarks
- Profile page — personal and academic details (read-only; updates via admin)
- Notices inbox — all official school notices with unread badge
- Access blocked page — shown when portal access is restricted, with contact form and school phone numbers
- Portal and results access can be individually restricted per student by authorised admins

---

## Technical Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2.12 (pure vanilla — no frameworks, no Composer) |
| Database | MariaDB 10.4.32 |
| Frontend | HTML5, CSS3, Vanilla JavaScript (no frameworks) |
| Hosting | cPanel (shared hosting) |
| Push notifications | Web Push API + VAPID (self-contained pure PHP implementation using OpenSSL) |
| PWA | Service Worker, Web App Manifest, three-cache offline strategy |
| Rich text | TinyMCE (admin CMS only) |
| Fonts | Google Fonts — Playfair Display + DM Sans |

**Zero external PHP dependencies.** No Composer, no Laravel, no Symfony, no third-party packages. Every line is handwritten.

---

## Architecture

```
ibeku-high-school/
├── public/                    # Web root — all publicly accessible files
│   ├── index.php              # Homepage
│   ├── about.php
│   ├── academics.php
│   ├── admissions.php
│   ├── contact.php
│   ├── events.php
│   ├── gallery.php
│   ├── hall-of-fame.php
│   ├── news.php
│   ├── news-single.php
│   ├── results.php
│   ├── students.php
│   ├── manifest.json          # PWA manifest
│   ├── sw.js                  # Service worker (true offline support)
│   ├── offline.php            # Offline fallback page
│   ├── verify-review.php      # Review email verification
│   ├── admin/                 # Admin panel (role-protected)
│   │   ├── login.php
│   │   ├── index.php          # Dashboard
│   │   ├── messages.php       # Staff messaging
│   │   ├── student-portal.php # Portal access control
│   │   ├── student-notices.php
│   │   └── ...                # 50+ admin pages
│   ├── portal/                # Student portal (student-auth-protected)
│   │   ├── login.php
│   │   ├── dashboard.php
│   │   ├── results.php
│   │   ├── profile.php
│   │   ├── notifications.php
│   │   ├── blocked.php
│   │   └── logout.php
│   └── assets/
│       ├── css/               # Stylesheets (style.css, portal.css, admin-layout.css, per-page)
│       ├── js/                # Scripts (main.js, admin.js, pwa.js, per-page)
│       └── images/            # Uploaded media (gallery, staff, students, icons)
├── src/                       # Server-side logic (not web-accessible)
│   ├── api/                   # AJAX endpoints
│   │   ├── check_result.php
│   │   ├── submit_admission.php
│   │   ├── submit_contact.php
│   │   ├── submit_review.php
│   │   ├── subscribe.php
│   │   ├── push-subscribe.php
│   │   └── ...
│   ├── config/
│   │   ├── database.php       # PDO connection + getSettings()
│   │   └── vapid.php          # VAPID keys (gitignored)
│   └── includes/
│       ├── header.php         # Shared public header + PWA head block
│       ├── footer.php         # Shared public footer
│       ├── admin-auth.php     # Admin session + role enforcement
│       ├── admin-sidebar.php  # Admin nav partial
│       ├── auth.php           # Student portal session functions
│       ├── portal-nav.php     # Student portal nav partial
│       ├── push-helper.php    # Shared VAPID push + email functions
│       └── functions.php      # Shared utility functions
├── database/
│   ├── schema.sql             # Full database schema
│   └── seed.sql               # Demo/seed data
└── docs/
    └── screenshots/           # UI screenshots
```

---

## Key Features

### Progressive Web App (PWA)
The site is fully installable as a PWA on Android, iOS, and desktop — no app store required. Students and parents can add it to their home screen directly from the browser for a native-app experience.

- **Three-cache strategy:** static shell (cache-first), public pages (network-first with cache fallback), images (cache-as-visited)
- **True offline support:** previously visited pages load without a connection; unvisited pages show a branded offline fallback
- **Install prompt:** custom install banner fires on Android/Chrome after 3 seconds; iOS users see a manual instruction nudge
- **SW update detection:** users are notified and prompted to refresh when a new version is deployed
- **Admin and portal routes are never cached** — they require a live database connection

### Web Push Notifications (No Third-Party Service)
Push notifications are delivered entirely through a self-contained PHP implementation — no Firebase, no OneSignal, no paid service required.

- VAPID keys generated once and stored in a gitignored config file
- Public website visitors can opt in to push notifications; their subscriptions are stored in `push_subscriptions`
- Admin staff can subscribe from the messages page to receive push alerts for new internal messages
- Broadcasts can be sent to all subscribers from the admin panel
- Targeted push delivered to individual staff members on new message receipt
- Email fallback fires alongside push for maximum reliability

### Role-Based Access Control
Every admin page enforces role checks server-side. Eight roles with granular permissions:

| Role | Key Permissions |
|---|---|
| superadmin | Full access to everything |
| principal | Content, students, notices, results publishing |
| vp_admin | Students, portal control, admissions |
| vp_academics | Results workflow, portal control |
| vp_general | News, events, gallery, newsletter |
| dean | Timetables, events, student notices |
| form_teacher | Results entry/approval, behavioural remarks |
| subject_teacher | Results entry only |

### Student Portal
Students log in separately from staff, using their admission number. The portal is completely isolated from the admin system — separate session name, separate DB table, separate auth functions.

Key behaviours:
- Default password is the admission number; students can change it after first login
- `portal_blocked` flag immediately redirects to a contact page on every request
- `results_blocked` flag hides results with a reason message
- All official notices (suspension, promotion, etc.) appear in the student's inbox with unread badges
- Session is refreshed from DB on every dashboard load so admin changes take effect immediately

---

## Database

The database (`ibeku_school`) contains 30+ tables covering every feature area. Key tables:

| Table | Purpose |
|---|---|
| `students` | Student records with portal auth, block flags, and photo |
| `users` | Admin/staff accounts with role and section |
| `results` | Exam results with subject, term, session, status |
| `result_scores` | Individual subject scores (CA + exam) |
| `staff_messages` | Internal staff inbox |
| `student_notifications` | Official notices to students |
| `push_subscriptions` | Web push subscription endpoints (with optional user_id) |
| `push_broadcast_log` | History of push broadcasts |
| `newsletter_log` | History of newsletter broadcasts |
| `subscribers` | Newsletter email subscriptions |
| `gallery` | Gallery photos with category and publish status |
| `news` | News articles with rich-text content |
| `events` | School events |
| `hall_of_fame` | Hall of fame entries |
| `admissions` | Admission enquiries from the public |
| `contact_messages` | Contact form submissions |
| `reviews` | Public testimonials pending moderation |
| `settings` | Site-wide configuration key-value store |
| `class_arms` | Active class groups |
| `subjects` | Subject catalogue |

---

## Setup (Local Development)

### Requirements
- PHP 8.1+
- MariaDB / MySQL 10.4+
- Apache or Nginx with mod_rewrite
- XAMPP, Laragon, or similar local stack

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/Klasicken1/ibeku-high-school.git
cd ibeku-high-school

# 2. Create the database
# In phpMyAdmin or MySQL CLI:
CREATE DATABASE ibeku_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# Then import:
mysql -u root ibeku_school < database/schema.sql
mysql -u root ibeku_school < database/seed.sql

# 3. Configure database connection
# Edit src/config/database.php with your local credentials

# 4. Configure VAPID keys (for push notifications)
# Copy the example and add your keys from https://vapidkeys.com
cp src/config/vapid.php.example src/config/vapid.php
# Edit src/config/vapid.php with your keys

# 5. Set BASE_PATH
# In src/includes/header.php, BASE_PATH is already set correctly:
# localhost → /ibeku-high-school/public/
# production → /

# 6. Visit the site
# http://localhost/ibeku-high-school/public/
# Admin: http://localhost/ibeku-high-school/public/admin/login.php
# Portal: http://localhost/ibeku-high-school/public/portal/login.php
```

### Default Admin Login
See `database/seed.sql` for the default superadmin credentials. **Change the password immediately after first login.**

---

## Production Deployment (cPanel)

1. Upload all files to the cPanel `public_html` directory (or a subdirectory)
2. Import `database/schema.sql` via phpMyAdmin
3. Update `src/config/database.php` with production credentials
4. Update `src/config/vapid.php` with your VAPID keys
5. In `src/includes/header.php`, the `BASE_PATH` auto-switches to `/` on non-localhost
6. In `public/manifest.json`, update `start_url` and `scope` from `/ibeku-high-school/public/` to `/`
7. Enable AutoSSL (Let's Encrypt) in cPanel — **HTTPS is required for the service worker and push notifications**
8. Create an `images/students/` directory under `public/assets/images/` and set permissions to 755

---

## Contact

**Ibeku High School**  
Umuahia, Abia State, Nigeria  
[ibekuhighschool.sch.ng](https://ibekuhighschool.sch.ng)  
contact@ibekuhighschool.sch.ng

**Developer**  
Nweke Kenneth Nnaemeka  
NYSC Corps Member — AB/25C/0245  
Digital Technology Teacher, Ibeku High School  
[github.com/Klasicken1](https://github.com/Klasicken1)

---

## License

Copyright © 2026 Ibeku High School & Nweke Kenneth Nnaemeka. All rights reserved.

This codebase is the intellectual property of the developer and Ibeku High School. It was created as an NYSC Community Development Service project and serves as the official digital platform of the institution.

**This code is not open source.** You may not copy, reproduce, distribute, modify, or use any part of this codebase — in whole or in part — for any purpose without the express written permission of the copyright holder.

Viewing this repository on GitHub is permitted for reference and portfolio verification purposes only.

For licensing enquiries, contact: contact@ibekuhighschool.sch.ng