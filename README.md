---

## Local Development Setup

**Prerequisites:** XAMPP (or any PHP 8+ server), Git, VS Code

```bash
# Clone the repository
git clone https://github.com/Klasicken1/ibeku-high-school.git
cd ibeku-high-school

# Copy environment file and fill in credentials
cp .env.example .env

# Create the database
# Open phpMyAdmin → create database: ibeku_school
# Import: database/schema.sql then database/seed.sql

# Visit the site
# http://localhost/ibeku-high-school/public/

# Admin panel
# http://localhost/ibeku-high-school/public/admin/
```

**BASE_PATH convention:** All PHP files and JS fetch calls use
`/ibeku-high-school/` as the subfolder prefix on localhost.
On production (cPanel root domain) this becomes `/` — update the
constant in `src/config/database.php` before deploying.

---

## Deployment (cPanel)

1. Upload all files to `public_html/` via File Manager or FTP
2. Create MySQL database and user in cPanel → MySQL Databases
3. Import `database/schema.sql` via phpMyAdmin
4. Update `src/config/database.php` with cPanel credentials
5. Set `BASE_PATH` to `/` in `src/config/database.php`
6. Set folder permissions: `public/assets/images/staff/` → 755
7. Test all public pages and admin panel

---

## Planned — Phase 5

- **Staff internal messaging** — bell icon and unread badge in admin
  header; staff_messages table already created; message triggers
  on results entry and approval events
- **Browser push notifications** — Web Push API with VAPID keys;
  opt-in prompt for visitors and parents; admin interface to compose
  and broadcast announcements; when installed as a PWA, push
  notifications delivered as native app notifications via the same
  Web Push / VAPID infrastructure — no separate system needed
- **PWA conversion** — manifest.json, service worker with layered
  caching, offline fallback page, online/offline banner
- **News CMS** — full CRUD for news.php from admin panel
- **Gallery CMS** — upload and manage gallery images from admin panel
- **Events CMS** — manage events.php calendar from admin panel
- **Admissions backend** — PHP handler connecting enquiry form to
  a notifiable inbox
- **Newsletter broadcast** — subscriber list management
- **cPanel production deployment**

---

## NYSC Context

This project was built as a Personal CDS digital transformation
initiative during NYSC primary assignment at Ibeku High School,
where the developer serves as a Digital Technology teacher
(Corps Member AB/25C/0245, Batch C).

The project demonstrates full-stack PHP development including
database design, role-based access control, file upload handling,
API design, anti-spam techniques, and progressive enhancement —
and serves as a portfolio piece for the developer's career progress.

Evidence including session photos, screenshots, and meeting notes
are stored in the `/docs` folder.

---

## License

MIT — free to use, study, and build upon.