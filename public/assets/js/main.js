/* ============================================================
   IBEKU HIGH SCHOOL — SHARED JAVASCRIPT
   File: public/assets/js/main.js
   ============================================================ */

'use strict';


/* ============================================================
   1. ANNOUNCEMENT BAR
   ============================================================ */
(function initAnnouncementBar() {
  var bar   = document.getElementById('annBar');
  var close = document.getElementById('annBarClose');
  if (!bar) return;

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
   2. SITE POPUP
   ============================================================ */
(function initSitePopup() {
  var popup = document.getElementById('sitePopup');
  var close = document.getElementById('sitePopupClose');
  if (!popup) return;

  var scrollPct = parseInt(popup.dataset.scrollPct, 10)   || 20;
  var delaySecs = parseInt(popup.dataset.delaySeconds, 10) || 5;
  var shown     = false;

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

  var timer = setTimeout(showPopup, delaySecs * 1000);

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
   3. NAVIGATION
   ============================================================ */
(function initNav() {
  var burger  = document.getElementById('navBurger');
  var navMenu = document.getElementById('navMenu');
  if (!burger || !navMenu) return;

  burger.addEventListener('click', function () {
    burger.classList.toggle('active');
    navMenu.classList.toggle('open');
  });

  var dropParents = navMenu.querySelectorAll('.nav__item--has-drop > .nav__link');

  dropParents.forEach(function (link) {
    link.addEventListener('click', function (e) {
      var item = link.parentElement;

      if (window.innerWidth <= 900) {
        e.preventDefault();
        navMenu.querySelectorAll('.nav__item--has-drop.open').forEach(function (el) {
          if (el !== item) el.classList.remove('open');
        });
        item.classList.toggle('open');
        return;
      }

      var alreadyOpen = item.classList.contains('open');
      document.querySelectorAll('.nav__item--has-drop.open').forEach(function (el) {
        el.classList.remove('open');
      });
      if (!alreadyOpen) {
        e.preventDefault();
        item.classList.add('open');
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (window.innerWidth > 900 && !e.target.closest('.nav__item--has-drop')) {
      document.querySelectorAll('.nav__item--has-drop.open').forEach(function (el) {
        el.classList.remove('open');
      });
    }
  });

  navMenu.querySelectorAll('.nav__link:not(.nav__item--has-drop > .nav__link)').forEach(function (link) {
    link.addEventListener('click', function () {
      navMenu.classList.remove('open');
      burger.classList.remove('active');
    });
  });

  navMenu.querySelectorAll('.nav__dropdown-link').forEach(function (link) {
    link.addEventListener('click', function () {
      navMenu.classList.remove('open');
      burger.classList.remove('active');
    });
  });
}());


/* ============================================================
   4. SCROLL REVEAL
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


/* ============================================================
   8. WEB PUSH NOTIFICATIONS
   ============================================================ */
(function initPush() {

  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
  var publicKey = window.IHS_PUSH_KEY;
  if (!publicKey || publicKey === 'REPLACE_WITH_YOUR_PUBLIC_KEY') return;

  if (window.location.pathname.indexOf('/admin/') !== -1) return;

  /* ── Uses window.IHS_BASE set by header.php — works on localhost and production ── */
  navigator.serviceWorker.register(window.IHS_BASE + 'sw.js', { scope: window.IHS_BASE })
    .then(function (reg) {
      window._ihsSWReg = reg;
      return reg.pushManager.getSubscription();
    })
    .then(function (existing) {
      if (existing) return;

      try {
        if (sessionStorage.getItem('ihs_push_dismissed') === '1') return;
      } catch (e) {}

      setTimeout(showPushBanner, 8000);
    })
    .catch(function (err) {
      console.warn('IHS SW registration failed:', err);
    });

  function showPushBanner() {
    var banner = document.getElementById('pushOptIn');
    if (!banner) return;
    banner.style.display = 'flex';
    banner.classList.add('push-banner--visible');
  }

  window.ihsSubscribePush = function () {
    if (!window._ihsSWReg) return;

    Notification.requestPermission().then(function (permission) {
      if (permission !== 'granted') {
        hidePushBanner(true);
        return;
      }

      var appServerKey = urlBase64ToUint8Array(publicKey);

      window._ihsSWReg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: appServerKey,
      })
        .then(function (subscription) {
          /* ── Uses window.IHS_API set by header.php ── */
          return fetch(window.IHS_API + 'push-subscribe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscription.toJSON()),
          });
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            hidePushBanner(true);
            showPushConfirm();
          }
        })
        .catch(function (err) {
          console.warn('IHS push subscribe error:', err);
          hidePushBanner(true);
        });
    });
  };

  window.ihsDismissPush = function () {
    hidePushBanner(true);
  };

  function hidePushBanner(permanent) {
    var banner = document.getElementById('pushOptIn');
    if (banner) banner.style.display = 'none';
    if (permanent) {
      try { sessionStorage.setItem('ihs_push_dismissed', '1'); } catch (e) {}
    }
  }

  function showPushConfirm() {
    var el = document.getElementById('pushConfirm');
    if (!el) return;
    el.style.display = 'flex';
    setTimeout(function () { el.style.display = 'none'; }, 4000);
  }

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(base64);
    var output  = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) {
      output[i] = rawData.charCodeAt(i);
    }
    return output;
  }

}());


/* ============================================================
   9. ONLINE / OFFLINE BANNER
   ============================================================ */
(function initOnlineOfflineBanner() {
  var banner = null;

  function getBanner() {
    if (banner) return banner;
    banner = document.createElement('div');
    banner.id = 'ihs-connection-banner';
    banner.style.cssText = [
      'position:fixed', 'top:0', 'left:0', 'right:0', 'z-index:99999',
      'padding:10px 20px', 'text-align:center', 'font-size:13.5px',
      'font-weight:600', 'font-family:DM Sans,sans-serif',
      'transform:translateY(-100%)', 'transition:transform .3s ease',
    ].join(';');
    document.body.appendChild(banner);
    return banner;
  }

  function showBanner(msg, bg, auto) {
    var b = getBanner();
    b.textContent      = msg;
    b.style.background = bg;
    b.style.color      = '#fff';
    b.style.transform  = 'translateY(0)';
    if (auto) {
      setTimeout(function () { b.style.transform = 'translateY(-100%)'; }, 3500);
    }
  }

  window.addEventListener('offline', function () {
    showBanner('You are offline. Some content may not be available.', '#cc3333', false);
  });

  window.addEventListener('online', function () {
    showBanner('Connection restored.', '#1a7a3a', true);
  });
}());