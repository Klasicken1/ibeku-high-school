<?php
/* ============================================================
   IBEKU HIGH SCHOOL — EVENTS PAGE
   File: public/events.php
   ============================================================ */

$pageTitle   = 'Events — Ibeku High School, Umuahia';
$pageDesc    = 'Upcoming and past events at Ibeku High School — examinations, sports days, cultural events, graduation, PTA meetings, and more.';
$currentPage = 'news';
$pageCss     = 'events';

require_once '../src/includes/header.php';
require_once '../src/config/database.php';
$pdo = getDB();

$categories = [
    'academic'    => 'Academic',
    'sports'      => 'Sports',
    'culture'     => 'Culture',
    'examination' => 'Examination',
    'meeting'     => 'Meeting',
    'holiday'     => 'Holiday',
    'general'     => 'General',
];

$categoryColors = [
    'academic'    => ['bg' => '#e6f0ff', 'color' => '#1a5a9a'],
    'sports'      => ['bg' => '#e6f9ed', 'color' => '#1a7a3a'],
    'culture'     => ['bg' => '#f0ecfa', 'color' => '#3d1a6e'],
    'examination' => ['bg' => '#ffe6e6', 'color' => '#cc3333'],
    'meeting'     => ['bg' => '#fff3e6', 'color' => '#8a4a00'],
    'holiday'     => ['bg' => '#fffbe6', 'color' => '#8a6a00'],
    'general'     => ['bg' => '#f4f3f9', 'color' => '#6b6b80'],
];

/* ── Featured event — most recent upcoming published and featured ── */
$featuredEvent = $pdo->query(
    "SELECT * FROM events
     WHERE is_published = 1 AND is_featured = 1
       AND event_date >= CURDATE()
     ORDER BY event_date ASC LIMIT 1"
)->fetch();

/* Fallback: next upcoming published event if none is featured */
if (!$featuredEvent) {
    $featuredEvent = $pdo->query(
        "SELECT * FROM events
         WHERE is_published = 1 AND event_date >= CURDATE()
         ORDER BY event_date ASC LIMIT 1"
    )->fetch();
}

/* ── Next 2 upcoming events after featured (for secondary cards) ── */
$secondaryStmt = $pdo->prepare(
    "SELECT * FROM events
     WHERE is_published = 1
       AND event_date >= CURDATE()
       AND id != ?
     ORDER BY event_date ASC LIMIT 2"
);
$secondaryStmt->execute([$featuredEvent['id'] ?? 0]);
$secondaryEvents = $secondaryStmt->fetchAll();

/* ── Full upcoming list (excluding featured and secondary) ── */
$excludeIds = array_filter([
    $featuredEvent['id']  ?? null,
    $secondaryEvents[0]['id'] ?? null,
    $secondaryEvents[1]['id'] ?? null,
]);
$placeholders = implode(',', array_fill(0, max(count($excludeIds), 1), '?'));
$upcomingStmt = $pdo->prepare(
    "SELECT * FROM events
     WHERE is_published = 1
       AND event_date >= CURDATE()
       AND id NOT IN ($placeholders)
     ORDER BY event_date ASC, start_time ASC"
);
$upcomingStmt->execute($excludeIds ?: [0]);
$upcomingEvents = $upcomingStmt->fetchAll();

/* ── Past events ── */
$pastEvents = $pdo->query(
    "SELECT * FROM events
     WHERE is_published = 1 AND event_date < CURDATE()
     ORDER BY event_date DESC LIMIT 6"
)->fetchAll();

/* ── Fallback hardcoded past events if DB is empty ── */
$defaultPastEvents = [
    ['icon'=>'🏆','event_date'=>'2024-12-10','title'=>'Abia State Science Quiz Championship','description'=>'IHS retained the state science quiz title for the third consecutive year.'],
    ['icon'=>'🎭','event_date'=>'2024-12-06','title'=>'Annual Cultural Day Celebration',      'description'=>'Students celebrated the school\'s cultural heritage with traditional attire, drama, and music.'],
    ['icon'=>'🎓','event_date'=>'2024-11-30','title'=>'SSS 3 Valedictory Service & Graduation','description'=>'The Class of 2024 graduated in a colourful valedictory ceremony attended by parents and well-wishers.'],
    ['icon'=>'📚','event_date'=>'2024-11-22','title'=>'Prize-Giving Day',                    'description'=>'Outstanding students were recognised and awarded at the annual prize-giving ceremony.'],
    ['icon'=>'💻','event_date'=>'2024-11-15','title'=>'Computer Lab Commissioning',           'description'=>'The newly refurbished computer lab was officially commissioned, funded by the IHS Old Students Association.'],
    ['icon'=>'⚽','event_date'=>'2024-11-05','title'=>'Inter-House Sports Day',              'description'=>'An exciting day of athletics, football, and field events, with Red House emerging overall champion.'],
];
if (empty($pastEvents)) {
    $pastEvents    = $defaultPastEvents;
    $pastIsDefault = true;
} else {
    $pastIsDefault = false;
}
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--events">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">Events</span>
    </nav>
    <h1>School <em>Events</em> &amp;<br/>Calendar</h1>
    <p>Stay up to date with examinations, sports days, cultural events, graduation ceremonies, PTA meetings, and everything happening at Ibeku High School.</p>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     FILTER BAR
     ═══════════════════════════════════════════ -->
<div class="events-filter-bar">
  <div class="events-filter-bar__inner wrap">
    <button class="events-tab active" data-cat="all">All Events</button>
    <?php foreach ($categories as $key => $label): ?>
    <button class="events-tab" data-cat="<?php echo $key; ?>"><?php echo $label; ?></button>
    <?php endforeach; ?>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     FEATURED UPCOMING EVENTS — from DB
     ═══════════════════════════════════════════ -->
<section class="featured-events" id="upcoming">
  <div class="featured-events__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">What's Coming Up</span>
        <h2 class="stitle">Featured <span>Upcoming Events</span></h2>
      </div>
    </div>

    <?php if (!$featuredEvent): ?>
    <p style="color:#6b6b80;text-align:center;padding:40px 0">
      No upcoming events at the moment. Check back soon.
    </p>
    <?php else: ?>
    <div class="featured-events-grid">

      <!-- Main featured event -->
      <div class="featured-event-card reveal">
        <div class="featured-event-card__date-badge">
          <span class="day"><?php echo date('d', strtotime($featuredEvent['event_date'])); ?></span>
          <span class="month"><?php echo date('M', strtotime($featuredEvent['event_date'])); ?></span>
        </div>
        <span class="featured-event-card__cat">
          <?php echo htmlspecialchars($categories[$featuredEvent['category']] ?? 'General'); ?>
        </span>
        <h3><?php echo htmlspecialchars($featuredEvent['title']); ?></h3>
        <?php if ($featuredEvent['description']): ?>
        <p><?php echo htmlspecialchars($featuredEvent['description']); ?></p>
        <?php endif; ?>
        <div class="featured-event-card__meta">
          <?php if ($featuredEvent['start_time']): ?>
          <span>🕐 <?php echo date('g:ia', strtotime($featuredEvent['start_time'])); ?></span>
          <?php endif; ?>
          <?php if ($featuredEvent['venue']): ?>
          <span>📍 <?php echo htmlspecialchars($featuredEvent['venue']); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Secondary featured events -->
      <?php
      $cardStyles = ['blue', 'purple'];
      foreach ($secondaryEvents as $si => $sf):
        $style    = $cardStyles[$si % 2];
        $catColor = $categoryColors[$sf['category']] ?? $categoryColors['general'];
      ?>
      <div class="secondary-event-card reveal">
        <div class="secondary-event-card__header secondary-event-card__header--<?php echo $style; ?>">
          <div class="secondary-event-card__date">
            <span class="day"><?php echo date('d', strtotime($sf['event_date'])); ?></span>
            <span class="month"><?php echo date('M', strtotime($sf['event_date'])); ?></span>
          </div>
          <span class="secondary-event-card__cat">
            <?php echo htmlspecialchars($categories[$sf['category']] ?? 'General'); ?>
          </span>
          <h3><?php echo htmlspecialchars($sf['title']); ?></h3>
        </div>
        <div class="secondary-event-card__body">
          <?php if ($sf['description']): ?>
          <p><?php echo htmlspecialchars(mb_substr($sf['description'], 0, 120)) . (mb_strlen($sf['description']) > 120 ? '…' : ''); ?></p>
          <?php endif; ?>
          <div class="secondary-event-card__meta">
            <?php if ($sf['start_time']): ?>
            <span>🕐 <?php echo date('g:ia', strtotime($sf['start_time'])); ?></span>
            <?php endif; ?>
            <?php if ($sf['venue']): ?>
            <span>📍 <?php echo htmlspecialchars($sf['venue']); ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

    </div>
    <?php endif; ?>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     FULL UPCOMING EVENTS LIST — from DB
     ═══════════════════════════════════════════ -->
<section class="upcoming-events">
  <div class="upcoming-events__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Full Schedule</span>
        <h2 class="stitle">All <span>Upcoming Events</span></h2>
      </div>
    </div>

    <div class="events-list" id="eventsList">

      <?php if (empty($upcomingEvents) && !$featuredEvent): ?>
      <div class="events-none" id="eventsNone">
        <p>No upcoming events scheduled</p>
        <span>Check back soon — events are added regularly.</span>
      </div>
      <?php else: ?>

      <?php foreach ($upcomingEvents as $ev):
        $catColor = $categoryColors[$ev['category']] ?? $categoryColors['general'];
        $isToday  = $ev['event_date'] === date('Y-m-d');
      ?>
      <div class="event-list-item reveal"
           data-cat="<?php echo htmlspecialchars($ev['category']); ?>">

        <div class="event-list-item__date">
          <span class="day"><?php echo date('d', strtotime($ev['event_date'])); ?></span>
          <span class="month"><?php echo date('M', strtotime($ev['event_date'])); ?></span>
        </div>

        <div class="event-list-item__body">
          <h4>
            <?php echo htmlspecialchars($ev['title']); ?>
            <?php if ($isToday): ?><span style="background:#4a90d9;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-left:6px;text-transform:uppercase">Today</span><?php endif; ?>
          </h4>
          <?php if ($ev['description']): ?>
          <p><?php echo htmlspecialchars(mb_substr($ev['description'], 0, 120)) . (mb_strlen($ev['description']) > 120 ? '…' : ''); ?></p>
          <?php endif; ?>
          <div class="event-list-item__tags">
            <span class="event-tag event-tag--<?php echo htmlspecialchars($ev['category']); ?>"
                  style="background:<?php echo $catColor['bg']; ?>;color:<?php echo $catColor['color']; ?>">
              <?php echo htmlspecialchars($categories[$ev['category']] ?? 'General'); ?>
            </span>
            <?php if ($ev['venue']): ?>
            <span class="event-tag event-tag--general">
              📍 <?php echo htmlspecialchars($ev['venue']); ?>
            </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="event-list-item__time">
          <?php if ($ev['start_time']): ?>
          <strong><?php echo date('g:ia', strtotime($ev['start_time'])); ?></strong>
          <?php endif; ?>
          <span><?php echo date('M j', strtotime($ev['event_date'])); ?></span>
        </div>

      </div>
      <?php endforeach; ?>

      <div class="events-none" id="eventsNone" style="display:none">
        <p>No events in this category</p>
        <span>Select a different category or check back soon.</span>
      </div>

      <?php endif; ?>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     PAST EVENTS — from DB with fallback
     ═══════════════════════════════════════════ -->
<section class="past-events" id="past">
  <div class="past-events__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Looking Back</span>
        <h2 class="stitle">Past <span>Events</span></h2>
        <p class="ssub">A record of recent events and activities at Ibeku High School.</p>
      </div>
    </div>

    <div class="past-events-grid">
      <?php foreach ($pastEvents as $i => $pe):
        /* Support both DB rows and fallback hardcoded array */
        $peTitle = $pe['title'] ?? '';
        $peDesc  = $pe['description'] ?? '';
        $peDate  = isset($pe['event_date'])
            ? date('F j, Y', strtotime($pe['event_date']))
            : ($pe['date'] ?? '');
        $peIcon  = $pe['icon'] ?? '📅';
      ?>
      <div class="past-event-card reveal">
        <div class="past-event-card__thumb past-event-card__thumb--<?php echo ($i % 6) + 1; ?>" aria-hidden="true">
          <?php echo $peIcon; ?>
        </div>
        <div class="past-event-card__body">
          <span class="past-event-card__date"><?php echo htmlspecialchars($peDate); ?></span>
          <h4><?php echo htmlspecialchars($peTitle); ?></h4>
          <p><?php echo htmlspecialchars(mb_substr($peDesc, 0, 120)) . (mb_strlen($peDesc) > 120 ? '…' : ''); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<?php require_once '../src/includes/footer.php'; ?>

<script>
/* ── Events category filter ── */
(function () {
  var tabs  = document.querySelectorAll('.events-tab');
  var items = document.querySelectorAll('.event-list-item');
  var noRes = document.getElementById('eventsNone');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');

      var cat     = tab.dataset.cat;
      var visible = 0;

      items.forEach(function (item) {
        var show = cat === 'all' || item.dataset.cat === cat;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
    });
  });
}());
</script>