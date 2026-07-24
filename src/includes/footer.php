<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SHARED FOOTER
   File: src/includes/footer.php
   ============================================================ */

if (!isset($_site)) {
    require_once dirname(__DIR__) . '/config/database.php';
    $_site = getSettings();
}

/* Load VAPID public key for push opt-in banner */
$vapidPublicKey = '';
$vapidConfigPath = dirname(__DIR__) . '/config/vapid.php';
if (file_exists($vapidConfigPath)) {
    require_once $vapidConfigPath;
    $vapidPublicKey = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';
}
$showPushBanner = !empty($vapidPublicKey)
    && $vapidPublicKey !== 'REPLACE_WITH_YOUR_PUBLIC_KEY';
?>

<footer class="footer">
  <div class="footer__inner">

    <div class="footer__contact-strip">
      <div class="footer__contact-item">
        <span class="footer__contact-icon" aria-hidden="true">📍</span>
        <span><?php echo htmlspecialchars($_site['school_address']); ?></span>
      </div>
      <div class="footer__contact-item">
        <span class="footer__contact-icon" aria-hidden="true">🕐</span>
        <span><?php echo htmlspecialchars($_site['school_hours'] ?? 'Mon – Fri: 8:00 AM – 3:00 PM'); ?></span>
      </div>
      <div class="footer__contact-item">
        <span class="footer__contact-icon" aria-hidden="true">✉</span>
        <a href="mailto:<?php echo htmlspecialchars($_site['school_email']); ?>">
          <?php echo htmlspecialchars($_site['school_email']); ?>
        </a>
      </div>
      <div class="footer__contact-item">
        <span class="footer__contact-icon" aria-hidden="true">📞</span>
        <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $_site['school_phone'])); ?>">
          <?php echo htmlspecialchars($_site['school_phone']); ?>
        </a>
      </div>
    </div>

    <div class="footer__grid">

      <div class="footer__brand">
        <div style="display:flex;align-items:center;gap:12px;">
          <div class="nav__crest" aria-hidden="true">IHS</div>
          <div>
            <p style="color:#fff;font-size:15px;font-weight:700;font-family:'Playfair Display',serif;margin:0;line-height:1.2;">
              <?php echo htmlspecialchars($_site['school_name']); ?>
            </p>
            <p style="font-size:11px;color:rgba(255,255,255,.38);margin:0;">
              Umuahia, Abia State &middot; Est. 1954
            </p>
          </div>
        </div>
        <p>
          Raising disciplined, excellent, and morally sound young
          Nigerians since 1954. One of the finest government secondary
          schools in South-East Nigeria.
        </p>
        <div class="footer__socials">
          <a class="footer__social" href="#" title="Facebook"  aria-label="Facebook">f</a>
          <a class="footer__social" href="#" title="Twitter/X" aria-label="Twitter">&#x1D417;</a>
          <a class="footer__social" href="#" title="Instagram" aria-label="Instagram">&#128247;</a>
          <a class="footer__social" href="#" title="YouTube"   aria-label="YouTube">&#9654;</a>
        </div>
      </div>

      <div class="footer__col">
        <h4>School</h4>
        <ul>
          <li><a href="<?php echo BASE_PATH; ?>about.php">About the School</a></li>
          <li><a href="<?php echo BASE_PATH; ?>about.php#history">School History</a></li>
          <li><a href="<?php echo BASE_PATH; ?>about.php#principal-ss">SS Principal's Message</a></li>
          <li><a href="<?php echo BASE_PATH; ?>about.php#principal-js">JS Principal's Message</a></li>
          <li><a href="<?php echo BASE_PATH; ?>about.php#anthem">School Anthem</a></li>
          <li><a href="<?php echo BASE_PATH; ?>about.php#facilities">Facilities</a></li>
        </ul>
      </div>

      <div class="footer__col">
        <h4>Academics</h4>
        <ul>
          <li><a href="<?php echo BASE_PATH; ?>academics.php">Departments</a></li>
          <li><a href="<?php echo BASE_PATH; ?>academics.php#subjects">Subjects Offered</a></li>
          <li><a href="<?php echo BASE_PATH; ?>academics.php#timetable">Timetables</a></li>
          <li><a href="<?php echo BASE_PATH; ?>academics.php#clubs">Clubs &amp; Societies</a></li>
          <li><a href="<?php echo BASE_PATH; ?>academics.php#awards">Awards &amp; Honours</a></li>
          <li><a href="<?php echo BASE_PATH; ?>academics.php#resources">Learning Resources</a></li>
        </ul>
      </div>

      <div class="footer__col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="<?php echo BASE_PATH; ?>results.php">Check Results</a></li>
          <li><a href="<?php echo BASE_PATH; ?>admissions.php">Admissions</a></li>
          <li><a href="<?php echo BASE_PATH; ?>news.php">News &amp; Events</a></li>
          <li><a href="<?php echo BASE_PATH; ?>gallery.php">Gallery</a></li>
          <li><a href="<?php echo BASE_PATH; ?>hall-of-fame.php">Hall of Fame</a></li>
          <li><a href="<?php echo BASE_PATH; ?>corps.php">NYSC Corps Members</a></li>
          <li><a href="<?php echo BASE_PATH; ?>contact.php">Contact Us</a></li>
        </ul>
      </div>

    </div>

    <div class="footer__bottom">
      <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($_site['school_name']); ?>, Umuahia. All rights reserved.</p>
      <div class="footer__nysc-badge">NYSC CDS Digital Transformation Project</div>
    </div>

  </div>
</footer>


<!-- ═══════════════════════════════════════════
     INTRUSIVE POPUP NOTIFICATION
     ═══════════════════════════════════════════ -->
<?php if (!empty($_site['popup_show']) && $_site['popup_show'] === '1' && !empty($_site['popup_text'])): ?>
<div class="site-popup" id="sitePopup" role="dialog" aria-modal="false" aria-label="Site announcement"
     data-scroll-pct="<?php echo (int) ($_site['popup_trigger_scroll'] ?? 20); ?>"
     data-delay-seconds="<?php echo (int) ($_site['popup_trigger_seconds'] ?? 5); ?>">
  <div class="site-popup__card">
    <button class="site-popup__close" id="sitePopupClose" type="button" aria-label="Close">&#10005;</button>
    <?php if (!empty($_site['popup_title'])): ?>
    <h3 class="site-popup__title"><?php echo htmlspecialchars($_site['popup_title']); ?></h3>
    <?php endif; ?>
    <p class="site-popup__text"><?php echo nl2br(htmlspecialchars($_site['popup_text'])); ?></p>
    <?php if (!empty($_site['popup_link'])): ?>
    <a href="<?php echo htmlspecialchars($_site['popup_link']); ?>" class="site-popup__cta">
      <?php echo htmlspecialchars($_site['popup_link_text'] ?: 'Learn more →'); ?>
    </a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════
     PUSH NOTIFICATION OPT-IN BANNER
     Shown 8 seconds after page load if:
     - Browser supports push
     - VAPID key is configured
     - User hasn't dismissed this session
     - User not already subscribed
     ═══════════════════════════════════════════ -->
<?php if ($showPushBanner): ?>
<div id="pushOptIn" style="display:none"
     role="dialog" aria-label="Enable push notifications"
     aria-live="polite">
  <div class="push-banner__card">
    <div class="push-banner__icon" aria-hidden="true">🔔</div>
    <div class="push-banner__text">
      <strong>Stay updated</strong>
      <p>Get instant alerts for results, events, and important school notices.</p>
    </div>
    <div class="push-banner__actions">
      <button onclick="ihsSubscribePush()" class="push-banner__yes">
        Yes, notify me
      </button>
      <button onclick="ihsDismissPush()" class="push-banner__no">
        No thanks
      </button>
    </div>
  </div>
</div>

<!-- Success confirmation — fades out after 4s -->
<div id="pushConfirm" style="display:none" aria-live="assertive">
  <div class="push-confirm__card">
    ✅ Notifications enabled! You'll receive school updates directly.
  </div>
</div>

<style>
  /* Push opt-in banner */
  #pushOptIn {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    z-index: 9000; width: calc(100% - 40px); max-width: 520px;
    animation: pushSlideUp .35s ease;
  }
  @keyframes pushSlideUp {
    from { opacity:0; transform:translateX(-50%) translateY(20px); }
    to   { opacity:1; transform:translateX(-50%) translateY(0); }
  }
  .push-banner__card {
    background: #1a0835; border: 1px solid rgba(74,144,217,.3);
    border-radius: 14px; padding: 16px 18px;
    display: flex; align-items: center; gap: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.35);
    flex-wrap: wrap;
  }
  .push-banner__icon { font-size: 26px; flex-shrink: 0; }
  .push-banner__text { flex: 1; min-width: 160px; }
  .push-banner__text strong { display:block; color:#fff; font-size:14px; margin-bottom:3px; }
  .push-banner__text p { color:rgba(255,255,255,.6); font-size:12.5px; margin:0; }
  .push-banner__actions { display:flex; gap:8px; flex-shrink:0; }
  .push-banner__yes {
    background: #4a90d9; color: #fff; border: none;
    padding: 8px 16px; border-radius: 7px; font-size: 13px;
    font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif;
  }
  .push-banner__yes:hover { background: #3a7dc4; }
  .push-banner__no {
    background: rgba(255,255,255,.08); color: rgba(255,255,255,.6);
    border: 1px solid rgba(255,255,255,.15);
    padding: 8px 14px; border-radius: 7px; font-size: 13px;
    cursor: pointer; font-family: 'DM Sans', sans-serif;
  }
  .push-banner__no:hover { background: rgba(255,255,255,.14); }

  /* Push confirm toast */
  #pushConfirm {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    z-index: 9001;
  }
  .push-confirm__card {
    background: #1a7a3a; color: #fff; border-radius: 10px;
    padding: 12px 22px; font-size: 13.5px; font-weight: 600;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
  }
</style>
<?php endif; ?>


<!-- ── Inject VAPID public key for main.js ── -->
<?php if ($showPushBanner): ?>
<script>window.IHS_PUSH_KEY = <?php echo json_encode($vapidPublicKey); ?>;</script>
<?php endif; ?>


<button class="back-to-top" id="backToTop" aria-label="Back to top">&#8593;</button>

<script src="<?php echo BASE_PATH; ?>assets/js/main.js"></script>
<?php if (!empty($pageJs)): ?>
<script src="<?php echo BASE_PATH; ?>assets/js/pages/<?php echo htmlspecialchars($pageJs); ?>.js"></script>
<?php endif; ?>
</body>
</html>