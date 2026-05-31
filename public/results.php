<?php
/* ============================================================
   IBEKU HIGH SCHOOL — RESULTS PAGE
   File: public/results.php
   ============================================================ */

$pageTitle   = 'Check Results — Ibeku High School';
$pageDesc    = 'Check your academic results online. Enter your admission number to view your term results, subject scores, grades, and class position.';
$currentPage = 'students';
$pageCss     = 'results';
$pageJs      = 'results';

require_once '../src/includes/header.php';
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--results">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <a href="<?php echo BASE_PATH; ?>students.php">Students</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">Check Results</span>
    </nav>
    <h1>Check Your <em>Results</em><br/>Online</h1>
    <p>Enter your Admission Number to view your academic results — subject scores, grades, class position, and remarks — for any examination term.</p>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     MAIN RESULT CHECKER
     ═══════════════════════════════════════════ -->
<section class="checker-section" id="checker">
  <div class="checker-section__inner wrap">

    <!-- Left: instructions -->
    <div class="checker-section__info reveal">
      <span class="slabel">Student Portal</span>
      <h2>Find Your <span>Results</span></h2>
      <p>Ibeku High School results are now available online. Use the form to the right to check your result for any published term. Your Admission Number is printed on your school ID card and fee receipt.</p>

      <div class="checker-steps">
        <div class="checker-step">
          <div class="checker-step__num">1</div>
          <div class="checker-step__text">
            <h4>Enter Your Admission Number</h4>
            <p>Type your full admission number exactly as it appears on your school ID — e.g. IHS/2024/0421</p>
          </div>
        </div>
        <div class="checker-step">
          <div class="checker-step__num">2</div>
          <div class="checker-step__text">
            <h4>Select Your Class</h4>
            <p>Choose the class you were in during the term you want to check — JSS 1 through SSS 3.</p>
          </div>
        </div>
        <div class="checker-step">
          <div class="checker-step__num">3</div>
          <div class="checker-step__text">
            <h4>Select the Term</h4>
            <p>Choose the examination term — First, Second, or Third Term — and the academic session.</p>
          </div>
        </div>
        <div class="checker-step">
          <div class="checker-step__num">4</div>
          <div class="checker-step__text">
            <h4>View and Print</h4>
            <p>Your result will appear instantly. You can print your result slip directly from this page.</p>
          </div>
        </div>
      </div>

      <!-- Demo IDs -->
      <div class="checker-demo-ids">
        <p><strong>Try the demo</strong> — click an ID below to test the checker:</p>
        <div class="checker-demo-ids__ids">
          <button class="demo-id" onclick="fillDemo('IHS/2024/0421')">IHS/2024/0421</button>
          <button class="demo-id" onclick="fillDemo('IHS/2024/0105')">IHS/2024/0105</button>
        </div>
      </div>
    </div>

    <!-- Right: checker form -->
    <div class="checker-card--full reveal">
      <h3>View My Results</h3>
      <p>Enter your details below and click Check Results.</p>

      <div class="form-group">
        <label class="form-label" for="rcId">Admission Number</label>
        <input
          class="form-input"
          type="text"
          id="rcId"
          placeholder="e.g. IHS/2024/0421"
          autocomplete="off"
          style="text-transform:uppercase"
        />
      </div>

      <div class="form-row" style="margin-bottom:0">
        <div class="form-group">
          <label class="form-label" for="rcClass">Class</label>
          <select class="form-input" id="rcClass">
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
          <label class="form-label" for="rcTerm">Term</label>
          <select class="form-input" id="rcTerm">
            <option value="">Select term</option>
            <option>First Term 2024/2025</option>
            <option>Second Term 2024/2025</option>
            <option>Third Term 2023/2024</option>
            <option>Second Term 2023/2024</option>
            <option>First Term 2023/2024</option>
          </select>
        </div>
      </div>

      <button class="btn--check-full" id="checkBtn" onclick="checkResultFull()">
        Check My Results &rarr;
      </button>

      <!-- Result output panel -->
      <div class="result-panel" id="rcPanel" aria-live="polite">
        <div class="result-panel__header">
          <h4 id="rcPanelName">Student Name</h4>
          <span class="result-panel__term" id="rcPanelTerm">Term</span>
        </div>
        <div class="result-panel__meta" id="rcPanelMeta"></div>
        <div class="result-panel__subjects" id="rcPanelSubjects"></div>
        <div class="result-panel__footer">
          <p>Ibeku High School, Umuahia &mdash; Official Result</p>
          <button
            class="btn btn--ghost btn--sm"
            onclick="printResult()"
            style="padding:6px 16px;font-size:12px">
            🖨 Print Result Slip
          </button>
        </div>
      </div>

      <!-- Not found message -->
      <div class="result-not-found--full" id="rcNotFound" role="alert">
        <p>No results found</p>
        <span>
          Please check your Admission Number and try again.
          If the problem continues, visit the school office.
        </span>
      </div>

      <!-- ═══════════════════════════════════════════
           PRINTABLE RESULT SHEET
           Hidden on screen — rendered on print only.
           Populated by results.js printResult()
           ═══════════════════════════════════════════ -->
      <div class="result-sheet" id="resultSheet">

        <!-- Header -->
        <div class="rs-header">
          <div class="rs-header__logo">IHS</div>
          <div class="rs-header__school">Ibeku High School</div>
          <div class="rs-header__address">
            Umuahia, Abia State, Nigeria &nbsp;|&nbsp;
            Tel: +234 000 000 0000 &nbsp;|&nbsp;
            info@ibekuhighschool.edu.ng
          </div>
          <div class="rs-header__title" id="rsPrintTitle">Student Academic Report</div>
        </div>

        <!-- Student info -->
        <div class="rs-info">
          <div class="rs-info__item">
            <span class="rs-info__label">Name:</span>
            <span class="rs-info__value" id="rsPrintName"></span>
          </div>
          <div class="rs-info__item">
            <span class="rs-info__label">Admission No.:</span>
            <span class="rs-info__value" id="rsPrintAdmNo"></span>
          </div>
          <div class="rs-info__item">
            <span class="rs-info__label">Class:</span>
            <span class="rs-info__value" id="rsPrintClass"></span>
          </div>
          <div class="rs-info__item">
            <span class="rs-info__label">Session:</span>
            <span class="rs-info__value" id="rsPrintSession"></span>
          </div>
          <div class="rs-info__item">
            <span class="rs-info__label">Term:</span>
            <span class="rs-info__value" id="rsPrintTerm"></span>
          </div>
          <div class="rs-info__item">
            <span class="rs-info__label">No. in Class:</span>
            <span class="rs-info__value" id="rsPrintTotal"></span>
          </div>
        </div>

        <!-- Subject table -->
        <table class="rs-table">
          <thead>
            <tr>
              <th style="text-align:left;width:28%">Subject</th>
              <th class="rs-table__score-header">1st Test<br/><small>(15)</small></th>
              <th class="rs-table__score-header">2nd Test<br/><small>(15)</small></th>
              <th class="rs-table__score-header">Exam<br/><small>(70)</small></th>
              <th>Total<br/><small>(100)</small></th>
              <th>Grade</th>
              <th>Remark</th>
            </tr>
          </thead>
          <tbody id="rsPrintSubjects"></tbody>
        </table>

        <!-- Summary -->
        <div class="rs-summary">
          <div class="rs-summary__item">
            <span class="rs-summary__label">Position in Class</span>
            <span class="rs-summary__value" id="rsPrintPosition"></span>
          </div>
          <div class="rs-summary__item">
            <span class="rs-summary__label">Total Score</span>
            <span class="rs-summary__value" id="rsPrintTotalScore"></span>
          </div>
          <div class="rs-summary__item">
            <span class="rs-summary__label">Average</span>
            <span class="rs-summary__value" id="rsPrintAvg"></span>
          </div>
          <div class="rs-summary__item">
            <span class="rs-summary__label">No. of Subjects</span>
            <span class="rs-summary__value" id="rsPrintSubjCount"></span>
          </div>
        </div>

        <!-- Comments -->
        <div class="rs-comments">
          <div class="rs-comment-box">
            <span class="rs-comment-box__label">Form Teacher's Comment</span>
            <div class="rs-comment-box__line"></div>
            <div class="rs-comment-box__line"></div>
            <div class="rs-comment-box__sig">
              <span>Signature: _______________</span>
              <span>Date: _______________</span>
            </div>
          </div>
          <div class="rs-comment-box">
            <span class="rs-comment-box__label">Principal's Comment</span>
            <div class="rs-comment-box__line"></div>
            <div class="rs-comment-box__line"></div>
            <div class="rs-comment-box__sig">
              <span>Signature: _______________</span>
              <span>Date: _______________</span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="rs-footer">
          <div class="rs-footer__resumption">
            <strong>Next Term Resumption</strong>
            <span id="rsPrintResumption"></span>
          </div>
          <div class="rs-footer__stamp">
            SCHOOL<br/>STAMP
          </div>
          <div class="rs-footer__generated">
            <strong>Generated</strong>
            <span id="rsPrintDate"></span>
          </div>
        </div>

        <div class="rs-watermark">
          This result slip is computer-generated. For an official stamped copy, contact the school office.
          &nbsp;|&nbsp; Ibeku High School, Umuahia, Abia State
        </div>

      </div><!-- end .result-sheet -->

    </div><!-- end .checker-card--full -->

  </div>
</section>


<!-- ═══════════════════════════════════════════
     HOW IT WORKS
     ═══════════════════════════════════════════ -->
<section class="how-it-works" id="how">
  <div class="how-it-works__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel">Simple &amp; Fast</span>
      <h2 class="stitle">How the Result <span>Checker Works</span></h2>
    </div>

    <div class="how-grid">
      <div class="how-card reveal">
        <div class="how-card__num">1</div>
        <span class="how-card__icon" aria-hidden="true">🪪</span>
        <h3>Enter Your ID</h3>
        <p>Type your Admission Number from your school ID card or fee receipt into the checker above.</p>
      </div>
      <div class="how-card reveal">
        <div class="how-card__num">2</div>
        <span class="how-card__icon" aria-hidden="true">🎓</span>
        <h3>Select Class &amp; Term</h3>
        <p>Choose your class level and the examination term you want to view results for.</p>
      </div>
      <div class="how-card reveal">
        <div class="how-card__num">3</div>
        <span class="how-card__icon" aria-hidden="true">📊</span>
        <h3>View Your Results</h3>
        <p>Your subject scores, grades, class position, and remarks appear instantly on screen.</p>
      </div>
      <div class="how-card reveal">
        <div class="how-card__num">4</div>
        <span class="how-card__icon" aria-hidden="true">🖨️</span>
        <h3>Print Your Slip</h3>
        <p>Use the Print button to print your result slip directly from the browser — no downloads needed.</p>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     GRADING SYSTEM
     ═══════════════════════════════════════════ -->
<section class="grading-section" id="grading">
  <div class="grading-section__inner wrap">

    <div class="reveal">
      <span class="slabel">Academic Standards</span>
      <h2 class="stitle">Grading <span>System</span></h2>
      <p class="ssub">Ibeku High School uses the standard Nigerian secondary school grading system for WAEC and internal examinations.</p>

      <div class="grade-table">
        <div class="grade-table__header">
          <span>Grade</span>
          <span>Score Range</span>
          <span>Remark</span>
          <span>Meaning</span>
        </div>
        <?php
        $grades = [
          ['A1', '75 – 100', 'Excellent', 'Outstanding performance'],
          ['B2', '70 – 74',  'Very Good', 'Above average performance'],
          ['B3', '65 – 69',  'Good',      'Good performance'],
          ['C4', '60 – 64',  'Credit',    'Satisfactory performance'],
          ['C5', '55 – 59',  'Credit',    'Satisfactory performance'],
          ['C6', '50 – 54',  'Credit',    'Pass — minimum for credit'],
          ['D7', '45 – 49',  'Pass',      'Below average — pass only'],
          ['E8', '40 – 44',  'Pass',      'Minimum pass grade'],
          ['F9', '0 – 39',   'Fail',      'Below minimum — failed'],
        ];
        foreach ($grades as $g):
          $letter = $g[0][0];
        ?>
        <div class="grade-row">
          <span><strong class="grade-badge grade-badge--<?php echo $letter; ?>"><?php echo $g[0]; ?></strong></span>
          <span><?php echo $g[1]; ?></span>
          <span><?php echo $g[2]; ?></span>
          <span><?php echo $g[3]; ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="reveal">
      <span class="slabel">Important Notes</span>
      <h2 class="stitle">Result <span>Information</span></h2>
      <div class="grading-notes">
        <div class="grading-note">
          <span class="grading-note__icon" aria-hidden="true">📋</span>
          <div class="grading-note__text">
            <h4>When Are Results Published?</h4>
            <p>Results are uploaded by subject teachers and approved by the Vice Principal (Academics) after each examination. They are typically available within two weeks of the end of examinations.</p>
          </div>
        </div>
        <div class="grading-note">
          <span class="grading-note__icon" aria-hidden="true">🔒</span>
          <div class="grading-note__text">
            <h4>Result Privacy</h4>
            <p>Results are tied to your unique Admission Number. Only someone with your Admission Number can view your results. Keep your ID card secure.</p>
          </div>
        </div>
        <div class="grading-note">
          <span class="grading-note__icon" aria-hidden="true">❓</span>
          <div class="grading-note__text">
            <h4>Result Not Found?</h4>
            <p>If your result is not found, either it has not yet been published or your Admission Number was entered incorrectly. Contact the school office for assistance.</p>
          </div>
        </div>
        <div class="grading-note">
          <span class="grading-note__icon" aria-hidden="true">🖨️</span>
          <div class="grading-note__text">
            <h4>Official Result Slips</h4>
            <p>The printed result from this website is for reference only. Official stamped result slips are available from the school office on request.</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     FAQ
     ═══════════════════════════════════════════ -->
<section class="faq-section" id="faq">
  <div class="faq-section__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel">Common Questions</span>
      <h2 class="stitle">Frequently Asked <span>Questions</span></h2>
    </div>

    <div class="faq-list" id="faqList">
      <?php
      $faqs = [
        [
          'q' => 'Where do I find my Admission Number?',
          'a' => 'Your Admission Number is printed on your school ID card, your fee payment receipt, and on any official correspondence from the school. It follows the format IHS/YEAR/NUMBER — for example, IHS/2024/0421. If you cannot find it, visit the school office with your name and class.',
        ],
        [
          'q' => 'My result is not showing — what should I do?',
          'a' => 'There are two common reasons: (1) Your result has not yet been published for that term — results are typically available two weeks after examinations end. (2) Your Admission Number was entered incorrectly — check for typos and ensure it is in the format IHS/YEAR/NUMBER. If neither applies, visit the school office.',
        ],
        [
          'q' => 'Can I check results for previous terms and years?',
          'a' => 'Yes. Use the Term dropdown to select any published term. Results are available for all terms since the online system was launched. For results from before the system was introduced, contact the school office.',
        ],
        [
          'q' => 'Is the printed result from this website official?',
          'a' => 'The result displayed on this website is accurate and drawn directly from the school database. However, for official purposes — such as university applications or employment — you should request a stamped and signed result slip from the school office.',
        ],
        [
          'q' => 'Why is my score different from what my teacher told me?',
          'a' => 'If you notice a discrepancy between your online result and what was communicated to you, contact your form teacher or the school office immediately. Results can be corrected by the subject teacher through the admin panel before final publication.',
        ],
        [
          'q' => 'Can my parents check my result?',
          'a' => 'Yes. Any person with your Admission Number can view your result. If you would like your parents to check your results, share your Admission Number with them.',
        ],
      ];
      foreach ($faqs as $i => $faq): ?>
      <div class="faq-item reveal" id="faq-<?php echo $i; ?>">
        <button
          class="faq-item__question"
          onclick="toggleFaq(<?php echo $i; ?>)"
          aria-expanded="false"
          aria-controls="faq-answer-<?php echo $i; ?>">
          <span><?php echo htmlspecialchars($faq['q']); ?></span>
          <span class="faq-item__icon" aria-hidden="true">+</span>
        </button>
        <div class="faq-item__answer" id="faq-answer-<?php echo $i; ?>">
          <p><?php echo htmlspecialchars($faq['a']); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<?php
$pageJs = 'results';
require_once '../src/includes/footer.php';
?>