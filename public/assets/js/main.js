/* ============================================================
   IBEKU HIGH SCHOOL — SHARED JAVASCRIPT
   File: public/assets/js/main.js
   ============================================================ */

'use strict';


/* ============================================================
   1. ANNOUNCEMENT BAR
   Controlled from Settings. Dismisses smoothly with
   sessionStorage so it doesn't reappear in the same session.
   ============================================================ */
(function initAnnouncementBar() {
  var bar   = document.getElementById('annBar');
  var close = document.getElementById('annBarClose');
  if (!bar) return;

  /* If already dismissed this session, hide immediately */
  try {
    if (sessionStorage.getItem('ihs_ann_dismissed') === '1') {
      bar.style.display = 'none';
      return;
    }
  } catch (e) {}

  if (close) {
    close.addEventListener('click', function () {
      bar.classList.add('is-hiding');
      setTimeout(function () { bar.style.display = 'none'; }, 300);
      try { sessionStorage.setItem('ihs_ann_dismissed', '1'); } catch (e) {}
    });
  }
}());


/* ============================================================
   2. SITE POPUP — triggers on scroll % OR time, whichever first.
   Independent of the announcement bar.
   Trigger values come from data-scroll-pct and data-delay-seconds
   attributes set in footer.php from Settings.
   ============================================================ */
(function initSitePopup() {
  var popup = document.getElementById('sitePopup');
  var close = document.getElementById('sitePopupClose');
  if (!popup) return;

  var scrollPct  = parseInt(popup.dataset.scrollPct, 10)    || 20;
  var delaySecs  = parseInt(popup.dataset.delaySeconds, 10)  || 5;
  var shown      = false;

  try {
    if (sessionStorage.getItem('ihs_popup_dismissed') === '1') return;
  } catch (e) {}

  function showPopup() {
    if (shown) return;
    shown = true;
    popup.classList.add('is-visible');
  }

  function dismissPopup() {
    popup.classList.remove('is-visible');
    try { sessionStorage.setItem('ihs_popup_dismissed', '1'); } catch (e) {}
    setTimeout(function () { popup.style.display = 'none'; }, 350);
  }

  /* Trigger 1: time on page */
  var timer = setTimeout(showPopup, delaySecs * 1000);

  /* Trigger 2: scroll percentage */
  function onScroll() {
    var scrolled = window.scrollY;
    var height   = document.documentElement.scrollHeight - window.innerHeight;
    var pct      = height > 0 ? (scrolled / height) * 100 : 0;
    if (pct >= scrollPct) {
      showPopup();
      window.removeEventListener('scroll', onScroll);
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });

  if (close) {
    close.addEventListener('click', function () {
      clearTimeout(timer);
      window.removeEventListener('scroll', onScroll);
      dismissPopup();
    });
  }
}());


/* ============================================================
   3. NAVIGATION — burger + mobile dropdowns + desktop click
   ============================================================ */
(function initNav() {
  var burger  = document.getElementById('navBurger');
  var navMenu = document.getElementById('navMenu');
  if (!burger || !navMenu) return;

  /* Mobile burger toggle */
  burger.addEventListener('click', function () {
    burger.classList.toggle('active');
    navMenu.classList.toggle('open');
  });

  var dropParents = navMenu.querySelectorAll('.nav__item--has-drop > .nav__link');

  dropParents.forEach(function (link) {
    link.addEventListener('click', function (e) {
      var item = link.parentElement;

      /* Mobile: tap toggles dropdown, prevents navigation */
      if (window.innerWidth <= 900) {
        e.preventDefault();
        navMenu.querySelectorAll('.nav__item--has-drop.open').forEach(function (el) {
          if (el !== item) el.classList.remove('open');
        });
        item.classList.toggle('open');
        return;
      }

      /* Desktop: first click opens (prevents nav), second click navigates */
      var alreadyOpen = item.classList.contains('open');
      document.querySelectorAll('.nav__item--has-drop.open').forEach(function (el) {
        el.classList.remove('open');
      });
      if (!alreadyOpen) {
        e.preventDefault();
        item.classList.add('open');
      }
      /* If alreadyOpen === true, we don't preventDefault so the link navigates */
    });
  });

  /* Close dropdown when clicking outside on desktop */
  document.addEventListener('click', function (e) {
    if (window.innerWidth > 900 && !e.target.closest('.nav__item--has-drop')) {
      document.querySelectorAll('.nav__item--has-drop.open').forEach(function (el) {
        el.classList.remove('open');
      });
    }
  });

  /* Close mobile nav when any non-parent link is clicked */
  navMenu.querySelectorAll('.nav__link:not(.nav__item--has-drop > .nav__link)').forEach(function (link) {
    link.addEventListener('click', function () {
      navMenu.classList.remove('open');
      burger.classList.remove('active');
    });
  });

  /* Close mobile nav when a dropdown child link is clicked */
  navMenu.querySelectorAll('.nav__dropdown-link').forEach(function (link) {
    link.addEventListener('click', function () {
      navMenu.classList.remove('open');
      burger.classList.remove('active');
    });
  });
}());


/* ============================================================
   4. SCROLL REVEAL
   Elements with .reveal animate in when they enter the viewport.
   ============================================================ */
(function initReveal() {
  var elements = document.querySelectorAll('.reveal');
  if (!elements.length) return;

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('vis');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08 });

  elements.forEach(function (el) { observer.observe(el); });
}());


/* ============================================================
   5. BACK TO TOP BUTTON
   ============================================================ */
(function initBackToTop() {
  var btn = document.getElementById('backToTop');
  if (!btn) return;

  window.addEventListener('scroll', function () {
    btn.classList.toggle('show', window.scrollY > 500);
  }, { passive: true });

  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}());


/* ============================================================
   6. PAGE ANCHOR NAVIGATION
   Highlights the active anchor tab as the user scrolls.
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
      if (section.offsetTop <= scrollY && section.offsetTop + section.offsetHeight > scrollY) {
        anchors.forEach(function (a) { a.classList.remove('active'); });
        anchor.classList.add('active');
      }
    });
  }, { passive: true });
}());


/* ============================================================
   7. STAFF FILTER BUTTONS
   UI filter on About / Academics pages.
   ============================================================ */
(function initStaffFilter() {
  var filterBtns = document.querySelectorAll('.filter-btn');
  if (!filterBtns.length) return;

  filterBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      filterBtns.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');

      var filter = btn.dataset.filter || 'all';
      document.querySelectorAll('.staff-dir-card, .staff-card').forEach(function (card) {
        if (filter === 'all') {
          card.style.display = '';
        } else {
          card.style.display = (card.dataset.filter === filter) ? '' : 'none';
        }
      });
    });
  });
}());