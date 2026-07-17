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
require_once '../src/config/database.php';
$pdo = getDB();

/* ── Load all Hall of Fame entries by category ── */
$allEntries = $pdo->query(
    "SELECT * FROM hall_of_fame WHERE is_published = 1 ORDER BY sort_order ASC, full_name ASC"
)->fetchAll();

/* Group by category */
$byCategory = [
    'alumni'   => [],
    'academic' => [],
    'sports'   => [],
    'prefect'  => [],
    'staff'    => [],
];
foreach ($allEntries as $entry) {
    $cat = $entry['category'];
    if (isset($byCategory[$cat])) {
        $byCategory[$cat][] = $entry;
    }
}

/* ── Load all published alumni for the alumni wall ── */
$alumniWall = $pdo->query(
    "SELECT * FROM alumni WHERE is_published = 1 ORDER BY sort_order ASC, full_name ASC"
)->fetchAll();

/* Get unique fields for filter buttons */
$alumniFields = [];
foreach ($alumniWall as $a) {
    if ($a['field'] && !in_array($a['field'], $alumniFields, true)) {
        $alumniFields[] = $a['field'];
    }
}

/* ── Stats ── */
$totalInductees = count($allEntries);
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO — GOLD THEMED
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--hof<?php echo getInnerHeroImage('hall_of_fame') ? ' page-hero--photo' : ''; ?>"<?php echo renderInnerHeroStyle('hall_of_fame'); ?>>

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
        <span class="hof-hero__stat-num"><?php echo $totalInductees > 0 ? $totalInductees . '+' : '150+'; ?></span>
        <span class="hof-hero__stat-lbl">Inductees</span>
      </div>
      <div>
        <span class="hof-hero__stat-num">1954</span>
        <span class="hof-hero__stat-lbl">Since</span>
      </div>
      <div>
        <span class="hof-hero__stat-num"><?php echo count(array_filter($byCategory, fn($c) => !empty($c))); ?></span>
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
     DISTINGUISHED ALUMNI — from hall_of_fame table
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

    <?php if (empty($byCategory['alumni'])): ?>
    <p style="color:rgba(255,255,255,.5);text-align:center;padding:40px 0">
      Distinguished alumni inductees will appear here once added by the administrator.
    </p>
    <?php else: ?>
    <div class="featured-grid">
      <?php foreach ($byCategory['alumni'] as $e): ?>
      <div class="featured-card reveal">
        <span class="featured-card__crown" aria-hidden="true">👑</span>
        <div class="featured-card__photo">
          <?php if (!empty($e['photo'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($e['photo']); ?>"
               alt="<?php echo htmlspecialchars($e['full_name']); ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <span class="featured-card__initials" style="display:none">
            <?php echo strtoupper(substr($e['full_name'], 0, 2)); ?>
          </span>
          <?php else: ?>
          <span class="featured-card__initials">
            <?php echo strtoupper(substr($e['full_name'], 0, 2)); ?>
          </span>
          <?php endif; ?>
        </div>
        <h3><?php echo htmlspecialchars($e['full_name']); ?></h3>
        <?php if ($e['class_year']): ?>
        <span class="featured-card__class">Class of <?php echo htmlspecialchars($e['class_year']); ?></span>
        <?php endif; ?>
        <?php if ($e['field']): ?>
        <span class="featured-card__field"><?php echo htmlspecialchars($e['field']); ?></span>
        <?php endif; ?>
        <p><?php echo htmlspecialchars($e['achievement']); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ACADEMIC STARS — from hall_of_fame table
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

    <?php
    $medalTops = ['star-card__top--gold','star-card__top--silver','star-card__top--bronze','star-card__top--blue'];
    $medals    = ['🥇','🥈','🥉','⭐'];
    ?>

    <?php if (empty($byCategory['academic'])): ?>
    <p style="color:#6b6b80;text-align:center;padding:40px 0">
      Academic star inductees will appear here once added by the administrator.
    </p>
    <?php else: ?>
    <div class="academic-stars-grid">
      <?php foreach ($byCategory['academic'] as $i => $e): ?>
      <div class="star-card reveal">
        <div class="star-card__top <?php echo $medalTops[$i % 4]; ?>">
          <span class="star-card__medal" aria-hidden="true"><?php echo $medals[$i % 4]; ?></span>
          <div class="star-card__photo">
            <?php if (!empty($e['photo'])): ?>
            <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($e['photo']); ?>"
                 alt="<?php echo htmlspecialchars($e['full_name']); ?>"
                 style="width:100%;height:100%;object-fit:cover;border-radius:50%"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
            <span class="star-card__photo-initials" style="display:none">
              <?php echo strtoupper(substr($e['full_name'], 0, 2)); ?>
            </span>
            <?php else: ?>
            <span class="star-card__photo-initials">
              <?php echo strtoupper(substr($e['full_name'], 0, 2)); ?>
            </span>
            <?php endif; ?>
          </div>
          <h4><?php echo htmlspecialchars($e['full_name']); ?></h4>
          <?php if ($e['class_year']): ?>
          <span>Class of <?php echo htmlspecialchars($e['class_year']); ?></span>
          <?php endif; ?>
        </div>
        <div class="star-card__body">
          <?php if ($e['field']): ?>
          <span class="star-card__subject"><?php echo htmlspecialchars($e['field']); ?></span>
          <?php endif; ?>
          <p><?php echo htmlspecialchars($e['achievement']); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     SPORTS CHAMPIONS — from hall_of_fame table
     ═══════════════════════════════════════════ -->
<section class="sports-section" id="sports">
  <div class="sports-section__inner wrap">

    <span class="slabel">Athletic Excellence</span>
    <h2 class="stitle">Sports <span style="color:var(--blue-light)">Champions</span></h2>
    <p style="color:rgba(255,255,255,.7);max-width:560px;line-height:1.8;margin-top:10px">
      Teams and individuals who have brought sporting glory to Ibeku High School at the state, zonal, and national levels.
    </p>

    <?php if (empty($byCategory['sports'])): ?>
    <p style="color:rgba(255,255,255,.5);text-align:center;padding:40px 0">
      Sports champion inductees will appear here once added by the administrator.
    </p>
    <?php else: ?>
    <div class="sports-grid">
      <?php foreach ($byCategory['sports'] as $e): ?>
      <div class="sports-card reveal">
        <span class="sports-card__icon" aria-hidden="true">
          <?php echo !empty($e['field']) ? htmlspecialchars($e['field']) : '🏆'; ?>
        </span>
        <h3><?php echo htmlspecialchars($e['full_name']); ?></h3>
        <?php if ($e['class_year']): ?>
        <span class="sports-card__year">Class of <?php echo htmlspecialchars($e['class_year']); ?></span>
        <?php endif; ?>
        <p><?php echo htmlspecialchars($e['achievement']); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     HEAD PREFECTS HALL — from hall_of_fame table
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

    <?php if (empty($byCategory['prefect'])): ?>
    <p style="color:#6b6b80;text-align:center;padding:40px 0">
      Head Prefect inductees will appear here once added by the administrator.
    </p>
    <?php else: ?>
    <div class="prefects-grid">
      <?php foreach ($byCategory['prefect'] as $e): ?>
      <div class="prefect-card reveal">
        <div class="prefect-card__photo">
          <?php if (!empty($e['photo'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($e['photo']); ?>"
               alt="<?php echo htmlspecialchars($e['full_name']); ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <span class="prefect-card__initials" style="display:none">
            <?php echo strtoupper(substr($e['full_name'], 0, 1)); ?>
          </span>
          <?php else: ?>
          <span class="prefect-card__initials">
            <?php echo strtoupper(substr($e['full_name'], 0, 1)); ?>
          </span>
          <?php endif; ?>
        </div>
        <h4><?php echo htmlspecialchars($e['full_name']); ?></h4>
        <p><?php echo htmlspecialchars($e['field'] ?: 'Head Prefect'); ?></p>
        <?php if ($e['class_year']): ?>
        <span>Class of <?php echo htmlspecialchars($e['class_year']); ?></span>
        <?php endif; ?>
        <div class="prefect-badge">🎖️ Head Prefect</div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ALUMNI WALL — from alumni table
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

    <?php if (!empty($alumniFields)): ?>
    <div class="alumni-filter">
      <button class="alumni-filter-btn active" data-filter="all">All</button>
      <?php foreach ($alumniFields as $f): ?>
      <button class="alumni-filter-btn" data-filter="<?php echo htmlspecialchars($f); ?>">
        <?php echo htmlspecialchars($f); ?>
      </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($alumniWall)): ?>
    <p style="color:#6b6b80;text-align:center;padding:40px 0">
      Alumni profiles will appear here once added by the administrator.
    </p>
    <?php else: ?>
    <div class="alumni-grid" id="alumniWallGrid">
      <?php foreach ($alumniWall as $a): ?>
      <div class="alumni-card reveal"
           data-field="<?php echo htmlspecialchars($a['field'] ?? ''); ?>">
        <div class="alumni-card__photo">
          <?php if (!empty($a['photo'])): ?>
          <img src="<?php echo BASE_PATH; ?>assets/images/staff/<?php echo htmlspecialchars($a['photo']); ?>"
               alt="<?php echo htmlspecialchars($a['full_name']); ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <span class="alumni-card__initials" style="display:none">
            <?php echo strtoupper(substr($a['full_name'], 0, 2)); ?>
          </span>
          <?php else: ?>
          <span class="alumni-card__initials">
            <?php echo strtoupper(substr($a['full_name'], 0, 2)); ?>
          </span>
          <?php endif; ?>
        </div>
        <h4><?php echo htmlspecialchars($a['full_name']); ?></h4>
        <?php if ($a['class_year']): ?>
        <span class="alumni-card__class">Class of <?php echo htmlspecialchars($a['class_year']); ?></span>
        <?php endif; ?>
        <?php if ($a['field']): ?>
        <span class="alumni-tag"><?php echo htmlspecialchars($a['field']); ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="alumni-wall-section__cta">
      <a href="#nominate" class="btn btn--secondary">
        Are you an IHS Alumnus? Register Here →
      </a>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     NOMINATION FORM — posts to API
     ═══════════════════════════════════════════ -->
<section class="nominate-section" id="nominate">
  <div class="nominate-section__inner">

    <span class="slabel slabel--gold">Preserve Our Legacy</span>
    <h2>Nominate Someone for the<br/><em>Hall of Fame</em></h2>
    <p>Know a distinguished IHS alumnus, a remarkable student, or a legendary teacher who deserves recognition? Submit a nomination and help us honour the people who have made Ibeku High School great.</p>

    <!-- Success message — shown by JS after submit -->
    <div id="nomSuccess" style="display:none;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:20px 24px;margin-bottom:20px;color:#fff;text-align:center">
      <div style="font-size:28px;margin-bottom:8px">🏆</div>
      <strong style="font-size:16px">Nomination Received!</strong>
      <p style="margin-top:8px;color:rgba(255,255,255,.8);font-size:13.5px" id="nomSuccessMsg">
        Thank you! Your nomination has been received and will be reviewed by the Hall of Fame committee.
      </p>
    </div>

    <div class="nominate-form" id="nomFormWrapper">

      <!-- Honeypot field — hidden from real users, catches bots -->
      <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off"/>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label form-label--light" for="nomYourName">Your Full Name *</label>
          <input class="form-input form-input--dark" type="text" id="nomYourName"
                 placeholder="Your full name" required/>
        </div>
        <div class="form-group">
          <label class="form-label form-label--light" for="nomEmail">Your Email Address *</label>
          <input class="form-input form-input--dark" type="email" id="nomEmail"
                 placeholder="your@email.com" required/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label form-label--light" for="nomineeName">Nominee's Full Name *</label>
          <input class="form-input form-input--dark" type="text" id="nomineeName"
                 placeholder="Nominee's full name" required/>
        </div>
        <div class="form-group">
          <label class="form-label form-label--light" for="nomineeYear">Nominee's Class Year</label>
          <input class="form-input form-input--dark" type="text" id="nomineeYear"
                 placeholder="e.g. 2005"/>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label form-label--light" for="nomCategory">Category</label>
        <select class="form-input form-input--dark" id="nomCategory">
          <option value="">Select a category</option>
          <option value="alumni">Distinguished Alumni</option>
          <option value="academic">Academic Star</option>
          <option value="sports">Sports Champion</option>
          <option value="prefect">Head Prefect</option>
          <option value="staff">Legendary Staff</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label form-label--light" for="nomReason">Why do you nominate this person? *</label>
        <textarea class="form-input form-input--dark" id="nomReason" rows="4" required
                  placeholder="Tell us about their achievements and what makes them deserving of Hall of Fame recognition..."
                  style="resize:vertical;"></textarea>
      </div>

      <button class="btn--nominate" id="nomBtn" onclick="submitNomination()">
        Submit Nomination 🏆
      </button>

      <div id="nomError" style="display:none;margin-top:12px;background:rgba(204,51,51,.2);border:1px solid rgba(204,51,51,.4);border-radius:8px;padding:10px 14px;color:#ffaaaa;font-size:13px"></div>

    </div>

  </div>
</section>


<?php require_once '../src/includes/footer.php'; ?>


<script>
/* ── Alumni wall field filter ── */
(function () {
  var filterBtns = document.querySelectorAll('.alumni-filter-btn');
  if (!filterBtns.length) return;

  filterBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      filterBtns.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');

      var filter = btn.dataset.filter || 'all';
      document.querySelectorAll('#alumniWallGrid .alumni-card').forEach(function (card) {
        if (filter === 'all' || card.dataset.field === filter) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
}());


/* ── Nomination form submission ── */
function submitNomination() {
  var nomYourName  = document.getElementById('nomYourName').value.trim();
  var nomEmail     = document.getElementById('nomEmail').value.trim();
  var nomineeName  = document.getElementById('nomineeName').value.trim();
  var nomineeYear  = document.getElementById('nomineeYear').value.trim();
  var nomCategory  = document.getElementById('nomCategory').value;
  var nomReason    = document.getElementById('nomReason').value.trim();
  var errorEl      = document.getElementById('nomError');
  var btn          = document.getElementById('nomBtn');

  errorEl.style.display = 'none';

  if (!nomYourName || !nomEmail || !nomineeName || !nomReason) {
    errorEl.textContent = 'Please fill in all required fields.';
    errorEl.style.display = 'block';
    return;
  }

  var formData = new FormData();
  formData.append('nominator_name',    nomYourName);
  formData.append('nominator_email',   nomEmail);
  formData.append('nominee_name',      nomineeName);
  formData.append('nominee_class_year',nomineeYear);
  formData.append('category',          nomCategory);
  formData.append('reason',            nomReason);
  formData.append('website',           ''); /* honeypot */

  btn.textContent = 'Submitting…';
  btn.disabled    = true;

  fetch('<?php echo API_PATH; ?>submit_nomination.php', {
    method: 'POST',
    body: formData,
  })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        document.getElementById('nomSuccessMsg').textContent = data.message;
        document.getElementById('nomSuccess').style.display = 'block';
        document.getElementById('nomFormWrapper').style.display = 'none';
      } else if (data.errors) {
        var first = Object.values(data.errors)[0];
        errorEl.textContent = first;
        errorEl.style.display = 'block';
        btn.textContent = 'Submit Nomination 🏆';
        btn.disabled = false;
      } else {
        errorEl.textContent = data.message || 'Something went wrong. Please try again.';
        errorEl.style.display = 'block';
        btn.textContent = 'Submit Nomination 🏆';
        btn.disabled = false;
      }
    })
    .catch(function (err) {
      console.error('Nomination error:', err);
      errorEl.textContent = 'A connection error occurred. Please try again.';
      errorEl.style.display = 'block';
      btn.textContent = 'Submit Nomination 🏆';
      btn.disabled = false;
    });
}
</script>