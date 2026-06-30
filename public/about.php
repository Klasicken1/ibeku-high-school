<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ABOUT PAGE
   File: public/about.php
   ============================================================ */

$pageTitle   = 'About — Ibeku High School, Umuahia';
$pageDesc    = 'Learn about the history, vision, mission, principals, staff, and facilities of Ibeku High School, Umuahia, Abia State.';
$currentPage = 'about';
$pageCss     = 'about';

require_once '../src/includes/header.php';
require_once '../src/config/database.php';
$pdo = getDB();
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--about">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">About</span>
    </nav>
    <h1>The Story of<br/><em>Ibeku High School</em></h1>
    <p>Over seven decades of academic excellence, discipline, and character formation — shaping South-East Nigeria's finest young minds since 1954.</p>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     PAGE ANCHOR NAV
     ═══════════════════════════════════════════ -->
<div class="page-anchors">
  <div class="page-anchors__inner wrap">
    <a href="#history"      class="page-anchor active">History</a>
    <a href="#vision"       class="page-anchor">Vision &amp; Mission</a>
    <a href="#anthem"       class="page-anchor">School Anthem</a>
    <a href="#rules"        class="page-anchor">Rules &amp; Regulations</a>
    <a href="#principal-ss" class="page-anchor">SS Principal</a>
    <a href="#principal-js" class="page-anchor">JS Principal</a>
    <a href="#facilities"   class="page-anchor">Facilities</a>
    <a href="#staff"        class="page-anchor">Staff Directory</a>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     HISTORY
     ═══════════════════════════════════════════ -->
<section class="history-section" id="history">
  <div class="history-section__inner wrap">

    <div class="reveal">
      <div class="history-section__img">
        <div class="history-section__img-placeholder">
          <svg width="72" height="72" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
          </svg>
          <p>Historical school photo</p>
        </div>
        <div class="history-year-badge">Est. 1954</div>
      </div>
    </div>

    <div class="reveal">
      <span class="slabel">Our Heritage</span>
      <h2 class="stitle">A Legacy Born in <span>1954</span></h2>
      <div class="history-section__text">
        <p>Ibeku High School was established in 1954 in Umuahia, Abia State, as part of the colonial-era expansion of secondary education across Eastern Nigeria. From its earliest days, the school set itself apart through a commitment to academic rigour, moral formation, and the holistic development of its students.</p>
        <p>The school draws its name and identity from the Ibeku people of Umuahia — one of the major communities in Abia State — and has always seen itself as a school deeply rooted in community, yet reaching for national and global excellence.</p>
        <h3>Growth Through the Decades</h3>
        <p>In the decades following its founding, Ibeku High School expanded steadily — growing its student population, adding new departments, constructing new facilities, and building a teaching faculty of increasingly qualified educators. Through the Nigerian civil war, post-independence transitions, and the evolution of Nigeria's educational system, Ibeku High School remained standing and delivering.</p>
        <p>Today the school is structured into two distinct sections — Junior Secondary (JSS 1–3) and Senior Secondary (SSS 1–3) — each under its own dedicated principal, with a combined enrolment of over 2,400 students and a teaching and support staff of more than 120.</p>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     TIMELINE — driven by milestones table
     ═══════════════════════════════════════════ -->
<section class="timeline-section">
  <div class="timeline-section__inner wrap">
    <h2 class="timeline-section__title">Key Milestones in Our History</h2>

    <?php
    $milestones = $pdo->query(
        "SELECT * FROM milestones WHERE is_published = 1 ORDER BY sort_order ASC, id ASC"
    )->fetchAll();

    /* Fall back to hardcoded milestones if none in DB yet */
    $defaultMilestones = [
        ['era_label' => '1954',  'title' => 'School Founded',              'description' => 'Ibeku High School is established in Umuahia as part of Eastern Nigeria\'s secondary education expansion under colonial administration.'],
        ['era_label' => '1960s', 'title' => 'Post-Independence Growth',    'description' => 'Following Nigerian independence, the school expands its curriculum, increases enrolment, and strengthens its academic programmes in Sciences, Arts, and Commerce.'],
        ['era_label' => '1970s', 'title' => 'Post-War Reconstruction',     'description' => 'After the Nigerian civil war, Ibeku High School rebuilds and restores its facilities, recommitting to its mission of educating the young people of Umuahia and Abia State.'],
        ['era_label' => '1980s', 'title' => 'Junior Secondary Introduced', 'description' => 'The school adopts Nigeria\'s 6-3-3-4 educational system, establishing a formal Junior Secondary section alongside the Senior Secondary programme.'],
        ['era_label' => '2000s', 'title' => 'ICT & Digital Education',     'description' => 'A dedicated computer laboratory is established, introducing ICT as a formal subject and beginning the school\'s journey toward digital literacy for all students.'],
        ['era_label' => '2025',  'title' => 'Official Website Launched',   'description' => 'As part of an NYSC CDS digital transformation initiative, Ibeku High School launches its first official website — featuring an online result checker, admissions portal, and news system.'],
    ];

    if (empty($milestones)) {
        $milestones = $defaultMilestones;
    }
    ?>

    <div class="timeline">
      <?php foreach ($milestones as $i => $ms):
        $isLeft = ($i % 2 === 0);
      ?>
      <div class="timeline__item reveal">
        <?php if ($isLeft): ?>
        <div class="timeline__content">
          <h4><?php echo htmlspecialchars($ms['title']); ?></h4>
          <p><?php echo htmlspecialchars($ms['description']); ?></p>
        </div>
        <div class="timeline__dot"><?php echo htmlspecialchars($ms['era_label']); ?></div>
        <div class="timeline__spacer"></div>
        <?php else: ?>
        <div class="timeline__spacer"></div>
        <div class="timeline__dot"><?php echo htmlspecialchars($ms['era_label']); ?></div>
        <div class="timeline__content">
          <h4><?php echo htmlspecialchars($ms['title']); ?></h4>
          <p><?php echo htmlspecialchars($ms['description']); ?></p>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     VISION & MISSION
     ═══════════════════════════════════════════ -->
<section class="vision-section" id="vision">
  <div class="vision-section__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel">Our Purpose</span>
      <h2 class="stitle">Vision, Mission &amp; <span>Core Values</span></h2>
    </div>

    <div class="vm-grid">
      <div class="vm-card vm-card--vision reveal">
        <span class="vm-card__icon" aria-hidden="true">🔭</span>
        <h3>Our Vision</h3>
        <p>To be the leading centre of secondary education in South-East Nigeria — producing graduates who are academically excellent, morally sound, and equipped to lead in every sphere of society.</p>
      </div>
      <div class="vm-card vm-card--mission reveal">
        <span class="vm-card__icon" aria-hidden="true">🎯</span>
        <h3>Our Mission</h3>
        <p>To provide a disciplined, inclusive, and stimulating learning environment where every student discovers their potential, develops strong values, and is prepared for the opportunities and challenges of the future.</p>
      </div>
    </div>

    <h3 style="text-align:center;font-size:1.5rem;color:var(--purple);margin:50px 0 20px">Our Core Values</h3>
    <div class="values-grid">
      <div class="value-item reveal"><span class="value-item__icon" aria-hidden="true">⚖️</span><h4>Integrity</h4></div>
      <div class="value-item reveal"><span class="value-item__icon" aria-hidden="true">🏆</span><h4>Excellence</h4></div>
      <div class="value-item reveal"><span class="value-item__icon" aria-hidden="true">🎖️</span><h4>Discipline</h4></div>
      <div class="value-item reveal"><span class="value-item__icon" aria-hidden="true">🤝</span><h4>Community</h4></div>
      <div class="value-item reveal"><span class="value-item__icon" aria-hidden="true">💡</span><h4>Innovation</h4></div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     SCHOOL ANTHEM
     ═══════════════════════════════════════════ -->
<section class="anthem-section" id="anthem">
  <div class="anthem-section__inner wrap">

    <span class="slabel text-center">Our Identity</span>
    <h2 class="stitle text-center">The School <span>Anthem</span></h2>
    <p class="ssub text-center" style="margin:0 auto 10px">
      The anthem of Ibeku High School reflects our values of excellence, unity, and service to Nigeria and humanity.
    </p>

    <div class="anthem-card reveal">
      <span class="anthem-card__label">🎵 The Ibeku High School Anthem</span>
      <div class="anthem-card__verses">
        <div class="anthem-verse">
          <p>Ibeku High School, our noble alma mater,</p>
          <p>We gather here to honour your name,</p>
          <p>With hearts full of pride and voices united,</p>
          <p>We sing of your glory and timeless fame.</p>
        </div>
        <div class="anthem-divider"></div>
        <div class="anthem-verse">
          <p>In knowledge and wisdom, you guide us forward,</p>
          <p>In discipline strong, you shape every mind,</p>
          <p>Through challenges faced and victories celebrated,</p>
          <p>We carry your spirit throughout all of time.</p>
        </div>
        <div class="anthem-divider"></div>
        <div class="anthem-verse">
          <p>Ibeku, Ibeku, our light ever shining,</p>
          <p>From Umuahia your beacon burns bright,</p>
          <p>We pledge to uphold all you have taught us,</p>
          <p>And walk ever forward in truth and in light.</p>
        </div>
        <p class="anthem-note">* Placeholder lyrics — update with the official school anthem text before launch.</p>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     RULES & REGULATIONS
     ═══════════════════════════════════════════ -->
<section class="rules-section" id="rules">
  <div class="rules-section__inner wrap">

    <span class="slabel">School Standards</span>
    <h2 class="stitle">Rules &amp; <span>Regulations</span></h2>
    <p class="ssub">Ibeku High School maintains high standards of conduct. Every student is expected to uphold these rules at all times on and off school premises.</p>

    <div class="rules-grid">
      <div class="rule-card reveal">
        <h3 class="rule-card__title">👔 Dress &amp; Appearance</h3>
        <ul>
          <li>Full school uniform must be worn at all times on school premises</li>
          <li>Uniforms must be clean, well-pressed, and properly fitted</li>
          <li>No jewellery, nail polish, or artificial hair allowed</li>
          <li>Hair must be neatly kept — no braids, weaves, or dreadlocks</li>
          <li>School shoes and socks must match prescribed standards</li>
        </ul>
      </div>
      <div class="rule-card reveal">
        <h3 class="rule-card__title">📚 Academic Conduct</h3>
        <ul>
          <li>Punctuality to all classes is mandatory</li>
          <li>All assignments and homework must be submitted on time</li>
          <li>Examination malpractice results in immediate suspension</li>
          <li>Students must bring all required materials to every class</li>
          <li>Respect for teachers and fellow students is non-negotiable</li>
        </ul>
      </div>
      <div class="rule-card reveal">
        <h3 class="rule-card__title">🏫 General Behaviour</h3>
        <ul>
          <li>Mobile phones are prohibited during school hours</li>
          <li>Fighting, bullying, or harassment leads to suspension</li>
          <li>Students must remain within school premises during school hours</li>
          <li>Vandalism of school property attracts serious sanctions</li>
          <li>Substance use of any kind is strictly prohibited</li>
        </ul>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     BOTH PRINCIPALS — driven by Settings
     ═══════════════════════════════════════════ -->
<section class="principals-section" id="principal-ss">
  <div class="principals-section__inner wrap">

    <div class="principals-section__header reveal">
      <span class="slabel">Our Leadership</span>
      <h2 class="stitle">Messages from Our <span>Principals</span></h2>
      <p class="ssub" style="margin:0 auto;text-align:center">
        Ibeku High School is led by two dedicated principals — one for Senior Secondary and one for Junior Secondary — each committed to the school's mission of excellence.
      </p>
    </div>

    <div class="principals-grid">

      <!-- SS Principal -->
      <div class="principal-card reveal">
        <div class="principal-card__top principal-card__top--ss">
          <div class="principal-card__photo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
          </div>
          <span class="principal-card__badge">Senior Secondary Principal</span>
          <h3 class="principal-card__name"><?php echo htmlspecialchars($_site['principal_ss_name']); ?></h3>
          <p class="principal-card__title">Principal, Senior Secondary — <?php echo htmlspecialchars($_site['school_name']); ?></p>
        </div>
        <div class="principal-card__body">
          <span class="principal-card__qmark" aria-hidden="true">&ldquo;</span>
          <blockquote><?php echo nl2br(htmlspecialchars($_site['principal_ss_message'])); ?></blockquote>
        </div>
      </div>

      <!-- JS Principal -->
      <div class="principal-card reveal" id="principal-js">
        <div class="principal-card__top principal-card__top--js">
          <div class="principal-card__photo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
          </div>
          <span class="principal-card__badge">Junior Secondary Principal</span>
          <h3 class="principal-card__name"><?php echo htmlspecialchars($_site['principal_js_name']); ?></h3>
          <p class="principal-card__title">Principal, Junior Secondary — <?php echo htmlspecialchars($_site['school_name']); ?></p>
        </div>
        <div class="principal-card__body">
          <span class="principal-card__qmark" aria-hidden="true">&ldquo;</span>
          <blockquote><?php echo nl2br(htmlspecialchars($_site['principal_js_message'])); ?></blockquote>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     FACILITIES
     ═══════════════════════════════════════════ -->
<section class="facilities-section" id="facilities">
  <div class="facilities-section__inner wrap">

    <span class="slabel">Our Campus</span>
    <h2 class="stitle">School <span>Facilities</span></h2>
    <p class="ssub">Ibeku High School provides the infrastructure and resources our students need to learn, grow, and thrive.</p>

    <div class="facilities-grid">
      <div class="facility-card reveal">
        <div class="facility-card__img facility-card__img--1" aria-hidden="true">🔬</div>
        <div class="facility-card__body"><h3>Science Laboratory</h3><p>A fully equipped science lab supporting Physics, Chemistry, and Biology practicals for SSS students.</p></div>
      </div>
      <div class="facility-card reveal">
        <div class="facility-card__img facility-card__img--2" aria-hidden="true">💻</div>
        <div class="facility-card__body"><h3>Computer Laboratory</h3><p>A modern ICT lab with desktop computers and internet access, recently refurbished through alumni donations.</p></div>
      </div>
      <div class="facility-card reveal">
        <div class="facility-card__img facility-card__img--3" aria-hidden="true">📚</div>
        <div class="facility-card__body"><h3>School Library</h3><p>A well-stocked library with textbooks, reference materials, and reading resources for all year groups.</p></div>
      </div>
      <div class="facility-card reveal">
        <div class="facility-card__img facility-card__img--4" aria-hidden="true">⚽</div>
        <div class="facility-card__body"><h3>Sports Fields</h3><p>Dedicated football pitch, basketball court, and athletics track supporting inter-house and inter-school competitions.</p></div>
      </div>
      <div class="facility-card reveal">
        <div class="facility-card__img facility-card__img--5" aria-hidden="true">🎭</div>
        <div class="facility-card__body"><h3>Assembly Hall</h3><p>A large, covered assembly hall used for morning assembly, cultural events, prize-giving, and school functions.</p></div>
      </div>
      <div class="facility-card reveal">
        <div class="facility-card__img facility-card__img--6" aria-hidden="true">🏫</div>
        <div class="facility-card__body"><h3>Classrooms</h3><p>Well-ventilated, properly furnished classrooms across the JS and SS sections, designed for focused learning.</p></div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     STAFF DIRECTORY — driven by staff table
     ═══════════════════════════════════════════ -->
<section class="staff-directory" id="staff">
  <div class="staff-directory__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Our Team</span>
        <h2 class="stitle">Staff <span>Directory</span></h2>
      </div>
    </div>

    <?php
    $staffMembers = $pdo->query(
        "SELECT * FROM staff WHERE is_published = 1 ORDER BY sort_order ASC, full_name ASC"
    )->fetchAll();

    $staffCategories = [
        'administration' => 'Administration',
        'sciences'       => 'Sciences',
        'arts'           => 'Arts',
        'commercial'     => 'Commercial',
        'support'        => 'Support Staff',
    ];
    ?>

    <div class="staff-filter">
      <button class="filter-btn active" data-filter="all">All Staff</button>
      <?php foreach ($staffCategories as $key => $label): ?>
      <button class="filter-btn" data-filter="<?php echo $key; ?>"><?php echo $label; ?></button>
      <?php endforeach; ?>
    </div>

    <?php if (empty($staffMembers)): ?>
    <p style="color:#6b6b80;text-align:center;padding:40px 0">
      Staff profiles will appear here once added by the administrator.
    </p>
    <?php else: ?>
    <div class="staff-directory__grid">
      <?php foreach ($staffMembers as $m): ?>
      <div class="staff-dir-card reveal" data-filter="<?php echo htmlspecialchars($m['category']); ?>">
        <div class="staff-dir-card__photo">
          <?php if (!empty($m['photo'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($m['photo']); ?>"
               alt="<?php echo htmlspecialchars($m['full_name']); ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="staff-dir-card__initials" style="display:none">
            <?php echo htmlspecialchars(strtoupper(substr($m['full_name'], 0, 2))); ?>
          </div>
          <?php else: ?>
          <div class="staff-dir-card__initials">
            <?php echo htmlspecialchars(strtoupper(substr($m['full_name'], 0, 2))); ?>
          </div>
          <?php endif; ?>
        </div>
        <h4><?php echo htmlspecialchars($m['full_name']); ?></h4>
        <p><?php echo htmlspecialchars($m['role']); ?></p>
        <?php if ($m['department']): ?>
        <span><?php echo htmlspecialchars($m['department']); ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>


<?php require_once '../src/includes/footer.php'; ?>