<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SINGLE NEWS ARTICLE
   File: public/news-single.php
   ============================================================ */

require_once __DIR__ . '/../src/config/database.php';

$pdo  = getDB();
$slug = $_GET['slug'] ?? '';

if ($slug === '') {
    header('Location: news.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT n.*, u.full_name AS author_name
     FROM   news n
     LEFT JOIN users u ON u.id = n.author_id
     WHERE  n.slug = ? AND n.is_published = 1
     LIMIT  1'
);
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $pageTitle   = 'Article Not Found — Ibeku High School';
    $pageDesc    = 'The article you are looking for could not be found.';
    $currentPage = 'news';
    $pageCss     = 'news';
    require_once __DIR__ . '/../src/includes/header.php';
    ?>
    <div class="wrap article-not-found" style="padding:80px 20px;text-align:center">
      <div style="font-size:48px;margin-bottom:16px">📰</div>
      <h1 style="color:#3d1a6e;font-family:'Playfair Display',serif;margin-bottom:12px">Article Not Found</h1>
      <p style="color:#6b6b80;font-size:15px;margin-bottom:24px">This article may have been unpublished or removed.</p>
      <a href="<?php echo BASE_PATH; ?>news.php" class="btn btn--primary">← Back to News</a>
    </div>
    <?php
    require_once __DIR__ . '/../src/includes/footer.php';
    exit;
}

/* ── Track a view (best effort, never blocks the page) ── */
try {
    $pdo->prepare('UPDATE news SET views = views + 1 WHERE id = ?')->execute([$article['id']]);
} catch (PDOException $e) {
    error_log('IHS news-single view counter error: ' . $e->getMessage());
}

$categoryLabels = [
    'achievement'  => 'Achievement',
    'academic'     => 'Academic',
    'ict'          => 'ICT',
    'sports'       => 'Sports',
    'announcement' => 'Announcement',
    'culture'      => 'Culture',
    'general'      => 'General',
];

$categoryIcons = [
    'achievement'  => '🏆',
    'academic'     => '📚',
    'ict'          => '💻',
    'sports'       => '⚽',
    'announcement' => '📢',
    'culture'      => '🎭',
    'general'      => '📰',
];

$pageTitle   = ($article['meta_title'] ?: $article['title']) . ' — Ibeku High School';
$pageDesc    = $article['meta_description'] ?: $article['excerpt'] ?: '';
$currentPage = 'news';
$pageCss     = 'news';

require_once __DIR__ . '/../src/includes/header.php';

/* ── Related articles: same category, excluding this one ── */
$relatedStmt = $pdo->prepare(
    'SELECT title, slug, image, category FROM news
     WHERE  is_published = 1 AND category = ? AND id != ?
     ORDER  BY published_at DESC LIMIT 3'
);
$relatedStmt->execute([$article['category'], $article['id']]);
$related = $relatedStmt->fetchAll();

/* ── Sanitise TinyMCE HTML body ──
   Allows all standard formatting tags but strips scripts,
   event handlers (onclick etc.), and javascript: hrefs. ── */
$allowedTags = '<p><br><strong><b><em><i><u><s><ul><ol><li>'
             . '<h2><h3><h4><h5><blockquote><a><img><table>'
             . '<thead><tbody><tr><th><td><figure><figcaption>'
             . '<span><div><hr><pre><code>';
$safeBody = strip_tags($article['body'] ?? '', $allowedTags);
$safeBody = preg_replace('/\bon\w+\s*=\s*(["\'])[^"\']*\1/i', '', $safeBody);
$safeBody = preg_replace('/href\s*=\s*(["\'])javascript:[^"\']*\1/i', 'href="#"', $safeBody);
?>


<!-- ═══════════════════════════════════════════
     ARTICLE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--news article-hero">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <a href="<?php echo BASE_PATH; ?>news.php" style="color:rgba(255,255,255,.7)">News</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">
        <?php echo htmlspecialchars($categoryLabels[$article['category']] ?? 'General'); ?>
      </span>
    </nav>
    <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
    <div class="article-meta-row">
      <span>📅 <?php echo date('F j, Y', strtotime($article['published_at'])); ?></span>
      <?php if ($article['author_name']): ?>
      <span>✍️ <?php echo htmlspecialchars($article['author_name']); ?></span>
      <?php endif; ?>
      <span>👁 <?php echo (int) $article['views']; ?> views</span>
      <span class="article-cat-badge">
        <?php echo $categoryIcons[$article['category']] ?? '📰'; ?>
        <?php echo htmlspecialchars($categoryLabels[$article['category']] ?? 'General'); ?>
      </span>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     ARTICLE BODY
     ═══════════════════════════════════════════ -->
<article class="wrap article-body-wrap">

  <?php if (!empty($article['image'])): ?>
  <img src="<?php echo BASE_PATH; ?>assets/images/news/<?php echo htmlspecialchars($article['image']); ?>"
       alt="<?php echo htmlspecialchars($article['title']); ?>"
       class="article-featured-img"
       loading="lazy"/>
  <?php endif; ?>

  <?php if (!empty($article['excerpt'])): ?>
  <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
  <?php endif; ?>

  <div class="article-body">
    <?php echo $safeBody; ?>
  </div>

  <div class="article-back-link">
    <a href="<?php echo BASE_PATH; ?>news.php" class="btn btn--ghost">← Back to All News</a>
  </div>

  <?php if (!empty($related)): ?>
  <div class="related-articles">
    <h3 class="related-articles__title">Related Articles</h3>
    <div class="related-grid">
      <?php foreach ($related as $r): ?>
      <a href="<?php echo BASE_PATH; ?>news-single.php?slug=<?php echo urlencode($r['slug']); ?>"
         class="related-card">
        <div class="related-card__thumb">
          <?php if (!empty($r['image'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/news/<?php echo htmlspecialchars($r['image']); ?>"
               alt="<?php echo htmlspecialchars($r['title']); ?>"
               loading="lazy"/>
          <?php else: ?>
          <?php echo $categoryIcons[$r['category']] ?? '📰'; ?>
          <?php endif; ?>
        </div>
        <div class="related-card__body">
          <span class="related-card__cat">
            <?php echo htmlspecialchars($categoryLabels[$r['category']] ?? 'General'); ?>
          </span>
          <h4><?php echo htmlspecialchars($r['title']); ?></h4>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</article>

<style>
  /* ── Article body styles — ensures TinyMCE content renders correctly ── */
  .article-body-wrap { max-width: 780px; margin: 0 auto; padding: 40px 20px 60px; }

  .article-excerpt {
    font-size: 17px; color: #5a5870; line-height: 1.7;
    border-left: 4px solid #3d1a6e; padding-left: 18px;
    margin: 28px 0; font-style: italic;
  }

  .article-featured-img {
    width: 100%; max-height: 460px; object-fit: cover;
    border-radius: 12px; margin-bottom: 32px;
    box-shadow: 0 8px 32px rgba(61,26,110,.12);
  }

  .article-body { font-size: 16px; color: #2a2840; line-height: 1.85; }
  .article-body p  { margin-bottom: 1.2em; }
  .article-body h2 { font-family: 'Playfair Display', serif; font-size: 24px; color: #3d1a6e; margin: 32px 0 12px; }
  .article-body h3 { font-family: 'Playfair Display', serif; font-size: 20px; color: #3d1a6e; margin: 24px 0 10px; }
  .article-body h4 { font-size: 17px; font-weight: 700; color: #1a1a2e; margin: 20px 0 8px; }
  .article-body ul, .article-body ol { padding-left: 24px; margin-bottom: 1.2em; }
  .article-body li { margin-bottom: 6px; }
  .article-body blockquote {
    border-left: 4px solid #4a90d9; padding: 12px 18px;
    background: #f0ecfa; border-radius: 0 8px 8px 0;
    margin: 24px 0; color: #3d1a6e; font-style: italic;
  }
  .article-body a { color: #4a90d9; text-decoration: underline; }
  .article-body a:hover { color: #3d1a6e; }
  .article-body img {
    max-width: 100%; border-radius: 8px; margin: 16px 0;
    box-shadow: 0 4px 16px rgba(0,0,0,.1);
  }
  .article-body table {
    width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;
  }
  .article-body th {
    background: #3d1a6e; color: #fff; padding: 10px 14px; text-align: left;
  }
  .article-body td { padding: 9px 14px; border-bottom: 1px solid #f0eef6; }
  .article-body tr:hover td { background: #faf9fd; }
  .article-body pre, .article-body code {
    background: #f4f3f9; border-radius: 6px; font-size: 14px;
    padding: 2px 6px; font-family: monospace;
  }
  .article-body pre { padding: 16px; overflow-x: auto; }

  .article-cat-badge {
    background: rgba(255,255,255,.15); border-radius: 20px;
    padding: 3px 12px; font-size: 12px; font-weight: 600;
  }

  .article-back-link { margin: 40px 0 32px; }

  /* Related articles */
  .related-articles { border-top: 1px solid #f0eef6; padding-top: 32px; margin-top: 8px; }
  .related-articles__title { font-family: 'Playfair Display', serif; font-size: 20px; color: #3d1a6e; margin-bottom: 20px; }
  .related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 16px; }
  .related-card { text-decoration: none; background: #fff; border: 1px solid #e8e6f0; border-radius: 10px; overflow: hidden; transition: box-shadow .2s; }
  .related-card:hover { box-shadow: 0 4px 16px rgba(61,26,110,.1); }
  .related-card__thumb { height: 120px; display: flex; align-items: center; justify-content: center; background: #f4f3f9; font-size: 32px; overflow: hidden; }
  .related-card__thumb img { width: 100%; height: 100%; object-fit: cover; }
  .related-card__body { padding: 12px; }
  .related-card__cat { font-size: 11px; font-weight: 600; color: #9b97b0; text-transform: uppercase; display: block; margin-bottom: 4px; }
  .related-card__body h4 { font-size: 13.5px; font-weight: 600; color: #1a1a2e; line-height: 1.4; margin: 0; }
</style>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>