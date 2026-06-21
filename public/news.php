<?php
/* ============================================================
   IBEKU HIGH SCHOOL — NEWS PAGE
   File: public/news.php
   ============================================================ */

$pageTitle   = 'News & Announcements — Ibeku High School';
$pageDesc    = 'Latest news, announcements, and updates from Ibeku High School, Umuahia. Stay informed about school events, achievements, and important notices.';
$currentPage = 'news';
$pageCss     = 'news';

require_once __DIR__ . '/../src/includes/header.php';
require_once __DIR__ . '/../src/config/database.php';

$pdo = getDB();

/* ── Category display labels and emoji fallback (when no image uploaded) ── */
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

/* ── Featured article ── */
$featured = $pdo->query(
    "SELECT * FROM news WHERE is_published = 1 AND featured = 1
     ORDER BY published_at DESC LIMIT 1"
)->fetch();

/* Fallback: if nothing is marked featured, use the most recent published article */
if (!$featured) {
    $featured = $pdo->query(
        "SELECT * FROM news WHERE is_published = 1
         ORDER BY published_at DESC LIMIT 1"
    )->fetch();
}

/* ── Remaining articles (everything except the featured one) ── */
$articlesStmt = $pdo->prepare(
    "SELECT * FROM news WHERE is_published = 1 AND id != ?
     ORDER BY published_at DESC"
);
$articlesStmt->execute([$featured['id'] ?? 0]);
$articles = $articlesStmt->fetchAll();

/*
 * ANNOUNCEMENTS
 * Note: these remain a hardcoded array for now — the schema does
 * not yet have a dedicated announcements table with urgency badges.
 * TODO Phase 4: build an announcements table + admin CRUD, same
 * pattern as news, if the school wants these editable too.
 */
$announcements = [
  [
    'badge' => 'urgent', 'badge_label' => 'URGENT',
    'icon'  => '⚠️',
    'title' => 'Fee Payment Deadline — Second Term 2024/2025',
    'body'  => 'All students must complete Second Term fee payments on or before January 10, 2025. Students with outstanding fees will not be permitted to sit Second Term examinations.',
    'date'  => 'December 20, 2024',
  ],
  [
    'badge' => 'notice', 'badge_label' => 'NOTICE',
    'icon'  => '📋',
    'title' => 'New School Website Launched',
    'body'  => 'Ibeku High School has officially launched its first website. Students can now check results online, download timetables, and access school information at any time.',
    'date'  => 'December 15, 2024',
  ],
  [
    'badge' => 'info', 'badge_label' => 'INFO',
    'icon'  => '📖',
    'title' => 'Library Hours Extended for SSS 3 Students',
    'body'  => 'To support SSS 3 students preparing for WAEC, the school library will be open from 7:00 AM to 4:30 PM every school day from January until examination period.',
    'date'  => 'November 30, 2024',
  ],
  [
    'badge' => 'notice', 'badge_label' => 'NOTICE',
    'icon'  => '🏥',
    'title' => 'Medical Examination for New JSS 1 Students',
    'body'  => 'All new JSS 1 students are required to undergo the mandatory medical examination on Wednesday, January 15, 2025. Parents should accompany their wards.',
    'date'  => 'January 5, 2025',
  ],
];
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--news">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">News &amp; Announcements</span>
    </nav>
    <h1>News &amp; <em>Announcements</em></h1>
    <p>Stay up to date with the latest news, achievements, notices, and announcements from Ibeku High School, Umuahia.</p>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     CATEGORY FILTER BAR
     ═══════════════════════════════════════════ -->
<div class="news-filter-bar">
  <div class="news-filter-bar__inner wrap">
    <div class="news-filter-tabs">
      <button class="news-tab active" data-cat="all">All</button>
      <button class="news-tab" data-cat="achievement">Achievement</button>
      <button class="news-tab" data-cat="academic">Academic</button>
      <button class="news-tab" data-cat="announcement">Announcement</button>
      <button class="news-tab" data-cat="sports">Sports</button>
      <button class="news-tab" data-cat="ict">ICT</button>
      <button class="news-tab" data-cat="culture">Culture</button>
    </div>
    <div class="news-search">
      <span class="news-search__icon">🔍</span>
      <input type="text" id="newsSearch" placeholder="Search news..." autocomplete="off"/>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     FEATURED ARTICLE
     ═══════════════════════════════════════════ -->
<?php if ($featured): ?>
<div class="featured-article" id="featured">
  <div class="featured-article__inner reveal">
    <div class="featured-article__card">

      <div class="featured-article__img">
        <?php if (!empty($featured['image'])): ?>
        <img src="<?php echo BASE_PATH; ?>assets/images/news/<?php echo htmlspecialchars($featured['image']); ?>"
             alt="<?php echo htmlspecialchars($featured['title']); ?>"
             style="width:100%;height:100%;object-fit:cover;"/>
        <?php else: ?>
        <div class="featured-article__img-placeholder" aria-hidden="true">
          <?php echo $categoryIcons[$featured['category']] ?? '📰'; ?>
        </div>
        <?php endif; ?>
        <span class="featured-article__badge">⭐ Featured Story</span>
      </div>

      <div class="featured-article__body">
        <span class="featured-article__cat cat--<?php echo $featured['category']; ?>">
          <?php echo htmlspecialchars($categoryLabels[$featured['category']] ?? 'General'); ?>
        </span>
        <h2><?php echo htmlspecialchars($featured['title']); ?></h2>
        <div class="featured-article__meta">
          <span class="featured-article__date">📅 <?php echo date('F j, Y', strtotime($featured['published_at'])); ?></span>
        </div>
        <p><?php echo htmlspecialchars($featured['excerpt'] ?? ''); ?></p>
        <a href="<?php echo BASE_PATH; ?>news-single.php?slug=<?php echo urlencode($featured['slug']); ?>" class="featured-article__read-more">Read Full Story →</a>
      </div>

    </div>
  </div>
</div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════
     NEWS GRID
     ═══════════════════════════════════════════ -->
<section class="news-grid-section" id="news">
  <div class="news-grid-section__inner wrap">

    <div class="news-grid-section__header">
      <h3>Latest News</h3>
      <span class="news-count" id="newsCount"><?php echo count($articles); ?> articles</span>
    </div>

    <div class="news-grid" id="newsGrid">

      <?php foreach ($articles as $article): ?>
      <article class="news-card reveal" data-cat="<?php echo htmlspecialchars($article['category']); ?>" data-title="<?php echo htmlspecialchars(strtolower($article['title'])); ?>">

        <div class="news-card__thumb" aria-hidden="true">
          <?php if (!empty($article['image'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/news/<?php echo htmlspecialchars($article['image']); ?>"
               alt="<?php echo htmlspecialchars($article['title']); ?>"
               style="width:100%;height:100%;object-fit:cover;"/>
          <?php else: ?>
          <?php echo $categoryIcons[$article['category']] ?? '📰'; ?>
          <?php endif; ?>
        </div>

        <div class="news-card__body">
          <span class="news-card__cat cat--<?php echo $article['category']; ?>">
            <?php echo htmlspecialchars($categoryLabels[$article['category']] ?? 'General'); ?>
          </span>
          <h3><?php echo htmlspecialchars($article['title']); ?></h3>
          <p><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></p>
          <div class="news-card__footer">
            <span class="news-card__date"><?php echo date('F j, Y', strtotime($article['published_at'])); ?></span>
            <a href="<?php echo BASE_PATH; ?>news-single.php?slug=<?php echo urlencode($article['slug']); ?>" class="news-card__link">Read more →</a>
          </div>
        </div>

      </article>
      <?php endforeach; ?>

      <!-- Shown when no articles match the filter/search -->
      <div class="news-no-results" id="newsNoResults" style="<?php echo empty($articles) ? '' : 'display:none'; ?>">
        <p>No articles found</p>
        <span>Try a different category or search term.</span>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     ANNOUNCEMENTS
     ═══════════════════════════════════════════ -->
<section class="announcements-band" id="announcements">
  <div class="announcements-band__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel slabel--light">Official Notices</span>
        <h2 class="stitle stitle--white">School <span style="color:var(--blue-light)">Announcements</span></h2>
      </div>
    </div>

    <div class="announcements-list">
      <?php foreach ($announcements as $ann): ?>
      <div class="announcement-item reveal">
        <span class="announcement-item__icon" aria-hidden="true"><?php echo $ann['icon']; ?></span>
        <div class="announcement-item__body">
          <span class="announcement-item__badge badge--<?php echo $ann['badge']; ?>">
            <?php echo htmlspecialchars($ann['badge_label']); ?>
          </span>
          <h4><?php echo htmlspecialchars($ann['title']); ?></h4>
          <p><?php echo htmlspecialchars($ann['body']); ?></p>
        </div>
        <span class="announcement-item__date"><?php echo htmlspecialchars($ann['date']); ?></span>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     NEWSLETTER SIGNUP
     ═══════════════════════════════════════════ -->
<section class="news-newsletter">
  <div class="news-newsletter__inner">
    <span class="slabel">Stay Connected</span>
    <h2>Subscribe to School Updates</h2>
    <p>Get important notices, results announcements, event reminders, and school news delivered directly to your inbox. Never miss an update from Ibeku High School.</p>
    <div class="news-newsletter__form">
      <label for="nlNewsEmail" class="sr-only">Email address</label>
      <input class="form-input" type="email" id="nlNewsEmail" placeholder="Enter your email address"/>
      <button class="btn btn--secondary" onclick="subscribeNewsletter()">Subscribe</button>
    </div>
    <div class="news-newsletter__success" id="nlSuccess">
      ✅ Subscribed! You will receive school updates by email.
    </div>
  </div>
</section>


<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>

<script>
/* ── Category filter ── */
(function () {
  var tabs    = document.querySelectorAll('.news-tab');
  var cards   = document.querySelectorAll('.news-card');
  var noRes   = document.getElementById('newsNoResults');
  var count   = document.getElementById('newsCount');

  function filterNews() {
    var activeCat    = document.querySelector('.news-tab.active').dataset.cat;
    var searchTerm   = (document.getElementById('newsSearch').value || '').toLowerCase().trim();
    var visibleCount = 0;

    cards.forEach(function (card) {
      var catMatch    = activeCat === 'all' || card.dataset.cat === activeCat;
      var searchMatch = !searchTerm || card.dataset.title.includes(searchTerm);
      var show        = catMatch && searchMatch;

      card.style.display = show ? '' : 'none';
      if (show) visibleCount++;
    });

    if (noRes)  noRes.style.display  = visibleCount === 0 ? 'block' : 'none';
    if (count)  count.textContent    = visibleCount + ' article' + (visibleCount !== 1 ? 's' : '');
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      filterNews();
    });
  });

  var searchInput = document.getElementById('newsSearch');
  if (searchInput) {
    searchInput.addEventListener('input', filterNews);
  }
}());

/* ── Newsletter — sends to src/api/subscribe.php ── */
function subscribeNewsletter() {
  var input   = document.getElementById('nlNewsEmail');
  var success = document.getElementById('nlSuccess');

  if (!input || !input.value.trim()) {
    alert('Please enter your email address.');
    return;
  }

  var formData = new FormData();
  formData.append('email', input.value.trim());

  fetch('/ibeku-high-school/src/api/subscribe.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        if (success) {
          success.textContent = '✅ ' + data.message;
          success.style.display = 'block';
        }
        input.value = '';
      } else {
        alert(data.message || 'Something went wrong. Please try again.');
      }
    })
    .catch(function (err) {
      console.error('Subscribe error:', err);
      alert('A connection error occurred. Please try again.');
    });
}
</script>