<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMISSIONS PAGE
   File: public/admissions.php
   ============================================================ */

$pageTitle   = 'Admissions — Ibeku High School, Umuahia';
$pageDesc    = 'Apply for admission to Ibeku High School. Learn about entry requirements for JSS 1 and SSS 1, how to apply, fees, and important dates.';
$currentPage = 'admissions';
$pageCss     = 'admissions';

require_once '../src/includes/header.php';

$_site = getSettings();

if ($_site['admissions_open'] !== '1'):
?>
<div style="max-width:600px;margin:80px auto;text-align:center;padding:40px 24px;background:#fff;border:1px solid #e8e6f0;border-radius:16px;font-family:'DM Sans',sans-serif">
  <div style="font-size:48px;margin-bottom:16px">📋</div>
  <h2 style="color:#3d1a6e;font-family:'Playfair Display',serif;margin-bottom:12px">Admissions Currently Closed</h2>
  <p style="color:#6b6b80;font-size:15px;line-height:1.6">
    We are not currently accepting new applications. Please check back during the next admissions window or contact the school office to register your interest.
  </p>
  <div style="margin-top:20px;display:flex;flex-direction:column;gap:8px;align-items:center;font-size:14px;color:#6b6b80">
    <span>📞 <?php echo htmlspecialchars($_site['school_phone']); ?></span>
    <span>✉ <?php echo htmlspecialchars($_site['school_email']); ?></span>
    <span>📍 <?php echo htmlspecialchars($_site['school_address']); ?></span>
  </div>
  <p style="margin-top:16px">
    <a href="<?php echo BASE_PATH; ?>contact.php" style="color:#4a90d9">Contact the school office →</a>
  </p>
</div>
<?php
require_once '../src/includes/footer.php';
exit;
endif;
?>


<!-- ═══════════════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════════════ -->
<div class="page-hero page-hero--admissions">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">›</span>
      <span style="color:rgba(255,255,255,.85)">Admissions</span>
    </nav>
    <h1>Join <em>Ibeku High School</em><br/>Umuahia</h1>
    <p>We welcome applications for JSS 1 and SSS 1 entry each academic session. Discover what makes Ibeku High School the right choice for your child's secondary education.</p>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     OVERVIEW STATS
     ═══════════════════════════════════════════ -->
<div class="admissions-overview">
  <div class="admissions-overview__grid wrap">
    <div class="overview-stat reveal">
      <span class="overview-stat__num">2,400+</span>
      <span class="overview-stat__lbl">Current Students</span>
    </div>
    <div class="overview-stat reveal">
      <span class="overview-stat__num">98%</span>
      <span class="overview-stat__lbl">WAEC Pass Rate</span>
    </div>
    <div class="overview-stat reveal">
      <span class="overview-stat__num">70+</span>
      <span class="overview-stat__lbl">Years of Excellence</span>
    </div>
    <div class="overview-stat reveal">
      <span class="overview-stat__num">120+</span>
      <span class="overview-stat__lbl">Qualified Staff</span>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     PAGE ANCHOR NAV
     ═══════════════════════════════════════════ -->
<div class="page-anchors">
  <div class="page-anchors__inner wrap">
    <a href="#why"          class="page-anchor active">Why Ibeku</a>
    <a href="#requirements" class="page-anchor">Requirements</a>
    <a href="#how-to-apply" class="page-anchor">How to Apply</a>
    <a href="#fees"         class="page-anchor">Fees &amp; Dates</a>
    <a href="#apply-now"    class="page-anchor">Apply Now</a>
    <a href="#faq"          class="page-anchor">FAQ</a>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     WHY JOIN IBEKU
     ═══════════════════════════════════════════ -->
<section class="why-section" id="why">
  <div class="why-section__inner wrap">

    <div class="why-grid">
      <div class="why-card reveal">
        <span class="why-card__icon" aria-hidden="true">🏆</span>
        <h3>Academic Excellence</h3>
        <p>Consistently high WAEC and NECO results. Three consecutive Abia State Science Quiz Championship titles.</p>
      </div>
      <div class="why-card reveal">
        <span class="why-card__icon" aria-hidden="true">👨‍🏫</span>
        <h3>Qualified Teachers</h3>
        <p>Over 120 dedicated, qualified teaching staff committed to bringing out the best in every student.</p>
      </div>
      <div class="why-card reveal">
        <span class="why-card__icon" aria-hidden="true">💻</span>
        <h3>Modern Facilities</h3>
        <p>Fully equipped science laboratory, recently refurbished computer lab with internet access, and a well-stocked library.</p>
      </div>
      <div class="why-card reveal">
        <span class="why-card__icon" aria-hidden="true">🎭</span>
        <h3>Holistic Development</h3>
        <p>15+ clubs and societies, sports teams, cultural events, and competitions developing every dimension of the student.</p>
      </div>
      <div class="why-card reveal">
        <span class="why-card__icon" aria-hidden="true">⚖️</span>
        <h3>Discipline &amp; Values</h3>
        <p>A school culture built on integrity, discipline, and strong moral values — the foundation every student needs for life.</p>
      </div>
      <div class="why-card reveal">
        <span class="why-card__icon" aria-hidden="true">🌍</span>
        <h3>Strong Alumni Network</h3>
        <p>15,000+ proud alumni across medicine, law, engineering, public service, and business — an invaluable lifelong network.</p>
      </div>
    </div>

    <div class="why-text reveal">
      <span class="slabel">Why Choose Us</span>
      <h2 class="stitle">A Decision That <span>Shapes a Future</span></h2>
      <p>Choosing the right secondary school is one of the most important decisions a family will make. At Ibeku High School, we have been getting that decision right for over 70 years.</p>
      <p>Our graduates go on to the best universities in Nigeria and across the world — not just because of their academic scores, but because of the character, discipline, and resilience they develop within our walls.</p>
      <p>We are not just preparing students for examinations. We are preparing them for life.</p>
      <div class="why-text__highlights">
        <div class="why-highlight">Government-approved and accredited curriculum</div>
        <div class="why-highlight">Separate JS and SS sections with dedicated principals</div>
        <div class="why-highlight">Online result checker and school website</div>
        <div class="why-highlight">Active old students association and alumni support</div>
        <div class="why-highlight">Safe, structured, and supportive school environment</div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ENTRY REQUIREMENTS
     ═══════════════════════════════════════════ -->
<section class="requirements-section" id="requirements">
  <div class="requirements-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Entry Criteria</span>
        <h2 class="stitle">Admission <span>Requirements</span></h2>
        <p class="ssub">Requirements differ between the Junior Secondary and Senior Secondary entry levels. Please read carefully before applying.</p>
      </div>
    </div>

    <div class="requirements-grid">

      <div class="req-card reveal">
        <div class="req-card__header req-card__header--jss">
          <span class="req-card__icon" aria-hidden="true">🏫</span>
          <h3>JSS 1 Entry</h3>
          <p>Junior Secondary School — Year 1</p>
        </div>
        <div class="req-card__body">
          <h4>Age Requirement</h4>
          <ul>
            <li>Applicants must be between 10 and 13 years of age at the time of entry</li>
          </ul>
          <h4>Academic Qualification</h4>
          <ul>
            <li>Successful completion of Primary 6</li>
            <li>Primary School Leaving Certificate (PSLC) or equivalent</li>
            <li>Strong performance in English Language and Mathematics</li>
          </ul>
          <h4>Entrance Assessment</h4>
          <ul>
            <li>Written entrance examination in English Language and Mathematics</li>
            <li>Candidates are ranked and offered places based on performance</li>
          </ul>
          <h4>Documents Required</h4>
          <ul>
            <li>Completed application form</li>
            <li>Birth certificate or sworn affidavit of age</li>
            <li>Primary School Leaving Certificate</li>
            <li>Last school report card</li>
            <li>4 recent passport photographs</li>
          </ul>
        </div>
      </div>

      <div class="req-card reveal">
        <div class="req-card__header req-card__header--sss">
          <span class="req-card__icon" aria-hidden="true">🎓</span>
          <h3>SSS 1 Entry</h3>
          <p>Senior Secondary School — Year 1</p>
        </div>
        <div class="req-card__body">
          <h4>Age Requirement</h4>
          <ul>
            <li>Applicants must be between 14 and 17 years of age at the time of entry</li>
          </ul>
          <h4>Academic Qualification</h4>
          <ul>
            <li>Successful completion of JSS 3 with a valid Junior WAEC (BECE) result</li>
            <li>Credit or Merit in English Language and Mathematics</li>
            <li>Minimum of 5 passes in core Junior WAEC subjects</li>
          </ul>
          <h4>Entrance Assessment</h4>
          <ul>
            <li>Written entrance examination in English Language, Mathematics, and Basic Science</li>
            <li>Interview for shortlisted candidates</li>
          </ul>
          <h4>Documents Required</h4>
          <ul>
            <li>Completed application form</li>
            <li>Birth certificate or sworn affidavit of age</li>
            <li>Junior WAEC (BECE) result slip</li>
            <li>JSS 3 school report card</li>
            <li>Transfer certificate from previous school</li>
            <li>4 recent passport photographs</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     HOW TO APPLY
     ═══════════════════════════════════════════ -->
<section class="apply-steps-section" id="how-to-apply">
  <div class="apply-steps-section__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel">Application Process</span>
      <h2 class="stitle">How to <span>Apply</span></h2>
      <p class="ssub" style="margin:0 auto">The application process is simple and straightforward. Follow these four steps to secure your child's place at Ibeku High School.</p>
    </div>

    <div class="steps-grid">
      <div class="step-card reveal">
        <div class="step-card__num">1</div>
        <span class="step-card__icon" aria-hidden="true">📝</span>
        <h3>Submit Enquiry</h3>
        <p>Fill the online enquiry form below or visit the school office to collect a printed application form. Our admissions team will contact you within 48 hours.</p>
      </div>
      <div class="step-card reveal">
        <div class="step-card__num">2</div>
        <span class="step-card__icon" aria-hidden="true">📂</span>
        <h3>Submit Documents</h3>
        <p>Return the completed application form with all required documents — certificates, birth certificate, report card, and 4 passport photographs.</p>
      </div>
      <div class="step-card reveal">
        <div class="step-card__num">3</div>
        <span class="step-card__icon" aria-hidden="true">✏️</span>
        <h3>Entrance Assessment</h3>
        <p>Eligible candidates are invited to sit the entrance examination in English Language and Mathematics. Results are communicated within one week.</p>
      </div>
      <div class="step-card reveal">
        <div class="step-card__num">4</div>
        <span class="step-card__icon" aria-hidden="true">🎉</span>
        <h3>Offer &amp; Enrolment</h3>
        <p>Successful candidates receive an offer letter. Complete registration by paying the required fees and submitting any outstanding documents.</p>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     FEES & IMPORTANT DATES
     ═══════════════════════════════════════════ -->
<section class="fees-dates-section" id="fees">
  <div class="fees-dates-section__inner wrap">

    <div class="fees-card reveal">
      <div class="fees-card__header">
        <h3>School Fees</h3>
        <p><?php echo htmlspecialchars($_site['current_session']); ?> Academic Session — indicative figures</p>
      </div>
      <div class="fees-table">
        <?php
        $fees = [
          ['Application Form',              '₦2,000'],
          ['Acceptance Fee (new students)', '₦5,000'],
          ['Tuition — JSS (per term)',      '₦[Amount]'],
          ['Tuition — SSS (per term)',      '₦[Amount]'],
          ['Levies &amp; Development',      '₦[Amount]'],
          ['PTA Dues (per session)',         '₦[Amount]'],
        ];
        foreach ($fees as $fee): ?>
        <div class="fees-row">
          <span class="fees-row__item"><?php echo $fee[0]; ?></span>
          <span class="fees-row__amount"><?php echo $fee[1]; ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="fees-note">
        * Fee amounts are subject to confirmation by school management.
        Contact the school office for the current official fee schedule.
      </div>
    </div>

    <div class="dates-card reveal">
      <div class="dates-card__header">
        <h3>Important Dates</h3>
        <p><?php echo htmlspecialchars($_site['current_session']); ?> Admissions Calendar</p>
      </div>
      <div class="dates-table">
        <?php
        $dates = [
          ['Application Forms Available',   'January'],
          ['Application Deadline — JSS 1',  'March'],
          ['Application Deadline — SSS 1',  'March'],
          ['Entrance Examination',          'April'],
          ['Results Announcement',          'April'],
          ['Acceptance &amp; Registration', 'May'],
          ['Resumption — New Students',     'September'],
        ];
        foreach ($dates as $date): ?>
        <div class="dates-row">
          <span class="dates-row__event"><?php echo $date[0]; ?></span>
          <span class="dates-row__date"><?php echo $date[1]; ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="fees-note">
        * Dates are indicative and subject to confirmation.
        Contact the school office to confirm current deadlines.
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     APPLICATION FORM — wired to submit_admission.php
     ═══════════════════════════════════════════ -->
<section class="application-form-section" id="apply-now">
  <div class="application-form-section__inner wrap">

    <div class="application-form-section__info reveal">
      <span class="slabel">Get in Touch</span>
      <h2 class="stitle">Start Your <span>Application</span></h2>
      <p>Fill the form and our admissions office will contact you within 48 hours to guide you through the next steps.</p>
      <p>You may also visit us in person at the school office during working hours — Monday to Friday, 8:00 AM to 3:00 PM.</p>

      <div class="contact-options">
        <div class="contact-option">
          <span class="contact-option__icon" aria-hidden="true">📍</span>
          <div class="contact-option__text">
            <strong>Visit Us</strong>
            <span><?php echo htmlspecialchars($_site['school_address']); ?></span>
          </div>
        </div>
        <div class="contact-option">
          <span class="contact-option__icon" aria-hidden="true">📞</span>
          <div class="contact-option__text">
            <strong>Call the School Office</strong>
            <span><?php echo htmlspecialchars($_site['school_phone']); ?></span>
          </div>
        </div>
        <div class="contact-option">
          <span class="contact-option__icon" aria-hidden="true">✉️</span>
          <div class="contact-option__text">
            <strong>Email Admissions</strong>
            <span><?php echo htmlspecialchars($_site['school_email']); ?></span>
          </div>
        </div>
        <div class="contact-option">
          <span class="contact-option__icon" aria-hidden="true">🕐</span>
          <div class="contact-option__text">
            <strong>Office Hours</strong>
            <span><?php echo htmlspecialchars($_site['school_hours'] ?: 'Monday – Friday, 8:00 AM – 3:00 PM'); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="application-form-card reveal">
      <h3>Admissions Enquiry</h3>
      <p>Complete this form and our admissions team will be in touch within 48 hours.</p>

      <span class="form-section-label">Parent / Guardian Information</span>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="admParentFirst">First Name *</label>
          <input class="form-input" type="text" id="admParentFirst" placeholder="First name"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="admParentLast">Last Name *</label>
          <input class="form-input" type="text" id="admParentLast" placeholder="Last name"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="admEmail">Email Address *</label>
          <input class="form-input" type="email" id="admEmail" placeholder="your@email.com"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="admPhone">Phone Number *</label>
          <input class="form-input" type="tel" id="admPhone" placeholder="+234 000 000 0000"/>
        </div>
      </div>

      <span class="form-section-label">Student Information</span>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="admStudentFirst">Student First Name *</label>
          <input class="form-input" type="text" id="admStudentFirst" placeholder="First name"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="admStudentLast">Student Last Name *</label>
          <input class="form-input" type="text" id="admStudentLast" placeholder="Last name"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="admDob">Date of Birth</label>
          <input class="form-input" type="date" id="admDob"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="admGender">Gender</label>
          <select class="form-input" id="admGender">
            <option value="">Select gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
        </div>
      </div>

      <span class="form-section-label">Application Details</span>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="admClass">Applying for *</label>
          <select class="form-input" id="admClass">
            <option value="">Select entry level</option>
            <option value="JSS1">JSS 1 — Junior Secondary</option>
            <option value="SSS1">SSS 1 — Senior Secondary</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="admSession">Academic Session</label>
          <select class="form-input" id="admSession">
            <option value="">Select session</option>
            <?php
            $currentSession = $_site['current_session'] ?? '2025/2026';
            $sessionYear    = (int) substr($currentSession, 0, 4);
            for ($y = $sessionYear; $y <= $sessionYear + 1; $y++):
                $sess = $y . '/' . ($y + 1);
            ?>
            <option value="<?php echo $sess; ?>" <?php echo $sess === $currentSession ? 'selected' : ''; ?>>
              <?php echo $sess; ?>
            </option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="admPrevSchool">Previous School (if any)</label>
        <input class="form-input" type="text" id="admPrevSchool" placeholder="Name of previous school"/>
      </div>

      <div class="form-group">
        <label class="form-label" for="admMessage">Additional Information (optional)</label>
        <textarea class="form-input" id="admMessage" rows="3"
                  placeholder="Any additional information you would like us to know..."
                  style="resize:vertical"></textarea>
      </div>

      <button class="btn--apply" id="admBtn" onclick="submitAdmissionForm()">
        Submit Admissions Enquiry &rarr;
      </button>

      <div class="form-success" id="admSuccess" style="display:none">
        <p>✅ Enquiry Received!</p>
        <span>
          Thank you. Our admissions office will contact you at the email or phone
          number provided within 48 hours.
        </span>
      </div>

      <div id="admError"
           style="display:none;margin-top:12px;background:#ffe6e6;border:1px solid #ffcccc;border-radius:8px;padding:10px 14px;font-size:13.5px;color:#cc3333">
      </div>

      <p class="form-privacy">
        Your information is kept strictly confidential and used only for
        admissions purposes. We do not share your data with third parties.
      </p>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════
     ADMISSIONS FAQ
     ═══════════════════════════════════════════ -->
<section class="admissions-faq" id="faq">
  <div class="admissions-faq__inner wrap">

    <div class="section-header--center reveal">
      <span class="slabel">Common Questions</span>
      <h2 class="stitle">Admissions <span>FAQ</span></h2>
    </div>

    <div class="faq-list">
      <?php
      $faqs = [
        ['q' => 'When does the admissions process open each year?',
         'a' => 'Application forms are typically made available at the beginning of the year, usually January or February, for entry in the following September. Contact the school office to confirm the exact dates for the current admissions cycle.'],
        ['q' => 'Is there an entrance examination?',
         'a' => 'Yes. All applicants — for both JSS 1 and SSS 1 entry — are required to sit a written entrance examination in English Language and Mathematics. SSS 1 applicants also sit Basic Science. Candidates are offered places based on their examination performance.'],
        ['q' => 'Can a student transfer from another school into a class other than JSS 1 or SSS 1?',
         'a' => 'Transfer admissions into other class levels are considered on a case-by-case basis, subject to availability of space and the student meeting academic requirements. Contact the admissions office directly to enquire about transfer placements.'],
        ['q' => 'Is the school co-educational?',
         'a' => 'Yes. Ibeku High School is a co-educational government secondary school, admitting both male and female students across all class levels from JSS 1 to SSS 3.'],
        ['q' => 'What happens after the entrance examination?',
         'a' => 'Results of the entrance examination are communicated to applicants within one week. Successful candidates receive an offer letter and are given a deadline to complete registration by paying the required fees and submitting any outstanding documents.'],
        ['q' => 'Are there scholarships or fee waivers available?',
         'a' => 'The school occasionally offers support for exceptionally talented students in financial need. Enquire at the admissions office about current scholarship and bursary arrangements. The IHS Old Students Association also runs a support fund for students from difficult circumstances.'],
        ['q' => 'What is the school uniform?',
         'a' => 'Ibeku High School\'s uniform consists of a light blue shirt and dark purple trousers or skirt. Full uniform details including sports kit requirements are provided in the school prospectus issued at the time of application.'],
      ];
      foreach ($faqs as $i => $faq): ?>
      <div class="faq-item reveal" id="admfaq-<?php echo $i; ?>">
        <button class="faq-item__question" onclick="toggleAdmFaq(<?php echo $i; ?>)" aria-expanded="false">
          <span><?php echo htmlspecialchars($faq['q']); ?></span>
          <span class="faq-item__icon" aria-hidden="true">+</span>
        </button>
        <div class="faq-item__answer">
          <p><?php echo htmlspecialchars($faq['a']); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<?php require_once '../src/includes/footer.php'; ?>

<script>
/* ── Admissions form submission ── */
function submitAdmissionForm() {
  var errorEl = document.getElementById('admError');
  var btn     = document.getElementById('admBtn');
  errorEl.style.display = 'none';

  var fields = {
    admParentFirst:  document.getElementById('admParentFirst').value.trim(),
    admParentLast:   document.getElementById('admParentLast').value.trim(),
    admEmail:        document.getElementById('admEmail').value.trim(),
    admPhone:        document.getElementById('admPhone').value.trim(),
    admStudentFirst: document.getElementById('admStudentFirst').value.trim(),
    admStudentLast:  document.getElementById('admStudentLast').value.trim(),
    admClass:        document.getElementById('admClass').value,
  };

  var missing = Object.values(fields).some(function (v) { return v === ''; });
  if (missing) {
    errorEl.textContent    = 'Please fill in all required fields (marked with *).';
    errorEl.style.display  = 'block';
    errorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    return;
  }

  var fd = new FormData();
  fd.append('parent_first',    fields.admParentFirst);
  fd.append('parent_last',     fields.admParentLast);
  fd.append('parent_email',    fields.admEmail);
  fd.append('parent_phone',    fields.admPhone);
  fd.append('student_first',   fields.admStudentFirst);
  fd.append('student_last',    fields.admStudentLast);
  fd.append('date_of_birth',   document.getElementById('admDob').value);
  fd.append('gender',          document.getElementById('admGender').value);
  fd.append('entry_class',     fields.admClass);
  fd.append('session',         document.getElementById('admSession').value);
  fd.append('previous_school', document.getElementById('admPrevSchool').value.trim());
  fd.append('message',         document.getElementById('admMessage').value.trim());

  btn.textContent = 'Submitting…';
  btn.disabled    = true;

  fetch('<?php echo API_PATH; ?>submit_admission.php', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        document.getElementById('admSuccess').style.display = 'block';
        document.getElementById('admSuccess').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        /* Clear form */
        ['admParentFirst','admParentLast','admEmail','admPhone',
         'admStudentFirst','admStudentLast','admDob','admPrevSchool','admMessage'].forEach(function (id) {
          var el = document.getElementById(id);
          if (el) el.value = '';
        });
        document.getElementById('admGender').value  = '';
        document.getElementById('admClass').value   = '';
        document.getElementById('admSession').value = '';
      } else if (data.errors) {
        errorEl.textContent   = Object.values(data.errors)[0];
        errorEl.style.display = 'block';
      } else {
        errorEl.textContent   = data.message || 'Something went wrong. Please try again.';
        errorEl.style.display = 'block';
      }
    })
    .catch(function () {
      errorEl.textContent   = 'A connection error occurred. Please try again.';
      errorEl.style.display = 'block';
    })
    .finally(function () {
      btn.textContent = 'Submit Admissions Enquiry →';
      btn.disabled    = false;
    });
}

/* ── FAQ accordion ── */
function toggleAdmFaq(index) {
  var item   = document.getElementById('admfaq-' + index);
  var isOpen = item ? item.classList.contains('open') : false;
  document.querySelectorAll('.faq-item.open').forEach(function (el) {
    el.classList.remove('open');
    var b = el.querySelector('.faq-item__question');
    if (b) b.setAttribute('aria-expanded', 'false');
  });
  if (!isOpen && item) {
    item.classList.add('open');
    var btn = item.querySelector('.faq-item__question');
    if (btn) btn.setAttribute('aria-expanded', 'true');
  }
}
</script>