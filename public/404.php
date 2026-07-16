<?php
/* ============================================================
   IBEKU HIGH SCHOOL — 404 NOT FOUND PAGE
   File: public/404.php

   Served via .htaccess ErrorDocument 404 directive.
   Sends a proper 404 status code (important for SEO — search
   engines must see 404, not 200, or they'll index broken URLs)
   and asks them not to index this page at all via $pageRobots.

   Uses the full site header/footer (live DB access required —
   unlike offline.php, which stays standalone and pre-cacheable
   for the service worker's no-network fallback scenario).
   ============================================================ */

declare(strict_types=1);

http_response_code(404);
header('Cache-Control: no-store, no-cache, must-revalidate');

$pageTitle   = 'Page Not Found — Ibeku High School';
$pageDesc    = 'The page you are looking for could not be found on the Ibeku High School website.';
$pageRobots  = 'noindex, follow';
$currentPage = '';

require_once dirname(__DIR__) . '/src/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero__inner">
    <div class="breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep">/</span>
      <span>Page Not Found</span>
    </div>
    <h1>404 — <em>Page Not Found</em></h1>
    <p>The page you're looking for doesn't exist — it may have been moved, renamed, or the link may be incorrect.</p>
  </div>
</section>

<section class="section text-center">
  <div class="wrap">
    <span class="slabel">Try one of these instead</span>
    <h2 class="stitle">Popular <span>Pages</span></h2>

    <div class="grid-3" style="margin-top:36px">
      <a href="<?php echo BASE_PATH; ?>about.php" class="card" style="padding:26px;text-decoration:none">
        <div style="font-size:28px;margin-bottom:10px">🏫</div>
        <strong style="color:#3d1a6e;font-size:15px">About the School</strong>
      </a>
      <a href="<?php echo BASE_PATH; ?>academics.php" class="card" style="padding:26px;text-decoration:none">
        <div style="font-size:28px;margin-bottom:10px">📚</div>
        <strong style="color:#3d1a6e;font-size:15px">Academics &amp; Timetables</strong>
      </a>
      <a href="<?php echo BASE_PATH; ?>news.php" class="card" style="padding:26px;text-decoration:none">
        <div style="font-size:28px;margin-bottom:10px">📰</div>
        <strong style="color:#3d1a6e;font-size:15px">School News</strong>
      </a>
      <a href="<?php echo BASE_PATH; ?>events.php" class="card" style="padding:26px;text-decoration:none">
        <div style="font-size:28px;margin-bottom:10px">📅</div>
        <strong style="color:#3d1a6e;font-size:15px">Events</strong>
      </a>
      <a href="<?php echo BASE_PATH; ?>admissions.php" class="card" style="padding:26px;text-decoration:none">
        <div style="font-size:28px;margin-bottom:10px">📝</div>
        <strong style="color:#3d1a6e;font-size:15px">Admissions</strong>
      </a>
      <a href="<?php echo BASE_PATH; ?>contact.php" class="card" style="padding:26px;text-decoration:none">
        <div style="font-size:28px;margin-bottom:10px">📞</div>
        <strong style="color:#3d1a6e;font-size:15px">Contact Us</strong>
      </a>
    </div>

    <a href="<?php echo BASE_PATH; ?>index.php" class="btn btn--primary btn--lg" style="margin-top:40px;display:inline-block">
      ← Back to Home
    </a>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/src/includes/footer.php'; ?>