<?php
/* ============================================================
   IBEKU HIGH SCHOOL — OFFLINE FALLBACK PAGE
   File: public/offline.php

   Served by the service worker when a public page request
   fails (no network AND no cached version available).
   PHP executes when SW pre-caches this on install; after
   that, SW serves the cached HTML response directly.
   ============================================================ */

// Prevent server-side caching of this page
header('Cache-Control: no-store, no-cache, must-revalidate');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>You're Offline — Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #f4f3f9;
      color: #1a1a2e;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      text-align: center;
    }

    .card {
      background: #fff;
      border-radius: 20px;
      padding: 3rem 2.5rem;
      max-width: 480px;
      width: 100%;
      box-shadow: 0 4px 32px rgba(61,26,110,.08);
    }

    .logo-badge {
      width: 72px;
      height: 72px;
      background: #3d1a6e;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-family: 'Playfair Display', serif;
      font-size: 1.6rem;
      font-weight: 900;
      color: #fff;
      letter-spacing: 2px;
    }

    .offline-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      display: block;
    }

    h1 {
      font-family: 'Playfair Display', serif;
      font-size: 1.75rem;
      color: #3d1a6e;
      margin-bottom: 0.75rem;
      line-height: 1.25;
    }

    p {
      font-size: 1rem;
      color: #6b6b80;
      line-height: 1.7;
      margin-bottom: 1.5rem;
    }

    .divider {
      border: none;
      border-top: 1px solid #f0eef6;
      margin: 1.5rem 0;
    }

    .cached-links {
      text-align: left;
    }

    .cached-links p {
      font-size: 0.85rem;
      font-weight: 600;
      color: #3d1a6e;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.75rem;
    }

    .cached-links ul {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .cached-links a {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
      background: #f8f7fc;
      border: 1px solid #e8e6f0;
      border-radius: 10px;
      text-decoration: none;
      color: #1a1a2e;
      font-size: 0.9rem;
      font-weight: 500;
      transition: background 0.15s;
    }

    .cached-links a:hover { background: #ede9f7; }
    .cached-links a .icon { font-size: 1rem; }

    .btn-retry {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #3d1a6e;
      color: #fff;
      border: none;
      padding: 12px 28px;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      margin-top: 1rem;
      transition: background 0.2s;
    }
    .btn-retry:hover { background: #5a2d9e; }

    .footer-note {
      margin-top: 2rem;
      font-size: 0.8rem;
      color: #9b97b0;
    }
  </style>
</head>
<body>

  <div class="card">
    <div class="logo-badge">IHS</div>

    <span class="offline-icon">📶</span>

    <h1>You're Offline</h1>
    <p>
      It looks like you've lost your internet connection.
      Some pages you've visited before are still available below.
    </p>

    <button class="btn-retry" onclick="window.location.reload()">
      ↻ Try Again
    </button>

    <hr class="divider"/>

    <div class="cached-links">
      <p>Pages available offline</p>
      <ul>
        <li><a href="index.php"><span class="icon">🏠</span> Home</a></li>
        <li><a href="about.php"><span class="icon">🏫</span> About the School</a></li>
        <li><a href="academics.php"><span class="icon">📚</span> Academics &amp; Timetables</a></li>
        <li><a href="news.php"><span class="icon">📰</span> School News</a></li>
        <li><a href="events.php"><span class="icon">📅</span> Events</a></li>
        <li><a href="admissions.php"><span class="icon">📝</span> Admissions</a></li>
        <li><a href="contact.php"><span class="icon">📞</span> Contact Us</a></li>
      </ul>
    </div>
  </div>

  <p class="footer-note">
    Ibeku High School · Umuahia, Abia State · Official Website
  </p>

  <script>
    // Auto-reload when connection is restored
    window.addEventListener('online', () => window.location.reload());
  </script>

</body>
</html>