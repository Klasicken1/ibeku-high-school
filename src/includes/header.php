<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SHARED HEADER
   File: src/includes/header.php
   ============================================================ */

if ($_SERVER['HTTP_HOST'] === 'localhost') {
    define('BASE_PATH', '/ibeku-high-school/public/');
    define('API_PATH',  '/ibeku-high-school/src/api/');
} else {
    define('BASE_PATH', '/');
    define('API_PATH',  '/src/api/');
}

/* ── Load settings — used for nav, announcement bar, footer ── */
require_once dirname(__DIR__) . '/config/database.php';
$_site = getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <!-- ═══ PWA ═══════════════════════════════════════════════ -->
  <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json"/>
  <meta name="theme-color" content="#3d1a6e"/>
  <meta name="mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
  <meta name="apple-mobile-web-app-title" content="IHS"/>
  <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>assets/images/icons/icon-192.png"/>
  <!-- ═══ END PWA ════════════════════════════════════════════ -->

  <!-- ═══ JS PATH GLOBALS ════════════════════════════════════
       Makes BASE_PATH and API_PATH available to all JS files.
       Used by main.js, results.js etc. for fetch() API calls.
       ════════════════════════════════════════════════════════ -->
  <script>
    window.IHS_BASE = '<?php echo BASE_PATH; ?>';
    window.IHS_API  = '<?php echo API_PATH; ?>';
  </script>

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
     PWA SCRIPT — registered early so SW is active ASAP
     ═══════════════════════════════════════════ -->
<script src="<?php echo BASE_PATH; ?>assets/js/pwa.js" defer></script>

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
  <span class="ann-bar__text">
    <?php echo htmlspecialchars($_site['announcement_text']); ?>
    <?php if (!empty($_site['announcement_link'])): ?>
    <a href="<?php echo htmlspecialchars($_site['announcement_link']); ?>" class="ann-bar__link">
      <?php echo htmlspecialchars($_site['announcement_link_text'] ?: 'Read more →'); ?>
    </a>
    <?php endif; ?>
  </span>
  <button class="ann-bar__close" id="annBarClose" type="button" aria-label="Dismiss announcement">&#10005;</button>
</div>
<?php endif; ?>