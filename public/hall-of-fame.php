<?php
/* ============================================================
   IBEKU HIGH SCHOOL — HALL OF FAME PAGE
   File: public/hall-of-fame.php
   ============================================================ */

$pageTitle   = 'Hall of Fame — Ibeku High School';
$pageDesc    = 'Celebrating the outstanding students, alumni, athletes, scholars, and leaders who have brought glory to Ibeku High School across the generations.';
$currentPage = 'students';
$pageCss     = 'hall-of-fame';

require_once '../src/includes/header.php';
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO — GOLD THEMED
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--hof">

  <!-- Floating gold particles -->
  <div class="hof-hero__particles" aria-hidden="true">
    <span class="hof-hero__particle"></span>
    <span class="hof-hero__particle"></span>
    <span class="hof-hero__particle"></span>
    <span class="hof-hero__particle"></span>
    <span class="hof-hero__particle"></span>
    <span class="hof-hero__particle"></span>
  </div>

  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <a href="<?php echo BASE_PATH; ?>students.php">Students</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">Hall of Fame</span>
    </nav>

    <div class="hof-hero__emblem" aria-hidden="true">🏆</div>

    <h1>The Ibeku High School<br/><em>Hall of Fame</em></h1>
    <p>Celebrating the outstanding students, alumni, athletes, scholars, and leaders who have brought glory to Ibeku High School across the generations.</p>

    <div class="hof-hero__stats">
      <div>
        <span class="hof-hero__stat-num">150+</span>
        <span class="hof-hero__stat-lbl">Inductees</span>
      </div>
      <div>
        <span class="hof-hero__stat-num">1954</span>
        <span class="hof-hero__stat-lbl">Since</span>
      </div>
      <div>
        <span class="hof-hero__stat-num">8</span>
        <span class="hof-hero__stat-lbl">Categories</span>
      </div>
      <div>
        <span class="hof-hero__stat-num">15,000+</span>
        <span class="hof-hero__stat-lbl">Alumni Community</span>
      </div>
    </div>
  </div>

</div>


<!-- ═══════════════════════════════════════════
     PAGE ANCHOR NAV — GOLD THEMED
     ═══════════════════════════════════════════ -->
<div class="page-anchors page-anchors--hof">
  <div class="page-anchors__inner wrap">
    <a href="#featured"  class="page-anchor active">Distinguished Alumni</a>
    <a href="#academics" class="page-anchor">Academic Stars</a>
    <a href="#sports"    class="page-anchor">Sports Champions</a>
    <a href="#prefects"  class="page-anchor">Head Prefects</a>
    <a href="#alumni"    class="page-anchor">Alumni Wall</a>
    <a href="#nominate"  class="page-anchor">Nominate Someone</a>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     FEATURED DISTINGUISHED ALUMNI
     ═══════════════════════════════════════════ -->
<section class="featured-section" id="featured">
  <div class="featured-section__inner wrap">

    <span class="slabel">Distinguished Inductees</span>
    <h2 class="stitle" style="color:#fff">
      Our Most <span style="color:var(--gold)">Distinguished</span> Alumni
    </h2>
    <p style="color:rgba(255,255,255,.7);max-width:600px;line-height:1.8;margin-top:10px">
      These individuals have gone on to shape Nigeria and the world — and they all started their journey at Ibeku High School, Umuahia.
    </p>

    <div class="featured-grid">

      <div class="featured-card reveal">
        <span class="featured-card__crown" aria-hidden="true">👑</span>
        <div class="featured-card__photo">
          <!--
            REPLACE WITH REAL PHOTO:
            <img src="<?php echo BASE_PATH; ?>assets/images/staff/alumni-1.jpg" alt="Distinguished Alumnus"/>
          -->
          <span class="featured-card__initials">AO</span>
        </div>
        <h3>[Distinguished Alumnus Name]</h3>
        <span class="featured-card__class">Class of [Year]</span>
        <span class="featured-card__field">Medicine &amp; Public Health</span>
        <p>A trailblazing medical professional who rose to become one of Nigeria's foremost specialists in public health, serving communities across Abia State and beyond.</p>
        <div class="featured-card__achievement">
          <span class="featured-card__ach-icon" aria-hidden="true">🎖️</span>
          <span class="featured-card__ach-text">Federal Award for Excellence in Medicine</span>
        </div>
      </div>

      <div class="featured-card reveal">
        <span class="featured-card__crown" aria-hidden="true">👑</span>
        <div class="featured-card__photo">
          <span class="featured-card__initials">CN</span>
        </div>
        <h3>[Distinguished Alumnus Name]</h3>
        <span class="featured-card__class">Class of [Year]</span>
        <span class="featured-card__field">Law &amp; Public Service</span>
        <p>A respected legal practitioner and public servant who has held significant positions in the Nigerian judiciary, championing justice and the rule of law.</p>
        <div class="featured-card__achievement">
          <span class="featured-card__ach-icon" aria-hidden="true">⚖️</span>
          <span class="featured-card__ach-text">Senior Advocate of Nigeria (SAN)</span>
        </div>
      </div>

      <div class="featured-card reveal">
        <span class="featured-card__crown" aria-hidden="true">👑</span>
        <div class="featured-card__photo">
          <span class="featured-card__initials">EI</span>
        </div>
        <h3>[Distinguished Alumnus Name]</h3>
        <span class="featured-card__class">Class of [Year]</span>
        <span class="featured-card__field">Engineering &amp; Technology</span>
        <p>A pioneering engineer and entrepreneur whose work in infrastructure development has impacted millions across South-East Nigeria and the wider country.</p>
        <div class="featured-card__achievement">
          <span class="featured-card__ach-icon" aria-hidden="true">🏗️</span>
          <span class="featured-card__ach-text">COREN Fellow &amp; National Merit Award</span>
        </div>
      </div>

    </div>

    <p class="featured-section__note">
      * Real alumni names and details to be confirmed and inserted by school management before launch.
    </p>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ACADEMIC STARS
     ═══════════════════════════════════════════ -->
<section class="academic-stars-section" id="academics">
  <div class="academic-stars-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Top Scholars</span>
        <h2 class="stitle">Academic <span>Stars</span></h2>
        <p class="ssub">Students who achieved the highest academic results in their examinations at Ibeku High School.</p>
      </div>
    </div>

    <div class="academic-stars-grid">

      <div class="star-card reveal">
        <div class="star-card__top star-card__top--gold">
          <span class="star-card__medal" aria-hidden="true">🥇</span>
          <div class="star-card__photo">
            <span class="star-card__photo-initials">AO</span>
          </div>
          <h4>[Student Name]</h4>
          <span>Class of [Year] &middot; SSS 3</span>
        </div>
        <div class="star-card__body">
          <span class="star-card__subject">Overall Best Graduating Student</span>
          <div class="star-card__score">98.4<span>% avg</span></div>
          <p>Exceptional performance across all subjects with distinctions in Mathematics, Physics, and Chemistry.</p>
        </div>
      </div>

      <div class="star-card reveal">
        <div class="star-card__top star-card__top--silver">
          <span class="star-card__medal" aria-hidden="true">🥈</span>
          <div class="star-card__photo">
            <span class="star-card__photo-initials">CE</span>
          </div>
          <h4>[Student Name]</h4>
          <span>Class of [Year] &middot; SSS 3</span>
        </div>
        <div class="star-card__body">
          <span class="star-card__subject">Best in Sciences</span>
          <div class="star-card__score">A1<span> in all science subjects</span></div>
          <p>A record-breaking performance in WAEC Sciences — the first student to score A1 in all five science subjects.</p>
        </div>
      </div>

      <div class="star-card reveal">
        <div class="star-card__top star-card__top--bronze">
          <span class="star-card__medal" aria-hidden="true">🥉</span>
          <div class="star-card__photo">
            <span class="star-card__photo-initials">NG</span>
          </div>
          <h4>[Student Name]</h4>
          <span>Class of [Year] &middot; SSS 3</span>
        </div>
        <div class="star-card__body">
          <span class="star-card__subject">Best in Arts &amp; Humanities</span>
          <div class="star-card__score">A1<span> in Lit, Govt, History</span></div>
          <p>Topped the state in WAEC Literature in English and represented Abia State in the national literary competition.</p>
        </div>
      </div>

      <div class="star-card reveal">
        <div class="star-card__top star-card__top--blue">
          <span class="star-card__medal" aria-hidden="true">⭐</span>
          <div class="star-card__photo">
            <span class="star-card__photo-initials">IA</span>
          </div>
          <h4>[Student Name]</h4>
          <span>Class of [Year] &middot; SSS 3</span>
        </div>
        <div class="star-card__body">
          <span class="star-card__subject">Best in Commercial Studies</span>
          <div class="star-card__score">A1<span> in Accounting &amp; Economics</span></div>
          <p>Outstanding performance in business and commerce subjects. Now studying Accountancy at UNN.</p>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     SPORTS CHAMPIONS
     ═══════════════════════════════════════════ -->
<section class="sports-section" id="sports">
  <div class="sports-section__inner wrap">

    <span class="slabel">Athletic Excellence</span>
    <h2 class="stitle">Sports <span style="color:var(--blue-light)">Champions</span></h2>
    <p style="color:rgba(255,255,255,.7);max-width:560px;line-height:1.8;margin-top:10px">
      Teams and individuals who have brought sporting glory to Ibeku High School at the state, zonal, and national levels.
    </p>

    <div class="sports-grid">

      <div class="sports-card reveal">
        <span class="sports-card__icon" aria-hidden="true">🔬</span>
        <h3>Science Quiz Champions</h3>
        <span class="sports-card__year">2022 &middot; 2023 &middot; 2024</span>
        <p>Ibeku High School's science quiz team has won the Abia State Secondary School Science Quiz Championship three consecutive years — an unprecedented feat in the state.</p>
        <span class="sports-card__badge">3× State Champions</span>
      </div>

      <div class="sports-card reveal">
        <span class="sports-card__icon" aria-hidden="true">⚽</span>
        <h3>Football Team</h3>
        <span class="sports-card__year">Umuahia Zone Champions</span>
        <p>The IHS football team has consistently dominated zonal secondary school competitions, producing players who have gone on to represent Abia State in national competitions.</p>
        <span class="sports-card__badge">Zonal Champions</span>
      </div>

      <div class="sports-card reveal">
        <span class="sports-card__icon" aria-hidden="true">🏃</span>
        <h3>Athletics &amp; Track</h3>
        <span class="sports-card__year">Multiple State Medals</span>
        <p>IHS athletes have won medals in the Abia State secondary school athletics championships in 100m, 200m, relay, and long jump events across multiple years.</p>
        <span class="sports-card__badge">State Medalists</span>
      </div>

      <div class="sports-card reveal">
        <span class="sports-card__icon" aria-hidden="true">🎤</span>
        <h3>Debate Team</h3>
        <span class="sports-card__year">Zonal &amp; State Competitions</span>
        <p>The IHS debate and public speaking team has produced outstanding orators who have competed at the highest levels of secondary school debate in Abia State.</p>
        <span class="sports-card__badge">Best Debaters</span>
      </div>

      <div class="sports-card reveal">
        <span class="sports-card__icon" aria-hidden="true">🧮</span>
        <h3>Mathematics Competition</h3>
        <span class="sports-card__year">COWSSO &amp; National Level</span>
        <p>IHS mathematics students have represented the school and state in the Mathematics Olympiad, with notable performances at the COWSSO regional competitions.</p>
        <span class="sports-card__badge">National Participants</span>
      </div>

      <div class="sports-card reveal">
        <span class="sports-card__icon" aria-hidden="true">🎭</span>
        <h3>Cultural &amp; Drama</h3>
        <span class="sports-card__year">Annual Cultural Festival</span>
        <p>The IHS drama and cultural group has won awards at the Abia State Schools Cultural Festival, representing the school's rich artistic tradition and diverse heritage.</p>
        <span class="sports-card__badge">Cultural Award Winners</span>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     HEAD PREFECTS HALL
     ═══════════════════════════════════════════ -->
<section class="prefects-section" id="prefects">
  <div class="prefects-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Student Leadership</span>
        <h2 class="stitle">Head Prefects <span>Hall of Fame</span></h2>
        <p class="ssub">The students who served Ibeku High School as Head Prefect — the highest student leadership position in the school.</p>
      </div>
    </div>

    <div class="prefects-grid">
      <!--
        UPDATE: Replace all placeholders with real Head Prefect
        names and years from school records.
      -->
      <?php
      /* Phase 2: this array will be replaced with a database query
         $prefects = $db->query("SELECT * FROM prefects ORDER BY year DESC"); */
      $prefects = [
        ['initials' => 'AO', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'CE', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'NG', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'IO', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'UA', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'EM', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'AC', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'OI', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'NK', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
        ['initials' => 'IC', 'name' => '[Head Prefect Name]', 'year' => '[Year]'],
      ];
      foreach ($prefects as $p): ?>
      <div class="prefect-card reveal">
        <div class="prefect-card__photo">
          <!--
            REPLACE WITH REAL PHOTO:
            <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo strtolower($p['initials']); ?>.jpg"
                 alt="<?php echo htmlspecialchars($p['name']); ?>"/>
          -->
          <span class="prefect-card__initials"><?php echo htmlspecialchars($p['initials']); ?></span>
        </div>
        <h4><?php echo htmlspecialchars($p['name']); ?></h4>
        <p>Head Prefect</p>
        <span>Class of <?php echo htmlspecialchars($p['year']); ?></span>
        <div class="prefect-badge">🎖️ Head Prefect</div>
      </div>
      <?php endforeach; ?>
    </div>

    <p class="prefects-section__note">
      * Replace placeholders with real Head Prefect names from school records.
    </p>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ALUMNI WALL
     ═══════════════════════════════════════════ -->
<section class="alumni-wall-section" id="alumni">
  <div class="alumni-wall-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Our Community</span>
        <h2 class="stitle">The IHS <span>Alumni Wall</span></h2>
        <p class="ssub">A growing directory of proud Ibeku High School alumni — wherever you are in the world, Ibeku is always home.</p>
      </div>
    </div>

    <div class="alumni-filter">
      <button class="alumni-filter-btn active">All</button>
      <button class="alumni-filter-btn">Medicine</button>
      <button class="alumni-filter-btn">Law</button>
      <button class="alumni-filter-btn">Engineering</button>
      <button class="alumni-filter-btn">Education</button>
      <button class="alumni-filter-btn">Business</button>
      <button class="alumni-filter-btn">Public Service</button>
    </div>

    <div class="alumni-grid">
      <?php
      /* Phase 2: replace with database query
         $alumni = $db->query("SELECT * FROM alumni ORDER BY name ASC"); */
      $alumni = [
        ['initials'=>'AO','name'=>'[Name]','year'=>'[Year]','field'=>'Medicine'],
        ['initials'=>'CE','name'=>'[Name]','year'=>'[Year]','field'=>'Law'],
        ['initials'=>'NG','name'=>'[Name]','year'=>'[Year]','field'=>'Engineering'],
        ['initials'=>'IO','name'=>'[Name]','year'=>'[Year]','field'=>'Education'],
        ['initials'=>'UA','name'=>'[Name]','year'=>'[Year]','field'=>'Business'],
        ['initials'=>'EM','name'=>'[Name]','year'=>'[Year]','field'=>'Public Service'],
        ['initials'=>'AC','name'=>'[Name]','year'=>'[Year]','field'=>'Medicine'],
        ['initials'=>'OI','name'=>'[Name]','year'=>'[Year]','field'=>'Engineering'],
        ['initials'=>'NK','name'=>'[Name]','year'=>'[Year]','field'=>'Law'],
        ['initials'=>'IC','name'=>'[Name]','year'=>'[Year]','field'=>'Business'],
        ['initials'=>'BE','name'=>'[Name]','year'=>'[Year]','field'=>'Education'],
        ['initials'=>'FO','name'=>'[Name]','year'=>'[Year]','field'=>'Medicine'],
        ['initials'=>'GC','name'=>'[Name]','year'=>'[Year]','field'=>'Public Service'],
        ['initials'=>'HA','name'=>'[Name]','year'=>'[Year]','field'=>'Engineering'],
        ['initials'=>'JN','name'=>'[Name]','year'=>'[Year]','field'=>'Business'],
      ];
      foreach ($alumni as $a): ?>
      <div class="alumni-card reveal">
        <div class="alumni-card__photo">
          <span class="alumni-card__initials"><?php echo htmlspecialchars($a['initials']); ?></span>
        </div>
        <h4><?php echo htmlspecialchars($a['name']); ?></h4>
        <span class="alumni-card__class">Class of <?php echo htmlspecialchars($a['year']); ?></span>
        <span class="alumni-tag"><?php echo htmlspecialchars($a['field']); ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="alumni-wall-section__cta">
      <a href="#nominate" class="btn btn--secondary">
        Are you an IHS Alumnus? Register Here →
      </a>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     NOMINATION FORM
     ═══════════════════════════════════════════ -->
<section class="nominate-section" id="nominate">
  <div class="nominate-section__inner">

    <span class="slabel slabel--gold">Preserve Our Legacy</span>
    <h2>Nominate Someone for the<br/><em>Hall of Fame</em></h2>
    <p>Know a distinguished IHS alumnus, a remarkable student, or a legendary teacher who deserves recognition? Submit a nomination and help us honour the people who have made Ibeku High School great.</p>

    <div class="nominate-form">
      <!--
        Phase 2: wrap in <form method="POST" action="<?php echo BASE_PATH; ?>src/api/submit_nomination.php">
      -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label form-label--light" for="nomYourName">Your Full Name</label>
          <input class="form-input form-input--dark" type="text" id="nomYourName" placeholder="Your full name"/>
        </div>
        <div class="form-group">
          <label class="form-label form-label--light" for="nomEmail">Your Email Address</label>
          <input class="form-input form-input--dark" type="email" id="nomEmail" placeholder="your@email.com"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label form-label--light" for="nomineeName">Nominee's Full Name</label>
          <input class="form-input form-input--dark" type="text" id="nomineeName" placeholder="Nominee's full name"/>
        </div>
        <div class="form-group">
          <label class="form-label form-label--light" for="nomineeYear">Nominee's Class Year</label>
          <input class="form-input form-input--dark" type="text" id="nomineeYear" placeholder="e.g. 2005"/>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label form-label--light" for="nomCategory">Category</label>
        <input class="form-input form-input--dark" type="text" id="nomCategory" placeholder="e.g. Academic, Sports, Public Service, Medicine"/>
      </div>
      <div class="form-group">
        <label class="form-label form-label--light" for="nomReason">Why do you nominate this person?</label>
        <textarea
          class="form-input form-input--dark"
          id="nomReason"
          rows="4"
          placeholder="Tell us about their achievements and what makes them deserving of Hall of Fame recognition..."
          style="resize:vertical;"
        ></textarea>
      </div>
      <button
        class="btn--nominate"
        onclick="alert('Nomination submitted! The Hall of Fame committee will review it.\n\nPhase 2: connects to PHP backend.')">
        Submit Nomination 🏆
      </button>
    </div>

  </div>
</section>


<?php
/* No page-specific JS needed for this page */
require_once '../src/includes/footer.php';
?>