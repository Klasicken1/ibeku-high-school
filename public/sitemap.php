<?php
/* ============================================================
   IBEKU HIGH SCHOOL — XML SITEMAP
   File: public/sitemap.php

   Dynamically generated — no hardcoded domain. Uses the same
   localhost-vs-production detection as header.php, so this
   keeps working unchanged through the ibekuhighschool.com →
   ibekuhighschool.sch.ng migration.

   Includes:
   - Static public pages
   - Published news articles (news-single.php?slug=X)
   - Active corps member profiles (corps-profile.php?code=X)

   Excludes: 404.php, offline.php, unsubscribe.php,
   verify-review.php, and everything under admin/, portal/,
   portal-corps/ (private or transactional, not for search
   engines).
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/config/database.php';

header('Content-Type: application/xml; charset=utf-8');

$pdo = getDB();

/* ── Same domain/base-path detection as header.php ── */
$isLocal = $_SERVER['HTTP_HOST'] === 'localhost';
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'];
$base    = $isLocal ? '/ibeku-high-school/public/' : '/';
$siteUrl = $scheme . '://' . $host . $base;

/* ── Static pages: [path, changefreq, priority] ── */
$staticPages = [
    ['index.php',        'daily',   '1.0'],
    ['about.php',        'monthly', '0.8'],
    ['academics.php',    'monthly', '0.8'],
    ['students.php',     'monthly', '0.7'],
    ['hall-of-fame.php', 'monthly', '0.7'],
    ['corps.php',        'weekly',  '0.7'],
    ['news.php',         'daily',   '0.8'],
    ['events.php',       'weekly',  '0.7'],
    ['gallery.php',      'weekly',  '0.6'],
    ['results.php',      'monthly', '0.6'],
    ['admissions.php',   'monthly', '0.8'],
    ['contact.php',      'yearly',  '0.5'],
];

/* ── Dynamic: published news articles ── */
$newsStmt = $pdo->query(
    "SELECT slug, published_at FROM news
     WHERE is_published = 1
     ORDER BY published_at DESC"
);
$newsArticles = $newsStmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Dynamic: active corps member profiles ── */
$corpsStmt = $pdo->query(
    "SELECT state_code FROM corps_members
     WHERE status = 'active'
     ORDER BY full_name ASC"
);
$corpsMembers = $corpsStmt->fetchAll(PDO::FETCH_ASSOC);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<?php foreach ($staticPages as [$path, $changefreq, $priority]): ?>
  <url>
    <loc><?php echo htmlspecialchars($siteUrl . $path); ?></loc>
    <changefreq><?php echo $changefreq; ?></changefreq>
    <priority><?php echo $priority; ?></priority>
  </url>
<?php endforeach; ?>

<?php foreach ($newsArticles as $article): ?>
  <url>
    <loc><?php echo htmlspecialchars($siteUrl . 'news-single.php?slug=' . urlencode($article['slug'])); ?></loc>
    <?php if (!empty($article['published_at'])): ?>
    <lastmod><?php echo date('Y-m-d', strtotime($article['published_at'])); ?></lastmod>
    <?php endif; ?>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
<?php endforeach; ?>

<?php foreach ($corpsMembers as $member): ?>
  <url>
    <loc><?php echo htmlspecialchars($siteUrl . 'corps-profile.php?code=' . urlencode($member['state_code'])); ?></loc>
    <changefreq>monthly</changefreq>
    <priority>0.4</priority>
  </url>
<?php endforeach; ?>

</urlset>