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
require_once '../src/config/database.php';
$pdo = getDB();

/* ── Load dynamic data from DB ── */
$staffFromDB = $pdo->query(
    "SELECT * FROM staff WHERE is_published = 1 ORDER BY sort_order ASC, full_name ASC"
)->fetchAll();

$clubsFromDB = $pdo->query(
    "SELECT * FROM clubs WHERE is_published = 1 ORDER BY sort_order ASC, name ASC"
)->fetchAll();

$awardsFromDB = $pdo->query(
    "SELECT * FROM awards WHERE is_published = 1 ORDER BY sort_order ASC, id ASC"
)->fetchAll();

$staffCategories = [
    'administration' => 'Administration',
    'sciences'       => 'Sciences',
    'arts'           => 'Arts',
    'commercial'     => 'Commercial',
    'support'        => 'Support Staff',
];

/* Fallback clubs if none in DB */
$defaultClubs = [
    ['icon'=>'🔬','name'=>'Science Club',              'description'=>'Experiments, competitions, and science olympiad preparation. Home of our 3× state quiz champions.',                                            'patron'=>null],
    ['icon'=>'📖','name'=>'Literary & Debate Society', 'description'=>'Public speaking, essay writing, and inter-school debate competitions at state and national level.',                                          'patron'=>null],
    ['icon'=>'💻','name'=>'Computer & ICT Club',       'description'=>'Coding basics, digital literacy, and technology projects for students across all year groups.',                                             'patron'=>null],
    ['icon'=>'🎭','name'=>'Drama & Cultural Club',     'description'=>'Annual productions, cultural day performances, and representation at the Abia State Schools Cultural Festival.',                            'patron'=>null],
    ['icon'=>'⚽','name'=>'Sports Club',               'description'=>'Football, basketball, athletics, and table tennis. Inter-house and inter-school competitions throughout the year.',                         'patron'=>null],
    ['icon'=>'🏥','name'=>'Red Cross Society',         'description'=>'First aid training, health campaigns, and community outreach. Open to students from JSS 2 upwards.',                                       'patron'=>null],
    ['icon'=>'✝️','name'=>'Scripture Union',           'description'=>'Weekly Scripture Union meetings, Bible study sessions, praise and worship, and Christian character development activities.',               'patron'=>null],
    ['icon'=>'☪️','name'=>'Muslim Students Society',  'description'=>'Islamic studies, prayer sessions, and moral education in a structured and welcoming environment.',                                          'patron'=>null],
];

/* Fallback awards if none in DB */
$defaultAwards = [
    ['icon'=>'🏆','title'=>'Abia State Science Quiz Championship', 'year_label'=>'2022 · 2023 · 2024',           'description'=>'Ibeku High School has won the Abia State Secondary School Science Quiz Championship three consecutive years — an unprecedented achievement in the history of the competition.', 'badge_text'=>'🥇 3× State Champions'],
    ['icon'=>'🎤','title'=>'Inter-School Debate Competition',      'year_label'=>'Umuahia Zone — Multiple Years', 'description'=>'Our debate team has consistently represented Ibeku High School at the highest level of secondary school debate in Umuahia and Abia State.',                                 'badge_text'=>'Zonal Champions'],
    ['icon'=>'🏃','title'=>'Abia State Athletics Championships',   'year_label'=>'Multiple Years',               'description'=>'IHS athletes have won medals across sprint, relay, and field events at the Abia State secondary school athletics championships.',                                            'badge_text'=>'State Medalists'],
    ['icon'=>'🧮','title'=>'Mathematics Olympiad',                 'year_label'=>'COWSSO Regional Level',         'description'=>'IHS mathematics students have represented the school and Abia State in the Mathematics Olympiad, competing at the COWSSO regional level.',                                  'badge_text'=>'Regional Participants'],
    ['icon'=>'🎭','title'=>'Cultural & Drama Festival',            'year_label'=>'Abia State Schools Festival',   'description'=>'Our drama and cultural group has won awards at the Abia State Schools Cultural Festival, showcasing the artistic talent and rich cultural heritage of IHS.',               'badge_text'=>'Festival Award Winners'],
    ['icon'=>'📝','title'=>'WAEC & NECO Excellence',              'year_label'=>'Consistent Year-on-Year',       'description'=>'Ibeku High School maintains a consistent 98%+ WAEC pass rate year after year, with students regularly achieving A1 grades across all departments.',                         'badge_text'=>'98%+ Pass Rate'],
];
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

    <?php
    $metaPath      = __DIR__ . '/assets/timetables/meta.json';
    $timetableMeta = ['session' => '2024/2025', 'files' => []];
    if (file_exists($metaPath)) {
        $decoded = json_decode(file_get_contents($metaPath), true);
        if (is_array($decoded)) $timetableMeta = $decoded;
    }
    $sessionLabel = htmlspecialchars($timetableMeta['session'] ?? '2024/2025');

    $timetables = [
      ['level'=>'JSS 1','file'=>'timetable-jss1.pdf','section'=>'jss','desc'=>'Full week schedule for JSS 1 students covering all 8 periods per day.'],
      ['level'=>'JSS 2','file'=>'timetable-jss2.pdf','section'=>'jss','desc'=>'Full week schedule for JSS 2 students covering all 8 periods per day.'],
      ['level'=>'JSS 3','file'=>'timetable-jss3.pdf','section'=>'jss','desc'=>'Full week schedule for JSS 3 students covering all 8 periods per day.'],
      ['level'=>'SSS 1','file'=>'timetable-sss1.pdf','section'=>'sss','desc'=>'Full week schedule for SSS 1 students across all three departments.'],
      ['level'=>'SSS 2','file'=>'timetable-sss2.pdf','section'=>'sss','desc'=>'Full week schedule for SSS 2 students across all three departments.'],
      ['level'=>'SSS 3','file'=>'timetable-sss3.pdf','section'=>'sss','desc'=>'Full week schedule for SSS 3 students across all three departments.'],
    ];
    ?>

    <div class="timetable-grid">
      <?php foreach ($timetables as $tt):
        $isJss       = $tt['section'] === 'jss';
        $headerClass = $isJss ? 'timetable-card__header--jss' : 'timetable-card__header--sss';
        $filePath    = BASE_PATH . 'assets/timetables/' . $tt['file'];
        $fileExists  = file_exists(__DIR__ . '/assets/timetables/' . $tt['file']);
        $classSlug   = strtolower(str_replace(' ', '', $tt['level']));
        $lastUpdated = $timetableMeta['files'][$classSlug] ?? null;
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
            <strong style="color:var(--purple)"><?php echo $sessionLabel; ?> Academic Session</strong>
          </p>
          <?php if ($fileExists): ?>
          <div class="timetable-card__status timetable-card__status--available">
            <span class="timetable-card__dot"></span>Current timetable available
          </div>
          <?php if ($lastUpdated): ?>
          <p class="timetable-card__updated">Last updated: <?php echo date('F j, Y', strtotime($lastUpdated)); ?></p>
          <?php endif; ?>
          <a href="<?php echo htmlspecialchars($filePath); ?>"
             class="btn--download <?php echo $isJss ? '' : 'btn--download--purple'; ?>"
             download="<?php echo htmlspecialchars($tt['level']); ?>-Timetable-IHS.pdf"
             target="_blank" rel="noopener noreferrer">
            &#11015; Download <?php echo htmlspecialchars($tt['level']); ?> Timetable (PDF)
          </a>
          <?php else: ?>
          <div class="timetable-card__status timetable-card__status--pending">
            <span class="timetable-card__dot"></span>Timetable pending upload
          </div>
          <a href="<?php echo BASE_PATH; ?>contact.php" class="btn--download" style="background:var(--muted)">
            Contact School Office
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     STAFF DIRECTORY — driven by staff table
     Single source of truth, no duplication with about.php
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
      <?php foreach ($staffCategories as $key => $label): ?>
      <button class="filter-btn" data-filter="<?php echo $key; ?>"><?php echo $label; ?></button>
      <?php endforeach; ?>
    </div>

    <?php if (empty($staffFromDB)): ?>
    <p style="color:#6b6b80;text-align:center;padding:40px 0">
      Staff profiles will appear here once added by the administrator.
    </p>
    <?php else: ?>
    <div class="staff-full-grid" id="staffGrid">
      <?php foreach ($staffFromDB as $m): ?>
      <div class="staff-full-card reveal"
           data-filter="<?php echo htmlspecialchars($m['category']); ?>"
           data-section="<?php echo htmlspecialchars($m['section']); ?>">
        <div class="staff-full-card__photo">
          <?php if (!empty($m['photo'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($m['photo']); ?>"
               alt="<?php echo htmlspecialchars($m['full_name']); ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="staff-full-card__initials" style="display:none">
            <?php echo htmlspecialchars(strtoupper(substr($m['full_name'], 0, 2))); ?>
          </div>
          <?php else: ?>
          <div class="staff-full-card__initials">
            <?php echo htmlspecialchars(strtoupper(substr($m['full_name'], 0, 2))); ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="staff-full-card__body">
          <h3><?php echo htmlspecialchars($m['full_name']); ?></h3>
          <span class="staff-full-card__role"><?php echo htmlspecialchars($m['role']); ?></span>
          <?php if ($m['department']): ?>
          <span class="staff-full-card__dept"><?php echo htmlspecialchars($m['department']); ?></span>
          <?php endif; ?>
          <span class="staff-full-card__section-badge staff-full-card__section-badge--<?php echo $m['section']; ?>">
            <?php echo $m['section'] === 'ss' ? 'Senior Secondary' : ($m['section'] === 'js' ? 'Junior Secondary' : 'Both Sections'); ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     CLUBS & SOCIETIES — driven by clubs table
     ═══════════════════════════════════════════ -->
<section class="clubs-section" id="clubs">
  <div class="clubs-section__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel">Co-Curricular Activities</span>
      <h2 class="stitle">Clubs &amp; <span>Societies</span></h2>
      <p class="ssub" style="margin:0 auto">Life at Ibeku High School goes far beyond the classroom. Our active clubs develop leadership, creativity, and talent in every student.</p>
    </div>

    <?php $clubsToShow = !empty($clubsFromDB) ? $clubsFromDB : $defaultClubs; ?>
    <div class="clubs-grid">
      <?php foreach ($clubsToShow as $club): ?>
      <div class="club-card reveal">
        <span class="club-card__icon" aria-hidden="true"><?php echo htmlspecialchars($club['icon'] ?? '🎯'); ?></span>
        <h3><?php echo htmlspecialchars($club['name']); ?></h3>
        <p><?php echo htmlspecialchars($club['description'] ?? ''); ?></p>
        <?php if (!empty($club['patron'])): ?>
        <span class="club-card__tag">Patron: <?php echo htmlspecialchars($club['patron']); ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     COMPETITIONS & AWARDS — driven by awards table
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

    <?php
    $awardsToShow  = !empty($awardsFromDB) ? $awardsFromDB : $defaultAwards;
    $awardColors   = ['award-card--gold', 'award-card--blue', 'award-card--purple'];
    $badgeColors   = ['award-badge--gold', 'award-badge--blue', 'award-badge--purple'];
    ?>
    <div class="awards-grid">
      <?php foreach ($awardsToShow as $i => $award):
        $colorClass = $awardColors[$i % 3];
        $badgeClass = $badgeColors[$i % 3];
      ?>
      <div class="award-card <?php echo $colorClass; ?> reveal">
        <span class="award-card__icon" aria-hidden="true"><?php echo htmlspecialchars($award['icon'] ?? '🏆'); ?></span>
        <h3><?php echo htmlspecialchars($award['title']); ?></h3>
        <?php if (!empty($award['year_label'])): ?>
        <span class="award-card__year"><?php echo htmlspecialchars($award['year_label']); ?></span>
        <?php endif; ?>
        <p><?php echo htmlspecialchars($award['description'] ?? ''); ?></p>
        <?php if (!empty($award['badge_text'])): ?>
        <span class="award-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($award['badge_text']); ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
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