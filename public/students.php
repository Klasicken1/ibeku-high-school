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

/*
 * PREFECTS DATA
 * Phase 2: replace with database query:
 * SELECT * FROM prefects WHERE session = '2024/2025' ORDER BY role_order ASC
 */
$head_prefects = [
  [
    'name'     => '[Head Boy Name]',
    'role'     => 'Head Boy',
    'section'  => 'ss',
    'session'  => '2024/2025',
    'initials' => 'HB',
    'quote'    => 'It is an honour to serve Ibeku High School. My goal is to be a bridge between the student body and the school administration, ensuring every student\'s voice is heard.',
    'img'      => '',
  ],
  [
    'name'     => '[Head Girl Name]',
    'role'     => 'Head Girl',
    'section'  => 'ss',
    'session'  => '2024/2025',
    'initials' => 'HG',
    'quote'    => 'Leadership is not about authority — it is about service. I am committed to making Ibeku High School a better place for every student during my time in office.',
    'img'      => '',
  ],
];

$other_prefects = [
  ['name'=>'[Name]','role'=>'Assistant Head Boy',    'section'=>'SS','initials'=>'AH','img'=>''],
  ['name'=>'[Name]','role'=>'Assistant Head Girl',   'section'=>'SS','initials'=>'AG','img'=>''],
  ['name'=>'[Name]','role'=>'Senior Prefect — SS',   'section'=>'SS','initials'=>'SP','img'=>''],
  ['name'=>'[Name]','role'=>'Senior Prefect — SS',   'section'=>'SS','initials'=>'SP','img'=>''],
  ['name'=>'[Name]','role'=>'JS Head Boy',           'section'=>'JS','initials'=>'JB','img'=>''],
  ['name'=>'[Name]','role'=>'JS Head Girl',          'section'=>'JS','initials'=>'JG','img'=>''],
  ['name'=>'[Name]','role'=>'Sports Prefect',        'section'=>'SS','initials'=>'SP','img'=>''],
  ['name'=>'[Name]','role'=>'Library Prefect',       'section'=>'SS','initials'=>'LP','img'=>''],
  ['name'=>'[Name]','role'=>'Labour Prefect',        'section'=>'SS','initials'=>'LP','img'=>''],
  ['name'=>'[Name]','role'=>'Social Prefect',        'section'=>'SS','initials'=>'SP','img'=>''],
  ['name'=>'[Name]','role'=>'Health Prefect',        'section'=>'SS','initials'=>'HP','img'=>''],
  ['name'=>'[Name]','role'=>'Chapel Prefect',        'section'=>'SS','initials'=>'CP','img'=>''],
];

/*
 * NOTABLE ALUMNI DATA
 * Phase 2: replace with database query:
 * SELECT * FROM alumni WHERE featured = 1 ORDER BY class_year DESC LIMIT 6
 */
$notable_alumni = [
  [
    'name'     => '[Distinguished Alumnus]',
    'class'    => 'Class of [Year]',
    'field'    => 'Medicine & Public Health',
    'initials' => 'AO',
    'bio'      => 'A distinguished medical professional who rose to prominence in public health across Abia State and Nigeria.',
    'img'      => '',
  ],
  [
    'name'     => '[Distinguished Alumnus]',
    'class'    => 'Class of [Year]',
    'field'    => 'Law & Public Service',
    'initials' => 'CN',
    'bio'      => 'A respected legal practitioner who has held significant positions in the Nigerian judiciary system.',
    'img'      => '',
  ],
  [
    'name'     => '[Distinguished Alumnus]',
    'class'    => 'Class of [Year]',
    'field'    => 'Engineering & Technology',
    'initials' => 'EI',
    'bio'      => 'A pioneering engineer whose infrastructure work has impacted millions across South-East Nigeria.',
    'img'      => '',
  ],
];
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--students">
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
      <p>Meet the student leaders serving Ibeku High School for the 2024/2025 session.</p>
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
     PREFECTS & STUDENT LEADERS
     ═══════════════════════════════════════════ -->
<section class="prefects-section" id="prefects">
  <div class="prefects-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Student Leadership 2024/2025</span>
        <h2 class="stitle">Prefects &amp; <span>Student Leaders</span></h2>
        <p class="ssub">The student prefect body of Ibeku High School represents the highest level of student leadership, bridging the gap between students and the school administration.</p>
      </div>
    </div>

    <!-- Head Boy & Head Girl -->
    <div class="head-prefects-grid">
      <?php foreach ($head_prefects as $hp): ?>
      <div class="head-prefect-card <?php echo $hp['section'] === 'js' ? 'head-prefect-card--js' : ''; ?> reveal">
        <div class="head-prefect-card__photo">
          <?php if (!empty($hp['img'])): ?>
            <img src="<?php echo BASE_PATH . htmlspecialchars($hp['img']); ?>" alt="<?php echo htmlspecialchars($hp['name']); ?>"/>
          <?php else: ?>
            <div class="head-prefect-card__initials"><?php echo htmlspecialchars($hp['initials']); ?></div>
          <?php endif; ?>
        </div>
        <div class="head-prefect-card__body">
          <span class="head-prefect-card__badge badge--<?php echo $hp['section']; ?>">
            <?php echo strtoupper($hp['section']); ?> — <?php echo htmlspecialchars($hp['role']); ?>
          </span>
          <h3><?php echo htmlspecialchars($hp['name']); ?></h3>
          <span class="role"><?php echo htmlspecialchars($hp['role']); ?>, Ibeku High School</span>
          <span class="session">📅 Session: <?php echo htmlspecialchars($hp['session']); ?></span>
          <blockquote>&ldquo;<?php echo htmlspecialchars($hp['quote']); ?>&rdquo;</blockquote>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Other prefects -->
    <h3 class="prefects-sub-title">Other Student Leaders — 2024/2025</h3>
    <div class="prefects-grid">
      <?php foreach ($other_prefects as $p): ?>
      <div class="prefect-card reveal">
        <div class="prefect-card__photo">
          <?php if (!empty($p['img'])): ?>
            <img src="<?php echo BASE_PATH . htmlspecialchars($p['img']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>"/>
          <?php else: ?>
            <div class="prefect-card__initials"><?php echo htmlspecialchars($p['initials']); ?></div>
          <?php endif; ?>
        </div>
        <div class="prefect-card__body">
          <h4><?php echo htmlspecialchars($p['name']); ?></h4>
          <span class="prefect-card__role"><?php echo htmlspecialchars($p['role']); ?></span>
          <span class="prefect-card__section"><?php echo htmlspecialchars($p['section']); ?> Section</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ALUMNI SECTION
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

    <!-- Notable alumni -->
    <div class="section-header reveal">
      <div>
        <span class="slabel">Distinguished Graduates</span>
        <h2 class="stitle">Notable <span>Alumni</span></h2>
      </div>
      <a href="<?php echo BASE_PATH; ?>hall-of-fame.php" class="btn btn--ghost">Full Hall of Fame →</a>
    </div>

    <div class="notable-alumni-grid">
      <?php foreach ($notable_alumni as $alumni): ?>
      <div class="notable-alumni-card reveal">
        <div class="notable-alumni-card__top">
          <div class="notable-alumni-card__photo">
            <?php if (!empty($alumni['img'])): ?>
              <img src="<?php echo BASE_PATH . htmlspecialchars($alumni['img']); ?>" alt="<?php echo htmlspecialchars($alumni['name']); ?>"/>
            <?php else: ?>
              <?php echo htmlspecialchars($alumni['initials']); ?>
            <?php endif; ?>
          </div>
          <h3><?php echo htmlspecialchars($alumni['name']); ?></h3>
          <span class="notable-alumni-card__class"><?php echo htmlspecialchars($alumni['class']); ?></span>
        </div>
        <div class="notable-alumni-card__body">
          <span class="notable-alumni-card__field"><?php echo htmlspecialchars($alumni['field']); ?></span>
          <p><?php echo htmlspecialchars($alumni['bio']); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Alumni register CTA -->
    <div class="alumni-register-cta" id="alumni-register">
      <h3>Are You an IHS Alumnus?</h3>
      <p>Join the official alumni directory. Connect with classmates, support current students, and stay connected to your alma mater.</p>
      <a href="<?php echo BASE_PATH; ?>hall-of-fame.php#nominate" class="btn btn--secondary">Register in the Alumni Directory →</a>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     SCHOLARSHIPS & SUPPORT
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

      <div class="scholarship-card scholarship-card--purple reveal">
        <span class="scholarship-card__icon" aria-hidden="true">🏆</span>
        <h3>OSA Academic Excellence Award</h3>
        <p>Awarded annually by the IHS Old Students Association to the best graduating SSS 3 student. Covers full tuition for the first year of university study.</p>
        <div class="scholarship-card__eligibility">
          <strong>Eligibility</strong>
          <span>SSS 3 students with outstanding WAEC results and exemplary conduct record.</span>
        </div>
        <span class="scholarship-card__contact">Contact: OSA Secretary via school office</span>
      </div>

      <div class="scholarship-card scholarship-card--blue reveal">
        <span class="scholarship-card__icon" aria-hidden="true">🤝</span>
        <h3>Hardship &amp; Welfare Bursary</h3>
        <p>Available to students experiencing financial hardship. Provides partial or full fee waiver for one or more terms, subject to assessment by the Guidance Counsellor.</p>
        <div class="scholarship-card__eligibility">
          <strong>Eligibility</strong>
          <span>Any student in genuine financial difficulty. Applications are confidential.</span>
        </div>
        <span class="scholarship-card__contact">Contact: Guidance Counsellor, SS or JS Section</span>
      </div>

      <div class="scholarship-card scholarship-card--gold reveal">
        <span class="scholarship-card__icon" aria-hidden="true">🌟</span>
        <h3>State Government Scholarship</h3>
        <p>Abia State Government offers merit-based scholarships to outstanding secondary school students. IHS nominates eligible students annually.</p>
        <div class="scholarship-card__eligibility">
          <strong>Eligibility</strong>
          <span>Students with minimum 5 A1 grades in WAEC. Nominated by school management.</span>
        </div>
        <span class="scholarship-card__contact">Contact: VP Academics office</span>
      </div>

      <div class="scholarship-card scholarship-card--purple reveal">
        <span class="scholarship-card__icon" aria-hidden="true">⚽</span>
        <h3>Sports Scholarship</h3>
        <p>Students who represent the school or state in recognised sporting competitions may be eligible for a partial fee reduction as recognition of their contribution.</p>
        <div class="scholarship-card__eligibility">
          <strong>Eligibility</strong>
          <span>Students who have represented IHS at zonal or state level in any sport.</span>
        </div>
        <span class="scholarship-card__contact">Contact: VP General Duties office</span>
      </div>

      <div class="scholarship-card scholarship-card--blue reveal">
        <span class="scholarship-card__icon" aria-hidden="true">📖</span>
        <h3>External Scholarship Listings</h3>
        <p>The school Guidance Counsellors maintain an updated list of external scholarships and grants available to secondary school students in Abia State and Nigeria.</p>
        <div class="scholarship-card__eligibility">
          <strong>Eligibility</strong>
          <span>Varies by scheme. See the Guidance Counsellor for the current list.</span>
        </div>
        <span class="scholarship-card__contact">Contact: Guidance Counsellor, SS or JS Section</span>
      </div>

      <div class="scholarship-card scholarship-card--gold reveal">
        <span class="scholarship-card__icon" aria-hidden="true">💡</span>
        <h3>Science &amp; Technology Award</h3>
        <p>Sponsored by IHS alumni in the technology and engineering sector, this award supports students showing exceptional promise in the sciences and ICT.</p>
        <div class="scholarship-card__eligibility">
          <strong>Eligibility</strong>
          <span>SSS 1 and SSS 2 students in the Science department with outstanding scores.</span>
        </div>
        <span class="scholarship-card__contact">Contact: HOD Sciences or ICT Coordinator</span>
      </div>

    </div>

    <!-- Scholarship note -->
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