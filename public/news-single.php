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
    <div class="wrap article-not-found">
      <h1>Article Not Found</h1>
      <p>This article may have been unpublished or removed.</p>
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
?>

<div class="page-hero page-hero--news article-hero">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <a href="<?php echo BASE_PATH; ?>news.php" style="color:rgba(255,255,255,.7)">News</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)"><?php echo htmlspecialchars($categoryLabels[$article['category']] ?? 'General'); ?></span>
    </nav>
    <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
    <div class="article-meta-row">
      <span>📅 <?php echo date('F j, Y', strtotime($article['published_at'])); ?></span>
      <?php if ($article['author_name']): ?>
      <span>✍️ <?php echo htmlspecialchars($article['author_name']); ?></span>
      <?php endif; ?>
      <span>👁 <?php echo (int) $article['views']; ?> views</span>
    </div>
  </div>
</div>

<article class="wrap article-body-wrap">

  <?php if (!empty($article['image'])): ?>
  <img src="<?php echo BASE_PATH; ?>assets/images/news/<?php echo htmlspecialchars($article['image']); ?>"
       alt="<?php echo htmlspecialchars($article['title']); ?>"
       class="article-featured-img"/>
  <?php endif; ?>

  <div class="article-body">
    <?php echo $article['body']; ?>
  </div>

  <div class="article-back-link">
    <a href="<?php echo BASE_PATH; ?>news.php" class="btn btn--ghost">← Back to All News</a>
  </div>

  <?php if (!empty($related)): ?>
  <div class="related-articles">
    <h3 class="related-articles__title">Related Articles</h3>
    <div class="related-grid">
      <?php foreach ($related as $r): ?>
      <a href="<?php echo BASE_PATH; ?>news-single.php?slug=<?php echo urlencode($r['slug']); ?>" class="related-card">
        <div class="related-card__thumb">
          <?php if (!empty($r['image'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/news/<?php echo htmlspecialchars($r['image']); ?>" alt=""/>
          <?php else: ?>
          📰
          <?php endif; ?>
        </div>
        <div class="related-card__body">
          <h4><?php echo htmlspecialchars($r['title']); ?></h4>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</article>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>