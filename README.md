# Ibeku High School — Official Website

**Live URL:** _Coming soon_  
**Project Type:** NYSC Personal Community Development Service (CDS) Project — Digital Transformation Initiative  
**Built by:** Kenneth Nweke — Full-Stack Developer, NYSC Corps Member (2025/2026)  
**School:** Ibeku High School, Umuahia, Abia State, Nigeria  

---

## About This Project

Ibeku High School is one of the oldest and most respected government secondary
schools in South-East Nigeria. This project gives the school its first official
digital presence — a fully functional website with a live result checker, news
system, gallery, admissions portal, and a custom admin panel.

Built entirely from scratch as an NYSC Personal CDS initiative and documented publicly
to demonstrate the full development process.

---

## Features

- Public website with school information, news, events, and gallery
- **Online result checker** — students enter their ID and view term results
- Admissions enquiry system
- Contact form
- **Admin panel** — school staff can upload results, post news, manage gallery
- Fully responsive — works on mobile, tablet, and desktop
- Clean, accessible design using school colours (green and gold)

---

## Tech Stack

| Layer     | Technology              |
|-----------|-------------------------|
| Frontend  | HTML5, CSS3, JavaScript |
| Backend   | PHP 8+                  |
| Database  | MySQL                   |
| Hosting   | cPanel Shared Hosting   |
| Version Control | Git + GitHub      |

---

## Project Phases

| Phase | Description                        | Status      |
|-------|------------------------------------|-------------|
| 1     | Static frontend (HTML/CSS/JS)      | 🔄 In progress |
| 2     | PHP backend + MySQL database       | ⏳ Pending  |
| 3     | Admin panel                        | ⏳ Pending  |

---

## Local Development Setup

### Prerequisites
- XAMPP (or any PHP 8+ local server)
- Git
- A code editor (VS Code recommended)

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/Klasicken1/ibeku-high-school.git

# 2. Navigate into the project
cd ibeku-high-school

# 3. Copy environment variables
cp .env.example .env
# Then open .env and fill in your database credentials

# 4. Import the database
# Open phpMyAdmin, create a database called ibeku_school
# Import database/schema.sql, then database/seed.sql

# 5. Move the project into your XAMPP htdocs folder
# Then visit: http://localhost/ibeku-high-school/public/
```

---

## NYSC Documentation

This project is documented as a Personal CDS digital transformation initiative.
Evidence including Coding session photos/videos, screenshots, meeting notes, and launch records
are stored in the `/docs` folder.

---

## License

MIT — free to use, study, and build upon.