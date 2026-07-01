# Ibeku High School — Official Website

**Live URL:** Coming soon (cPanel deployment pending)
**Repository:** https://github.com/Klasicken1/ibeku-high-school
**Developer:** Nweke Kenneth Nnaemeka — NYSC Corps Member AB/25C/0245
**Institution:** Ibeku High School, Umuahia, Abia State, Nigeria
**Project Type:** NYSC Personal Community Development Service (CDS)
— Digital Transformation Initiative

---

## About This Project

Ibeku High School is one of the oldest and most respected government
secondary schools in South-East Nigeria, founded in 1954. This project
gives the school its first official digital presence — a fully
content-managed, installable Progressive Web App built from scratch
as an NYSC Personal CDS initiative.

The site includes an online result checker, a complete admin panel,
Hall of Fame system, browser push notifications, offline support,
staff internal messaging, and a CMS where every section of every
public page is managed without touching code.

---

## Features

### Public Website
- **Homepage** — hero carousel, stats, principal's message,
  departments, result checker widget, staff preview, news,
  YouTube embed, reviews/testimonials with live submission form
- **About** — school history, DB-driven timeline, vision & mission,
  anthem, rules, principals' messages, facilities, staff directory
- **Academics** — departments, timetable downloads, staff directory,
  clubs & societies, competitions & awards, learning resources
- **Students** — result checker, prefects by session, notable alumni,
  scholarships
- **Hall of Fame** — 5 categories, alumni wall with field filter,
  public nomination form
- **Results** — online result checker with printable result slip
- **Admissions** — requirements, process steps, fees, enquiry form
- **News** — DB-driven articles, category filter, search
- **Gallery** — DB-driven photo grid, category filter, lightbox
- **Events** — DB-driven upcoming and past events calendar
- **Contact** — department contacts, Google Maps embed

### Admin Panel
Role-based access: `superadmin · principal · vp_admin ·
vp_academics · vp_general · dean · form_teacher · subject_teacher`

| Page | Access | Purpose |
|---|---|---|
| Dashboard | All | Overview and quick actions |
| Settings | Superadmin | 7-tab site customiser |
| Staff Directory | Superadmin | Staff CRUD with photo upload |
| History Timeline | Superadmin | About page milestones |
| Clubs & Societies | Superadmin | Academics clubs |
| Awards | Superadmin | Competitions & awards |
| Alumni | Superadmin | Alumni with featured toggle |
| Scholarships | Superadmin | Students page scholarships |
| Prefects | Superadmin, Principal | Student leaders by session |
| Hall of Fame | Superadmin | Inductees across 5 categories |
| Nominations | Superadmin | Review and convert nominations |
| Reviews | Superadmin, Principal, VP General | Approve visitor reviews |
| News | Principal+ | Article list with publish workflow |
| Create News | Principal+ | TinyMCE rich text article editor |
| All Events | VP General+ | Events calendar management |
| Create Event | VP General+ | Add and edit events |
| Gallery | All | Photo grid management |
| Upload Photos | Principal+ | Bulk photo upload with categories |
| Students | VP Admin+ | Student records |
| Promote Students | Principal+ | Session promotion workflow |
| Admissions | VP Admin+ | Enquiry workflow with student conversion |
| Messages | All | Internal staff messaging |
| Push Notifications | Superadmin, Principal | Broadcast push notifications |
| Newsletter | Principal+ | Subscriber list and email broadcast |
| Manage Users | Superadmin | Admin account management |
| Manage Classes | Superadmin | Class arms |
| Manage Subjects | Superadmin | Subject list |
| Settings | Superadmin | All site settings |

### Progressive Web App (PWA)
- Installable on Android, iOS, and desktop from the browser
- Offline support — cached pages available without internet
- Native-feeling standalone display mode
- App shortcuts to Results, News, and Admissions
- Online/offline detection banner

### Browser Push Notifications
- Opt-in banner appears after 8 seconds on public pages
- Admin broadcasts instantly to all subscribed browsers
- When installed as a PWA, delivered as native app notifications
- Self-contained VAPID/JWT implementation — no Composer required
- Stale subscriptions auto-cleaned on broadcast
- Full broadcast history log in admin

### Staff Internal Messaging
- Inbox, Sent, and Compose views
- Unread count badge on sidebar bell icon
- Reply, mark read, delete

### Reviews System
- Star picker submission form on homepage
- 64-character token verification (no email server required)
- Rate limited: 1 per email per 24 hours
- Admin approval workflow

### Announcement & Popup System
- Site-wide announcement bar (sessionStorage dismiss)
- Bottom-right popup with scroll% and time triggers
- Both managed from Settings admin page

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2.12 |
| Database | MariaDB 10.4.32 |
| Frontend | Vanilla HTML5, CSS3, JavaScript (no frameworks) |
| Fonts | Google Fonts — Playfair Display, DM Sans |
| Rich Text Editor | TinyMCE (news articles) |
| Hosting | cPanel shared hosting (deployment pending) |
| Version Control | Git + GitHub |

**Design system:**
Blue `#4a90d9` · Purple `#3d1a6e` · Gold `#e8a020`
Headings: Playfair Display · Body: DM Sans

---

## Database Schema

### Phase 2 Core Tables
`users` · `students` · `subjects` · `classes` · `results`
· `result_scores` · `news` · `gallery` · `events` · `timetables`
· `admissions` · `subscribers` · `contact_messages`

### Phase 4 CMS Tables
`settings` · `staff` · `milestones` · `clubs` · `awards`
· `alumni` · `scholarships` · `prefects` · `hall_of_fame`
· `hall_of_fame_nominations` · `reviews` · `staff_messages`

### Phase 5 Tables
`push_subscriptions` · `push_broadcast_log` · `newsletter_log`

---

## Project Structure