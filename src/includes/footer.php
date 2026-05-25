<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SHARED FOOTER
   File: src/includes/footer.php

   Included at the bottom of every public page.
   Outputs: footer HTML, back-to-top button,
            shared JS, and page-specific JS.

   HOW TO USE ON A PAGE:
   ─────────────────────
   At the very bottom of any page file:

     <?php
       $pageJs = 'home';   // loads assets/js/pages/home.js
                           // omit if page has no specific JS
       require_once '../src/includes/footer.php';
     ?>

   ============================================================ */
?>

<!-- ═══════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════ -->
<footer class="footer">
  <div class="footer__inner">

    <div class="footer__grid">

      <!-- Brand column -->
      <div class="footer__brand">
        <div style="display:flex;align-items:center;gap:12px;">
          <div class="nav__crest" aria-hidden="true">IHS</div>
          <div>
            <p style="color:#fff;font-size:15px;font-weight:700;font-family:'Playfair Display',serif;margin:0;line-height:1.2;">
              Ibeku High School
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
          <!-- UPDATE: Replace # with real social media profile links -->
          <a class="footer__social" href="#" title="Facebook"  aria-label="Facebook">f</a>
          <a class="footer__social" href="#" title="Twitter/X" aria-label="Twitter">&#x1D417;</a>
          <a class="footer__social" href="#" title="Instagram" aria-label="Instagram">&#128247;</a>
          <a class="footer__social" href="#" title="YouTube"   aria-label="YouTube">&#9654;</a>
        </div>
      </div>

      <!-- School column -->
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

      <!-- Academics column -->
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

      <!-- Quick links column -->
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

    <!-- Footer bottom bar -->
    <div class="footer__bottom">
      <p>
        &copy; <?php echo date('Y'); ?> Ibeku High School, Umuahia.
        All rights reserved.
      </p>
      <div class="footer__nysc-badge">
        NYSC CDS Digital Transformation Project &middot; 2025
      </div>
    </div>

  </div>
</footer>


<!-- ═══════════════════════════════════════════
     BACK TO TOP BUTTON
     Shown/hidden by assets/js/main.js
     ═══════════════════════════════════════════ -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
  &#8593;
</button>


<!-- ═══════════════════════════════════════════
     SCRIPTS
     Shared JS first, then page-specific JS.
     ═══════════════════════════════════════════ -->

<!-- Shared JS — runs on every page -->
<script src="<?php echo BASE_PATH; ?>assets/js/main.js"></script>

<!-- Page-specific JS — only loaded when $pageJs is set -->
<?php if (!empty($pageJs)): ?>
  <script src="<?php echo BASE_PATH; ?>assets/js/pages/<?php echo htmlspecialchars($pageJs); ?>.js"></script>
<?php endif; ?>

</body>
</html>