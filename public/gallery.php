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
require_once '../src/config/database.php';
$pdo = getDB();

$categoryLabels = [
    'sports'     => 'Sports',
    'events'     => 'Events',
    'classrooms' => 'Classrooms',
    'graduation' => 'Graduation',
    'culture'    => 'Culture',
    'assembly'   => 'Assembly',
    'ict'        => 'ICT',
    'general'    => 'General',
];

$categoryIcons = [
    'sports'     => '⚽',
    'events'     => '🏅',
    'classrooms' => '📚',
    'graduation' => '🎓',
    'culture'    => '🎭',
    'assembly'   => '🏫',
    'ict'        => '💻',
    'general'    => '📷',
];

/* ── Load published photos from DB ── */
$photosFromDB = $pdo->query(
    "SELECT * FROM gallery WHERE is_published = 1
     ORDER BY sort_order ASC, uploaded_at DESC"
)->fetchAll();

/* ── Fallback photos when DB is empty ── */
$defaultPhotos = [
    ['id'=>1,'category'=>'sports',    'title'=>'Science Quiz Championship — Winners',           'filename'=>'','caption'=>''],
    ['id'=>2,'category'=>'assembly',  'title'=>'Morning Assembly — Flag Raising',               'filename'=>'','caption'=>''],
    ['id'=>3,'category'=>'classrooms','title'=>'SSS 2 Science Class in Session',                'filename'=>'','caption'=>''],
    ['id'=>4,'category'=>'ict',       'title'=>'Students in the Newly Refurbished Computer Lab','filename'=>'','caption'=>''],
    ['id'=>5,'category'=>'culture',   'title'=>'Cultural Day — Traditional Attire Display',     'filename'=>'','caption'=>''],
    ['id'=>6,'category'=>'graduation','title'=>'SSS 3 Graduation Ceremony',                     'filename'=>'','caption'=>''],
    ['id'=>7,'category'=>'sports',    'title'=>'Inter-House Sports Day — Track Events',         'filename'=>'','caption'=>''],
    ['id'=>8,'category'=>'events',    'title'=>'Prize-Giving Day',                              'filename'=>'','caption'=>''],
    ['id'=>9,'category'=>'classrooms','title'=>'Biology Practical — Science Laboratory',        'filename'=>'','caption'=>''],
    ['id'=>10,'category'=>'assembly', 'title'=>'End-of-Term Thanksgiving Assembly',             'filename'=>'','caption'=>''],
    ['id'=>11,'category'=>'sports',   'title'=>'Football Team — Zonal Championship Winners',   'filename'=>'','caption'=>''],
    ['id'=>12,'category'=>'culture',  'title'=>'Drama Club Performance — Cultural Day',        'filename'=>'','caption'=>''],
];

$photos      = !empty($photosFromDB) ? $photosFromDB : $defaultPhotos;
$totalPhotos = count($photos);
$isFromDB    = !empty($photosFromDB);
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
      <?php foreach ($categoryLabels as $key => $label): ?>
      <button class="gallery-tab" data-cat="<?php echo $key; ?>"><?php echo $label; ?></button>
      <?php endforeach; ?>
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
     GALLERY GRID — from DB with fallback
     ═══════════════════════════════════════════ -->
<section class="gallery-section">
  <div class="gallery-section__inner wrap">

    <div class="gallery-grid" id="galleryGrid">

      <?php foreach ($photos as $i => $photo): ?>
      <?php
        $cat      = $photo['category'] ?? 'general';
        $title    = $photo['title'] ?? ($photo['caption'] ?? '');
        $filename = $photo['filename'] ?? '';
        $label    = $categoryLabels[$cat] ?? ucfirst($cat);
        $icon     = $categoryIcons[$cat]  ?? '📷';
      ?>
      <div class="gallery-item reveal"
           data-cat="<?php echo htmlspecialchars($cat); ?>"
           data-index="<?php echo $i; ?>"
           onclick="openLightbox(<?php echo $i; ?>)"
           role="button"
           tabindex="0"
           aria-label="View photo: <?php echo htmlspecialchars($title); ?>">

        <?php if ($isFromDB && !empty($filename)): ?>
        <img src="<?php echo BASE_PATH; ?>assets/images/gallery/<?php echo htmlspecialchars($filename); ?>"
             alt="<?php echo htmlspecialchars($title); ?>"
             loading="lazy"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
        <div class="gallery-item__placeholder" style="display:none">
          <?php echo $icon; ?>
          <span><?php echo htmlspecialchars($title); ?></span>
        </div>
        <?php else: ?>
        <div class="gallery-item__placeholder">
          <?php echo $icon; ?>
          <span><?php echo htmlspecialchars($title); ?></span>
        </div>
        <?php endif; ?>

        <div class="gallery-item__overlay">
          <span class="gallery-item__overlay-zoom">🔍</span>
          <div class="gallery-item__overlay-title"><?php echo htmlspecialchars($title); ?></div>
          <div class="gallery-item__overlay-cat"><?php echo htmlspecialchars($label); ?></div>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="gallery-no-results" id="galleryNoResults" style="display:none">
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
    <div id="lightboxMedia"></div>
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
/* Pass gallery data as JSON for the lightbox JS */
$galleryJson = json_encode(array_map(function ($p, $i) use ($categoryLabels, $categoryIcons) {
    $cat      = $p['category'] ?? 'general';
    $filename = $p['filename'] ?? '';
    return [
        'index'    => $i,
        'title'    => $p['title'] ?? ($p['caption'] ?? ''),
        'cat'      => $cat,
        'catLabel' => $categoryLabels[$cat] ?? ucfirst($cat),
        'img'      => $filename ? 'assets/images/gallery/' . $filename : '',
        'icon'     => $categoryIcons[$cat] ?? '📷',
    ];
}, $photos, array_keys($photos)));
?>
<script>
var GALLERY_DATA = <?php echo $galleryJson; ?>;
</script>

<?php
$pageJs = 'gallery';
require_once '../src/includes/footer.php';
?>