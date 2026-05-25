<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SHARED HEADER
   File: src/includes/header.php

   Included at the top of every public page.
   Outputs: <!DOCTYPE html>, <head>, Google Fonts,
            CSS links, topbar, nav, and announcement bar.

   HOW TO USE ON A PAGE:
   ─────────────────────
   At the very top of any page file, define these variables
   BEFORE including this file:

     <?php
       $pageTitle   = 'About — Ibeku High School';
       $pageDesc    = 'Learn about the history, vision...';
       $currentPage = 'about';   // highlights active nav item
       $pageCss     = 'about';   // loads assets/css/pages/about.css
     ?>
     <?php require_once '../src/includes/header.php'; ?>

   ============================================================ */

/* ----------------------------------------------------------
   BASE PATH
   Auto-detects local vs live. No manual change needed.

   LOCAL  (XAMPP):  '/ibeku-high-school/public/'
   LIVE   (cPanel): '/'
   ---------------------------------------------------------- */
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    define('BASE_PATH', '/ibeku-high-school/public/');
} else {
    define('BASE_PATH', '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <!-- SEO Meta Tags -->
  <meta name="description" content="<?php echo htmlspecialchars($pageDesc ?? 'Official website of Ibeku High School, Umuahia, Abia State.'); ?>"/>
  <meta name="author"      content="Ibeku High School"/>
  <meta name="robots"      content="index, follow"/>

  <!-- Open Graph (WhatsApp / social sharing previews) -->
  <meta property="og:title"       content="<?php echo htmlspecialchars($pageTitle ?? 'Ibeku High School'); ?>"/>
  <meta property="og:description" content="<?php echo htmlspecialchars($pageDesc  ?? 'Official website of Ibeku High School, Umuahia.'); ?>"/>
  <meta property="og:type"        content="website"/>
  <meta property="og:image"       content="<?php echo BASE_PATH; ?>assets/images/og-image.jpg"/>

  <title><?php echo htmlspecialchars($pageTitle ?? 'Ibeku High School — Umuahia, Abia State'); ?></title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>

  <!-- Shared stylesheet — loaded on every page -->
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css"/>

  <!-- Page-specific stylesheet — only loaded when $pageCss is set -->
  <?php if (!empty($pageCss)): ?>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/pages/<?php echo htmlspecialchars($pageCss); ?>.css"/>
  <?php endif; ?>

  <!-- Favicon — uncomment when favicon.png is ready -->
  <!-- <link rel="icon" type="image/png" href="<?php echo BASE_PATH; ?>assets/images/favicon.png"/> -->

</head>
<body>


<!-- ═══════════════════════════════════════════
     TOPBAR
     ═══════════════════════════════════════════ -->
<div class="topbar">
  <div class="topbar__left">
    <span>📍 Umuahia, Abia State, Nigeria</span>
    <span class="topbar__divider"></span>
    <span>🕐 Mon – Fri: 8:00 AM – 3:00 PM</span>
  </div>
  <div class="topbar__right">
    <!-- UPDATE: Replace with real school email and phone number -->
    <a href="mailto:info@ibekuhighschool.edu.ng">✉ info@ibekuhighschool.edu.ng</a>
    <span class="topbar__divider"></span>
    <a href="tel:+2340000000000">📞 +234 000 000 0000</a>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     STICKY NAVIGATION
     ═══════════════════════════════════════════ -->
<nav class="nav">
  <div class="nav__inner">

    <!-- Logo — replace .nav__crest div with <img> once logo is ready -->
    <a href="<?php echo BASE_PATH; ?>index.php" class="nav__logo">
      <div class="nav__crest" aria-hidden="true">IHS</div>
      <div class="nav__logo-text">
        <strong>Ibeku High School</strong>
        <small>Umuahia, Abia State · Est. 1954</small>
      </div>
    </a>

    <!-- Navigation menu -->
    <ul class="nav__menu" id="navMenu" role="menubar">

      <li class="nav__item <?php echo ($currentPage === 'home') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>index.php" class="nav__link">Home</a>
      </li>

      <!-- About dropdown -->
      <li class="nav__item nav__item--has-drop <?php echo ($currentPage === 'about') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>about.php" class="nav__link">
          About <span class="nav__chev" aria-hidden="true"></span>
        </a>
        <div class="nav__dropdown" role="menu">
          <a href="<?php echo BASE_PATH; ?>about.php"              class="nav__dropdown-link">About the School</a>
          <a href="<?php echo BASE_PATH; ?>about.php#history"      class="nav__dropdown-link">History of the School</a>
          <a href="<?php echo BASE_PATH; ?>about.php#vision"       class="nav__dropdown-link">Vision &amp; Mission</a>
          <a href="<?php echo BASE_PATH; ?>about.php#anthem"       class="nav__dropdown-link">School Anthem</a>
          <a href="<?php echo BASE_PATH; ?>about.php#rules"        class="nav__dropdown-link">Rules &amp; Regulations</a>
          <div class="nav__dropdown-divider"></div>
          <a href="<?php echo BASE_PATH; ?>about.php#principal-ss" class="nav__dropdown-link">SS Principal's Message</a>
          <a href="<?php echo BASE_PATH; ?>about.php#principal-js" class="nav__dropdown-link">JS Principal's Message</a>
        </div>
      </li>

      <!-- Academics dropdown -->
      <li class="nav__item nav__item--has-drop <?php echo ($currentPage === 'academics') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>academics.php" class="nav__link">
          Academics <span class="nav__chev" aria-hidden="true"></span>
        </a>
        <div class="nav__dropdown" role="menu">
          <a href="<?php echo BASE_PATH; ?>academics.php#departments" class="nav__dropdown-link">Departments &amp; Subjects</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#timetable"   class="nav__dropdown-link">School Timetable</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#staff"       class="nav__dropdown-link">Staff Directory</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#clubs"       class="nav__dropdown-link">Clubs &amp; Societies</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#awards"      class="nav__dropdown-link">Competitions &amp; Awards</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#resources"   class="nav__dropdown-link">Learning Resources</a>
        </div>
      </li>

      <!-- Students dropdown -->
      <li class="nav__item nav__item--has-drop <?php echo ($currentPage === 'students') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>students.php" class="nav__link">
          Students <span class="nav__chev" aria-hidden="true"></span>
        </a>
        <div class="nav__dropdown" role="menu">
          <a href="<?php echo BASE_PATH; ?>results.php"              class="nav__dropdown-link">Check Results Online</a>
          <a href="<?php echo BASE_PATH; ?>students.php#prefects"    class="nav__dropdown-link">Prefects &amp; Student Leaders</a>
          <a href="<?php echo BASE_PATH; ?>hall-of-fame.php"         class="nav__dropdown-link">Hall of Fame</a>
          <a href="<?php echo BASE_PATH; ?>students.php#alumni"      class="nav__dropdown-link">Alumni / Old Students</a>
          <a href="<?php echo BASE_PATH; ?>students.php#sponsorship" class="nav__dropdown-link">Scholarships &amp; Support</a>
        </div>
      </li>

      <!-- News dropdown -->
      <li class="nav__item nav__item--has-drop <?php echo ($currentPage === 'news') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>news.php" class="nav__link">
          News <span class="nav__chev" aria-hidden="true"></span>
        </a>
        <div class="nav__dropdown" role="menu">
          <a href="<?php echo BASE_PATH; ?>news.php"    class="nav__dropdown-link">News &amp; Blog</a>
          <a href="<?php echo BASE_PATH; ?>events.php"  class="nav__dropdown-link">Events</a>
          <a href="<?php echo BASE_PATH; ?>gallery.php" class="nav__dropdown-link">Gallery</a>
        </div>
      </li>

      <li class="nav__item <?php echo ($currentPage === 'admissions') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>admissions.php" class="nav__link">Admissions</a>
      </li>

      <li class="nav__item <?php echo ($currentPage === 'contact') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>contact.php" class="nav__link">Contact</a>
      </li>

      <!-- CTA buttons -->
      <li class="nav__item nav__item--results">
        <a href="<?php echo BASE_PATH; ?>results.php" class="nav__link">Check Results</a>
      </li>

      <li class="nav__item nav__item--cta">
        <a href="<?php echo BASE_PATH; ?>admissions.php" class="nav__link">Apply Now</a>
      </li>

    </ul>

    <!-- Mobile burger button -->
    <button class="nav__burger" id="navBurger" aria-label="Open navigation menu" aria-expanded="false">
      <span></span>
      <span></span>
      <span></span>
    </button>

  </div>
</nav>


<!-- ═══════════════════════════════════════════
     ANNOUNCEMENT BAR
     Shown and dismissed by assets/js/main.js
     Update the message and link below as needed.
     ═══════════════════════════════════════════ -->
<div class="ann-bar" id="annBar" role="alert" aria-live="polite">
  <span class="ann-bar__pill">NOTICE</span>
  <span>
    2024/2025 First Term results are now available online.&nbsp;
    <a href="<?php echo BASE_PATH; ?>results.php">Check your results →</a>
  </span>
  <button class="ann-bar__close" id="annBarClose" aria-label="Dismiss announcement">
    &#10005;
  </button>
</div>