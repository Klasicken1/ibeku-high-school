<?php
/* ============================================================
   IBEKU HIGH SCHOOL — OFFLINE FALLBACK PAGE
   File: public/offline.php

   Shown by the service worker when the user tries to visit
   a page with no network and no cached version available.
   Self-contained — no header/footer includes needed since
   those require DB/PHP which may also be unreachable offline.
   ============================================================ */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>You're Offline — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: linear-gradient(135deg, #1a0835 0%, #0d1a35 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    color: #fff;
  }

  .card {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 20px;
    padding: 48px 40px;
    max-width: 520px;
    width: 100%;
    text-align: center;
  }

  .icon {
    font-size: 64px;
    margin-bottom: 20px;
    display: block;
    animation: pulse 2s ease-in-out infinite;
  }
  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .6; }
  }

  .school-badge {
    display: inline-flex; align-items: center; gap: 10px;
    margin-bottom: 28px;
  }
  .crest {
    width: 40px; height: 40px; border-radius: 8px;
    background: linear-gradient(135deg, #3d1a6e, #4a90d9);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: #fff;
    font-family: 'Playfair Display', serif;
  }
  .school-name {
    font-size: 14px; color: rgba(255,255,255,.8);
    font-weight: 600; text-align: left; line-height: 1.3;
  }
  .school-name span { display: block; font-size: 11px; color: rgba(255,255,255,.4); font-weight: 400; }

  h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    color: #fff;
    margin-bottom: 14px;
    line-height: 1.2;
  }

  p {
    font-size: 15px;
    color: rgba(255,255,255,.65);
    line-height: 1.7;
    margin-bottom: 28px;
  }

  .cached-note {
    background: rgba(74,144,217,.15);
    border: 1px solid rgba(74,144,217,.3);
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 13px;
    color: rgba(255,255,255,.7);
    margin-bottom: 28px;
    text-align: left;
    line-height: 1.6;
  }
  .cached-note strong { color: #4a90d9; }

  .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

  .btn-retry {
    background: #3d1a6e; color: #fff; border: none;
    padding: 12px 28px; border-radius: 8px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: background .2s;
  }
  .btn-retry:hover { background: #5a2d9e; }

  .btn-home {
    background: rgba(255,255,255,.08); color: rgba(255,255,255,.8);
    border: 1px solid rgba(255,255,255,.15);
    padding: 12px 22px; border-radius: 8px;
    font-size: 14px; font-weight: 600; cursor: pointer;
    font-family: 'DM Sans', sans-serif; text-decoration: none;
    display: inline-block;
    transition: background .2s;
  }
  .btn-home:hover { background: rgba(255,255,255,.14); }

  .status-dot {
    display: inline-block;
    width: 8px; height: 8px; border-radius: 50%;
    background: #cc3333;
    margin-right: 6px;
    vertical-align: middle;
    animation: pulse 1.5s ease-in-out infinite;
  }
  .status-dot.online { background: #1a7a3a; animation: none; }

  .status-bar {
    margin-top: 24px;
    font-size: 12.5px;
    color: rgba(255,255,255,.4);
  }

  .quick-links {
    margin-top: 28px;
    border-top: 1px solid rgba(255,255,255,.08);
    padding-top: 24px;
    display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;
  }
  .quick-links a {
    color: rgba(255,255,255,.5); font-size: 12px; text-decoration: none;
    padding: 4px 10px; border-radius: 20px;
    border: 1px solid rgba(255,255,255,.1);
    transition: all .15s;
  }
  .quick-links a:hover { color: #fff; border-color: rgba(255,255,255,.3); }
</style>
</head>
<body>

  <div class="card">

    <div class="school-badge">
      <div class="crest">IHS</div>
      <div class="school-name">
        Ibeku High School
        <span>Umuahia, Abia State</span>
      </div>
    </div>

    <span class="icon" aria-hidden="true">📶</span>

    <h1>You're Offline</h1>

    <p>
      It looks like you've lost your internet connection.
      Connect to Wi-Fi or mobile data and try again.
    </p>

    <div class="cached-note">
      <strong>💡 Good news:</strong> Some pages you've visited recently may still be available.
      Try navigating to the homepage or another page you've visited before —
      the school website saves copies for offline use.
    </div>

    <div class="actions">
      <button class="btn-retry" onclick="retryPage()">
        🔄 Try Again
      </button>
      <a href="/ibeku-high-school/public/index.php" class="btn-home">
        🏠 Go to Homepage
      </a>
    </div>

    <div class="status-bar" id="statusBar">
      <span class="status-dot" id="statusDot"></span>
      <span id="statusText">No internet connection</span>
    </div>

    <div class="quick-links">
      <a href="/ibeku-high-school/public/about.php">About</a>
      <a href="/ibeku-high-school/public/academics.php">Academics</a>
      <a href="/ibeku-high-school/public/news.php">News</a>
      <a href="/ibeku-high-school/public/contact.php">Contact</a>
    </div>

  </div>

<script>
  /* Auto-retry when connection is restored */
  window.addEventListener('online', function () {
    var dot  = document.getElementById('statusDot');
    var text = document.getElementById('statusText');
    dot.classList.add('online');
    text.textContent = 'Connection restored — reloading…';
    setTimeout(function () { window.location.reload(); }, 1200);
  });

  window.addEventListener('offline', function () {
    var dot  = document.getElementById('statusDot');
    var text = document.getElementById('statusText');
    dot.classList.remove('online');
    text.textContent = 'No internet connection';
  });

  function retryPage() {
    if (navigator.onLine) {
      window.history.back();
    } else {
      window.location.reload();
    }
  }

  /* Check connection status on load */
  if (navigator.onLine) {
    /* We're somehow online but got the offline page —
       probably stale SW. Go back. */
    setTimeout(function () { window.history.back(); }, 500);
  }
</script>

</body>
</html>