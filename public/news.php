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

if (!$featured) {
    $featured = $pdo->query(
        "SELECT * FROM news WHERE is_published = 1
         ORDER BY published_at DESC LIMIT 1"
    )->fetch();
}

/* ── Remaining articles ── */
$articlesStmt = $pdo->prepare(
    "SELECT * FROM news WHERE is_published = 1 AND id != ?
     ORDER BY published_at DESC"
);
$articlesStmt->execute([$featured['id'] ?? 0]);
$articles = $articlesStmt->fetchAll();

/* ── Announcements — hardcoded until dedicated table is built ── */
$announcements = [
    [
        'badge' => 'urgent', 'badge_label' => 'URGENT',
        'icon'  => '⚠️',
        'title' => 'Fee Payment Deadline — Second Term',
        'body'  => 'All students must complete Second Term fee payments by the deadline. Students with outstanding fees will not be permitted to sit Second Term examinations.',
        'date'  => 'See school notice board',
    ],
    [
        'badge' => 'notice', 'badge_label' => 'NOTICE',
        'icon'  => '📋',
        'title' => 'New School Website Launched',
        'body'  => 'Ibeku High School has officially launched its first website. Students can now check results online, download timetables, and access school information at any time.',
        'date'  => 'Current session',
    ],
    [
        'badge' => 'info', 'badge_label' => 'INFO',
        'icon'  => '📖',
        'title' => 'Library Hours Extended for SSS 3 Students',
        'body'  => 'To support SSS 3 students preparing for WAEC, the school library will be open from 7:00 AM to 4:30 PM every school day until the examination period.',
        'date'  => 'See school notice board',
    ],
    [
        'badge' => 'notice', 'badge_label' => 'NOTICE',
        'icon'  => '🏥',
        'title' => 'Medical Examination for New JSS 1 Students',
        'body'  => 'All new JSS 1 students are required to undergo the mandatory medical examination. Parents should accompany their wards. Check notice board for date.',
        'date'  => 'See school notice board',
    ],
];
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--news<?php echo getInnerHeroImage('news') ? ' page-hero--photo' : ''; ?>"<?php echo renderInnerHeroStyle('news'); ?>>
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
      <?php foreach ($categoryLabels as $key => $label): ?>
      <button class="news-tab" data-cat="<?php echo $key; ?>"><?php echo $label; ?></button>
      <?php endforeach; ?>
    </div>
    <div class="news-search">
      <span class="news-search__icon">🔍</span>
      <input type="text" id="newsSearch" placeholder="Search news..." autocomplete="off"/>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     FEATURED ARTICLE — from DB
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
        <span class="featured-article__cat cat--<?php echo htmlspecialchars($featured['category']); ?>">
          <?php echo htmlspecialchars($categoryLabels[$featured['category']] ?? 'General'); ?>
        </span>
        <h2><?php echo htmlspecialchars($featured['title']); ?></h2>
        <div class="featured-article__meta">
          <span class="featured-article__date">
            📅 <?php echo date('F j, Y', strtotime($featured['published_at'])); ?>
          </span>
        </div>
        <p><?php echo htmlspecialchars($featured['excerpt'] ?? ''); ?></p>
        <a href="<?php echo BASE_PATH; ?>news-single.php?slug=<?php echo urlencode($featured['slug']); ?>"
           class="featured-article__read-more">Read Full Story →</a>
      </div>

    </div>
  </div>
</div>
<?php else: ?>
<!-- No published articles yet — empty state -->
<div style="text-align:center;padding:60px 20px;color:#6b6b80">
  <div style="font-size:40px;margin-bottom:12px">📰</div>
  <p style="font-size:15px">No news articles published yet. Check back soon.</p>
</div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════
     NEWS GRID — from DB
     ═══════════════════════════════════════════ -->
<section class="news-grid-section" id="news">
  <div class="news-grid-section__inner wrap">

    <div class="news-grid-section__header">
      <h3>Latest News</h3>
      <span class="news-count" id="newsCount"><?php echo count($articles); ?> article<?php echo count($articles) !== 1 ? 's' : ''; ?></span>
    </div>

    <div class="news-grid" id="newsGrid">

      <?php if (empty($articles)): ?>
      <div class="news-no-results" id="newsNoResults">
        <p>No more articles yet</p>
        <span>Check back soon — new articles are added regularly.</span>
      </div>
      <?php else: ?>

      <?php foreach ($articles as $article): ?>
      <article class="news-card reveal"
               data-cat="<?php echo htmlspecialchars($article['category']); ?>"
               data-title="<?php echo htmlspecialchars(strtolower($article['title'])); ?>">

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
          <span class="news-card__cat cat--<?php echo htmlspecialchars($article['category']); ?>">
            <?php echo htmlspecialchars($categoryLabels[$article['category']] ?? 'General'); ?>
          </span>
          <h3><?php echo htmlspecialchars($article['title']); ?></h3>
          <p><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></p>
          <div class="news-card__footer">
            <span class="news-card__date"><?php echo date('F j, Y', strtotime($article['published_at'])); ?></span>
            <a href="<?php echo BASE_PATH; ?>news-single.php?slug=<?php echo urlencode($article['slug']); ?>"
               class="news-card__link">Read more →</a>
          </div>
        </div>

      </article>
      <?php endforeach; ?>

      <div class="news-no-results" id="newsNoResults" style="display:none">
        <p>No articles found</p>
        <span>Try a different category or search term.</span>
      </div>

      <?php endif; ?>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     ANNOUNCEMENTS — hardcoded (dedicated
     table planned for a future phase)
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
    <div class="news-newsletter__success" id="nlSuccess" style="display:none">
      ✅ Subscribed! You will receive school updates by email.
    </div>
  </div>
</section>


<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>

<script>
/* ── Category filter + search ── */
(function () {
  var tabs  = document.querySelectorAll('.news-tab');
  var cards = document.querySelectorAll('.news-card');
  var noRes = document.getElementById('newsNoResults');
  var count = document.getElementById('newsCount');

  function filterNews() {
    var activeCat  = document.querySelector('.news-tab.active').dataset.cat;
    var searchTerm = (document.getElementById('newsSearch').value || '').toLowerCase().trim();
    var visible    = 0;

    cards.forEach(function (card) {
      var catMatch    = activeCat === 'all' || card.dataset.cat === activeCat;
      var searchMatch = !searchTerm || (card.dataset.title || '').includes(searchTerm);
      var show        = catMatch && searchMatch;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    if (noRes)  noRes.style.display = visible === 0 ? 'block' : 'none';
    if (count)  count.textContent   = visible + ' article' + (visible !== 1 ? 's' : '');
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      filterNews();
    });
  });

  var searchInput = document.getElementById('newsSearch');
  if (searchInput) searchInput.addEventListener('input', filterNews);
}());

/* ── Newsletter ── */
function subscribeNewsletter() {
  var input   = document.getElementById('nlNewsEmail');
  var success = document.getElementById('nlSuccess');
  if (!input || !input.value.trim()) { alert('Please enter your email address.'); return; }

  var fd = new FormData();
  fd.append('email', input.value.trim());

  fetch('<?php echo API_PATH; ?>subscribe.php', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        if (success) { success.textContent = '✅ ' + data.message; success.style.display = 'block'; }
        input.value = '';
      } else {
        alert(data.message || 'Something went wrong. Please try again.');
      }
    })
    .catch(function () { alert('A connection error occurred. Please try again.'); });
}
</script>