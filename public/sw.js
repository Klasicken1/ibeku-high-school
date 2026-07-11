/* ============================================================
   IBEKU HIGH SCHOOL — SERVICE WORKER
   File: public/sw.js

   PRODUCTION NOTE: No manual path changes needed.
   BASE is derived dynamically from the service worker scope,
   so this file works on both localhost and cPanel as-is.

   Caching strategy:
   - Static shell (CSS/JS/icons): cache-first, pre-cached on install
   - Public pages: network-first → cache fallback → offline.php
   - Images: cache-first, cached as visited (not pre-cached)
   - Google Fonts: cache-first
   - Admin & portal: NEVER cached — network only
   ============================================================ */

'use strict';

/* ── Cache names ────────────────────────────────────────── */
const VER          = 'v1';
const STATIC_CACHE = `ihs-static-${VER}`;
const PAGES_CACHE  = `ihs-pages-${VER}`;
const IMAGES_CACHE = `ihs-images-${VER}`;
const ALL_CACHES   = [STATIC_CACHE, PAGES_CACHE, IMAGES_CACHE];

/* ── Derive base path from scope (works on localhost + production) ── */
const BASE = new URL(self.registration.scope).pathname.replace(/\/$/, '');
// localhost → '/ibeku-high-school/public'
// production → ''

/* ── Static shell: pre-cached on install ────────────────── */
const STATIC_SHELL = [
  `${BASE}/offline.php`,
  `${BASE}/assets/css/style.css`,
  `${BASE}/assets/css/pages/home.css`,
  `${BASE}/assets/css/pages/about.css`,
  `${BASE}/assets/css/pages/academics.css`,
  `${BASE}/assets/css/pages/admissions.css`,
  `${BASE}/assets/css/pages/contact.css`,
  `${BASE}/assets/css/pages/events.css`,
  `${BASE}/assets/css/pages/gallery.css`,
  `${BASE}/assets/css/pages/hall-of-fame.css`,
  `${BASE}/assets/css/pages/news.css`,
  `${BASE}/assets/css/pages/results.css`,
  `${BASE}/assets/css/pages/students.css`,
  `${BASE}/assets/js/main.js`,
  `${BASE}/assets/js/pwa.js`,
  `${BASE}/assets/js/pages/home.js`,
  `${BASE}/assets/js/pages/gallery.js`,
  `${BASE}/assets/js/pages/results.js`,
  `${BASE}/assets/images/icons/icon-192.png`,
  `${BASE}/assets/images/icons/icon-512.png`,
  `${BASE}/assets/images/icons/icon.svg`,
];

/* ── INSTALL: pre-cache the static shell ────────────────── */
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then(cache => {
      // Use allSettled so one missing file doesn't abort the whole install
      return Promise.allSettled(
        STATIC_SHELL.map(url =>
          cache.add(url).catch(err =>
            console.warn(`[SW] Pre-cache failed for: ${url}`, err)
          )
        )
      );
    }).then(() => self.skipWaiting())
  );
});

/* ── ACTIVATE: remove old caches, claim clients ─────────── */
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys
          .filter(key => !ALL_CACHES.includes(key))
          .map(key => {
            console.log(`[SW] Deleting old cache: ${key}`);
            return caches.delete(key);
          })
      ))
      .then(() => self.clients.claim())
  );
});

/* ── FETCH: routing logic ───────────────────────────────── */
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Only handle GET requests from our own origin
  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  const path = url.pathname;

  // ── NEVER intercept admin or portal ──────────────────────
  // These require a live DB connection — fail naturally offline
  if (
    path.startsWith(`${BASE}/admin/`) ||
    path.startsWith(`${BASE}/portal/`)
  ) return;

  // ── Google Fonts: cache-first ────────────────────────────
  if (
    url.hostname === 'fonts.googleapis.com' ||
    url.hostname === 'fonts.gstatic.com'
  ) {
    event.respondWith(cacheFirst(request, STATIC_CACHE));
    return;
  }

  // ── Static assets (CSS, JS, fonts, SVG): cache-first ─────
  if (/\.(css|js|woff2?|ttf|otf|svg)$/i.test(path)) {
    event.respondWith(cacheFirst(request, STATIC_CACHE));
    return;
  }

  // ── Images: cache-first, stored in dedicated image cache ─
  if (/\.(png|jpe?g|gif|webp|ico)$/i.test(path)) {
    event.respondWith(cacheFirst(request, IMAGES_CACHE));
    return;
  }

  // ── PDF timetables: cache-first ──────────────────────────
  if (/\.pdf$/i.test(path)) {
    event.respondWith(cacheFirst(request, STATIC_CACHE));
    return;
  }

  // ── Public PHP pages: network-first with offline fallback ─
  if (isPublicPage(path)) {
    event.respondWith(networkFirstWithFallback(request));
    return;
  }
});

/* ── Route helpers ──────────────────────────────────────── */
function isPublicPage(path) {
  if (path === `${BASE}/` || path === `${BASE}/index.php`) return true;
  return (
    path.startsWith(`${BASE}/`) &&
    path.endsWith('.php') &&
    !path.includes('/admin/') &&
    !path.includes('/portal/')
  );
}

/* ── Cache strategies ───────────────────────────────────── */

/**
 * Cache-first: serve from cache if available, otherwise fetch
 * and store. Great for stable assets.
 */
async function cacheFirst(request, cacheName) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('', { status: 408, statusText: 'Network unavailable' });
  }
}

/**
 * Network-first: try the network, cache fresh responses, and
 * fall back to cache or the offline page when the network fails.
 */
async function networkFirstWithFallback(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(PAGES_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    // Network failed — try the page cache first
    const cached = await caches.match(request);
    if (cached) return cached;

    // Nothing cached — serve the offline fallback page
    const offlinePage = await caches.match(`${BASE}/offline.php`);
    if (offlinePage) return offlinePage;

    // Last resort plain text
    return new Response(
      '<h1 style="font-family:sans-serif;padding:2rem">You are offline</h1>' +
      '<p style="font-family:sans-serif;padding:0 2rem">Please check your connection and try again.</p>',
      { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
  }
}