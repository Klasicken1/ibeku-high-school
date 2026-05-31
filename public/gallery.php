<?php
/* ============================================================
   IBEKU HIGH SCHOOL — GALLERY PAGE
   File: public/gallery.php
   ============================================================ */

$pageTitle   = 'Gallery — Ibeku High School, Umuahia';
$pageDesc    = 'Photos and memories from Ibeku High School — sports, events, classrooms, graduation, cultural day, and more.';
$currentPage = 'news';
$pageCss     = 'gallery';
$pageJs      = 'gallery';

require_once '../src/includes/header.php';

/*
 * GALLERY DATA
 * Phase 2: replace with database query:
 * SELECT * FROM gallery WHERE published = 1 ORDER BY created_at DESC
 *
 * Categories: sports, events, classrooms, graduation, culture, assembly, ict, general
 *
 * item keys:
 *   cat      — category slug
 *   label    — category display label
 *   title    — photo caption
 *   icon     — emoji placeholder until real photo is added
 *   img      — path to real image (empty = show placeholder)
 *   wide     — true = spans 2 columns
 *   tall     — true = spans 2 rows
 */
$photos = [

  /* ── Row 1 ── */
  [
    'cat'   => 'sports',
    'label' => 'Sports',
    'title' => 'Science Quiz Championship 2024 — Winners',
    'icon'  => '🏆',
    'img'   => '',
    'wide'  => true,
    'tall'  => false,
  ],
  [
    'cat'   => 'assembly',
    'label' => 'Assembly',
    'title' => 'Morning Assembly — Flag Raising',
    'icon'  => '🏫',
    'img'   => '',
    'wide'  => false,
    'tall'  => true,
  ],
  [
    'cat'   => 'classrooms',
    'label' => 'Classrooms',
    'title' => 'SSS 2 Science Class in Session',
    'icon'  => '📚',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],

  /* ── Row 2 ── */
  [
    'cat'   => 'ict',
    'label' => 'ICT',
    'title' => 'Students in the Newly Refurbished Computer Lab',
    'icon'  => '💻',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'culture',
    'label' => 'Culture',
    'title' => 'Cultural Day 2024 — Traditional Attire Display',
    'icon'  => '🎭',
    'img'   => '',
    'wide'  => true,
    'tall'  => false,
  ],

  /* ── Row 3 ── */
  [
    'cat'   => 'graduation',
    'label' => 'Graduation',
    'title' => 'SSS 3 Graduation Ceremony 2024',
    'icon'  => '🎓',
    'img'   => '',
    'wide'  => true,
    'tall'  => false,
  ],
  [
    'cat'   => 'sports',
    'label' => 'Sports',
    'title' => 'Inter-House Sports Day — Track Events',
    'icon'  => '🏃',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'events',
    'label' => 'Events',
    'title' => 'Prize-Giving Day 2024',
    'icon'  => '🏅',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],

  /* ── Row 4 ── */
  [
    'cat'   => 'classrooms',
    'label' => 'Classrooms',
    'title' => 'Biology Practical in the Science Laboratory',
    'icon'  => '🔬',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'assembly',
    'label' => 'Assembly',
    'title' => 'End-of-Term Thanksgiving Assembly',
    'icon'  => '🙏',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'sports',
    'label' => 'Sports',
    'title' => 'Football Team — Zonal Championship Winners',
    'icon'  => '⚽',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'culture',
    'label' => 'Culture',
    'title' => 'Drama Club Performance — Cultural Day',
    'icon'  => '🎪',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],

  /* ── Row 5 ── */
  [
    'cat'   => 'events',
    'label' => 'Events',
    'title' => 'Parents-Teachers Association Meeting',
    'icon'  => '🤝',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'graduation',
    'label' => 'Graduation',
    'title' => 'Valedictory Service — Class of 2024',
    'icon'  => '📜',
    'img'   => '',
    'wide'  => true,
    'tall'  => false,
  ],
  [
    'cat'   => 'ict',
    'label' => 'ICT',
    'title' => 'ICT Club Members — Digital Skills Training',
    'icon'  => '🖥️',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],

  /* ── Row 6 ── */
  [
    'cat'   => 'classrooms',
    'label' => 'Classrooms',
    'title' => 'Library — Students Studying During Free Period',
    'icon'  => '📖',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'sports',
    'label' => 'Sports',
    'title' => 'Basketball Court — Inter-Class Tournament',
    'icon'  => '🏀',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'events',
    'label' => 'Events',
    'title' => 'New Students Orientation Day',
    'icon'  => '👋',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],
  [
    'cat'   => 'culture',
    'label' => 'Culture',
    'title' => 'Scripture Union — Weekly Fellowship',
    'icon'  => '✝️',
    'img'   => '',
    'wide'  => false,
    'tall'  => false,
  ],

];

$totalPhotos = count($photos);
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--gallery">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">Gallery</span>
    </nav>
    <h1>School Life in <em>Pictures</em></h1>
    <p>A collection of photographs celebrating life at Ibeku High School — from classrooms and sports to graduation and cultural events.</p>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     GALLERY FILTER BAR
     ═══════════════════════════════════════════ -->
<div class="gallery-filter-bar">
  <div class="gallery-filter-bar__inner wrap">
    <div class="gallery-filter-tabs">
      <button class="gallery-tab active" data-cat="all">All Photos</button>
      <button class="gallery-tab" data-cat="sports">Sports</button>
      <button class="gallery-tab" data-cat="events">Events</button>
      <button class="gallery-tab" data-cat="classrooms">Classrooms</button>
      <button class="gallery-tab" data-cat="graduation">Graduation</button>
      <button class="gallery-tab" data-cat="culture">Culture</button>
      <button class="gallery-tab" data-cat="assembly">Assembly</button>
      <button class="gallery-tab" data-cat="ict">ICT</button>
    </div>
    <div class="gallery-view-toggle">
      <button class="gallery-view-btn active" id="view4col" title="4-column view" onclick="setView(4)">⊞</button>
      <button class="gallery-view-btn" id="view3col" title="3-column view" onclick="setView(3)">⊟</button>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     STATS BAR
     ═══════════════════════════════════════════ -->
<div class="gallery-stats">
  <div class="gallery-stats__inner wrap">
    <span class="gallery-count">
      Showing <strong id="galleryCount"><?php echo $totalPhotos; ?></strong>
      of <strong><?php echo $totalPhotos; ?></strong> photos
    </span>
    <span class="gallery-upload-note">
      Photos are managed through the Admin Panel &mdash;
      click any photo to view full size
    </span>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     GALLERY GRID
     ═══════════════════════════════════════════ -->
<section class="gallery-section">
  <div class="gallery-section__inner wrap">

    <div class="gallery-grid" id="galleryGrid">

      <?php foreach ($photos as $i => $photo): ?>
      <div
        class="gallery-item<?php echo $photo['wide'] ? ' gallery-item--wide' : ''; ?><?php echo $photo['tall'] ? ' gallery-item--tall' : ''; ?> reveal"
        data-cat="<?php echo htmlspecialchars($photo['cat']); ?>"
        data-index="<?php echo $i; ?>"
        onclick="openLightbox(<?php echo $i; ?>)"
        role="button"
        tabindex="0"
        aria-label="View photo: <?php echo htmlspecialchars($photo['title']); ?>"
      >
        <?php if (!empty($photo['img'])): ?>
          <img src="<?php echo BASE_PATH . htmlspecialchars($photo['img']); ?>" alt="<?php echo htmlspecialchars($photo['title']); ?>" loading="lazy"/>
        <?php else: ?>
          <div class="gallery-item__placeholder">
            <?php echo $photo['icon']; ?>
            <span><?php echo htmlspecialchars($photo['title']); ?></span>
          </div>
        <?php endif; ?>

        <div class="gallery-item__overlay">
          <span class="gallery-item__overlay-zoom">🔍</span>
          <div class="gallery-item__overlay-title"><?php echo htmlspecialchars($photo['title']); ?></div>
          <div class="gallery-item__overlay-cat"><?php echo htmlspecialchars($photo['label']); ?></div>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- No results -->
      <div class="gallery-no-results" id="galleryNoResults">
        <p>No photos in this category yet</p>
        <span>Check back soon — new photos are added regularly.</span>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     LIGHTBOX
     ═══════════════════════════════════════════ -->
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer">
  <div class="lightbox__content">

    <button class="lightbox__close" onclick="closeLightbox()" aria-label="Close photo viewer">&#x2715;</button>

    <div id="lightboxMedia">
      <!-- Populated by gallery.js -->
    </div>

    <div class="lightbox__caption">
      <h4 id="lightboxTitle"></h4>
      <span id="lightboxCat"></span>
    </div>

    <span class="lightbox__counter" id="lightboxCounter"></span>
  </div>

  <button class="lightbox__nav lightbox__nav--prev" onclick="lightboxNav(-1)" aria-label="Previous photo">&#8592;</button>
  <button class="lightbox__nav lightbox__nav--next" onclick="lightboxNav(1)"  aria-label="Next photo">&#8594;</button>
</div>


<!-- ═══════════════════════════════════════════
     UPLOAD CTA
     ═══════════════════════════════════════════ -->
<section class="gallery-upload-cta">
  <div class="gallery-upload-cta__inner">
    <span class="slabel slabel--light">School Administration</span>
    <h2>Add Photos to the Gallery</h2>
    <p>School staff with gallery upload permissions can add new photos directly through the Admin Panel. Photos are organised by category and published instantly.</p>
    <a href="<?php echo BASE_PATH; ?>admin/login.php" class="btn btn--primary btn--lg">Go to Admin Panel →</a>
  </div>
</section>


<?php
$pageJs = 'gallery';
require_once '../src/includes/footer.php';
?>