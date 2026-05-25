/* ============================================================
   IBEKU HIGH SCHOOL — SHARED JAVASCRIPT
   File: public/assets/js/main.js

   Loaded on every page of the website.
   Handles: nav, announcement bar, scroll reveal, back to top.

   Page-specific scripts live in assets/js/pages/
   ============================================================ */

'use strict';


/* ============================================================
   1. ANNOUNCEMENT BAR
   - Slides open below the nav 1.5s after page load
   - Dismissed with the ✕ button
   - sessionStorage prevents reappearing in the same session
   ============================================================ */
(function initAnnBar() {
  var bar   = document.getElementById('annBar');
  var close = document.getElementById('annBarClose');

  if (!bar || !close) return;

  /* Don't show if already dismissed this session */
  if (sessionStorage.getItem('ihs_ann_dismissed')) return;

  setTimeout(function () {
    bar.classList.add('ann-bar--show');
  }, 1500);

  close.addEventListener('click', function () {
    bar.classList.remove('ann-bar--show');
    sessionStorage.setItem('ihs_ann_dismissed', '1');
  });
}());


/* ============================================================
   2. NAVIGATION — BURGER & MOBILE DROPDOWNS
   ============================================================ */
(function initNav() {
  var burger  = document.getElementById('navBurger');
  var navMenu = document.getElementById('navMenu');

  if (!burger || !navMenu) return;

  /* Toggle the full nav on mobile */
  burger.addEventListener('click', function () {
    burger.classList.toggle('active');
    navMenu.classList.toggle('open');
  });

  /* On mobile (≤900px): tap a dropdown parent to toggle it.
     On desktop: CSS :hover handles opening. */
  var dropParents = navMenu.querySelectorAll('.nav__item--has-drop > .nav__link');

  dropParents.forEach(function (link) {
    link.addEventListener('click', function (e) {
      if (window.innerWidth <= 900) {
        e.preventDefault();

        var item = link.parentElement;

        /* Close any other open dropdowns first */
        navMenu.querySelectorAll('.nav__item--has-drop.open').forEach(function (el) {
          if (el !== item) el.classList.remove('open');
        });

        item.classList.toggle('open');
      }
    });
  });

  /* Close the mobile nav when any non-parent link is clicked */
  navMenu.querySelectorAll('.nav__link:not(.nav__item--has-drop > .nav__link)').forEach(function (link) {
    link.addEventListener('click', function () {
      navMenu.classList.remove('open');
      burger.classList.remove('active');
    });
  });

  /* Also close dropdown links in the mobile expanded menu */
  navMenu.querySelectorAll('.nav__dropdown-link').forEach(function (link) {
    link.addEventListener('click', function () {
      navMenu.classList.remove('open');
      burger.classList.remove('active');
    });
  });
}());


/* ============================================================
   3. SCROLL REVEAL
   Any element with class .reveal animates into view when it
   enters the viewport. Powered by IntersectionObserver.
   ============================================================ */
(function initReveal() {
  var elements = document.querySelectorAll('.reveal');
  if (!elements.length) return;

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('vis');
        /* Stop observing once visible — no need to watch anymore */
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08 });

  elements.forEach(function (el) {
    observer.observe(el);
  });
}());


/* ============================================================
   4. BACK TO TOP BUTTON
   ============================================================ */
(function initBackToTop() {
  var btn = document.getElementById('backToTop');
  if (!btn) return;

  window.addEventListener('scroll', function () {
    if (window.scrollY > 500) {
      btn.classList.add('show');
    } else {
      btn.classList.remove('show');
    }
  }, { passive: true });

  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}());


/* ============================================================
   5. PAGE ANCHOR NAVIGATION
   Highlights the correct anchor tab as the user scrolls.
   Used on About, Hall of Fame, and other long pages.
   ============================================================ */
(function initAnchorNav() {
  var anchors = document.querySelectorAll('.page-anchor');
  if (!anchors.length) return;

  window.addEventListener('scroll', function () {
    var scrollY = window.scrollY + 130;

    anchors.forEach(function (anchor) {
      var targetId = anchor.getAttribute('href');
      if (!targetId || targetId.charAt(0) !== '#') return;

      var section = document.querySelector(targetId);
      if (!section) return;

      if (
        section.offsetTop <= scrollY &&
        section.offsetTop + section.offsetHeight > scrollY
      ) {
        anchors.forEach(function (a) { a.classList.remove('active'); });
        anchor.classList.add('active');
      }
    });
  }, { passive: true });
}());


/* ============================================================
   6. STAFF FILTER BUTTONS
   UI-only filter on About / Staff pages.
   In Phase 2 this will filter actual data from the database.
   ============================================================ */
(function initStaffFilter() {
  var filterBtns = document.querySelectorAll('.filter-btn');
  if (!filterBtns.length) return;

  filterBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      filterBtns.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');

      /* Phase 2: filter staff cards by data-department attribute */
    });
  });
}());