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

/*
 * EVENTS DATA
 * Phase 2: replace with database query:
 * SELECT * FROM events WHERE date >= CURDATE() ORDER BY date ASC
 *
 * Categories: academic, sports, culture, examination, meeting, holiday, general
 */

$featured_event = [
  'cat'      => 'examination',
  'cat_label'=> 'Examination',
  'icon'     => '✏️',
  'title'    => 'First Term Examinations — 2024/2025 Academic Session',
  'excerpt'  => 'The First Term examinations for all classes from JSS 1 to SSS 3 commence this month. Students are advised to collect their individual timetables from their form teachers and prepare adequately.',
  'day'      => '13',
  'month'    => 'Jan',
  'time'     => '8:00 AM',
  'venue'    => 'All Classrooms — IHS Main Campus',
  'duration' => 'Two weeks',
];

$secondary_featured = [
  [
    'cat'       => 'culture',
    'cat_label' => 'Culture',
    'icon'      => '🎭',
    'title'     => 'Inter-House Cultural Competition',
    'excerpt'   => 'Annual inter-house cultural competition featuring drama, poetry, traditional dance, and music performances from all four houses.',
    'day'       => '24',
    'month'     => 'Jan',
    'time'      => '10:00 AM',
    'venue'     => 'School Assembly Hall',
    'style'     => 'blue',
  ],
  [
    'cat'       => 'meeting',
    'cat_label' => 'Meeting',
    'icon'      => '🤝',
    'title'     => 'Parents-Teachers Association (PTA) Meeting',
    'excerpt'   => 'The First Term PTA meeting for parents and guardians of all students. Results and school updates will be shared.',
    'day'       => '01',
    'month'     => 'Feb',
    'time'      => '9:00 AM',
    'venue'     => 'School Assembly Hall',
    'style'     => 'purple',
  ],
];

$upcoming_events = [
  [
    'cat'      => 'academic',
    'tag'      => 'academic',
    'title'    => 'Second Term Resumption',
    'desc'     => 'Second Term of the 2024/2025 academic session resumes. All students must report by 8:00 AM.',
    'day'      => '13',
    'month'    => 'Jan',
    'time'     => '8:00 AM',
    'venue'    => 'IHS Main Campus',
  ],
  [
    'cat'      => 'examination',
    'tag'      => 'examination',
    'title'    => 'First Term Examinations Begin',
    'desc'     => 'First Term examinations commence for all classes. Timetables available from form teachers.',
    'day'      => '20',
    'month'    => 'Jan',
    'time'     => '8:00 AM',
    'venue'    => 'All Classrooms',
  ],
  [
    'cat'      => 'culture',
    'tag'      => 'culture',
    'title'    => 'Inter-House Cultural Competition',
    'desc'     => 'Drama, poetry, traditional dance, and music competition between the four school houses.',
    'day'      => '24',
    'month'    => 'Jan',
    'time'     => '10:00 AM',
    'venue'    => 'Assembly Hall',
  ],
  [
    'cat'      => 'sports',
    'tag'      => 'sports',
    'title'    => 'Inter-House Sports Day',
    'desc'     => 'Annual inter-house sports competition — athletics, football, basketball, and field events.',
    'day'      => '28',
    'month'    => 'Jan',
    'time'     => '8:30 AM',
    'venue'    => 'School Sports Field',
  ],
  [
    'cat'      => 'meeting',
    'tag'      => 'meeting',
    'title'    => 'PTA Meeting — First Term',
    'desc'     => 'Parents and guardians are invited to the First Term PTA meeting. Attendance is strongly encouraged.',
    'day'      => '01',
    'month'    => 'Feb',
    'time'     => '9:00 AM',
    'venue'    => 'Assembly Hall',
  ],
  [
    'cat'      => 'academic',
    'tag'      => 'academic',
    'title'    => 'Career Guidance Day — SSS 3',
    'desc'     => 'SSS 3 students will receive career guidance, JAMB preparation tips, and university application advice.',
    'day'      => '08',
    'month'    => 'Feb',
    'time'     => '10:00 AM',
    'venue'    => 'Assembly Hall',
  ],
  [
    'cat'      => 'holiday',
    'tag'      => 'holiday',
    'title'    => 'First Term Ends — School Closes',
    'desc'     => 'End of First Term 2024/2025. Students are dismissed after the closing assembly.',
    'day'      => '14',
    'month'    => 'Feb',
    'time'     => '12:00 PM',
    'venue'    => 'IHS Main Campus',
  ],
  [
    'cat'      => 'academic',
    'tag'      => 'academic',
    'title'    => 'Second Term Resumption',
    'desc'     => 'Second Term of the 2024/2025 academic session resumes for all students.',
    'day'      => '03',
    'month'    => 'Mar',
    'time'     => '8:00 AM',
    'venue'    => 'IHS Main Campus',
  ],
];

$past_events = [
  [
    'icon'   => '🏆',
    'thumb'  => 1,
    'date'   => 'December 10, 2024',
    'title'  => 'Abia State Science Quiz Championship 2024',
    'desc'   => 'IHS retained the Abia State Science Quiz Championship title for the third consecutive year.',
  ],
  [
    'icon'   => '🎭',
    'thumb'  => 2,
    'date'   => 'December 6, 2024',
    'title'  => 'Annual Cultural Day Celebration',
    'desc'   => 'Students celebrated the school\'s cultural heritage with traditional attire, drama, and music performances.',
  ],
  [
    'icon'   => '🎓',
    'thumb'  => 3,
    'date'   => 'November 30, 2024',
    'title'  => 'SSS 3 Valedictory Service & Graduation',
    'desc'   => 'The Class of 2024 graduated in a colourful valedictory ceremony attended by parents and well-wishers.',
  ],
  [
    'icon'   => '📚',
    'thumb'  => 4,
    'date'   => 'November 22, 2024',
    'title'  => 'Prize-Giving Day 2024',
    'desc'   => 'Outstanding students across all categories were recognised and awarded at the annual prize-giving ceremony.',
  ],
  [
    'icon'   => '💻',
    'thumb'  => 5,
    'date'   => 'November 15, 2024',
    'title'  => 'Computer Lab Commissioning',
    'desc'   => 'The newly refurbished computer lab was officially commissioned, funded by the IHS Old Students Association.',
  ],
  [
    'icon'   => '⚽',
    'thumb'  => 6,
    'date'   => 'November 5, 2024',
    'title'  => 'Inter-House Sports Day 2024',
    'desc'   => 'An exciting day of athletics, football, and field events, with Red House emerging overall champion.',
  ],
];
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
    <button class="events-tab" data-cat="academic">Academic</button>
    <button class="events-tab" data-cat="examination">Examinations</button>
    <button class="events-tab" data-cat="sports">Sports</button>
    <button class="events-tab" data-cat="culture">Culture</button>
    <button class="events-tab" data-cat="meeting">Meetings</button>
    <button class="events-tab" data-cat="holiday">Term Dates</button>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     FEATURED UPCOMING EVENTS
     ═══════════════════════════════════════════ -->
<section class="featured-events" id="upcoming">
  <div class="featured-events__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">What's Coming Up</span>
        <h2 class="stitle">Featured <span>Upcoming Events</span></h2>
      </div>
    </div>

    <div class="featured-events-grid">

      <!-- Main featured event -->
      <div class="featured-event-card reveal">
        <div class="featured-event-card__date-badge">
          <span class="day"><?php echo $featured_event['day']; ?></span>
          <span class="month"><?php echo $featured_event['month']; ?></span>
        </div>
        <span class="featured-event-card__icon" aria-hidden="true"><?php echo $featured_event['icon']; ?></span>
        <span class="featured-event-card__cat"><?php echo htmlspecialchars($featured_event['cat_label']); ?></span>
        <h3><?php echo htmlspecialchars($featured_event['title']); ?></h3>
        <p><?php echo htmlspecialchars($featured_event['excerpt']); ?></p>
        <div class="featured-event-card__meta">
          <span>🕐 <?php echo htmlspecialchars($featured_event['time']); ?></span>
          <span>📍 <?php echo htmlspecialchars($featured_event['venue']); ?></span>
          <span>⏱ <?php echo htmlspecialchars($featured_event['duration']); ?></span>
        </div>
      </div>

      <!-- Secondary featured events -->
      <?php foreach ($secondary_featured as $sf): ?>
      <div class="secondary-event-card reveal">
        <div class="secondary-event-card__header secondary-event-card__header--<?php echo $sf['style']; ?>">
          <div class="secondary-event-card__date">
            <span class="day"><?php echo $sf['day']; ?></span>
            <span class="month"><?php echo $sf['month']; ?></span>
          </div>
          <span class="secondary-event-card__icon" aria-hidden="true"><?php echo $sf['icon']; ?></span>
          <span class="secondary-event-card__cat"><?php echo htmlspecialchars($sf['cat_label']); ?></span>
          <h3><?php echo htmlspecialchars($sf['title']); ?></h3>
        </div>
        <div class="secondary-event-card__body">
          <p><?php echo htmlspecialchars($sf['excerpt']); ?></p>
          <div class="secondary-event-card__meta">
            <span>🕐 <?php echo htmlspecialchars($sf['time']); ?></span>
            <span>📍 <?php echo htmlspecialchars($sf['venue']); ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     FULL UPCOMING EVENTS LIST
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
      <?php foreach ($upcoming_events as $ev): ?>
      <div class="event-list-item reveal" data-cat="<?php echo htmlspecialchars($ev['cat']); ?>">

        <div class="event-list-item__date">
          <span class="day"><?php echo htmlspecialchars($ev['day']); ?></span>
          <span class="month"><?php echo htmlspecialchars($ev['month']); ?></span>
        </div>

        <div class="event-list-item__body">
          <h4><?php echo htmlspecialchars($ev['title']); ?></h4>
          <p><?php echo htmlspecialchars($ev['desc']); ?></p>
          <div class="event-list-item__tags">
            <span class="event-tag event-tag--<?php echo $ev['tag']; ?>">
              <?php echo ucfirst($ev['cat']); ?>
            </span>
            <span class="event-tag event-tag--general">
              📍 <?php echo htmlspecialchars($ev['venue']); ?>
            </span>
          </div>
        </div>

        <div class="event-list-item__time">
          <strong><?php echo htmlspecialchars($ev['time']); ?></strong>
          <span><?php echo htmlspecialchars($ev['month'] . ' ' . $ev['day']); ?></span>
        </div>

      </div>
      <?php endforeach; ?>

      <div class="events-none" id="eventsNone" style="display:none">
        <p>No events in this category</p>
        <span>Select a different category or check back soon.</span>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     PAST EVENTS
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
      <?php foreach ($past_events as $pe): ?>
      <div class="past-event-card reveal">
        <div class="past-event-card__thumb past-event-card__thumb--<?php echo $pe['thumb']; ?>" aria-hidden="true">
          <?php echo $pe['icon']; ?>
        </div>
        <div class="past-event-card__body">
          <span class="past-event-card__date"><?php echo htmlspecialchars($pe['date']); ?></span>
          <h4><?php echo htmlspecialchars($pe['title']); ?></h4>
          <p><?php echo htmlspecialchars($pe['desc']); ?></p>
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
  var tabs   = document.querySelectorAll('.events-tab');
  var items  = document.querySelectorAll('.event-list-item');
  var noRes  = document.getElementById('eventsNone');

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