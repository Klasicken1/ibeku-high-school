/* ============================================================
   IBEKU HIGH SCHOOL — SERVICE WORKER
   File: public/sw.js

   Caching strategy:
   - Cache-first:    CSS, JS, fonts, images (static assets)
   - Network-first:  All public PHP pages (fresh content)
   - No cache:       Admin panel, results checker, portal
                     (session-dependent or write-sensitive)
   - Offline page:   Shown when network fails on a page request
   - Push handler:   Receives and displays push notifications
   ============================================================ */

'use strict';

var CACHE_VERSION  = 'ihs-v1';
var STATIC_CACHE   = CACHE_VERSION + '-static';
var PAGES_CACHE    = CACHE_VERSION + '-pages';

/* Static assets to pre-cache on install */
var PRECACHE_ASSETS = [
  '/ibeku-high-school/public/assets/css/style.css',
  '/ibeku-high-school/public/assets/css/admin-layout.css',
  '/ibeku-high-school/public/assets/css/admin-timetables.css',
  '/ibeku-high-school/public/assets/js/main.js',
  '/ibeku-high-school/public/assets/js/admin.js',
  '/ibeku-high-school/public/offline.php',
];

/* Pages to pre-cache on install (network-first in runtime,
   but available offline as stale fallback) */
var PRECACHE_PAGES = [
  '/ibeku-high-school/public/index.php',
  '/ibeku-high-school/public/about.php',
  '/ibeku-high-school/public/academics.php',
  '/ibeku-high-school/public/news.php',
  '/ibeku-high-school/public/contact.php',
];

/* Paths that must NEVER be cached */
var NO_CACHE_PATTERNS = [
  /\/admin\//,
  /\/portal\//,
  /\/results\.php/,
  /\/src\/api\//,
  /\/verify-review\.php/,
  /\.env/,
];


/* ════════════════════════════════════════
   INSTALL — pre-cache static assets
   ════════════════════════════════════════ */
self.addEventListener('install', function (event) {
  event.waitUntil(
    Promise.all([
      caches.open(STATIC_CACHE).then(function (cache) {
        return cache.addAll(PRECACHE_ASSETS).catch(function (err) {
          console.warn('[SW] Pre-cache static failed:', err);
        });
      }),
      caches.open(PAGES_CACHE).then(function (cache) {
        return cache.addAll(PRECACHE_PAGES).catch(function (err) {
          console.warn('[SW] Pre-cache pages failed:', err);
        });
      }),
    ]).then(function () {
      return self.skipWaiting();
    })
  );
});


/* ════════════════════════════════════════
   ACTIVATE — clean up old caches
   ════════════════════════════════════════ */
self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (key) {
          return key.startsWith('ihs-') && key !== STATIC_CACHE && key !== PAGES_CACHE;
        }).map(function (key) {
          console.log('[SW] Deleting old cache:', key);
          return caches.delete(key);
        })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});


/* ════════════════════════════════════════
   FETCH — route requests
   ════════════════════════════════════════ */
self.addEventListener('fetch', function (event) {
  var url = new URL(event.request.url);

  /* Skip non-GET requests */
  if (event.request.method !== 'GET') return;

  /* Skip cross-origin requests */
  if (url.origin !== self.location.origin) return;

  /* Skip no-cache patterns (admin, portal, results, APIs) */
  if (NO_CACHE_PATTERNS.some(function (p) { return p.test(url.pathname); })) {
    return;
  }

  /* Static assets: CSS, JS, fonts, images — cache-first */
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(event.request, STATIC_CACHE));
    return;
  }

  /* PHP pages — network-first, fall back to cache, then offline */
  if (url.pathname.endsWith('.php') || url.pathname.endsWith('/')) {
    event.respondWith(networkFirstPage(event.request));
    return;
  }
});


/* ════════════════════════════════════════
   STRATEGY: Cache-first
   Serves from cache; fetches and updates
   cache if not present.
   ════════════════════════════════════════ */
function cacheFirst(request, cacheName) {
  return caches.open(cacheName).then(function (cache) {
    return cache.match(request).then(function (cached) {
      if (cached) return cached;
      return fetch(request).then(function (response) {
        if (response && response.status === 200) {
          cache.put(request, response.clone());
        }
        return response;
      });
    });
  });
}


/* ════════════════════════════════════════
   STRATEGY: Network-first for pages
   Tries network; caches fresh response;
   falls back to stale cache; then offline.
   ════════════════════════════════════════ */
function networkFirstPage(request) {
  return caches.open(PAGES_CACHE).then(function (cache) {
    return fetch(request).then(function (response) {
      /* Cache a fresh copy of successfully fetched pages */
      if (response && response.status === 200) {
        cache.put(request, response.clone());
      }
      return response;
    }).catch(function () {
      /* Network failed — try stale cache */
      return cache.match(request).then(function (cached) {
        if (cached) return cached;
        /* Nothing in cache — serve offline page */
        return caches.match('/ibeku-high-school/public/offline.php');
      });
    });
  });
}


/* ════════════════════════════════════════
   HELPER: Is this a static asset?
   ════════════════════════════════════════ */
function isStaticAsset(pathname) {
  return /\.(css|js|woff2?|ttf|otf|eot|svg|png|jpg|jpeg|webp|gif|ico)$/i.test(pathname);
}


/* ════════════════════════════════════════
   PUSH — receive and display notification
   ════════════════════════════════════════ */
self.addEventListener('push', function (event) {
  var data = {};

  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data = { title: 'Ibeku High School', body: event.data.text() };
    }
  }

  var title   = data.title   || 'Ibeku High School';
  var options = {
    body:    data.body    || 'You have a new notification from Ibeku High School.',
    icon:    data.icon    || '/ibeku-high-school/public/assets/images/icons/icon-192.png',
    badge:   data.badge   || '/ibeku-high-school/public/assets/images/icons/icon-192.png',
    data:  { url: data.url || '/ibeku-high-school/public/index.php' },
    vibrate: [200, 100, 200],
    requireInteraction: false,
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});


/* ════════════════════════════════════════
   NOTIFICATION CLICK — open the target URL
   ════════════════════════════════════════ */
self.addEventListener('notificationclick', function (event) {
  event.notification.close();

  var targetUrl = (event.notification.data && event.notification.data.url)
    ? event.notification.data.url
    : '/ibeku-high-school/public/index.php';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
      /* If a window is already open, focus it and navigate */
      for (var i = 0; i < windowClients.length; i++) {
        var client = windowClients[i];
        if ('focus' in client) {
          client.focus();
          if ('navigate' in client) client.navigate(targetUrl);
          return;
        }
      }
      /* Otherwise open a new window */
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});


/* ════════════════════════════════════════
   ONLINE / OFFLINE detection message
   Sent to all open clients so main.js
   can show the online/offline banner.
   ════════════════════════════════════════ */
self.addEventListener('message', function (event) {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});