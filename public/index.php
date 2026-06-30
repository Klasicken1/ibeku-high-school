<?php
/* ============================================================
   IBEKU HIGH SCHOOL — HOMEPAGE
   File: public/index.php
   ============================================================ */

$pageTitle   = 'Ibeku High School — Umuahia, Abia State';
$pageDesc    = 'Official website of Ibeku High School, Umuahia. Check results online, apply for admission, read news and stay connected.';
$currentPage = 'home';
$pageCss     = 'home';
$pageJs      = 'home';

require_once '../src/includes/header.php';
require_once '../src/config/database.php';
$pdo = getDB();

/* ── Load settings (already loaded in header via $_site but we
   need $pdo for the DB queries below) ── */

/* ── Staff preview — first 4 by sort order ── */
$staffPreview = $pdo->query(
    "SELECT * FROM staff WHERE is_published = 1
     ORDER BY sort_order ASC, full_name ASC LIMIT 4"
)->fetchAll();

/* ── Approved reviews — latest 3 ── */
$reviews = $pdo->query(
    "SELECT * FROM reviews
     WHERE status = 'approved' AND is_verified = 1
     ORDER BY created_at DESC LIMIT 3"
)->fetchAll();

/* ── YouTube video from settings ── */
$ytId    = $_site['youtube_video_id']    ?? '';
$ytTitle = $_site['youtube_video_title'] ?? 'Ibeku High School — School Life';

$relationshipLabels = [
    'parent'  => 'Parent / Guardian',
    'student' => 'Current Student',
    'alumnus' => 'Alumni',
    'staff'   => 'Staff Member',
    'visitor' => 'Visitor',
];
?>


<!-- ═══════════════════════════════════════════
     HERO CAROUSEL
     Controlled by assets/js/pages/home.js
     ═══════════════════════════════════════════ -->
<section class="hero" id="home" aria-label="School highlights">

  <div class="hero__slide hero__slide--1 active" aria-hidden="false">
    <div class="hero__overlay"></div>
    <div class="hero__dots" aria-hidden="true"></div>
    <div class="hero__content">
      <div class="hero__badge">⭐ Est. 1954 · Government Secondary School · Umuahia</div>
      <h1>Shaping Minds.<br/>Building <em>Character.</em><br/>Raising Leaders.</h1>
      <p>Ibeku High School — where academic excellence and strong values have been forged together for over 70 years in Umuahia, Abia State.</p>
      <div class="hero__btns">
        <a href="<?php echo BASE_PATH; ?>admissions.php" class="btn btn--primary btn--lg">Apply for Admission</a>
        <a href="<?php echo BASE_PATH; ?>results.php"    class="btn btn--outline btn--lg">Check Results Online</a>
      </div>
    </div>
  </div>

  <div class="hero__slide hero__slide--2" aria-hidden="true">
    <div class="hero__overlay"></div>
    <div class="hero__dots" aria-hidden="true"></div>
    <div class="hero__content">
      <div class="hero__badge">📚 Academic Excellence · WAEC · NECO · University Admissions</div>
      <h1>Consistently <em>Outstanding</em><br/>Examination Results.</h1>
      <p>Our students achieve top WAEC and NECO results year after year, securing admission to the best universities in Nigeria and beyond.</p>
      <div class="hero__btns">
        <a href="<?php echo BASE_PATH; ?>academics.php" class="btn btn--primary btn--lg">Explore Academics</a>
        <a href="<?php echo BASE_PATH; ?>about.php"     class="btn btn--outline btn--lg">Learn More About Us</a>
      </div>
    </div>
  </div>

  <div class="hero__slide hero__slide--3" aria-hidden="true">
    <div class="hero__overlay"></div>
    <div class="hero__dots" aria-hidden="true"></div>
    <div class="hero__content">
      <div class="hero__badge">🏆 Sports · Clubs · Competitions · ICT · Culture</div>
      <h1>Life Beyond the <em>Classroom</em><br/>at Ibeku High.</h1>
      <p>15+ active clubs, a modern computer lab, sports teams, cultural events — we develop every dimension of every student.</p>
      <div class="hero__btns">
        <a href="<?php echo BASE_PATH; ?>admissions.php" class="btn btn--primary btn--lg">Join Our School</a>
        <a href="<?php echo BASE_PATH; ?>contact.php"    class="btn btn--outline btn--lg">Contact the School</a>
      </div>
    </div>
  </div>

  <div class="hero__dots-nav" role="tablist" aria-label="Carousel navigation">
    <button class="hero__dot active" data-slide="0" role="tab" aria-selected="true"  aria-label="Slide 1"></button>
    <button class="hero__dot"        data-slide="1" role="tab" aria-selected="false" aria-label="Slide 2"></button>
    <button class="hero__dot"        data-slide="2" role="tab" aria-selected="false" aria-label="Slide 3"></button>
  </div>

</section>


<!-- ═══════════════════════════════════════════
     QUICK LINKS STRIP
     ═══════════════════════════════════════════ -->
<div class="quick-links" role="navigation" aria-label="Quick links">
  <div class="quick-links__grid wrap">
    <a href="<?php echo BASE_PATH; ?>results.php" class="quick-link">
      <div class="quick-link__icon" aria-hidden="true">📊</div>
      <div class="quick-link__text">
        <strong>Check Results</strong>
        <span>Enter your student ID</span>
      </div>
    </a>
    <a href="<?php echo BASE_PATH; ?>admissions.php" class="quick-link">
      <div class="quick-link__icon" aria-hidden="true">📝</div>
      <div class="quick-link__text">
        <strong>Admissions</strong>
        <span>Apply for <?php echo htmlspecialchars($_site['current_session'] ?? '2025/2026'); ?></span>
      </div>
    </a>
    <a href="<?php echo BASE_PATH; ?>news.php" class="quick-link">
      <div class="quick-link__icon" aria-hidden="true">📢</div>
      <div class="quick-link__text">
        <strong>News &amp; Events</strong>
        <span>Latest updates</span>
      </div>
    </a>
    <a href="<?php echo BASE_PATH; ?>contact.php" class="quick-link">
      <div class="quick-link__icon" aria-hidden="true">📞</div>
      <div class="quick-link__text">
        <strong>Contact School</strong>
        <span>Get in touch</span>
      </div>
    </a>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     STATS BAND
     ═══════════════════════════════════════════ -->
<div class="stats-band" aria-label="School statistics">
  <div class="stats-band__grid wrap">
    <div class="stat-item reveal">
      <span class="stat-item__num">70+</span>
      <span class="stat-item__lbl">Years of Excellence</span>
    </div>
    <div class="stat-item reveal">
      <span class="stat-item__num">2,400+</span>
      <span class="stat-item__lbl">Current Students</span>
    </div>
    <div class="stat-item reveal">
      <span class="stat-item__num">120+</span>
      <span class="stat-item__lbl">Dedicated Staff</span>
    </div>
    <div class="stat-item reveal">
      <span class="stat-item__num">15,000+</span>
      <span class="stat-item__lbl">Proud Alumni</span>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     ABOUT THE SCHOOL
     ═══════════════════════════════════════════ -->
<section class="about-section" id="about">
  <div class="about-section__inner wrap">

    <div class="about-section__img-wrap reveal">
      <div class="about-section__img">
        <div class="about-section__img-placeholder">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
          </svg>
          <p>Replace with school building photo</p>
        </div>
      </div>
      <div class="about-section__float">
        <strong>70+</strong>
        <p>Years Serving<br/>Umuahia</p>
      </div>
    </div>

    <div class="about-section__text reveal">
      <span class="slabel">Our Identity</span>
      <h2 class="stitle">About <span><?php echo htmlspecialchars($_site['school_name']); ?></span></h2>
      <p>Ibeku High School, located in Umuahia, Abia State, is one of the oldest and most respected government secondary schools in South-East Nigeria. Founded over seven decades ago, we have produced professionals, leaders, and change-makers across every sector of Nigerian society.</p>
      <p>The school runs both Junior Secondary (JSS 1–3) and Senior Secondary (SSS 1–3) programmes, each led by a dedicated principal, with departments for Sciences, Arts, and Commercial studies.</p>
      <div class="pillars">
        <div class="pillar">
          <h4>Our Vision</h4>
          <p>To be the leading centre of secondary education in South-East Nigeria.</p>
        </div>
        <div class="pillar">
          <h4>Our Mission</h4>
          <p>Raising disciplined, well-rounded graduates who serve society with integrity.</p>
        </div>
        <div class="pillar">
          <h4>Core Values</h4>
          <p><?php echo htmlspecialchars($_site['school_motto'] ?: 'Integrity · Excellence · Discipline · Community · Innovation'); ?>.</p>
        </div>
        <div class="pillar">
          <h4>Academic Arms</h4>
          <p>Sciences · Arts · Commercial — 25+ subjects offered.</p>
        </div>
      </div>
      <a href="<?php echo BASE_PATH; ?>about.php" class="btn btn--secondary">Read Full History</a>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     PRINCIPAL'S MESSAGE — from settings
     ═══════════════════════════════════════════ -->
<section class="principal-section" id="principal-ss">
  <div class="principal-section__inner wrap">

    <div class="principal-section__portrait reveal">
      <div class="principal-section__placeholder">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
        </svg>
        <span>Replace with SS<br/>Principal's photo</span>
      </div>
    </div>

    <div class="principal-section__quote reveal">
      <span class="principal-section__badge">Senior Secondary Principal</span>
      <span class="principal-section__qmark" aria-hidden="true">&ldquo;</span>
      <blockquote>
        <?php echo nl2br(htmlspecialchars(
            $\_site['principal_ss_message'] ?:
            'At Ibeku High School, we do not merely teach subjects — we shape futures. Every student who walks through our gates carries within them the potential to become a leader, a builder, a thinker. Our commitment is to help them discover that potential and develop it to its fullest through academic rigour, strong values, and a community of care that never gives up on any child.'
        )); ?>
      </blockquote>
      <p class="principal-section__sig">
        <strong><?php echo htmlspecialchars($_site['principal_ss_name'] ?: '[SS Principal\'s Full Name]'); ?></strong>
        <span>Principal, Senior Secondary — <?php echo htmlspecialchars($_site['school_name']); ?></span>
      </p>
      <p class="mt-3">
        <a href="<?php echo BASE_PATH; ?>about.php#principal-js" class="btn btn--ghost">
          Read JS Principal's Message →
        </a>
      </p>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     DEPARTMENTS
     ═══════════════════════════════════════════ -->
<section class="departments-section" id="departments">
  <div class="departments-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Academic Structure</span>
        <h2 class="stitle">Explore Our <span>Departments</span></h2>
      </div>
      <a href="<?php echo BASE_PATH; ?>academics.php" class="btn btn--ghost">View All Subjects</a>
    </div>

    <div class="grid-3">
      <div class="dept-card reveal">
        <div class="dept-card__top dept-card__top--sci" aria-hidden="true">🔬</div>
        <div class="dept-card__body">
          <h3>Sciences</h3>
          <p>Preparing students for medicine, engineering, and technology through rigorous lab work and theory.</p>
          <div class="dept-tags">
            <span class="dept-tag">Physics</span>
            <span class="dept-tag">Chemistry</span>
            <span class="dept-tag">Biology</span>
            <span class="dept-tag">Mathematics</span>
            <span class="dept-tag">Further Maths</span>
          </div>
        </div>
      </div>
      <div class="dept-card reveal">
        <div class="dept-card__top dept-card__top--arts" aria-hidden="true">🎭</div>
        <div class="dept-card__body">
          <h3>Arts &amp; Humanities</h3>
          <p>Developing critical thinkers and communicators with strong foundations in language and culture.</p>
          <div class="dept-tags">
            <span class="dept-tag">Literature</span>
            <span class="dept-tag">Government</span>
            <span class="dept-tag">History</span>
            <span class="dept-tag">CRS / IRS</span>
            <span class="dept-tag">Fine Art</span>
          </div>
        </div>
      </div>
      <div class="dept-card reveal">
        <div class="dept-card__top dept-card__top--com" aria-hidden="true">💼</div>
        <div class="dept-card__body">
          <h3>Commercial Studies</h3>
          <p>Building future entrepreneurs through accounting, commerce, and economic reasoning.</p>
          <div class="dept-tags">
            <span class="dept-tag">Accounting</span>
            <span class="dept-tag">Commerce</span>
            <span class="dept-tag">Economics</span>
            <span class="dept-tag">Office Practice</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     BENEFITS / WHY CHOOSE IBEKU
     ═══════════════════════════════════════════ -->
<section class="benefits-section" id="benefits">
  <div class="benefits-section__inner wrap">

    <div class="benefits-grid">
      <div class="benefit-card reveal">
        <span class="benefit-card__icon" aria-hidden="true">📚</span>
        <h3>Strong Academic Record</h3>
        <p>Consistently high WAEC and NECO results with students regularly topping state and national rankings.</p>
      </div>
      <div class="benefit-card reveal">
        <span class="benefit-card__icon" aria-hidden="true">🏆</span>
        <h3>Competition Winners</h3>
        <p>Champions in science olympiads, debates, maths contests, and national quiz competitions.</p>
      </div>
      <div class="benefit-card reveal">
        <span class="benefit-card__icon" aria-hidden="true">💻</span>
        <h3>ICT Integration</h3>
        <p>Functional computer lab, internet access, and ICT classes preparing students for a digital future.</p>
      </div>
      <div class="benefit-card reveal">
        <span class="benefit-card__icon" aria-hidden="true">⚽</span>
        <h3>Sports &amp; Culture</h3>
        <p>Football, basketball, athletics, drama, Red Cross, and over 15 active student societies.</p>
      </div>
    </div>

    <div class="reveal">
      <span class="slabel">Why Choose Ibeku</span>
      <h2 class="stitle">A School That Builds the <span>Whole Child</span></h2>
      <p class="ssub">Excellence here is not only academic. We develop character, leadership, discipline, and lifelong skills — the complete foundation every young person needs.</p>
      <div class="benefits-stats">
        <div class="benefit-stat">
          <div class="benefit-stat__num">98%</div>
          <div class="benefit-stat__text">
            <p>WAEC Pass Rate</p>
            <span>Consistent performance across all departments</span>
          </div>
        </div>
        <div class="benefit-stat">
          <div class="benefit-stat__num">15+</div>
          <div class="benefit-stat__text">
            <p>Clubs &amp; Societies</p>
            <span>Student life that goes far beyond the classroom</span>
          </div>
        </div>
        <div class="benefit-stat">
          <div class="benefit-stat__num">70+</div>
          <div class="benefit-stat__text">
            <p>Years of Service</p>
            <span>Proudly serving Umuahia and Abia State since 1954</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ONLINE RESULT CHECKER
     Logic lives in assets/js/pages/home.js
     ═══════════════════════════════════════════ -->
<section class="results-section" id="results">
  <div class="results-section__inner wrap">

    <div class="results-section__text reveal">
      <span class="slabel slabel--light">Student Portal</span>
      <h2 class="stitle stitle--white">Check Your <span style="color:var(--blue-light)">Results</span> Online</h2>
      <p class="ssub ssub--light">Students can now view their academic results directly on this website. Enter your Admission Number, select your class and term, then click check.</p>
      <ul class="results-section__checklist">
        <li>View results for any examination term</li>
        <li>See individual subject scores and grades</li>
        <li>Check your class position and remarks</li>
        <li>Print or save your result slip</li>
      </ul>
      <p class="mt-3" style="font-size:13px;color:rgba(255,255,255,.4)">
        Demo IDs:&nbsp;
        <strong style="color:var(--blue-light)">IHS/2024/0421</strong>
        &nbsp;or&nbsp;
        <strong style="color:var(--blue-light)">IHS/2024/0105</strong>
      </p>
    </div>

    <div class="checker-card reveal">
      <h3>View My Results</h3>

      <div class="form-group">
        <label class="form-label form-label--light" for="rcId">
          Student ID / Admission Number
        </label>
        <input class="form-input form-input--dark" type="text" id="rcId"
               placeholder="e.g. IHS/2024/0421" autocomplete="off"/>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label form-label--light" for="rcClass">Class</label>
          <select class="form-input form-input--dark" id="rcClass">
            <option value="">Select class</option>
            <option>JSS 1</option><option>JSS 2</option><option>JSS 3</option>
            <option>SSS 1</option><option>SSS 2</option><option>SSS 3</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label form-label--light" for="rcTerm">Term</label>
          <select class="form-input form-input--dark" id="rcTerm">
            <option value="">Select term</option>
            <option>First Term 2024/2025</option>
            <option>Second Term 2024/2025</option>
            <option>Third Term 2023/2024</option>
          </select>
        </div>
      </div>

      <button class="btn btn--check" onclick="checkResult()">
        Check My Results &rarr;
      </button>

      <div class="result-output" id="rcOutput" aria-live="polite">
        <span class="result-output__label" id="rcTermLabel"></span>
        <div class="result-subjects" id="rcSubjects"></div>
        <div class="result-meta"     id="rcMeta"></div>
      </div>

      <p class="result-not-found" id="rcNotFound" role="alert">
        No results found. Please verify your Admission Number or contact the school office.
      </p>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     STAFF PREVIEW — from staff table
     ═══════════════════════════════════════════ -->
<section class="staff-section" id="staff">
  <div class="staff-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Our Team</span>
        <h2 class="stitle">Meet the <span>Staff</span></h2>
      </div>
      <a href="<?php echo BASE_PATH; ?>academics.php#staff" class="btn btn--ghost">Full Staff Directory</a>
    </div>

    <div class="staff-grid">
      <?php if (empty($staffPreview)): ?>
      <!-- Fallback placeholders when DB is empty -->
      <div class="staff-card reveal">
        <div class="staff-card__photo"><div class="staff-card__initials">SP</div></div>
        <div class="staff-card__body">
          <h3><?php echo htmlspecialchars($_site['principal_ss_name'] ?: '[SS Principal]'); ?></h3>
          <p>SS Principal</p><span>Senior Secondary</span>
        </div>
      </div>
      <div class="staff-card reveal">
        <div class="staff-card__photo"><div class="staff-card__initials">JP</div></div>
        <div class="staff-card__body">
          <h3><?php echo htmlspecialchars($_site['principal_js_name'] ?: '[JS Principal]'); ?></h3>
          <p>JS Principal</p><span>Junior Secondary</span>
        </div>
      </div>
      <?php else: ?>
      <?php foreach ($staffPreview as $m): ?>
      <div class="staff-card reveal">
        <div class="staff-card__photo">
          <?php if (!empty($m['photo'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($m['photo']); ?>"
               alt="<?php echo htmlspecialchars($m['full_name']); ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="staff-card__initials" style="display:none">
            <?php echo strtoupper(substr($m['full_name'], 0, 2)); ?>
          </div>
          <?php else: ?>
          <div class="staff-card__initials">
            <?php echo strtoupper(substr($m['full_name'], 0, 2)); ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="staff-card__body">
          <h3><?php echo htmlspecialchars($m['full_name']); ?></h3>
          <p><?php echo htmlspecialchars($m['role']); ?></p>
          <?php if ($m['department']): ?>
          <span><?php echo htmlspecialchars($m['department']); ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     NEWS & ANNOUNCEMENTS
     ═══════════════════════════════════════════ -->
<section class="news-section" id="news">
  <div class="news-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Latest Updates</span>
        <h2 class="stitle">News &amp; <span>Announcements</span></h2>
      </div>
      <a href="<?php echo BASE_PATH; ?>news.php" class="btn btn--ghost">View All News</a>
    </div>

    <div class="news-grid">
      <article class="news-card news-card--featured reveal">
        <div class="news-card__thumb" style="height:200px" aria-hidden="true">🏆</div>
        <div class="news-card__body">
          <span class="news-card__tag news-card__tag--blue">Achievement</span>
          <h3>IHS Students Win Abia State Science Quiz Championship 2024</h3>
          <p>Our SS2 science team took first place — the school's third consecutive state championship title.</p>
          <span class="news-card__date">December 10, 2024</span>
        </div>
      </article>
      <article class="news-card reveal">
        <div class="news-card__thumb" style="height:148px" aria-hidden="true">📋</div>
        <div class="news-card__body">
          <span class="news-card__tag news-card__tag--purple">Academic</span>
          <h3>First Term Examinations Timetable Released</h3>
          <p>The 2024/2025 First Term timetable is now available. Collect from your form teacher.</p>
          <span class="news-card__date">November 28, 2024</span>
        </div>
      </article>
      <article class="news-card reveal">
        <div class="news-card__thumb" style="height:148px" aria-hidden="true">💻</div>
        <div class="news-card__body">
          <span class="news-card__tag news-card__tag--gold">ICT</span>
          <h3>Computer Lab Fully Refurbished by Alumni Donation</h3>
          <p>New desktops and internet access funded by the IHS old students association are now ready.</p>
          <span class="news-card__date">November 15, 2024</span>
        </div>
      </article>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     YOUTUBE VIDEO — from settings
     Only rendered when a video ID is saved
     ═══════════════════════════════════════════ -->
<?php if ($ytId): ?>
<section class="videos-section" id="videos">
  <div class="videos-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Watch &amp; Learn</span>
        <h2 class="stitle">School Life on <span>Video</span></h2>
      </div>
    </div>

    <div class="yt-embed-wrap reveal" style="max-width:720px;margin:0 auto;border-radius:14px;overflow:hidden;box-shadow:0 12px 40px rgba(61,26,110,.2)">
      <div style="position:relative;padding-top:56.25%">
        <iframe
          src="https://www.youtube.com/embed/<?php echo htmlspecialchars($ytId); ?>"
          title="<?php echo htmlspecialchars($ytTitle); ?>"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen loading="lazy"
          style="position:absolute;inset:0;width:100%;height:100%;border:0">
        </iframe>
      </div>
    </div>

    <?php if ($ytTitle): ?>
    <p style="text-align:center;margin-top:16px;font-size:14px;color:var(--muted)">
      <?php echo htmlspecialchars($ytTitle); ?>
    </p>
    <?php endif; ?>

  </div>
</section>
<?php endif; ?>


<!-- ═══════════════════════════════════════════
     TESTIMONIALS — from reviews table
     Falls back to hardcoded if DB is empty
     ═══════════════════════════════════════════ -->
<section class="testimonials-section" id="testimonials">
  <div class="testimonials-section__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel slabel--light">Voices from Our Community</span>
      <h2 class="stitle stitle--white">What They Say About <span style="color:var(--gold)">Ibeku High</span></h2>
    </div>

    <div class="grid-3">

      <?php if (!empty($reviews)): ?>
      <?php foreach ($reviews as $r): ?>
      <div class="testi-card reveal">
        <span class="testi-card__stars" aria-label="<?php echo (int)$r['rating']; ?> stars">
          <?php echo str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']); ?>
        </span>
        <blockquote><?php echo nl2br(htmlspecialchars($r['review_text'])); ?></blockquote>
        <div class="testi-card__author">
          <div class="testi-card__avatar" aria-hidden="true">
            <?php echo strtoupper(substr($r['reviewer_name'], 0, 2)); ?>
          </div>
          <div>
            <strong><?php echo htmlspecialchars($r['reviewer_name']); ?></strong>
            <span><?php echo htmlspecialchars($relationshipLabels[$r['relationship']] ?? $r['relationship']); ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <?php else: ?>
      <!-- Fallback hardcoded testimonials until reviews are submitted and approved -->
      <div class="testi-card reveal">
        <span class="testi-card__stars" aria-label="5 stars">★★★★★</span>
        <blockquote>Ibeku High School gave me the discipline and academic foundation that made me who I am today. The teachers genuinely cared about every student's future.</blockquote>
        <div class="testi-card__author">
          <div class="testi-card__avatar" aria-hidden="true">AO</div>
          <div>
            <strong>Alumni — Class of 2018</strong>
            <span>Now studying Medicine, UNN</span>
          </div>
        </div>
      </div>
      <div class="testi-card reveal">
        <span class="testi-card__stars" aria-label="5 stars">★★★★★</span>
        <blockquote>As a parent, I am confident my children receive not just academics but proper character formation. The school's values are truly evident in everything they do.</blockquote>
        <div class="testi-card__author">
          <div class="testi-card__avatar" aria-hidden="true">CN</div>
          <div>
            <strong>Parent of SS2 Student</strong>
            <span>Umuahia, Abia State</span>
          </div>
        </div>
      </div>
      <div class="testi-card reveal">
        <span class="testi-card__stars" aria-label="5 stars">★★★★★</span>
        <blockquote>The science programme here is exceptional. Our club won the state quiz three years running. The teachers push you to be your absolute best every single day.</blockquote>
        <div class="testi-card__author">
          <div class="testi-card__avatar" aria-hidden="true">EC</div>
          <div>
            <strong>SSS 3 Student</strong>
            <span>Science Department</span>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- Leave a Review CTA -->
    <div style="text-align:center;margin-top:40px" class="reveal">
      <p style="font-size:14px;color:rgba(255,255,255,.6);margin-bottom:14px">
        Studied here? Parent of a current student? Share your experience.
      </p>
      <button onclick="document.getElementById('reviewFormWrap').style.display='block';this.style.display='none';window.scrollTo({top:document.getElementById('reviewFormWrap').getBoundingClientRect().top+window.scrollY-80,behavior:'smooth'})"
              style="background:var(--gold);color:#1a0835;border:none;padding:11px 28px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer">
        Write a Review ★
      </button>
    </div>

    <!-- Review submission form — hidden until button clicked -->
    <div id="reviewFormWrap" style="display:none;margin-top:30px">
      <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:28px 24px;max-width:560px;margin:0 auto">
        <h3 style="color:#fff;font-family:'Playfair Display',serif;font-size:20px;margin-bottom:18px;text-align:center">
          Share Your Experience
        </h3>

        <!-- Honeypot -->
        <input type="text" id="rvWebsite" style="display:none" tabindex="-1" autocomplete="off"/>

        <!-- Star rating -->
        <div style="margin-bottom:16px;text-align:center">
          <div style="font-size:11px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Your Rating *</div>
          <div id="starPicker" style="font-size:28px;cursor:pointer;letter-spacing:4px">
            <span class="rv-star" data-val="1">☆</span>
            <span class="rv-star" data-val="2">☆</span>
            <span class="rv-star" data-val="3">☆</span>
            <span class="rv-star" data-val="4">☆</span>
            <span class="rv-star" data-val="5">☆</span>
          </div>
          <input type="hidden" id="rvRating" value="0"/>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
          <div>
            <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;margin-bottom:5px">Your Name *</label>
            <input class="form-input form-input--dark" type="text" id="rvName" placeholder="Full name"/>
          </div>
          <div>
            <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;margin-bottom:5px">Email *</label>
            <input class="form-input form-input--dark" type="email" id="rvEmail" placeholder="your@email.com"/>
          </div>
        </div>

        <div style="margin-bottom:12px">
          <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;margin-bottom:5px">Your Relationship to the School *</label>
          <select class="form-input form-input--dark" id="rvRelationship">
            <option value="">Select…</option>
            <option value="parent">Parent / Guardian</option>
            <option value="student">Current Student</option>
            <option value="alumnus">Alumni</option>
            <option value="staff">Staff Member</option>
            <option value="visitor">Visitor</option>
          </select>
        </div>

        <div style="margin-bottom:16px">
          <label style="display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;margin-bottom:5px">Your Review *</label>
          <textarea class="form-input form-input--dark" id="rvText" rows="4"
                    placeholder="Share your experience of Ibeku High School…"
                    style="resize:vertical"></textarea>
        </div>

        <button onclick="submitReview()"
                id="rvBtn"
                style="width:100%;background:var(--gold);color:#1a0835;border:none;padding:12px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer">
          Submit Review ★
        </button>

        <div id="rvError"
             style="display:none;margin-top:10px;background:rgba(204,51,51,.2);border:1px solid rgba(204,51,51,.4);border-radius:8px;padding:10px 14px;color:#ffaaaa;font-size:13px">
        </div>

        <div id="rvSuccess"
             style="display:none;margin-top:14px;background:rgba(26,122,58,.2);border:1px solid rgba(26,122,58,.4);border-radius:10px;padding:16px;text-align:center">
          <div style="font-size:24px;margin-bottom:6px">✅</div>
          <strong style="color:#fff;display:block;margin-bottom:6px">Thank you!</strong>
          <p style="color:rgba(255,255,255,.7);font-size:13px;margin-bottom:12px" id="rvSuccessMsg">
            One more step — click the confirmation link below to verify your review.
          </p>
          <a id="rvVerifyLink" href="#" target="_blank" rel="noopener noreferrer"
             style="display:inline-block;background:var(--gold);color:#1a0835;padding:9px 20px;border-radius:7px;font-size:13px;font-weight:700;text-decoration:none">
            ✓ Confirm My Review →
          </a>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ADMISSIONS CTA
     ═══════════════════════════════════════════ -->
<section class="admissions-section" id="admissions">
  <div class="admissions-section__inner wrap">

    <div class="reveal">
      <span class="slabel">Join Our School</span>
      <h2 class="stitle">How to <span>Apply</span></h2>
      <p class="ssub mb-4">We welcome applications for JSS 1 and SSS 1 entry each academic session.</p>

      <div class="admission-steps">
        <div class="admission-step">
          <div class="admission-step__num" aria-hidden="true">1</div>
          <div class="admission-step__text">
            <h4>Submit Enquiry</h4>
            <p>Fill the form or visit the school office to collect an application form and prospectus.</p>
          </div>
        </div>
        <div class="admission-step">
          <div class="admission-step__num" aria-hidden="true">2</div>
          <div class="admission-step__text">
            <h4>Complete Application</h4>
            <p>Return with birth certificate, primary school certificate, and 4 passport photographs.</p>
          </div>
        </div>
        <div class="admission-step">
          <div class="admission-step__num" aria-hidden="true">3</div>
          <div class="admission-step__text">
            <h4>Entrance Assessment</h4>
            <p>Eligible applicants sit a written assessment in English Language and Mathematics.</p>
          </div>
        </div>
        <div class="admission-step">
          <div class="admission-step__num" aria-hidden="true">4</div>
          <div class="admission-step__text">
            <h4>Offer &amp; Enrolment</h4>
            <p>Successful candidates receive an offer letter and complete registration and fee payment.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="admission-form-card reveal">
      <h3>Start Your Application</h3>
      <p>Register your interest and our admissions office will contact you within 48 hours.</p>
      <div class="form-group">
        <input class="form-input" type="text" placeholder="Parent / Guardian Full Name"/>
      </div>
      <div class="form-group">
        <input class="form-input" type="text" placeholder="Student Full Name"/>
      </div>
      <div class="form-group">
        <input class="form-input" type="email" placeholder="Email Address"/>
      </div>
      <div class="form-group">
        <input class="form-input" type="tel" placeholder="Phone Number"/>
      </div>
      <div class="form-group">
        <select class="form-input">
          <option value="">Applying for which class?</option>
          <option>JSS 1 — Junior Secondary</option>
          <option>SSS 1 — Senior Secondary</option>
        </select>
      </div>
      <button class="btn btn--primary btn--full btn--lg"
              onclick="alert('Enquiry received!\nAdmissions will contact you shortly.\n\nPhase 2: connects to PHP email backend.')">
        Submit Enquiry &rarr;
      </button>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     NEWSLETTER
     ═══════════════════════════════════════════ -->
<section class="newsletter-section">
  <div class="newsletter-section__inner">
    <span class="slabel slabel--light">Stay Connected</span>
    <h2>Subscribe to School Updates</h2>
    <p>Get exam timetables, news, events, and important school notices delivered directly to your inbox.</p>
    <div class="newsletter-form">
      <label for="nlEmail" class="sr-only">Email address</label>
      <input class="form-input form-input--dark" type="email" id="nlEmail"
             placeholder="Enter your email address"/>
      <button class="btn btn--primary"
              onclick="alert('Subscribed!\nPhase 2: connects to PHP backend.')">
        Subscribe
      </button>
    </div>
  </div>
</section>


<?php require_once '../src/includes/footer.php'; ?>


<script>
/* ═══════════════════════════════════
   STAR PICKER
   ═══════════════════════════════════ */
(function () {
  var stars  = document.querySelectorAll('.rv-star');
  var hidden = document.getElementById('rvRating');
  if (!stars.length) return;

  function paint(val) {
    stars.forEach(function (s) {
      s.textContent = parseInt(s.dataset.val, 10) <= val ? '★' : '☆';
      s.style.color = parseInt(s.dataset.val, 10) <= val ? '#e8a020' : 'rgba(255,255,255,.3)';
    });
  }

  stars.forEach(function (s) {
    s.addEventListener('mouseover', function () { paint(parseInt(s.dataset.val, 10)); });
    s.addEventListener('mouseout',  function () { paint(parseInt(hidden.value, 10) || 0); });
    s.addEventListener('click',     function () {
      hidden.value = s.dataset.val;
      paint(parseInt(s.dataset.val, 10));
    });
  });
}());


/* ═══════════════════════════════════
   REVIEW FORM SUBMISSION
   ═══════════════════════════════════ */
function submitReview() {
  var name         = document.getElementById('rvName').value.trim();
  var email        = document.getElementById('rvEmail').value.trim();
  var relationship = document.getElementById('rvRelationship').value;
  var rating       = document.getElementById('rvRating').value;
  var text         = document.getElementById('rvText').value.trim();
  var errorEl      = document.getElementById('rvError');
  var successEl    = document.getElementById('rvSuccess');
  var btn          = document.getElementById('rvBtn');

  errorEl.style.display   = 'none';
  successEl.style.display = 'none';

  if (!name || !email || !relationship || !text) {
    errorEl.textContent    = 'Please fill in all required fields.';
    errorEl.style.display  = 'block';
    return;
  }
  if (!rating || rating === '0') {
    errorEl.textContent    = 'Please select a star rating.';
    errorEl.style.display  = 'block';
    return;
  }

  var fd = new FormData();
  fd.append('reviewer_name',  name);
  fd.append('reviewer_email', email);
  fd.append('relationship',   relationship);
  fd.append('rating',         rating);
  fd.append('review_text',    text);
  fd.append('website',        document.getElementById('rvWebsite').value);

  btn.textContent = 'Submitting…';
  btn.disabled    = true;

  fetch('/ibeku-high-school/src/api/submit_review.php', {
    method: 'POST',
    body: fd,
  })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        document.getElementById('rvSuccessMsg').textContent = data.message;
        document.getElementById('rvVerifyLink').href        = data.verify_url;
        btn.style.display      = 'none';
        successEl.style.display = 'block';
      } else if (data.errors) {
        errorEl.textContent   = Object.values(data.errors)[0];
        errorEl.style.display = 'block';
        btn.textContent = 'Submit Review ★';
        btn.disabled    = false;
      } else {
        errorEl.textContent   = data.message || 'Something went wrong. Please try again.';
        errorEl.style.display = 'block';
        btn.textContent = 'Submit Review ★';
        btn.disabled    = false;
      }
    })
    .catch(function (err) {
      console.error('Review error:', err);
      errorEl.textContent   = 'A connection error occurred. Please try again.';
      errorEl.style.display = 'block';
      btn.textContent = 'Submit Review ★';
      btn.disabled    = false;
    });
}
</script>