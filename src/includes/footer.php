<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SHARED FOOTER
   File: src/includes/footer.php
   ============================================================ */

/* $_site already loaded by header.php on every public page */
if (!isset($_site)) {
    require_once dirname(__DIR__) . '/config/database.php';
    $_site = getSettings();
}
?>

<footer class="footer">
  <div class="footer__inner">

    <!-- Top contact strip — replaces the old topbar -->
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
        <a href="mailto:<?php echo htmlspecialchars($_site['school_email']); ?>"><?php echo htmlspecialchars($_site['school_email']); ?></a>
      </div>
      <div class="footer__contact-item">
        <span class="footer__contact-icon" aria-hidden="true">📞</span>
        <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $_site['school_phone'])); ?>"><?php echo htmlspecialchars($_site['school_phone']); ?></a>
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
          <li><a href="<?php echo BASE_PATH; ?>contact.php">Contact Us</a></li>
        </ul>
      </div>

    </div>

    <div class="footer__bottom">
      <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($_site['school_name']); ?>, Umuahia. All rights reserved.</p>
      <div class="footer__nysc-badge">NYSC CDS Digital Transformation Project &middot; 2025</div>
    </div>

  </div>
</footer>

<!-- ═══════════════════════════════════════════
     INTRUSIVE POPUP NOTIFICATION
     Triggered on 20% scroll OR 5s on page — whichever first.
     Independent of the announcement bar above the nav.
     Controlled from Settings (popup_show / popup_text / popup_link).
     ═══════════════════════════════════════════ -->
<?php if (!empty($_site['popup_show']) && $_site['popup_show'] === '1' && !empty($_site['popup_text'])): ?>
<div class="site-popup" id="sitePopup" role="dialog" aria-modal="false" aria-label="Site announcement">
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

<button class="back-to-top" id="backToTop" aria-label="Back to top">&#8593;</button>

<script src="<?php echo BASE_PATH; ?>assets/js/main.js"></script>
<?php if (!empty($pageJs)): ?>
<script src="<?php echo BASE_PATH; ?>assets/js/pages/<?php echo htmlspecialchars($pageJs); ?>.js"></script>
<?php endif; ?>
</body>
</html>