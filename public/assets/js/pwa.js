/* ============================================================
   IBEKU HIGH SCHOOL — PWA INSTALLER & OFFLINE BANNER
   File: public/assets/js/pwa.js

   Handles:
   1. Service worker registration
   2. Install prompt (Android/Chrome/Edge — custom banner)
   3. iOS install nudge (Safari doesn't fire beforeinstallprompt)
   4. Online / offline status banner
   ============================================================ */

'use strict';

/* ── 1. Service Worker Registration ─────────────────────── */
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register('./sw.js')
      .then(reg => {
        console.log('[PWA] SW registered. Scope:', reg.scope);

        // Check for SW updates on each page load
        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing;
          newWorker.addEventListener('statechange', () => {
            if (
              newWorker.state === 'installed' &&
              navigator.serviceWorker.controller
            ) {
              // A new version is available — show subtle update notice
              showUpdateNotice();
            }
          });
        });
      })
      .catch(err => console.warn('[PWA] SW registration failed:', err));
  });
}

/* ── 2. Install Prompt (Chrome/Edge/Android) ─────────────── */
let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredPrompt = e;

  // Respect user's previous dismiss (don't nag again for 7 days)
  const dismissed = localStorage.getItem('pwa-dismiss-ts');
  if (dismissed && Date.now() - parseInt(dismissed, 10) < 7 * 24 * 60 * 60 * 1000) return;

  // Show the install banner after a short delay (less intrusive)
  setTimeout(() => showInstallBanner(), 3000);
});

window.addEventListener('appinstalled', () => {
  hideInstallBanner();
  deferredPrompt = null;
  console.log('[PWA] App installed successfully.');
});

/* ── 3. iOS Install Nudge ────────────────────────────────── */
// iOS Safari doesn't support beforeinstallprompt.
// Detect standalone mode and show a manual instruction instead.
function isIos() {
  return /iphone|ipad|ipod/i.test(navigator.userAgent);
}
function isInStandaloneMode() {
  return window.matchMedia('(display-mode: standalone)').matches ||
         window.navigator.standalone === true;
}

window.addEventListener('DOMContentLoaded', () => {
  if (isIos() && !isInStandaloneMode()) {
    const dismissed = localStorage.getItem('pwa-ios-dismiss-ts');
    if (dismissed && Date.now() - parseInt(dismissed, 10) < 7 * 24 * 60 * 60 * 1000) return;
    setTimeout(() => showIosBanner(), 4000);
  }

  initOfflineBanner();
  injectBannerStyles();
});

/* ── Banner creators ─────────────────────────────────────── */

function showInstallBanner() {
  if (document.getElementById('pwa-install-banner')) return;

  const banner = document.createElement('div');
  banner.id = 'pwa-install-banner';
  banner.innerHTML = `
    <div class="pwa-banner__icon">IHS</div>
    <div class="pwa-banner__text">
      <strong>Install Ibeku High School</strong>
      <span>Add to your home screen for quick access</span>
    </div>
    <button class="pwa-banner__install" id="pwa-install-btn">Install</button>
    <button class="pwa-banner__close" id="pwa-install-dismiss" aria-label="Dismiss">✕</button>
  `;
  document.body.appendChild(banner);

  document.getElementById('pwa-install-btn').addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log('[PWA] Install outcome:', outcome);
    deferredPrompt = null;
    hideInstallBanner();
  });

  document.getElementById('pwa-install-dismiss').addEventListener('click', () => {
    hideInstallBanner();
    localStorage.setItem('pwa-dismiss-ts', Date.now().toString());
  });

  // Animate in
  requestAnimationFrame(() => banner.classList.add('pwa-banner--visible'));
}

function showIosBanner() {
  if (document.getElementById('pwa-ios-banner')) return;

  const banner = document.createElement('div');
  banner.id = 'pwa-ios-banner';
  banner.innerHTML = `
    <div class="pwa-banner__icon">IHS</div>
    <div class="pwa-banner__text">
      <strong>Install on your iPhone</strong>
      <span>Tap <strong>Share</strong> ⬆️ then <strong>"Add to Home Screen"</strong></span>
    </div>
    <button class="pwa-banner__close" id="pwa-ios-dismiss" aria-label="Dismiss">✕</button>
  `;
  document.body.appendChild(banner);

  document.getElementById('pwa-ios-dismiss').addEventListener('click', () => {
    banner.classList.remove('pwa-banner--visible');
    setTimeout(() => banner.remove(), 300);
    localStorage.setItem('pwa-ios-dismiss-ts', Date.now().toString());
  });

  requestAnimationFrame(() => banner.classList.add('pwa-banner--visible'));
}

function hideInstallBanner() {
  const banner = document.getElementById('pwa-install-banner');
  if (!banner) return;
  banner.classList.remove('pwa-banner--visible');
  setTimeout(() => banner.remove(), 300);
}

/* ── 4. Online / Offline Status Banner ───────────────────── */
function initOfflineBanner() {
  const banner = document.createElement('div');
  banner.id = 'pwa-offline-banner';
  banner.innerHTML = `
    <span>📵</span>
    <span>You're offline — showing saved content</span>
  `;
  document.body.appendChild(banner);

  function update() {
    banner.classList.toggle('pwa-offline--visible', !navigator.onLine);
  }

  window.addEventListener('online',  update);
  window.addEventListener('offline', update);
  update();
}

/* ── SW Update Notice ────────────────────────────────────── */
function showUpdateNotice() {
  const notice = document.createElement('div');
  notice.id = 'pwa-update-notice';
  notice.innerHTML = `
    <span>✨ A new version of this site is available.</span>
    <button onclick="window.location.reload()">Refresh</button>
    <button onclick="this.parentElement.remove()" aria-label="Dismiss">✕</button>
  `;
  document.body.appendChild(notice);
  requestAnimationFrame(() => notice.classList.add('pwa-banner--visible'));
}

/* ── Injected Styles ─────────────────────────────────────── */
function injectBannerStyles() {
  if (document.getElementById('pwa-styles')) return;

  const style = document.createElement('style');
  style.id = 'pwa-styles';
  style.textContent = `
    /* ── Base banner ── */
    #pwa-install-banner,
    #pwa-ios-banner {
      position: fixed;
      bottom: -120px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 2rem);
      max-width: 560px;
      background: #3d1a6e;
      color: #fff;
      border-radius: 16px;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 8px 32px rgba(61,26,110,.35);
      z-index: 9999;
      transition: bottom 0.35s cubic-bezier(0.34,1.56,0.64,1);
      font-family: 'DM Sans', sans-serif;
    }

    #pwa-install-banner.pwa-banner--visible,
    #pwa-ios-banner.pwa-banner--visible {
      bottom: 1.25rem;
    }

    .pwa-banner__icon {
      background: rgba(255,255,255,.15);
      border-radius: 10px;
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Playfair Display', serif;
      font-size: 0.8rem;
      font-weight: 900;
      letter-spacing: 1px;
      flex-shrink: 0;
    }

    .pwa-banner__text {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 2px;
      min-width: 0;
    }

    .pwa-banner__text strong {
      font-size: 0.9rem;
      font-weight: 700;
    }

    .pwa-banner__text span {
      font-size: 0.78rem;
      color: rgba(255,255,255,.8);
    }

    .pwa-banner__install {
      background: #e8a020;
      color: #fff;
      border: none;
      padding: 8px 18px;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 700;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      white-space: nowrap;
      flex-shrink: 0;
      transition: background 0.2s;
    }

    .pwa-banner__install:hover { background: #d4911a; }

    .pwa-banner__close {
      background: none;
      border: none;
      color: rgba(255,255,255,.7);
      font-size: 1rem;
      cursor: pointer;
      padding: 4px;
      line-height: 1;
      flex-shrink: 0;
    }

    .pwa-banner__close:hover { color: #fff; }

    /* ── Offline banner ── */
    #pwa-offline-banner {
      position: fixed;
      top: -50px;
      left: 0;
      right: 0;
      background: #1a1a2e;
      color: #fff;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.85rem;
      font-weight: 600;
      padding: 10px 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      z-index: 10000;
      transition: top 0.3s ease;
    }

    #pwa-offline-banner.pwa-offline--visible { top: 0; }

    /* ── Update notice ── */
    #pwa-update-notice {
      position: fixed;
      bottom: -80px;
      left: 50%;
      transform: translateX(-50%);
      background: #4a90d9;
      color: #fff;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.85rem;
      font-weight: 600;
      padding: 10px 16px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
      z-index: 9999;
      box-shadow: 0 4px 20px rgba(74,144,217,.4);
      transition: bottom 0.35s ease;
    }

    #pwa-update-notice.pwa-banner--visible { bottom: 1.25rem; }

    #pwa-update-notice button:first-of-type {
      background: #fff;
      color: #4a90d9;
      border: none;
      padding: 5px 14px;
      border-radius: 6px;
      font-size: 0.82rem;
      font-weight: 700;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
    }

    #pwa-update-notice button:last-of-type {
      background: none;
      border: none;
      color: rgba(255,255,255,.8);
      cursor: pointer;
      font-size: 1rem;
    }

    @media (max-width: 400px) {
      .pwa-banner__install { padding: 8px 12px; }
      .pwa-banner__text strong { font-size: 0.82rem; }
    }
  `;
  document.head.appendChild(style);
}