<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENTS PAGE
   File: public/students.php
   ============================================================ */

$pageTitle   = 'Students — Ibeku High School, Umuahia';
$pageDesc    = 'Student resources at Ibeku High School — check results, meet the prefects, connect with alumni, and discover scholarship opportunities.';
$currentPage = 'students';
$pageCss     = 'students';

require_once '../src/includes/header.php';
require_once '../src/config/database.php';
$pdo = getDB();

/* ── Load settings for current session ── */
$currentSession = $_site['current_session'] ?? '2025/2026';

/* ── Load prefects from DB ── */
$headPrefects = $pdo->prepare(
    "SELECT * FROM prefects
     WHERE session = ? AND is_active = 1
       AND role IN ('Head Boy','Head Girl','JS Head Boy','JS Head Girl')
     ORDER BY sort_order ASC"
);
$headPrefects->execute([$currentSession]);
$headPrefectList = $headPrefects->fetchAll();

$otherPrefects = $pdo->prepare(
    "SELECT * FROM prefects
     WHERE session = ? AND is_active = 1
       AND role NOT IN ('Head Boy','Head Girl','JS Head Boy','JS Head Girl')
     ORDER BY sort_order ASC"
);
$otherPrefects->execute([$currentSession]);
$otherPrefectList = $otherPrefects->fetchAll();

/* ── Load featured alumni from DB ── */
$notableAlumni = $pdo->query(
    "SELECT * FROM alumni
     WHERE is_featured = 1 AND is_published = 1
     ORDER BY sort_order ASC, full_name ASC
     LIMIT 6"
)->fetchAll();

/* ── Load scholarships from DB ── */
$scholarshipsFromDB = $pdo->query(
    "SELECT * FROM scholarships WHERE is_published = 1 ORDER BY sort_order ASC"
)->fetchAll();

/* Fallback scholarships if DB is empty */
$defaultScholarships = [
    ['icon'=>'🏆','title'=>'OSA Academic Excellence Award',  'color_theme'=>'purple','description'=>'Awarded annually by the IHS Old Students Association to the best graduating SSS 3 student. Covers full tuition for the first year of university study.','eligibility'=>'SSS 3 students with outstanding WAEC results and exemplary conduct record.','contact_info'=>'Contact: OSA Secretary via school office'],
    ['icon'=>'🤝','title'=>'Hardship & Welfare Bursary',     'color_theme'=>'blue',  'description'=>'Available to students experiencing financial hardship. Provides partial or full fee waiver for one or more terms, subject to assessment by the Guidance Counsellor.','eligibility'=>'Any student in genuine financial difficulty. Applications are confidential.','contact_info'=>'Contact: Guidance Counsellor, SS or JS Section'],
    ['icon'=>'🌟','title'=>'State Government Scholarship',   'color_theme'=>'gold',  'description'=>'Abia State Government offers merit-based scholarships to outstanding secondary school students. IHS nominates eligible students annually.','eligibility'=>'Students with minimum 5 A1 grades in WAEC. Nominated by school management.','contact_info'=>'Contact: VP Academics office'],
    ['icon'=>'⚽','title'=>'Sports Scholarship',             'color_theme'=>'purple','description'=>'Students who represent the school or state in recognised sporting competitions may be eligible for a partial fee reduction.','eligibility'=>'Students who have represented IHS at zonal or state level in any sport.','contact_info'=>'Contact: VP General Duties office'],
    ['icon'=>'📖','title'=>'External Scholarship Listings',  'color_theme'=>'blue',  'description'=>'The school Guidance Counsellors maintain an updated list of external scholarships and grants available to secondary school students in Abia State and Nigeria.','eligibility'=>'Varies by scheme. See the Guidance Counsellor for the current list.','contact_info'=>'Contact: Guidance Counsellor, SS or JS Section'],
    ['icon'=>'💡','title'=>'Science & Technology Award',     'color_theme'=>'gold',  'description'=>'Sponsored by IHS alumni in the technology and engineering sector, this award supports students showing exceptional promise in the sciences and ICT.','eligibility'=>'SSS 1 and SSS 2 students in the Science department with outstanding scores.','contact_info'=>'Contact: HOD Sciences or ICT Coordinator'],
];
$scholarshipsToShow = !empty($scholarshipsFromDB) ? $scholarshipsFromDB : $defaultScholarships;
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--students<?php echo getInnerHeroImage('students') ? ' page-hero--photo' : ''; ?>"<?php echo renderInnerHeroStyle('students'); ?>>
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">Students</span>
    </nav>
    <h1>Student <em>Resources</em><br/>at Ibeku High</h1>
    <p>Everything for current students, alumni, and prospective families — results, prefects, alumni connections, and scholarship opportunities.</p>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     STUDENT HUB — QUICK LINKS
     ═══════════════════════════════════════════ -->
<div class="student-hub">
  <div class="student-hub__inner wrap">

    <a href="<?php echo BASE_PATH; ?>results.php" class="hub-card">
      <span class="hub-card__icon" aria-hidden="true">📊</span>
      <h3>Check Results</h3>
      <p>Enter your Admission Number to view your term results, grades, and class position.</p>
      <span class="hub-card__arrow">→</span>
    </a>

    <a href="#prefects" class="hub-card">
      <span class="hub-card__icon" aria-hidden="true">🎖️</span>
      <h3>Prefects &amp; Leaders</h3>
      <p>Meet the student leaders serving Ibeku High School for the <?php echo htmlspecialchars($currentSession); ?> session.</p>
      <span class="hub-card__arrow">→</span>
    </a>

    <a href="#alumni" class="hub-card">
      <span class="hub-card__icon" aria-hidden="true">🌍</span>
      <h3>Alumni Network</h3>
      <p>Connect with 15,000+ proud IHS alumni across every profession and industry.</p>
      <span class="hub-card__arrow">→</span>
    </a>

    <a href="#scholarships" class="hub-card">
      <span class="hub-card__icon" aria-hidden="true">🎓</span>
      <h3>Scholarships &amp; Support</h3>
      <p>Discover financial support, bursaries, and scholarship opportunities for students.</p>
      <span class="hub-card__arrow">→</span>
    </a>

  </div>
</div>


<!-- ═══════════════════════════════════════════
     PAGE ANCHOR NAV
     ═══════════════════════════════════════════ -->
<div class="page-anchors">
  <div class="page-anchors__inner wrap">
    <a href="#prefects"     class="page-anchor active">Prefects &amp; Leaders</a>
    <a href="#alumni"       class="page-anchor">Alumni</a>
    <a href="#scholarships" class="page-anchor">Scholarships</a>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     PREFECTS & STUDENT LEADERS — from DB
     ═══════════════════════════════════════════ -->
<section class="prefects-section" id="prefects">
  <div class="prefects-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Student Leadership <?php echo htmlspecialchars($currentSession); ?></span>
        <h2 class="stitle">Prefects &amp; <span>Student Leaders</span></h2>
        <p class="ssub">The student prefect body of Ibeku High School represents the highest level of student leadership, bridging the gap between students and the school administration.</p>
      </div>
    </div>

    <?php if (empty($headPrefectList) && empty($otherPrefectList)): ?>
    <p style="color:#6b6b80;text-align:center;padding:40px 0">
      Prefect profiles for <?php echo htmlspecialchars($currentSession); ?> will appear here once added by the administrator.
    </p>
    <?php else: ?>

    <!-- Head Prefects -->
    <?php if (!empty($headPrefectList)): ?>
    <div class="head-prefects-grid">
      <?php foreach ($headPrefectList as $hp): ?>
      <div class="head-prefect-card <?php echo $hp['section'] === 'js' ? 'head-prefect-card--js' : ''; ?> reveal">
        <div class="head-prefect-card__photo">
          <?php if (!empty($hp['photo'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($hp['photo']); ?>"
               alt="<?php echo htmlspecialchars($hp['full_name']); ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="head-prefect-card__initials" style="display:none">
            <?php echo strtoupper(substr($hp['full_name'], 0, 1)); ?>
          </div>
          <?php else: ?>
          <div class="head-prefect-card__initials">
            <?php echo strtoupper(substr($hp['full_name'], 0, 1)); ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="head-prefect-card__body">
          <span class="head-prefect-card__badge badge--<?php echo htmlspecialchars($hp['section']); ?>">
            <?php echo strtoupper($hp['section']); ?> — <?php echo htmlspecialchars($hp['role']); ?>
          </span>
          <h3><?php echo htmlspecialchars($hp['full_name']); ?></h3>
          <span class="role"><?php echo htmlspecialchars($hp['role']); ?>, Ibeku High School</span>
          <span class="session">📅 Session: <?php echo htmlspecialchars($hp['session']); ?></span>
          <?php if ($hp['quote']): ?>
          <blockquote>&ldquo;<?php echo htmlspecialchars($hp['quote']); ?>&rdquo;</blockquote>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Other Prefects -->
    <?php if (!empty($otherPrefectList)): ?>
    <h3 class="prefects-sub-title">Other Student Leaders — <?php echo htmlspecialchars($currentSession); ?></h3>
    <div class="prefects-grid">
      <?php foreach ($otherPrefectList as $p): ?>
      <div class="prefect-card reveal">
        <div class="prefect-card__photo">
          <?php if (!empty($p['photo'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($p['photo']); ?>"
               alt="<?php echo htmlspecialchars($p['full_name']); ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="prefect-card__initials" style="display:none">
            <?php echo strtoupper(substr($p['full_name'], 0, 1)); ?>
          </div>
          <?php else: ?>
          <div class="prefect-card__initials">
            <?php echo strtoupper(substr($p['full_name'], 0, 1)); ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="prefect-card__body">
          <h4><?php echo htmlspecialchars($p['full_name']); ?></h4>
          <span class="prefect-card__role"><?php echo htmlspecialchars($p['role']); ?></span>
          <span class="prefect-card__section"><?php echo strtoupper($p['section']); ?> Section</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ALUMNI SECTION — featured alumni from DB
     ═══════════════════════════════════════════ -->
<section class="alumni-section" id="alumni">
  <div class="alumni-section__inner wrap">

    <div class="alumni-section__intro">
      <div class="alumni-section__text reveal">
        <span class="slabel">Old Students Association</span>
        <h2 class="stitle">The IHS <span>Alumni Network</span></h2>
        <p>Ibeku High School's alumni community spans over seven decades and reaches into every corner of Nigerian society and beyond. From medicine and law to engineering, public service, and business — IHS alumni are making an impact everywhere.</p>
        <p>The IHS Old Students Association (OSA) is an active body that supports current students through scholarships, mentorship, and facility donations — including the recent computer lab refurbishment.</p>
        <div style="margin-top:24px;display:flex;gap:14px;flex-wrap:wrap">
          <a href="<?php echo BASE_PATH; ?>hall-of-fame.php" class="btn btn--secondary">Visit Hall of Fame</a>
          <a href="#alumni-register" class="btn btn--ghost">Register as Alumni</a>
        </div>
      </div>

      <div class="alumni-stats">
        <div class="alumni-stat reveal">
          <span class="alumni-stat__num">15,000+</span>
          <span class="alumni-stat__lbl">Total Alumni</span>
        </div>
        <div class="alumni-stat reveal">
          <span class="alumni-stat__num">70+</span>
          <span class="alumni-stat__lbl">Years of Graduates</span>
        </div>
        <div class="alumni-stat reveal">
          <span class="alumni-stat__num">36</span>
          <span class="alumni-stat__lbl">States Represented</span>
        </div>
        <div class="alumni-stat reveal">
          <span class="alumni-stat__num">12+</span>
          <span class="alumni-stat__lbl">Countries Worldwide</span>
        </div>
      </div>
    </div>

    <!-- Notable alumni from DB -->
    <div class="section-header reveal">
      <div>
        <span class="slabel">Distinguished Graduates</span>
        <h2 class="stitle">Notable <span>Alumni</span></h2>
      </div>
      <a href="<?php echo BASE_PATH; ?>hall-of-fame.php" class="btn btn--ghost">Full Hall of Fame →</a>
    </div>

    <?php if (empty($notableAlumni)): ?>
    <p style="color:#6b6b80;text-align:center;padding:30px 0">
      Notable alumni profiles will appear here once added by the administrator.
    </p>
    <?php else: ?>
    <div class="notable-alumni-grid">
      <?php foreach ($notableAlumni as $a): ?>
      <div class="notable-alumni-card reveal">
        <div class="notable-alumni-card__top">
          <div class="notable-alumni-card__photo">
            <?php if (!empty($a['photo'])): ?>
            <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($a['photo']); ?>"
                 alt="<?php echo htmlspecialchars($a['full_name']); ?>"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='block'"/>
            <span style="display:none"><?php echo strtoupper(substr($a['full_name'], 0, 2)); ?></span>
            <?php else: ?>
            <?php echo strtoupper(substr($a['full_name'], 0, 2)); ?>
            <?php endif; ?>
          </div>
          <h3><?php echo htmlspecialchars($a['full_name']); ?></h3>
          <?php if ($a['class_year']): ?>
          <span class="notable-alumni-card__class">Class of <?php echo htmlspecialchars($a['class_year']); ?></span>
          <?php endif; ?>
        </div>
        <div class="notable-alumni-card__body">
          <?php if ($a['field']): ?>
          <span class="notable-alumni-card__field"><?php echo htmlspecialchars($a['field']); ?></span>
          <?php endif; ?>
          <?php if ($a['bio']): ?>
          <p><?php echo htmlspecialchars($a['bio']); ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Alumni register CTA -->
    <div class="alumni-register-cta" id="alumni-register">
      <h3>Are You an IHS Alumnus?</h3>
      <p>Join the official alumni directory. Connect with classmates, support current students, and stay connected to your alma mater.</p>
      <a href="<?php echo BASE_PATH; ?>hall-of-fame.php#nominate" class="btn btn--secondary">Register in the Alumni Directory →</a>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     SCHOLARSHIPS & SUPPORT — from DB
     ═══════════════════════════════════════════ -->
<section class="scholarships-section" id="scholarships">
  <div class="scholarships-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Financial Support</span>
        <h2 class="stitle">Scholarships &amp; <span>Bursaries</span></h2>
        <p class="ssub">Ibeku High School is committed to ensuring that financial constraints do not prevent talented students from accessing quality education.</p>
      </div>
    </div>

    <div class="scholarships-grid">
      <?php foreach ($scholarshipsToShow as $sch): ?>
      <div class="scholarship-card scholarship-card--<?php echo htmlspecialchars($sch['color_theme'] ?? 'purple'); ?> reveal">
        <span class="scholarship-card__icon" aria-hidden="true"><?php echo htmlspecialchars($sch['icon'] ?? '🎓'); ?></span>
        <h3><?php echo htmlspecialchars($sch['title']); ?></h3>
        <p><?php echo htmlspecialchars($sch['description']); ?></p>
        <?php if (!empty($sch['eligibility'])): ?>
        <div class="scholarship-card__eligibility">
          <strong>Eligibility</strong>
          <span><?php echo htmlspecialchars($sch['eligibility']); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($sch['contact_info'])): ?>
        <span class="scholarship-card__contact"><?php echo htmlspecialchars($sch['contact_info']); ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="background:var(--blue-pale);border-radius:12px;padding:22px 26px;margin-top:36px;border:1px solid var(--border);" class="reveal">
      <p style="font-size:14px;color:var(--muted);line-height:1.8;margin:0">
        <strong style="color:var(--purple)">Important:</strong>
        All scholarship applications must be submitted through the school office.
        Scholarship availability and amounts are subject to change each academic session.
        Contact the relevant office early — most scholarships have limited places and fixed deadlines.
        The school does not charge any fee for scholarship applications.
      </p>
    </div>

  </div>
</section>


<?php require_once '../src/includes/footer.php'; ?>