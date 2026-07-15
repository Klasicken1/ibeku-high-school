# Ibeku High School — Official Website

> **Live domain:** [ibekuhighschool.com](https://ibekuhighschool.com)  
> **Developer:** Nweke Kenneth Nnaemeka  
> **Project type:** NYSC Community Development Service (CDS) — Digital Transformation  
> **Institution:** Ibeku High School, Umuahia, Abia State, Nigeria

---

## Overview

This is the official website and digital management platform for **Ibeku High School**, one of the oldest and most respected government secondary schools in South-East Nigeria, founded in 1954.

The project was conceived and delivered as an NYSC CDS initiative by Kenneth Nweke (Corps Member AB/25C/0245), a Digital Technology teacher posted to Ibeku High School. It replaces the school's previous digital absence with a fully functional, database-driven web platform built entirely from scratch — no frameworks, no third-party CMS, and no external PHP dependencies beyond the standard library.

---

## What It Does

The platform serves three distinct user groups with separate, secured interfaces:

### Public Website
A fully responsive, SEO-optimised public-facing site covering every aspect of school life:
- **Homepage** with hero carousel, stats, departments, staff preview, testimonials, and admissions enquiry form
- **About** — school history, vision and mission, anthem, rules, and principal messages
- **Academics** — departments, subjects, timetable downloads, staff directory, clubs, awards
- **Students** — prefects, Hall of Fame, alumni, scholarships
- **News and Blog** — dynamic articles with rich-text content and featured images
- **Events** — upcoming and past school events
- **Gallery** — categorised photo gallery with lightbox
- **Admissions** — step-by-step process and online enquiry form
- **Contact** — contact form wired to admin inbox
- **Results checker** — public-facing exam result lookup by admission number
- **Newsletter** — subscription with working unsubscribe endpoint

### Admin Panel (`/admin`)
A full content management system accessible only to authenticated staff:
- Role-based access control across 8 staff roles (superadmin, principal, VP Admin, VP Academics, VP General, Dean, Form Teacher, Subject Teacher)
- Dashboard with key metrics
- Results entry, approval, and publishing workflow (3-stage pipeline)
- Timetable PDF uploads for SS and JS sections
- News, events, and gallery management
- Student management — registration with auto-generated editable admission numbers, promotion, photo upload, portal access control
- Staff directory management
- Hall of Fame nominations and approvals
- Admissions enquiry management
- Alumni, clubs, awards, scholarships, prefects, milestones management
- Review moderation
- Internal staff messaging with push and email notifications
- Newsletter subscriber management with broadcast emails and working unsubscribe links
- Web push notification broadcasts (VAPID-based, no third-party service)
- Site-wide settings (announcement bar, principal messages, social links, school contact details)
- **Student portal access control** — lock/unlock portal login per student (bulk supported)
- **Student results access control** — block/unblock results per student (bulk supported)
- **Student notices** — send official notices (Suspension, Expulsion, Promotion, Demotion, Retention, Behavioural Remark) directly to student portal inbox with role restrictions per notice type

### Student Portal (`/portal`)
A dedicated, secured portal for enrolled students:
- Login by admission number (default password is admission number, changeable)
- **Password change page** with live strength meter and show/hide toggle
- Dashboard with welcome card, quick links, and recent notices
- Results viewer — term-by-term academic results with CA1, CA2, exam score, total, grade, and remark
- Profile page — personal and academic details (read-only; updates via admin)
- Notices inbox — all official school notices with unread badge
- Access blocked page — shown when portal access is restricted; shows school phone from settings; contact form
- Portal and results access can be individually restricted per student by authorised admins with audit trail (who blocked, when, reason)

---

## Technical Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2 (pure vanilla — no frameworks, no Composer) |
| Database | MariaDB 10.4 |
| Frontend | HTML5, CSS3, Vanilla JavaScript (no frameworks) |
| Hosting | cPanel shared hosting (Namecheap) |
| Push notifications | Web Push API + VAPID (self-contained pure PHP using OpenSSL) |
| PWA | Service Worker, Web App Manifest, three-cache offline strategy |
| Rich text | TinyMCE (admin CMS only) |
| Fonts | Google Fonts — Playfair Display + DM Sans |

**Zero external PHP dependencies.** No Composer, no Laravel, no Symfony, no third-party packages.

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
│   ├── unsubscribe.php        # Newsletter unsubscribe endpoint
│   ├── verify-review.php
│   ├── manifest.json          # PWA manifest
│   ├── sw.js                  # Service worker (true offline support)
│   ├── offline.php            # Offline fallback page
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
│   │   ├── change-password.php
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
│       ├── header.php         # Shared public header + PWA head + JS path globals
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
Fully installable on Android, iOS, and desktop — no app store required.

- **Three-cache strategy:** static shell (cache-first), public pages (network-first with cache fallback), images (cache-as-visited)
- **True offline support:** previously visited pages load without connection; unvisited pages show branded offline fallback
- **Install prompt:** custom banner on Android/Chrome; iOS instruction nudge
- **SW update detection:** users notified and prompted to refresh on new deploy
- **Admin and portal routes never cached** — require live database connection

### Web Push Notifications (No Third-Party Service)
Self-contained PHP implementation — no Firebase, no OneSignal, no paid service.

- VAPID keys stored in gitignored config file
- Public visitors can opt in from the website
- Admin staff can subscribe from the messages page to receive push alerts for new messages
- Broadcasts from admin panel to all subscribers
- Targeted push to individual staff on new message receipt
- Email fallback fires alongside push

### Role-Based Access Control
Eight roles with granular server-side permissions:

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
Students log in separately from staff using their admission number. Completely isolated from the admin system — separate session name, separate DB table, separate auth functions.

- Default password is the admission number; students change it on first login
- `portal_blocked` flag immediately redirects to contact page on every request
- `results_blocked` flag hides results with reason message
- Official notices (suspension, promotion, etc.) appear in student inbox with unread badges
- Session refreshed from DB on every dashboard load so admin changes take effect immediately
- Audit trail: every block action records who did it, when, and why

---

## Database

The database (`ibeku_school`) contains 30+ tables. Key tables:

| Table | Purpose |
|---|---|
| `students` | Student records with portal auth, block flags, audit columns, photo |
| `users` | Admin/staff accounts with role and section |
| `results` | Exam result headers with term, session, publish status |
| `result_scores` | Individual subject scores (CA1, CA2, exam, total, grade, remark) |
| `staff_messages` | Internal staff inbox |
| `student_notifications` | Official notices to students (6 types) |
| `push_subscriptions` | Web push subscription endpoints with optional user_id |
| `push_broadcast_log` | History of push broadcasts |
| `newsletter_log` | History of newsletter broadcasts |
| `subscribers` | Newsletter email subscriptions with unsubscribe timestamp |
| `gallery` | Gallery photos with category and publish status |
| `news` | News articles with rich-text content |
| `events` | School events |
| `hall_of_fame` | Hall of fame entries |
| `admissions` | Admission enquiries from the public |
| `contact_messages` | Contact form submissions |
| `reviews` | Public testimonials pending moderation |
| `settings` | Site-wide configuration key-value store (`key`, `value` columns) |
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
cp src/config/vapid.php.example src/config/vapid.php
# Edit src/config/vapid.php with your keys from https://vapidkeys.com

# 5. Visit the site
# http://localhost/ibeku-high-school/public/
# Admin: http://localhost/ibeku-high-school/public/admin/login.php
# Portal: http://localhost/ibeku-high-school/public/portal/login.php
```

**Note:** `BASE_PATH` and `API_PATH` auto-detect localhost vs production — no manual changes needed between environments.

---

## Production Deployment (cPanel with Git Version Control)

### One-time setup

**1. Enable Shell Access**
- cPanel → Software → Manage Shell → enable Git Shell or Jailed Shell

**2. Create the database**
- cPanel → Databases → MySQL Databases
- Create database: `ibekjcra_school` (cPanel prefixes your username)
- Create user, assign all privileges
- Import `database/schema.sql` via phpMyAdmin

**3. Set document root**
- cPanel → Domains → the domain entry → Document Root
- Set to: `public_html/public` so the domain serves from the `public/` subdirectory directly

**4. Clone repository via Git Version Control**
- cPanel → Files → Git Version Control → Create
- Clone URL: `https://github.com/Klasicken1/ibeku-high-school.git`
- Repository Path: `/home/ibekjcra/public_html`
- Create → then pull to deploy

**5. Configure database connection**
- Edit `src/config/database.php` with production credentials (host: `localhost`, db: `ibekjcra_school`)

**6. Upload VAPID keys**
- Create `src/config/vapid.php` on the server (not in git — gitignored)
- Add your VAPID public key, private key, and subject

**7. Enable SSL**
- cPanel → Security → SSL/TLS → AutoSSL → run for `ibekuhighschool.com`
- HTTPS is required for the service worker and push notifications

**8. Set directory permissions**
- `public/assets/images/` — 755
- `public/assets/images/gallery/` — 755
- `public/assets/images/students/` — 755
- `public/assets/images/staff/` — 755
- `public/assets/timetables/` — 755

### Updating after changes
```
# On your local machine:
git push

# In cPanel Git Version Control:
# Click the repository → Update → Pull
```

---

## Contact

**Ibeku High School**
Umuahia, Abia State, Nigeria
[ibekuhighschool.com](https://ibekuhighschool.com)
contact@ibekuhighschool.sch.ng

**Developer**
Nweke Kenneth Nnaemeka
NYSC Corps Member — AB/25C/0245
Digital Technology Teacher, Ibeku High School
[github.com/Klasicken1](https://github.com/Klasicken1)

---

## License

Copyright 2026 Ibeku High School and Nweke Kenneth Nnaemeka. All rights reserved.

This codebase is the intellectual property of the developer and Ibeku High School. It was created as an NYSC Community Development Service project and serves as the official digital platform of the institution.

**This code is not open source.** You may not copy, reproduce, distribute, modify, or use any part of this codebase — in whole or in part — for any purpose without the express written permission of the copyright holders.

Viewing this repository on GitHub is permitted for reference and portfolio verification purposes only.

For licensing enquiries: contact@ibekuhighschool.sch.ng