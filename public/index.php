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
?>


<!-- ═══════════════════════════════════════════
     HERO CAROUSEL
     Controlled by assets/js/pages/home.js
     ═══════════════════════════════════════════ -->
<section class="hero" id="home" aria-label="School highlights">

  <!-- SLIDE 1 — School identity -->
  <div class="hero__slide hero__slide--1 active" aria-hidden="false">
    <!--
      ADD REAL PHOTO:
      <img class="hero__bg" src="/assets/images/hero/slide-1.jpg" alt="Ibeku High School students"/>
    -->
    <div class="hero__overlay"></div>
    <div class="hero__dots" aria-hidden="true"></div>
    <div class="hero__content">
      <div class="hero__badge">⭐ Est. 1954 · Government Secondary School · Umuahia</div>
      <h1>Shaping Minds.<br/>Building <em>Character.</em><br/>Raising Leaders.</h1>
      <p>Ibeku High School — where academic excellence and strong values have been forged together for over 70 years in Umuahia, Abia State.</p>
      <div class="hero__btns">
        <a href="/admissions.php" class="btn btn--primary btn--lg">Apply for Admission</a>
        <a href="/results.php"    class="btn btn--outline btn--lg">Check Results Online</a>
      </div>
    </div>
  </div>

  <!-- SLIDE 2 — Academic results -->
  <div class="hero__slide hero__slide--2" aria-hidden="true">
    <div class="hero__overlay"></div>
    <div class="hero__dots" aria-hidden="true"></div>
    <div class="hero__content">
      <div class="hero__badge">📚 Academic Excellence · WAEC · NECO · University Admissions</div>
      <h1>Consistently <em>Outstanding</em><br/>Examination Results.</h1>
      <p>Our students achieve top WAEC and NECO results year after year, securing admission to the best universities in Nigeria and beyond.</p>
      <div class="hero__btns">
        <a href="/academics.php" class="btn btn--primary btn--lg">Explore Academics</a>
        <a href="/about.php"     class="btn btn--outline btn--lg">Learn More About Us</a>
      </div>
    </div>
  </div>

  <!-- SLIDE 3 — Student life -->
  <div class="hero__slide hero__slide--3" aria-hidden="true">
    <div class="hero__overlay"></div>
    <div class="hero__dots" aria-hidden="true"></div>
    <div class="hero__content">
      <div class="hero__badge">🏆 Sports · Clubs · Competitions · ICT · Culture</div>
      <h1>Life Beyond the <em>Classroom</em><br/>at Ibeku High.</h1>
      <p>15+ active clubs, a modern computer lab, sports teams, cultural events — we develop every dimension of every student.</p>
      <div class="hero__btns">
        <a href="/admissions.php" class="btn btn--primary btn--lg">Join Our School</a>
        <a href="/contact.php"    class="btn btn--outline btn--lg">Contact the School</a>
      </div>
    </div>
  </div>

  <!-- Dot indicators — no prev/next arrows -->
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
    <a href="/results.php"    class="quick-link">
      <div class="quick-link__icon" aria-hidden="true">📊</div>
      <div class="quick-link__text">
        <strong>Check Results</strong>
        <span>Enter your student ID</span>
      </div>
    </a>
    <a href="/admissions.php" class="quick-link">
      <div class="quick-link__icon" aria-hidden="true">📝</div>
      <div class="quick-link__text">
        <strong>Admissions</strong>
        <span>Apply for 2025/2026</span>
      </div>
    </a>
    <a href="/news.php" class="quick-link">
      <div class="quick-link__icon" aria-hidden="true">📢</div>
      <div class="quick-link__text">
        <strong>News &amp; Events</strong>
        <span>Latest updates</span>
      </div>
    </a>
    <a href="/contact.php" class="quick-link">
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
        <!--
          REPLACE WITH REAL PHOTO:
          <img src="/assets/images/school-building.jpg" alt="Ibeku High School main building"/>
        -->
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
      <h2 class="stitle">About <span>Ibeku High School</span></h2>
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
          <p>Integrity · Excellence · Discipline · Community · Innovation.</p>
        </div>
        <div class="pillar">
          <h4>Academic Arms</h4>
          <p>Sciences · Arts · Commercial — 25+ subjects offered.</p>
        </div>
      </div>
      <a href="/about.php" class="btn btn--secondary">Read Full History</a>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     PRINCIPAL'S MESSAGE
     Homepage features the Senior Secondary Principal.
     JS Principal's message is on about.php.
     ═══════════════════════════════════════════ -->
<section class="principal-section" id="principal-ss">
  <div class="principal-section__inner wrap">

    <div class="principal-section__portrait reveal">
      <!--
        REPLACE WITH REAL SS PRINCIPAL PHOTO:
        <img src="/assets/images/staff/principal-ss.jpg" alt="Senior Secondary Principal"/>
      -->
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
        At Ibeku High School, we do not merely teach subjects — we shape futures.
        Every student who walks through our gates carries within them the potential
        to become a leader, a builder, a thinker. Our commitment is to help them
        discover that potential and develop it to its fullest through academic
        rigour, strong values, and a community of care that never gives up on
        any child.
      </blockquote>
      <!-- UPDATE: Replace with real SS Principal's name and title -->
      <p class="principal-section__sig">
        <strong>[SS Principal's Full Name]</strong>
        <span>Principal, Senior Secondary — Ibeku High School, Umuahia</span>
      </p>
      <p class="mt-3">
        <a href="/about.php#principal-js" class="btn btn--ghost">
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
      <a href="/academics.php" class="btn btn--ghost">View All Subjects</a>
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
        <input
          class="form-input form-input--dark"
          type="text"
          id="rcId"
          placeholder="e.g. IHS/2024/0421"
          autocomplete="off"
        />
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label form-label--light" for="rcClass">Class</label>
          <select class="form-input form-input--dark" id="rcClass">
            <option value="">Select class</option>
            <option>JSS 1</option>
            <option>JSS 2</option>
            <option>JSS 3</option>
            <option>SSS 1</option>
            <option>SSS 2</option>
            <option>SSS 3</option>
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

      <!-- checkResult() is defined in assets/js/pages/home.js -->
      <button class="btn btn--check" onclick="checkResult()">
        Check My Results &rarr;
      </button>

      <!-- Result output — shown/hidden by home.js -->
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
     STAFF PREVIEW
     ═══════════════════════════════════════════ -->
<section class="staff-section" id="staff">
  <div class="staff-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Our Team</span>
        <h2 class="stitle">Meet the <span>Staff</span></h2>
      </div>
      <a href="/academics.php#staff" class="btn btn--ghost">Full Staff Directory</a>
    </div>

    <div class="staff-grid">
      <!--
        UPDATE: Replace each card with real staff data.
        To add a photo replace .staff-card__initials with:
        <img src="/assets/images/staff/name.jpg" alt="Staff member name"/>
      -->
      <div class="staff-card reveal">
        <div class="staff-card__photo">
          <div class="staff-card__initials">SP</div>
        </div>
        <div class="staff-card__body">
          <h3>[SS Principal's Name]</h3>
          <p>SS Principal</p>
          <span>Senior Secondary</span>
        </div>
      </div>
      <div class="staff-card reveal">
        <div class="staff-card__photo">
          <div class="staff-card__initials">JP</div>
        </div>
        <div class="staff-card__body">
          <h3>[JS Principal's Name]</h3>
          <p>JS Principal</p>
          <span>Junior Secondary</span>
        </div>
      </div>
      <div class="staff-card reveal">
        <div class="staff-card__photo">
          <div class="staff-card__initials">HS</div>
        </div>
        <div class="staff-card__body">
          <h3>[HOD Sciences]</h3>
          <p>H.O.D Sciences</p>
          <span>Physics Dept.</span>
        </div>
      </div>
      <div class="staff-card reveal">
        <div class="staff-card__photo">
          <div class="staff-card__initials">IC</div>
        </div>
        <div class="staff-card__body">
          <h3>[ICT Coordinator]</h3>
          <p>ICT Coordinator</p>
          <span>Computer Studies</span>
        </div>
      </div>
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
      <a href="/news.php" class="btn btn--ghost">View All News</a>
    </div>

    <div class="news-grid">

      <article class="news-card news-card--featured reveal">
        <div class="news-card__thumb" style="height:200px" aria-hidden="true">🏆</div>
        <!--
          REPLACE WITH REAL IMAGE:
          <img class="news-card__thumb" style="height:200px;object-fit:cover"
               src="/assets/images/news/quiz-win.jpg" alt="IHS Science Quiz champions 2024"/>
        -->
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
     YOUTUBE VIDEOS
     ═══════════════════════════════════════════ -->
<section class="videos-section" id="videos">
  <div class="videos-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Watch &amp; Learn</span>
        <h2 class="stitle">School Life on <span>Video</span></h2>
      </div>
      <!-- UPDATE: Replace with real YouTube channel link -->
      <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="btn btn--ghost">
        Our YouTube Channel
      </a>
    </div>

    <div class="grid-3">

      <!--
        HOW TO ADD A REAL YOUTUBE VIDEO:
        1. Open the video on YouTube
        2. Click Share → Embed → copy the URL
           e.g. https://www.youtube.com/embed/VIDEO_ID
        3. Replace the .video-card__placeholder div with:
           <iframe
             src="https://www.youtube.com/embed/YOUR_VIDEO_ID"
             title="Video title"
             allow="accelerometer; autoplay; clipboard-write;
                    encrypted-media; gyroscope; picture-in-picture"
             allowfullscreen loading="lazy">
           </iframe>
      -->

      <div class="video-card reveal">
        <div class="video-card__embed">
          <div class="video-card__placeholder">
            <button class="video-card__play" aria-label="Play video">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
            </button>
            <p>School Assembly Highlights</p>
          </div>
        </div>
        <div class="video-card__body">
          <span class="video-card__tag">School Life</span>
          <h3>Morning Assembly &amp; School Culture</h3>
          <p>A glimpse into our daily assembly where discipline and community spirit are reinforced every morning.</p>
        </div>
      </div>

      <div class="video-card reveal">
        <div class="video-card__embed">
          <div class="video-card__placeholder">
            <button class="video-card__play" aria-label="Play video">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
            </button>
            <p>Science Competition Coverage</p>
          </div>
        </div>
        <div class="video-card__body">
          <span class="video-card__tag">Achievement</span>
          <h3>State Science Quiz Championship 2024</h3>
          <p>Watch our students compete and win the Abia State Science Quiz for the third consecutive year.</p>
        </div>
      </div>

      <div class="video-card reveal">
        <div class="video-card__embed">
          <div class="video-card__placeholder">
            <button class="video-card__play" aria-label="Play video">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
            </button>
            <p>Cultural Day &amp; Events</p>
          </div>
        </div>
        <div class="video-card__body">
          <span class="video-card__tag">Events</span>
          <h3>Annual Cultural Day Celebration</h3>
          <p>Highlights from our vibrant annual cultural day — showcasing the talent and pride of the IHS community.</p>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     TESTIMONIALS
     ═══════════════════════════════════════════ -->
<section class="testimonials-section" id="testimonials">
  <div class="testimonials-section__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel slabel--light">Voices from Our Community</span>
      <h2 class="stitle stitle--white">What They Say About <span style="color:var(--gold)">Ibeku High</span></h2>
    </div>

    <div class="grid-3">

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
      <!--
        Phase 2: wrap these inputs in:
        <form method="POST" action="/src/api/submit_admission.php">
        and replace the button onclick with a real form submit handler
      -->
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
      <button
        class="btn btn--primary btn--full btn--lg"
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
      <input
        class="form-input form-input--dark"
        type="email"
        id="nlEmail"
        placeholder="Enter your email address"
      />
      <!-- Phase 2: connect to /src/api/subscribe.php -->
      <button
        class="btn btn--primary"
        onclick="alert('Subscribed!\nPhase 2: connects to PHP backend.')">
        Subscribe
      </button>
    </div>
  </div>
</section>


<?php
$pageJs = 'home';
require_once '../src/includes/footer.php';
?>