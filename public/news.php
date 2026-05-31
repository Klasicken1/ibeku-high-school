<?php
/* ============================================================
   IBEKU HIGH SCHOOL — NEWS PAGE
   File: public/news.php
   ============================================================ */

$pageTitle   = 'News & Announcements — Ibeku High School';
$pageDesc    = 'Latest news, announcements, and updates from Ibeku High School, Umuahia. Stay informed about school events, achievements, and important notices.';
$currentPage = 'news';
$pageCss     = 'news';

require_once '../src/includes/header.php';

/*
 * NEWS DATA
 * Phase 2: replace these arrays with database queries:
 * SELECT * FROM news WHERE published = 1 ORDER BY created_at DESC
 *
 * Category options: achievement, academic, ict, sports, announcement, culture, general
 */

$featured = [
  'cat'     => 'achievement',
  'cat_label' => 'Achievement',
  'icon'    => '🏆',
  'title'   => 'IHS Students Win Abia State Science Quiz Championship for the Third Consecutive Year',
  'excerpt' => 'The Ibeku High School science quiz team has once again made the school proud, winning the Abia State Secondary School Science Quiz Championship for the third consecutive year — an unprecedented achievement in the history of the competition. The team, made up of SSS 2 students, defeated schools from across all local government areas to retain the title.',
  'date'    => 'December 10, 2024',
  'author'  => 'IHS Communications',
  'slug'    => 'science-quiz-championship-2024',
];

$articles = [
  [
    'cat' => 'academic', 'cat_label' => 'Academic',
    'icon' => '📋',
    'title' => 'First Term 2024/2025 Examinations Timetable Released',
    'excerpt' => 'The First Term examination timetable for the 2024/2025 academic session has been released. Students should collect copies from their form teachers.',
    'date' => 'November 28, 2024',
    'slug' => 'first-term-timetable-2024',
  ],
  [
    'cat' => 'ict', 'cat_label' => 'ICT',
    'icon' => '💻',
    'title' => 'Computer Lab Fully Refurbished Through Alumni Donation',
    'excerpt' => 'The school computer laboratory has been fully refurbished with new desktop computers and internet connectivity, funded through a generous donation from the IHS Old Students Association.',
    'date' => 'November 15, 2024',
    'slug' => 'computer-lab-refurbishment',
  ],
  [
    'cat' => 'sports', 'cat_label' => 'Sports',
    'icon' => '⚽',
    'title' => 'IHS Football Team Wins Umuahia Zonal Championship',
    'excerpt' => 'The Ibeku High School football team has won the Umuahia Zonal Secondary School Football Championship, defeating seven other schools in the competition.',
    'date' => 'November 5, 2024',
    'slug' => 'football-zonal-championship',
  ],
  [
    'cat' => 'announcement', 'cat_label' => 'Announcement',
    'icon' => '📢',
    'title' => 'Second Term Resumption Date Announced',
    'excerpt' => 'The school management wishes to inform all students and parents that the Second Term of the 2024/2025 academic session will resume on Monday, January 13, 2025.',
    'date' => 'December 18, 2024',
    'slug' => 'second-term-resumption-date',
  ],
  [
    'cat' => 'culture', 'cat_label' => 'Culture',
    'icon' => '🎭',
    'title' => 'Annual Cultural Day Celebration Holds December 6th',
    'excerpt' => 'The annual Ibeku High School Cultural Day celebration is scheduled for Friday, December 6, 2024. Students are encouraged to come in their cultural attires.',
    'date' => 'November 25, 2024',
    'slug' => 'cultural-day-2024',
  ],
  [
    'cat' => 'academic', 'cat_label' => 'Academic',
    'icon' => '📚',
    'title' => 'First Term 2024/2025 Results Now Available Online',
    'excerpt' => 'First Term examination results for the 2024/2025 academic session are now available on the school website. Students can check results using their Admission Number.',
    'date' => 'December 22, 2024',
    'slug' => 'first-term-results-available',
  ],
];

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
<div class="featured-article" id="featured">
  <div class="featured-article__inner reveal">
    <div class="featured-article__card">

      <div class="featured-article__img">
        <!--
          REPLACE WITH REAL IMAGE:
          <img src="<?php echo BASE_PATH; ?>assets/images/news/<?php echo $featured['slug']; ?>.jpg"
               alt="<?php echo htmlspecialchars($featured['title']); ?>"/>
        -->
        <div class="featured-article__img-placeholder" aria-hidden="true">
          <?php echo $featured['icon']; ?>
        </div>
        <span class="featured-article__badge">⭐ Featured Story</span>
      </div>

      <div class="featured-article__body">
        <span class="featured-article__cat cat--<?php echo $featured['cat']; ?>">
          <?php echo htmlspecialchars($featured['cat_label']); ?>
        </span>
        <h2><?php echo htmlspecialchars($featured['title']); ?></h2>
        <div class="featured-article__meta">
          <span class="featured-article__date">📅 <?php echo htmlspecialchars($featured['date']); ?></span>
          <span class="featured-article__author">✍️ <?php echo htmlspecialchars($featured['author']); ?></span>
        </div>
        <p><?php echo htmlspecialchars($featured['excerpt']); ?></p>
        <a href="<?php echo BASE_PATH; ?>news-single.php?slug=<?php echo urlencode($featured['slug']); ?>" class="featured-article__read-more">Read Full Story →</a>
      </div>

    </div>
  </div>
</div>


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
      <article class="news-card reveal" data-cat="<?php echo htmlspecialchars($article['cat']); ?>" data-title="<?php echo htmlspecialchars(strtolower($article['title'])); ?>">

        <div class="news-card__thumb" aria-hidden="true">
          <?php echo $article['icon']; ?>
          <!--
            REPLACE WITH REAL IMAGE:
            <img src="<?php echo BASE_PATH; ?>assets/images/news/<?php echo $article['slug']; ?>.jpg"
                 alt="<?php echo htmlspecialchars($article['title']); ?>"/>
          -->
        </div>

        <div class="news-card__body">
          <span class="news-card__cat cat--<?php echo $article['cat']; ?>">
            <?php echo htmlspecialchars($article['cat_label']); ?>
          </span>
          <h3><?php echo htmlspecialchars($article['title']); ?></h3>
          <p><?php echo htmlspecialchars($article['excerpt']); ?></p>
          <div class="news-card__footer">
            <span class="news-card__date"><?php echo htmlspecialchars($article['date']); ?></span>
            <a href="<?php echo BASE_PATH; ?>news-single.php?slug=<?php echo urlencode($article['slug']); ?>" class="news-card__link">Read more →</a>
          </div>
        </div>

      </article>
      <?php endforeach; ?>

      <!-- Shown when no articles match the filter/search -->
      <div class="news-no-results" id="newsNoResults">
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


<?php require_once '../src/includes/footer.php'; ?>

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

/* ── Newsletter ── */
function subscribeNewsletter() {
  var input   = document.getElementById('nlNewsEmail');
  var success = document.getElementById('nlSuccess');
  if (!input || !input.value.trim()) {
    alert('Please enter your email address.');
    return;
  }
  /* Phase 2: fetch() to src/api/subscribe.php */
  if (success) success.style.display = 'block';
}
</script>