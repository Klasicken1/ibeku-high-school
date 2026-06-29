<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SHARED HEADER
   File: src/includes/header.php
   ============================================================ */

if ($_SERVER['HTTP_HOST'] === 'localhost') {
    define('BASE_PATH', '/ibeku-high-school/public/');
} else {
    define('BASE_PATH', '/');
}

/* ── Load settings — used for topbar, announcement bar ── */
require_once dirname(__DIR__) . '/config/database.php';
$_site = getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?php echo htmlspecialchars($pageDesc ?? 'Official website of Ibeku High School, Umuahia, Abia State.'); ?>"/>
  <meta name="author"      content="<?php echo htmlspecialchars($_site['school_name']); ?>"/>
  <meta name="robots"      content="index, follow"/>
  <meta property="og:title"       content="<?php echo htmlspecialchars($pageTitle ?? $_site['school_name']); ?>"/>
  <meta property="og:description" content="<?php echo htmlspecialchars($pageDesc  ?? 'Official website of Ibeku High School, Umuahia.'); ?>"/>
  <meta property="og:type"        content="website"/>
  <meta property="og:image"       content="<?php echo BASE_PATH; ?>assets/images/og-image.jpg"/>
  <title><?php echo htmlspecialchars($pageTitle ?? $_site['school_name'] . ' — Umuahia, Abia State'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css"/>
  <?php if (!empty($pageCss)): ?>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/pages/<?php echo htmlspecialchars($pageCss); ?>.css"/>
  <?php endif; ?>
</head>
<body>

<!-- ═══════════════════════════════════════════
     TOPBAR
     ═══════════════════════════════════════════ -->
<div class="topbar">
  <div class="topbar__left">
    <span>📍 <?php echo htmlspecialchars($_site['school_address']); ?></span>
    <span class="topbar__divider"></span>
    <span>🕐 Mon – Fri: 8:00 AM – 3:00 PM</span>
  </div>
  <div class="topbar__right">
    <a href="mailto:<?php echo htmlspecialchars($_site['school_email']); ?>">✉ <?php echo htmlspecialchars($_site['school_email']); ?></a>
    <span class="topbar__divider"></span>
    <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $_site['school_phone'])); ?>">📞 <?php echo htmlspecialchars($_site['school_phone']); ?></a>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     STICKY NAVIGATION
     ═══════════════════════════════════════════ -->
<nav class="nav">
  <div class="nav__inner">
    <a href="<?php echo BASE_PATH; ?>index.php" class="nav__logo">
      <div class="nav__crest" aria-hidden="true">IHS</div>
      <div class="nav__logo-text">
        <strong><?php echo htmlspecialchars($_site['school_name']); ?></strong>
        <small>Umuahia, Abia State · Est. 1954</small>
      </div>
    </a>
    <ul class="nav__menu" id="navMenu" role="menubar">
      <li class="nav__item <?php echo ($currentPage === 'home') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>index.php" class="nav__link">Home</a>
      </li>
      <li class="nav__item nav__item--has-drop <?php echo ($currentPage === 'about') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>about.php" class="nav__link">About <span class="nav__chev" aria-hidden="true"></span></a>
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
      <li class="nav__item nav__item--has-drop <?php echo ($currentPage === 'academics') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>academics.php" class="nav__link">Academics <span class="nav__chev" aria-hidden="true"></span></a>
        <div class="nav__dropdown" role="menu">
          <a href="<?php echo BASE_PATH; ?>academics.php#departments" class="nav__dropdown-link">Departments &amp; Subjects</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#timetable"   class="nav__dropdown-link">School Timetable</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#staff"       class="nav__dropdown-link">Staff Directory</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#clubs"       class="nav__dropdown-link">Clubs &amp; Societies</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#awards"      class="nav__dropdown-link">Competitions &amp; Awards</a>
          <a href="<?php echo BASE_PATH; ?>academics.php#resources"   class="nav__dropdown-link">Learning Resources</a>
        </div>
      </li>
      <li class="nav__item nav__item--has-drop <?php echo ($currentPage === 'students') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>students.php" class="nav__link">Students <span class="nav__chev" aria-hidden="true"></span></a>
        <div class="nav__dropdown" role="menu">
          <a href="<?php echo BASE_PATH; ?>results.php"              class="nav__dropdown-link">Check Results Online</a>
          <a href="<?php echo BASE_PATH; ?>students.php#prefects"    class="nav__dropdown-link">Prefects &amp; Student Leaders</a>
          <a href="<?php echo BASE_PATH; ?>hall-of-fame.php"         class="nav__dropdown-link">Hall of Fame</a>
          <a href="<?php echo BASE_PATH; ?>students.php#alumni"      class="nav__dropdown-link">Alumni / Old Students</a>
          <a href="<?php echo BASE_PATH; ?>students.php#sponsorship" class="nav__dropdown-link">Scholarships &amp; Support</a>
        </div>
      </li>
      <li class="nav__item nav__item--has-drop <?php echo ($currentPage === 'news') ? 'nav__item--active' : ''; ?>">
        <a href="<?php echo BASE_PATH; ?>news.php" class="nav__link">News <span class="nav__chev" aria-hidden="true"></span></a>
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
      <li class="nav__item nav__item--results">
        <a href="<?php echo BASE_PATH; ?>results.php" class="nav__link">Check Results</a>
      </li>
      <li class="nav__item nav__item--cta">
        <a href="<?php echo BASE_PATH; ?>admissions.php" class="nav__link">Apply Now</a>
      </li>
    </ul>
    <button class="nav__burger" id="navBurger" aria-label="Open navigation menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- ═══════════════════════════════════════════
     ANNOUNCEMENT BAR — controlled from Settings
     ═══════════════════════════════════════════ -->
<?php if ($_site['announcement_show'] === '1' && $_site['announcement_text'] !== ''): ?>
<div class="ann-bar" id="annBar" role="alert" aria-live="polite">
  <span class="ann-bar__pill">NOTICE</span>
  <span>
    <?php echo htmlspecialchars($_site['announcement_text']); ?>
    <?php if ($_site['announcement_link']): ?>
    &nbsp;<a href="<?php echo htmlspecialchars($_site['announcement_link']); ?>">
      <?php echo htmlspecialchars($_site['announcement_link_text'] ?: 'Read more →'); ?>
    </a>
    <?php endif; ?>
  </span>
  <button class="ann-bar__close" id="annBarClose" aria-label="Dismiss announcement">&#10005;</button>
</div>
<?php endif; ?>