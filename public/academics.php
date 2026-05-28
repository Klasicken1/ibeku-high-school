<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ACADEMICS PAGE
   File: public/academics.php
   ============================================================ */

$pageTitle   = 'Academics — Ibeku High School, Umuahia';
$pageDesc    = 'Explore departments, download class timetables, meet our staff, and discover clubs, competitions, and learning resources at Ibeku High School.';
$currentPage = 'academics';
$pageCss     = 'academics';

require_once '../src/includes/header.php';
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--academics">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">Academics</span>
    </nav>
    <h1>Academic <em>Excellence</em><br/>at Ibeku High</h1>
    <p>Three departments, 25+ subjects, downloadable timetables for all classes, and a vibrant co-curricular programme — everything you need to know about learning at Ibeku High School.</p>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     PAGE ANCHOR NAV
     ═══════════════════════════════════════════ -->
<div class="page-anchors">
  <div class="page-anchors__inner wrap">
    <a href="#departments" class="page-anchor active">Departments</a>
    <a href="#timetable"   class="page-anchor">Timetables</a>
    <a href="#staff"       class="page-anchor">Staff Directory</a>
    <a href="#clubs"       class="page-anchor">Clubs &amp; Societies</a>
    <a href="#awards"      class="page-anchor">Competitions &amp; Awards</a>
    <a href="#resources"   class="page-anchor">Learning Resources</a>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     DEPARTMENTS & SUBJECTS
     ═══════════════════════════════════════════ -->
<section class="departments-section" id="departments">
  <div class="departments-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Academic Structure</span>
        <h2 class="stitle">Departments &amp; <span>Subjects</span></h2>
        <p class="ssub">Ibeku High School offers a broad curriculum across three academic departments, covering over 25 subjects at both Junior and Senior Secondary levels.</p>
      </div>
    </div>

    <!-- Sciences -->
    <div class="dept-full-card reveal" id="dept-sciences">
      <div class="dept-full-card__side dept-full-card__side--sci">
        <span class="dept-full-card__icon" aria-hidden="true">🔬</span>
        <h3>Sciences</h3>
      </div>
      <div class="dept-full-card__body">
        <p>The Science department prepares students for careers in medicine, engineering, technology, and the natural sciences. Practical laboratory sessions are conducted regularly for SSS students, reinforcing theory with hands-on experimentation.</p>
        <div class="dept-subjects">
          <span class="dept-subject">Mathematics</span>
          <span class="dept-subject">Further Mathematics</span>
          <span class="dept-subject">Physics</span>
          <span class="dept-subject">Chemistry</span>
          <span class="dept-subject">Biology</span>
          <span class="dept-subject">Agricultural Science</span>
          <span class="dept-subject">Computer Studies</span>
          <span class="dept-subject dept-subject--elective">Technical Drawing (elective)</span>
          <span class="dept-subject dept-subject--elective">Food &amp; Nutrition (elective)</span>
        </div>
      </div>
    </div>

    <!-- Arts -->
    <div class="dept-full-card reveal" id="dept-arts">
      <div class="dept-full-card__side dept-full-card__side--arts">
        <span class="dept-full-card__icon" aria-hidden="true">🎭</span>
        <h3>Arts &amp; Humanities</h3>
      </div>
      <div class="dept-full-card__body">
        <p>The Arts department develops critical thinkers, communicators, and creative minds. Students build strong foundations in language, history, civic responsibility, and cultural expression — essential skills for law, journalism, education, and public service.</p>
        <div class="dept-subjects">
          <span class="dept-subject">English Language</span>
          <span class="dept-subject">Literature in English</span>
          <span class="dept-subject">Government</span>
          <span class="dept-subject">History</span>
          <span class="dept-subject">Christian Religious Studies</span>
          <span class="dept-subject">Islamic Religious Studies</span>
          <span class="dept-subject">Fine Art</span>
          <span class="dept-subject">French</span>
          <span class="dept-subject dept-subject--elective">Igbo Language (elective)</span>
          <span class="dept-subject dept-subject--elective">Music (elective)</span>
        </div>
      </div>
    </div>

    <!-- Commercial -->
    <div class="dept-full-card reveal" id="dept-commercial">
      <div class="dept-full-card__side dept-full-card__side--com">
        <span class="dept-full-card__icon" aria-hidden="true">💼</span>
        <h3>Commercial Studies</h3>
      </div>
      <div class="dept-full-card__body">
        <p>The Commercial department produces business-minded graduates ready for entrepreneurship, accounting, and corporate careers. Students develop analytical and financial reasoning through a rigorous commercial curriculum.</p>
        <div class="dept-subjects">
          <span class="dept-subject">Economics</span>
          <span class="dept-subject">Accounting</span>
          <span class="dept-subject">Commerce</span>
          <span class="dept-subject">Office Practice</span>
          <span class="dept-subject">Business Studies</span>
          <span class="dept-subject dept-subject--elective">Store Management (elective)</span>
          <span class="dept-subject dept-subject--elective">Insurance (elective)</span>
        </div>
      </div>
    </div>

    <!-- General Studies — JSS -->
    <div class="dept-full-card reveal" id="dept-general">
      <div class="dept-full-card__side dept-full-card__side--gen">
        <span class="dept-full-card__icon" aria-hidden="true">📖</span>
        <h3>General Studies (JSS)</h3>
      </div>
      <div class="dept-full-card__body">
        <p>All Junior Secondary students follow a broad General Studies curriculum before choosing a department for Senior Secondary. This foundation covers core subjects across all disciplines, ensuring every student discovers their strengths before specialisation.</p>
        <div class="dept-subjects">
          <span class="dept-subject">English Language</span>
          <span class="dept-subject">Mathematics</span>
          <span class="dept-subject">Basic Science</span>
          <span class="dept-subject">Basic Technology</span>
          <span class="dept-subject">Social Studies</span>
          <span class="dept-subject">Civic Education</span>
          <span class="dept-subject">Business Studies</span>
          <span class="dept-subject">Cultural &amp; Creative Arts</span>
          <span class="dept-subject">Computer Studies</span>
          <span class="dept-subject">Physical Education</span>
          <span class="dept-subject">French</span>
          <span class="dept-subject dept-subject--elective">Igbo Language (elective)</span>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     TIMETABLES
     ═══════════════════════════════════════════ -->
<section class="timetable-section" id="timetable">
  <div class="timetable-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Class Schedules</span>
        <h2 class="stitle">Download <span>Timetables</span></h2>
        <p class="ssub">Current timetables for all six class levels are available for download. Timetables are updated each term by the Dean of Studies.</p>
      </div>
    </div>

    <div class="timetable-grid">

      <?php
      /*
       * TIMETABLE DATA
       * Phase 3: the Dean of Studies uploads PDFs via the admin panel.
       * The filename convention never changes — only the file content is replaced.
       * File path: public/assets/timetables/timetable-{level}.pdf
       */
      $timetables = [
        [
          'level'   => 'JSS 1',
          'file'    => 'timetable-jss1.pdf',
          'section' => 'jss',
          'term'    => '2024/2025 Academic Session',
          'desc'    => 'Full week schedule for JSS 1 students covering all 8 periods per day.',
        ],
        [
          'level'   => 'JSS 2',
          'file'    => 'timetable-jss2.pdf',
          'section' => 'jss',
          'term'    => '2024/2025 Academic Session',
          'desc'    => 'Full week schedule for JSS 2 students covering all 8 periods per day.',
        ],
        [
          'level'   => 'JSS 3',
          'file'    => 'timetable-jss3.pdf',
          'section' => 'jss',
          'term'    => '2024/2025 Academic Session',
          'desc'    => 'Full week schedule for JSS 3 students covering all 8 periods per day.',
        ],
        [
          'level'   => 'SSS 1',
          'file'    => 'timetable-sss1.pdf',
          'section' => 'sss',
          'term'    => '2024/2025 Academic Session',
          'desc'    => 'Full week schedule for SSS 1 students across all three departments.',
        ],
        [
          'level'   => 'SSS 2',
          'file'    => 'timetable-sss2.pdf',
          'section' => 'sss',
          'term'    => '2024/2025 Academic Session',
          'desc'    => 'Full week schedule for SSS 2 students across all three departments.',
        ],
        [
          'level'   => 'SSS 3',
          'file'    => 'timetable-sss3.pdf',
          'section' => 'sss',
          'term'    => '2024/2025 Academic Session',
          'desc'    => 'Full week schedule for SSS 3 students across all three departments.',
        ],
      ];

      foreach ($timetables as $tt):
        $isJss      = $tt['section'] === 'jss';
        $headerClass = $isJss ? 'timetable-card__header--jss' : 'timetable-card__header--sss';
        $btnClass    = $isJss ? '' : 'btn--download--purple';
        $filePath    = BASE_PATH . 'assets/timetables/' . $tt['file'];
        $fileExists  = file_exists(__DIR__ . '/assets/timetables/' . $tt['file']);
      ?>
      <div class="timetable-card reveal">
        <div class="timetable-card__header <?php echo $headerClass; ?>">
          <span class="timetable-card__level"><?php echo htmlspecialchars($tt['level']); ?></span>
          <span class="timetable-card__section-label">
            <?php echo $isJss ? 'Junior Secondary' : 'Senior Secondary'; ?>
          </span>
        </div>
        <div class="timetable-card__body">
          <p class="timetable-card__meta">
            <?php echo htmlspecialchars($tt['desc']); ?><br/>
            <strong style="color:var(--purple)"><?php echo htmlspecialchars($tt['term']); ?></strong>
          </p>
          <?php if ($fileExists): ?>
          <div class="timetable-card__status timetable-card__status--available">
            <span class="timetable-card__dot"></span>
            Current timetable available
          </div>
          
            href="<?php echo htmlspecialchars($filePath); ?>"
            class="btn--download <?php echo $btnClass; ?>"
            download="<?php echo htmlspecialchars($tt['level']); ?>-Timetable-IHS.pdf"
            target="_blank"
            rel="noopener noreferrer"
          >
            ⬇ Download <?php echo htmlspecialchars($tt['level']); ?> Timetable (PDF)
          </a>
          <?php else: ?>
          <div class="timetable-card__status timetable-card__status--pending">
            <span class="timetable-card__dot"></span>
            Timetable pending upload
          </div>
          <a href="<?php echo BASE_PATH; ?>contact.php" class="btn--download" style="background:var(--muted)">
            Contact School Office
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

    </div>

    <!-- Admin note -->
    <div class="timetable-admin-note reveal">
      <span class="timetable-admin-note__icon" aria-hidden="true">ℹ️</span>
      <div class="timetable-admin-note__text">
        <p>
          <strong>For school administrators:</strong> Timetables are managed through the
          <a href="<?php echo BASE_PATH; ?>admin/login.php" style="color:var(--blue-dark);font-weight:600">Admin Panel</a>.
          The Dean of Studies (JS) updates JSS timetables and the Dean of Studies (SS) updates SSS timetables.
          Uploaded files replace the current version immediately — no technical knowledge required.
        </p>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     STAFF DIRECTORY
     ═══════════════════════════════════════════ -->
<section class="staff-section" id="staff">
  <div class="staff-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Our Team</span>
        <h2 class="stitle">Staff <span>Directory</span></h2>
        <p class="ssub">Ibeku High School is served by over 120 dedicated teaching and support staff across both the Junior and Senior Secondary sections.</p>
      </div>
    </div>

    <div class="staff-filter">
      <button class="filter-btn active" data-filter="all">All Staff</button>
      <button class="filter-btn" data-filter="admin-ss">SS Administration</button>
      <button class="filter-btn" data-filter="admin-js">JS Administration</button>
      <button class="filter-btn" data-filter="sciences">Sciences</button>
      <button class="filter-btn" data-filter="arts">Arts</button>
      <button class="filter-btn" data-filter="commercial">Commercial</button>
      <button class="filter-btn" data-filter="general">General Studies</button>
    </div>

    <?php
    /*
     * STAFF DATA
     * Phase 2: replace this array with a database query:
     * SELECT * FROM staff WHERE is_active = 1 ORDER BY section, role_order
     *
     * Role hierarchy order:
     * principal > vp_admin > vp_academics > vp_general > dean > counselor > hod > form_teacher > subject_teacher
     */
    $staff = [
      /* ── SENIOR SECONDARY ADMINISTRATION ── */
      ['initials'=>'SP', 'name'=>'[SS Principal]',          'role'=>'Principal',                       'dept'=>'Senior Secondary', 'section'=>'ss', 'filter'=>'admin-ss'],
      ['initials'=>'VA', 'name'=>'[VP Administration SS]',  'role'=>'Vice Principal (Administration)', 'dept'=>'Senior Secondary', 'section'=>'ss', 'filter'=>'admin-ss'],
      ['initials'=>'VC', 'name'=>'[VP Academics SS]',       'role'=>'Vice Principal (Academics)',      'dept'=>'Senior Secondary', 'section'=>'ss', 'filter'=>'admin-ss'],
      ['initials'=>'VG', 'name'=>'[VP General Duties SS]',  'role'=>'Vice Principal (General Duties)', 'dept'=>'Senior Secondary', 'section'=>'ss', 'filter'=>'admin-ss'],
      ['initials'=>'DS', 'name'=>'[Dean of Studies SS]',    'role'=>'Dean of Studies',                 'dept'=>'Senior Secondary', 'section'=>'ss', 'filter'=>'admin-ss'],
      ['initials'=>'GC', 'name'=>'[Guidance Counsellor SS]','role'=>'Guidance Counsellor',             'dept'=>'Senior Secondary', 'section'=>'ss', 'filter'=>'admin-ss'],

      /* ── JUNIOR SECONDARY ADMINISTRATION ── */
      ['initials'=>'JP', 'name'=>'[JS Principal]',          'role'=>'Principal',                       'dept'=>'Junior Secondary', 'section'=>'js', 'filter'=>'admin-js'],
      ['initials'=>'JA', 'name'=>'[VP Administration JS]',  'role'=>'Vice Principal (Administration)', 'dept'=>'Junior Secondary', 'section'=>'js', 'filter'=>'admin-js'],
      ['initials'=>'JC', 'name'=>'[VP Academics JS]',       'role'=>'Vice Principal (Academics)',      'dept'=>'Junior Secondary', 'section'=>'js', 'filter'=>'admin-js'],
      ['initials'=>'JG', 'name'=>'[VP General Duties JS]',  'role'=>'Vice Principal (General Duties)', 'dept'=>'Junior Secondary', 'section'=>'js', 'filter'=>'admin-js'],
      ['initials'=>'JD', 'name'=>'[Dean of Studies JS]',    'role'=>'Dean of Studies',                 'dept'=>'Junior Secondary', 'section'=>'js', 'filter'=>'admin-js'],
      ['initials'=>'JL', 'name'=>'[Guidance Counsellor JS]','role'=>'Guidance Counsellor',             'dept'=>'Junior Secondary', 'section'=>'js', 'filter'=>'admin-js'],

      /* ── SCIENCES ── */
      ['initials'=>'HS', 'name'=>'[HOD Sciences]',          'role'=>'H.O.D Sciences',                 'dept'=>'Sciences',         'section'=>'ss', 'filter'=>'sciences'],
      ['initials'=>'PH', 'name'=>'[Physics Teacher]',       'role'=>'Subject Teacher',                 'dept'=>'Physics',          'section'=>'ss', 'filter'=>'sciences'],
      ['initials'=>'CH', 'name'=>'[Chemistry Teacher]',     'role'=>'Subject Teacher',                 'dept'=>'Chemistry',        'section'=>'ss', 'filter'=>'sciences'],
      ['initials'=>'BI', 'name'=>'[Biology Teacher]',       'role'=>'Subject Teacher',                 'dept'=>'Biology',          'section'=>'ss', 'filter'=>'sciences'],
      ['initials'=>'MT', 'name'=>'[Mathematics Teacher]',   'role'=>'Subject Teacher',                 'dept'=>'Mathematics',      'section'=>'ss', 'filter'=>'sciences'],

      /* ── ARTS ── */
      ['initials'=>'HA', 'name'=>'[HOD Arts]',              'role'=>'H.O.D Arts',                     'dept'=>'Arts',             'section'=>'ss', 'filter'=>'arts'],
      ['initials'=>'EN', 'name'=>'[English Teacher]',       'role'=>'Subject Teacher',                 'dept'=>'English',          'section'=>'ss', 'filter'=>'arts'],
      ['initials'=>'LT', 'name'=>'[Literature Teacher]',    'role'=>'Subject Teacher',                 'dept'=>'Literature',       'section'=>'ss', 'filter'=>'arts'],
      ['initials'=>'GT', 'name'=>'[Government Teacher]',    'role'=>'Subject Teacher',                 'dept'=>'Government',       'section'=>'ss', 'filter'=>'arts'],

      /* ── COMMERCIAL ── */
      ['initials'=>'HC', 'name'=>'[HOD Commercial]',        'role'=>'H.O.D Commercial',               'dept'=>'Commercial',       'section'=>'ss', 'filter'=>'commercial'],
      ['initials'=>'AC', 'name'=>'[Accounting Teacher]',    'role'=>'Subject Teacher',                 'dept'=>'Accounting',       'section'=>'ss', 'filter'=>'commercial'],
      ['initials'=>'EC', 'name'=>'[Economics Teacher]',     'role'=>'Subject Teacher',                 'dept'=>'Economics',        'section'=>'ss', 'filter'=>'commercial'],

      /* ── GENERAL STUDIES (JSS) ── */
      ['initials'=>'HG', 'name'=>'[HOD General Studies]',  'role'=>'H.O.D General Studies',           'dept'=>'General Studies',  'section'=>'js', 'filter'=>'general'],
      ['initials'=>'BS', 'name'=>'[Basic Science Teacher]', 'role'=>'Subject Teacher',                 'dept'=>'Basic Science',    'section'=>'js', 'filter'=>'general'],
      ['initials'=>'IC', 'name'=>'[ICT Coordinator]',       'role'=>'ICT Coordinator',                 'dept'=>'Computer Studies', 'section'=>'ss', 'filter'=>'general'],
    ];
    ?>

    <div class="staff-full-grid" id="staffGrid">
      <?php foreach ($staff as $s): ?>
      <div class="staff-full-card reveal" data-filter="<?php echo htmlspecialchars($s['filter']); ?>">
        <div class="staff-full-card__photo">
          <!--
            REPLACE WITH REAL PHOTO:
            <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo strtolower(str_replace(' ', '-', $s['name'])); ?>.jpg"
                 alt="<?php echo htmlspecialchars($s['name']); ?>"/>
          -->
          <div class="staff-full-card__initials"><?php echo htmlspecialchars($s['initials']); ?></div>
        </div>
        <div class="staff-full-card__body">
          <h3><?php echo htmlspecialchars($s['name']); ?></h3>
          <span class="staff-full-card__role"><?php echo htmlspecialchars($s['role']); ?></span>
          <span class="staff-full-card__dept"><?php echo htmlspecialchars($s['dept']); ?></span>
          <span class="staff-full-card__section-badge staff-full-card__section-badge--<?php echo $s['section']; ?>">
            <?php echo $s['section'] === 'ss' ? 'Senior Secondary' : 'Junior Secondary'; ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     CLUBS & SOCIETIES
     ═══════════════════════════════════════════ -->
<section class="clubs-section" id="clubs">
  <div class="clubs-section__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel">Co-Curricular Activities</span>
      <h2 class="stitle">Clubs &amp; <span>Societies</span></h2>
      <p class="ssub" style="margin:0 auto">Life at Ibeku High School goes far beyond the classroom. Our 15+ active clubs develop leadership, creativity, and talent in every student.</p>
    </div>

    <div class="clubs-grid">
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true">🔬</span>
        <h3>Science Club</h3>
        <p>Experiments, competitions, and science olympiad preparation. Home of our 3× state quiz champions.</p>
        <span class="club-card__tag">Academic</span>
      </div>
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true">📖</span>
        <h3>Literary &amp; Debate Society</h3>
        <p>Public speaking, essay writing, and inter-school debate competitions at state and national level.</p>
        <span class="club-card__tag">Academic</span>
      </div>
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true">💻</span>
        <h3>Computer &amp; ICT Club</h3>
        <p>Coding basics, digital literacy, and technology projects for students across all year groups.</p>
        <span class="club-card__tag">Technology</span>
      </div>
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true">🎭</span>
        <h3>Drama &amp; Cultural Club</h3>
        <p>Annual productions, cultural day performances, and representation at the Abia State Schools Cultural Festival.</p>
        <span class="club-card__tag">Arts &amp; Culture</span>
      </div>
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true">⚽</span>
        <h3>Sports Club</h3>
        <p>Football, basketball, athletics, and table tennis. Inter-house and inter-school competitions throughout the year.</p>
        <span class="club-card__tag">Sports</span>
      </div>
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true">🏥</span>
        <h3>Red Cross Society</h3>
        <p>First aid training, health campaigns, and community outreach. Open to students from JSS 2 upwards.</p>
        <span class="club-card__tag">Service</span>
      </div>
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true">✝️</span>
        <h3>Christian Students Fellowship</h3>
        <p>Weekly fellowship meetings, praise and worship sessions, and spiritual development activities.</p>
        <span class="club-card__tag">Faith</span>
      </div>
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true">☪️</span>
        <h3>Muslim Students Society</h3>
        <p>Islamic studies, prayer sessions, and moral education in a structured and welcoming environment.</p>
        <span class="club-card__tag">Faith</span>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     COMPETITIONS & AWARDS
     ═══════════════════════════════════════════ -->
<section class="awards-section" id="awards">
  <div class="awards-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Our Achievements</span>
        <h2 class="stitle">Competitions &amp; <span>Awards</span></h2>
        <p class="ssub">Ibeku High School competes at the highest levels — and wins.</p>
      </div>
    </div>

    <div class="awards-grid">

      <div class="award-card award-card--gold reveal">
        <span class="award-card__icon" aria-hidden="true">🏆</span>
        <h3>Abia State Science Quiz Championship</h3>
        <span class="award-card__year">2022 &middot; 2023 &middot; 2024</span>
        <p>Ibeku High School has won the Abia State Secondary School Science Quiz Championship three consecutive years — an unprecedented achievement in the history of the competition.</p>
        <span class="award-badge award-badge--gold">🥇 3× State Champions</span>
      </div>

      <div class="award-card award-card--blue reveal">
        <span class="award-card__icon" aria-hidden="true">🎤</span>
        <h3>Inter-School Debate Competition</h3>
        <span class="award-card__year">Umuahia Zone — Multiple Years</span>
        <p>Our debate team has consistently represented Ibeku High School at the highest level of secondary school debate in Umuahia and Abia State, producing outstanding student orators.</p>
        <span class="award-badge award-badge--blue">Zonal Champions</span>
      </div>

      <div class="award-card award-card--purple reveal">
        <span class="award-card__icon" aria-hidden="true">🏃</span>
        <h3>Abia State Athletics Championships</h3>
        <span class="award-card__year">Multiple Years &middot; Various Events</span>
        <p>IHS athletes have won medals across sprint, relay, and field events at the Abia State secondary school athletics championships, representing the school with distinction.</p>
        <span class="award-badge award-badge--purple">State Medalists</span>
      </div>

      <div class="award-card award-card--gold reveal">
        <span class="award-card__icon" aria-hidden="true">🧮</span>
        <h3>Mathematics Olympiad</h3>
        <span class="award-card__year">COWSSO Regional Level</span>
        <p>IHS mathematics students have represented the school and Abia State in the Mathematics Olympiad, competing at the COWSSO regional level against top students across West Africa.</p>
        <span class="award-badge award-badge--gold">Regional Participants</span>
      </div>

      <div class="award-card award-card--blue reveal">
        <span class="award-card__icon" aria-hidden="true">🎭</span>
        <h3>Cultural &amp; Drama Festival</h3>
        <span class="award-card__year">Abia State Schools Festival</span>
        <p>Our drama and cultural group has won awards at the Abia State Schools Cultural Festival, showcasing the artistic talent and rich cultural heritage of the IHS community.</p>
        <span class="award-badge award-badge--blue">Festival Award Winners</span>
      </div>

      <div class="award-card award-card--purple reveal">
        <span class="award-card__icon" aria-hidden="true">📝</span>
        <h3>WAEC &amp; NECO Excellence</h3>
        <span class="award-card__year">Consistent Year-on-Year Results</span>
        <p>Ibeku High School maintains a consistent 98%+ WAEC pass rate year after year, with students regularly achieving A1 grades across Sciences, Arts, and Commercial subjects.</p>
        <span class="award-badge award-badge--purple">98%+ Pass Rate</span>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     LEARNING RESOURCES
     ═══════════════════════════════════════════ -->
<section class="resources-section" id="resources">
  <div class="resources-section__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel slabel--light">Support Your Learning</span>
      <h2 class="stitle stitle--white">Learning <span style="color:var(--blue-light)">Resources</span></h2>
    </div>

    <div class="resources-grid">

      <div class="resource-card reveal">
        <span class="resource-card__icon" aria-hidden="true">📚</span>
        <h3>School Library</h3>
        <p>A well-stocked library with textbooks, reference materials, past question papers, and general reading resources available to all students during school hours.</p>
        <span class="resource-card__link">Available in the school library building</span>
      </div>

      <div class="resource-card reveal">
        <span class="resource-card__icon" aria-hidden="true">💻</span>
        <h3>Computer Laboratory</h3>
        <p>A fully equipped ICT lab with desktop computers and internet access, recently refurbished through an alumni donation. Available for supervised ICT classes and study sessions.</p>
        <span class="resource-card__link">Block B — Computer Laboratory</span>
      </div>

      <div class="resource-card reveal">
        <span class="resource-card__icon" aria-hidden="true">📄</span>
        <h3>Past Question Papers</h3>
        <p>WAEC and NECO past questions from previous years are available from the school office and the library. Essential preparation material for SSS 3 students.</p>
        <span class="resource-card__link">Collect from the school office</span>
      </div>

      <div class="resource-card reveal">
        <span class="resource-card__icon" aria-hidden="true">🔬</span>
        <h3>Science Laboratory</h3>
        <p>A fully equipped science laboratory supporting Physics, Chemistry, and Biology practical sessions. Lab sessions are scheduled as part of the SSS timetable.</p>
        <span class="resource-card__link">Block A — Science Laboratory</span>
      </div>

      <div class="resource-card reveal">
        <span class="resource-card__icon" aria-hidden="true">📋</span>
        <h3>Class Timetables</h3>
        <p>Current timetables for all six class levels are available for download above. Printed copies are also available from your form teacher at the start of each term.</p>
        <a href="#timetable" class="resource-card__link">Download timetables above →</a>
      </div>

      <div class="resource-card reveal">
        <span class="resource-card__icon" aria-hidden="true">🎓</span>
        <h3>Guidance &amp; Counselling</h3>
        <p>Our Guidance Counsellors are available for academic advice, subject selection guidance, university application support, and personal welfare discussions.</p>
        <a href="<?php echo BASE_PATH; ?>contact.php" class="resource-card__link">Contact the school office →</a>
      </div>

    </div>
  </div>
</section>


<?php require_once '../src/includes/footer.php'; ?>