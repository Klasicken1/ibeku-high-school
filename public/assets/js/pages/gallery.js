/* ============================================================
   IBEKU HIGH SCHOOL — GALLERY PAGE JAVASCRIPT
   File: public/assets/js/pages/gallery.js

   Handles:
     1. Category filter tabs
     2. Grid view toggle (4-col / 3-col)
     3. Lightbox — open, close, navigate, keyboard
   ============================================================ */

'use strict';

/* ── Build photos index from the DOM for lightbox navigation ── */
var GALLERY_ITEMS = [];

(function buildIndex() {
  document.querySelectorAll('.gallery-item').forEach(function (el) {
    var overlay = el.querySelector('.gallery-item__overlay-title');
    var cat     = el.querySelector('.gallery-item__overlay-cat');
    var img     = el.querySelector('img');
    var icon    = el.querySelector('.gallery-item__placeholder');

    GALLERY_ITEMS.push({
      index : parseInt(el.dataset.index, 10),
      title : overlay ? overlay.textContent.trim() : '',
      cat   : cat     ? cat.textContent.trim()     : '',
      src   : img     ? img.src                    : '',
      icon  : icon    ? icon.textContent.trim()     : '🖼️',
      el    : el,
    });
  });
}());

var currentLightboxIndex = 0;
var visibleItems = [];


/* ============================================================
   1. CATEGORY FILTER
   ============================================================ */
(function initFilter() {
  var tabs    = document.querySelectorAll('.gallery-tab');
  var items   = document.querySelectorAll('.gallery-item');
  var noRes   = document.getElementById('galleryNoResults');
  var counter = document.getElementById('galleryCount');

  function applyFilter(cat) {
    visibleItems = [];
    var count = 0;

    items.forEach(function (item) {
      var show = cat === 'all' || item.dataset.cat === cat;
      item.style.display = show ? '' : 'none';
      if (show) {
        count++;
        visibleItems.push(item);
      }
    });

    if (noRes)   noRes.style.display   = count === 0 ? 'block' : 'none';
    if (counter) counter.textContent   = count;
  }

  /* Initialise visible items */
  items.forEach(function (item) { visibleItems.push(item); });

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      applyFilter(tab.dataset.cat);
    });
  });
}());


/* ============================================================
   2. VIEW TOGGLE — 4 column / 3 column
   ============================================================ */
function setView(cols) {
  var grid  = document.getElementById('galleryGrid');
  var btn4  = document.getElementById('view4col');
  var btn3  = document.getElementById('view3col');

  if (!grid) return;

  if (cols === 3) {
    grid.classList.add('gallery-grid--3col');
    if (btn3) btn3.classList.add('active');
    if (btn4) btn4.classList.remove('active');
  } else {
    grid.classList.remove('gallery-grid--3col');
    if (btn4) btn4.classList.add('active');
    if (btn3) btn3.classList.remove('active');
  }
}


/* ============================================================
   3. LIGHTBOX
   ============================================================ */
function openLightbox(index) {
  currentLightboxIndex = index;
  renderLightbox(index);
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.body.style.overflow = '';
}

function renderLightbox(index) {
  var item    = GALLERY_ITEMS[index];
  var media   = document.getElementById('lightboxMedia');
  var title   = document.getElementById('lightboxTitle');
  var cat     = document.getElementById('lightboxCat');
  var counter = document.getElementById('lightboxCounter');

  if (!item || !media) return;

  /* Media — real image or placeholder */
  if (item.src) {
    media.innerHTML = '<img class="lightbox__img" src="' + item.src + '" alt="' + esc(item.title) + '"/>';
  } else {
    media.innerHTML =
      '<div class="lightbox__placeholder">' +
        '<span style="font-size:5rem">' + item.icon + '</span>' +
        '<span style="font-size:13px;color:rgba(255,255,255,.45)">Replace with real school photo</span>' +
      '</div>';
  }

  if (title)   title.textContent   = item.title;
  if (cat)     cat.textContent     = item.cat;
  if (counter) counter.textContent = (index + 1) + ' / ' + GALLERY_ITEMS.length;
}

function lightboxNav(direction) {
  var newIndex = currentLightboxIndex + direction;
  if (newIndex < 0)                     newIndex = GALLERY_ITEMS.length - 1;
  if (newIndex >= GALLERY_ITEMS.length) newIndex = 0;
  currentLightboxIndex = newIndex;
  renderLightbox(newIndex);
}

/* Keyboard navigation */
document.addEventListener('keydown', function (e) {
  var lb = document.getElementById('lightbox');
  if (!lb || !lb.classList.contains('open')) return;

  if (e.key === 'Escape')     closeLightbox();
  if (e.key === 'ArrowLeft')  lightboxNav(-1);
  if (e.key === 'ArrowRight') lightboxNav(1);
});

/* Click outside lightbox content to close */
document.getElementById('lightbox').addEventListener('click', function (e) {
  if (e.target === this) closeLightbox();
});

/* Keyboard activation for gallery items */
document.querySelectorAll('.gallery-item').forEach(function (item) {
  item.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      openLightbox(parseInt(item.dataset.index, 10));
    }
  });
});


/* ============================================================
   HELPER
   ============================================================ */
function esc(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}