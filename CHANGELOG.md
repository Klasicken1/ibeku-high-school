# Changelog — Ibeku High School Official Website

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [5.0.0] — 2026-07-01

### Added — Phase 5 Complete (CMS Completion, PWA, Push Notifications)

Phase 5 completes the CMS layer by rewiring the three remaining
hardcoded public pages, adds staff internal messaging, browser
push notifications with a self-contained VAPID implementation,
PWA installability, a service worker with offline support, and
newsletter broadcast management.

---

#### Public Pages Rewired (DB-driven)

**news.php**
- Featured article queries `news WHERE featured = 1` (falls back
  to most recent published article if none is featured)
- Article grid queries all remaining published articles ordered
  by published_at DESC
- Category filter and search driven by data attributes on rendered
  cards — no page reload needed
- Announcements remain a hardcoded fallback (no dedicated table yet)
- Newsletter form posts to `src/api/subscribe.php`

**gallery.php**
- Queries `gallery WHERE is_published = 1` ordered by sort_order
- Falls back to 12 hardcoded placeholder cards when DB is empty
- Category filter tabs generated from hardcoded category list
- Gallery data passed as `window.GALLERY_DATA` JSON for lightbox JS
- Photo `onerror` fallback to placeholder icon if image file missing

**events.php**
- Featured event: most recent upcoming published and featured event
- Secondary events: next 2 upcoming after featured
- Full upcoming list: all remaining upcoming published events
- Past events: last 6 published events before today; falls back to
  6 hardcoded past events if DB is empty
- Category filter JS on upcoming list only

---

#### Staff Internal Messaging (`public/admin/messages.php`)
- Inbox, Sent, and Compose views in a two-panel layout
- Compose with recipient dropdown (all active users except self),
  subject, and message body
- Open message view with full content, sender/recipient details,
  and reply shortcut (pre-fills recipient and subject)
- Mark individual messages as read on open; mark all read action
- Delete own messages (sent or received)
- Unread count badge on the Messages sidebar link
- Bell icon with unread count in sidebar header area
- Accessible to all admin roles
- `staff_messages` table auto-created with `CREATE TABLE IF NOT EXISTS`
  on first page load (schema was created in Phase 4; UI built here)

---

#### Web Push Notifications

**VAPID infrastructure (`src/config/vapid.php`)**
- VAPID public/private key constants with `.env` override support
- File added to `.gitignore` to protect the private key
- Key pair generated at https://vapidkeys.com/

**Push subscription API (`src/api/push-subscribe.php`)**
- Accepts raw JSON POST with `endpoint`, `keys.p256dh`, `keys.auth`
- Creates `push_subscriptions` table if not exists
- Upserts subscription by endpoint (handles re-subscriptions)
- Returns JSON success/error

**Push unsubscribe API (`src/api/push-unsubscribe.php`)**
- Deletes subscription by endpoint
- Returns JSON success/error

**Service Worker (`public/sw.js`)**
- Registers at scope `/ibeku-high-school/`
- **Cache-first** for all static assets (CSS, JS, fonts, images)
- **Network-first** for all public PHP pages; caches fresh responses;
  falls back to stale cache, then offline page
- **No caching** for admin panel, portal, results checker, APIs,
  or verify-review (session-dependent or write-sensitive)
- Pre-caches 5 static assets and 5 core pages on install
- Cleans up old cache versions on activate
- `push` event handler: receives payload JSON, calls
  `registration.showNotification()` with title, body, icon, badge
- `notificationclick` event: focuses existing window or opens new
  window at the notification's target URL
- `message` event: handles `SKIP_WAITING` for instant updates
- Cache version bumped in `CACHE_VERSION` constant to invalidate
  all caches when assets change

**Push opt-in banner (`src/includes/footer.php`)**
- Rendered only when VAPID keys are configured
- Appears 8 seconds after page load (never on admin pages)
- "Yes, notify me" calls `ihsSubscribePush()` in main.js
- "No thanks" dismisses for the session via sessionStorage
- Confirmation toast shown on successful subscription
- VAPID public key injected as `window.IHS_PUSH_KEY`

**Push broadcast admin (`public/admin/push-notifications.php`)**
- Role-restricted to superadmin and principal
- Compose form with title, body, optional URL, and live preview
- Self-contained VAPID JWT signing using OpenSSL — no Composer,
  no external dependencies; pure PHP P-256 ECDSA implementation
- Broadcasts to all subscriptions in `push_subscriptions`
- Auto-removes expired/invalid subscriptions (HTTP 410 responses)
- `push_broadcast_log` table records every broadcast with sender,
  title, sent count, fail count, and timestamp
- Broadcast history table shown on the page

**main.js additions**
- Section 8: SW registration, opt-in prompt orchestration,
  `ihsSubscribePush()` and `ihsDismissPush()` global functions,
  `urlBase64ToUint8Array()` utility for VAPID key conversion
- Section 9: Online/offline banner — slim fixed banner at top of
  page when connection is lost or restored; auto-hides on restore

---

#### PWA Manifest (`public/manifest.json`)
- `display: standalone` — installs as a native-feeling app
- `theme_color: #3d1a6e`, `background_color: #1a0835`
- Scope: `/ibeku-high-school/public/`
- Icons: SVG (`any maskable`), PNG 192×192, PNG 512×512
- 3 app shortcuts: Check Results, Latest News, Admissions
- Manifest linked in `<head>` via `header.php` alongside:
  `apple-mobile-web-app-capable`, `apple-mobile-web-app-title`,
  `apple-touch-icon`, and `theme-color` meta tags

**App icon (`public/assets/images/icons/icon.svg`)**
- Purple gradient background with blue accent ring
- Gold horizontal bars top and bottom
- White "IHS" text in serif, "IBEKU" subtitle
- PNG versions (192px, 512px) generated from SVG via canvas

---

#### Offline Fallback Page (`public/offline.php`)
- Self-contained branded page (no header/footer includes)
- Purple gradient background matching site design
- IHS crest, school name, friendly offline message
- "Try Again" button — navigates back if online, reloads if still offline
- "Go to Homepage" link pointing to cached homepage
- Quick links to other likely-cached pages
- `window.online` event listener: shows "Connection restored" and
  auto-reloads after 1.2 seconds
- `window.offline` event listener: updates status indicator
- If `navigator.onLine` is true on load (stale SW edge case),
  navigates back automatically after 500ms

---

#### Newsletter Admin (`public/admin/newsletter-admin.php`)
- Subscriber list with active/unsubscribed/all filter tabs
- Email search and CSV export of active subscribers
- Bulk delete with checkbox selection
- Broadcast compose form — plain text body wrapped in a branded
  HTML email template (purple header, school name, footer with
  unsubscribe note)
- Uses PHP `mail()` — works on cPanel without extra configuration
- `newsletter_log` table records every broadcast
- Recent broadcasts log shown below the compose form
- Role-restricted to superadmin, principal, vp_general

---

#### Admin Sidebar Updates
- Events, Create Event, Gallery Upload links added
- Messages link with live unread count badge
- Push Notifications link (superadmin, principal)
- Newsletter link (superadmin, principal, vp_general)
- Bell icon with unread message count rendered above nav links

---

## [4.0.0] — 2026-07-01

### Added — Phase 4 Complete (CMS & Content Management)

Full content management system. Every section of every public page
is database-driven with hardcoded fallbacks. Nine new DB tables,
nine new admin pages, three public APIs.

#### Admin Pages Added
Settings (7 tabs: school info, principals, academic year,
announcement bar, popup notification, YouTube embed, system info),
Staff Directory, History Milestones, Clubs & Societies,
Competitions & Awards, Alumni Directory, Scholarships, Prefects
(session-based, role-restricted), Hall of Fame (5 categories),
Nominations Inbox (status workflow + convert-to-entry), Reviews
(pending/approved/rejected tabs, unverified warning).

#### Public APIs Added
`submit_nomination.php` (honeypot, min 30 char reason),
`submit_review.php` (64-char token, rate limit 1/email/24hr),
`verify-review.php` (token verification, standalone page).

#### DB Tables Added
`settings`, `staff`, `milestones`, `clubs`, `awards`, `alumni`,
`scholarships`, `prefects`, `hall_of_fame`, `hall_of_fame_nominations`,
`reviews`, `staff_messages` (schema; UI in Phase 5).

#### Public Pages Rewired
`index.php` — staff preview, principal message, YouTube embed,
reviews widget, review submission form with star picker.
`about.php` — milestones timeline, staff directory.
`academics.php` — staff directory, clubs, awards.
`students.php` — prefects by session, featured alumni, scholarships.
`hall-of-fame.php` — all 5 categories, alumni wall, nomination form.

#### Infrastructure
`getSettings()` extended with all new keys. Topbar removed; footer
contact strip added. Popup notification system. Nav dropdown
modernised to CSS opacity/visibility. Announcement bar sessionStorage
dismiss.

---

## [3.0.0] — 2026-06-25

### Added — Phase 3 Complete (Admin Panel)

Role-based admin panel with 6 roles. Results entry, approve, and
publish workflow. Timetable PDF uploads. Student management and
promotion. Manage Users (superadmin). Admin layout CSS and JS
separate from public styles.

---

## [2.0.0] — 2026-06-19

### Added — Phase 2 Complete (Backend)

Full MySQL schema — 14 tables. Seed data. PDO connection layer
with grade calculator. Live APIs: check_result, submit_contact,
submit_admission, subscribe. All public forms wired to APIs.
Printable result sheet pulls live DB data.

### Fixed
Result checker silent failure, MySQL port 3306 conflict,
multi-line anchor tags breaking HTML render.

---

## [1.0.0] — 2026-05-31

### Added — Phase 1 Complete (Static Frontend)

All 11 public pages. Shared CSS, PHP header/footer with BASE_PATH
auto-detection, shared JS. Homepage carousel, about, hall of fame,
academics, results, admissions, contact, news, gallery, students,
events. Role-based admin architecture scaffolded.

---

## [0.1.0] — 2026-04-22

### Added — Project Initialised

Repository created, folder structure, README, .gitignore,
.env.example. Design system locked: #4a90d9 + #3d1a6e,
Playfair Display + DM Sans.