<?php
/* ============================================================
   IBEKU HIGH SCHOOL â€” CONTACT PAGE
   File: public/contact.php
   ============================================================ */

$pageTitle   = 'Contact Us â€” Ibeku High School, Umuahia';
$pageDesc    = 'Get in touch with Ibeku High School. Find our address, phone number, email, office hours, and location map. Send us a message directly from this page.';
$currentPage = 'contact';
$pageCss     = 'contact';

require_once '../src/includes/header.php';
?>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     PAGE HERO
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="page-hero page-hero--contact">
  <div class="page-hero__inner wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep" aria-hidden="true">â€º</span>
      <span style="color:rgba(255,255,255,.85)">Contact</span>
    </nav>
    <h1>Get in <em>Touch</em><br/>With Us</h1>
    <p>We would love to hear from you. Reach us by phone, email, or visit us at the school. Our office is open Monday to Friday during school hours.</p>
  </div>
</div>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     PAGE ANCHOR NAV
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="page-anchors">
  <div class="page-anchors__inner wrap">
    <a href="#contact"      class="page-anchor active">Send a Message</a>
    <a href="#map"          class="page-anchor">Find Us</a>
    <a href="#departments"  class="page-anchor">Department Contacts</a>
  </div>
</div>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     CONTACT INFO + FORM
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section class="contact-section" id="contact">
  <div class="contact-section__inner wrap">

    <!-- Left: contact information -->
    <div class="contact-info reveal">

      <span class="slabel">Reach Us</span>
      <h2 class="stitle">Contact <span>Information</span></h2>
      <p>Whether you have a question about admissions, need to speak to a teacher, or want to report an issue, we are here to help. Reach us through any of the channels below.</p>

      <div class="contact-cards">

        <div class="contact-card">
          <div class="contact-card__icon" aria-hidden="true">ðŸ“</div>
          <div class="contact-card__text">
            <strong>Our Address</strong>
            <!-- UPDATE: Confirm exact street address with school -->
            <span>Ibeku High School</span>
            <span>Umuahia, Abia State, Nigeria</span>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-card__icon" aria-hidden="true">ðŸ“ž</div>
          <div class="contact-card__text">
            <strong>Phone Number</strong>
            <!-- UPDATE: Replace with real phone number -->
            <a href="tel:+2340000000000">+234 000 000 0000</a>
            <span style="font-size:12px;margin-top:3px">
              Available Monday â€“ Friday, 8:00 AM â€“ 3:00 PM
            </span>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-card__icon" aria-hidden="true">âœ‰ï¸</div>
          <div class="contact-card__text">
            <strong>Email Address</strong>
            <!-- UPDATE: Replace with real email -->
            <a href="mailto:info@ibekuhighschool.edu.ng">info@ibekuhighschool.edu.ng</a>
            <a href="mailto:admissions@ibekuhighschool.edu.ng">admissions@ibekuhighschool.edu.ng</a>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-card__icon" aria-hidden="true">ðŸ«</div>
          <div class="contact-card__text">
            <strong>School Sections</strong>
            <span>Senior Secondary â€” Main Building</span>
            <span>Junior Secondary â€” Annex Building</span>
          </div>
        </div>

      </div>

      <!-- Office hours -->
      <div class="office-hours">
        <h4>ðŸ• Office Hours</h4>
        <?php
        $hours = [
          ['Monday',    '8:00 AM â€“ 3:00 PM', false],
          ['Tuesday',   '8:00 AM â€“ 3:00 PM', false],
          ['Wednesday', '8:00 AM â€“ 3:00 PM', false],
          ['Thursday',  '8:00 AM â€“ 3:00 PM', false],
          ['Friday',    '8:00 AM â€“ 3:00 PM', false],
          ['Saturday',  'Closed',             true],
          ['Sunday',    'Closed',             true],
        ];
        foreach ($hours as $h): ?>
        <div class="hours-row">
          <span class="hours-row__day"><?php echo $h[0]; ?></span>
          <span class="<?php echo $h[2] ? 'hours-row__closed' : 'hours-row__time'; ?>">
            <?php echo $h[1]; ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Social links -->
      <div class="contact-socials">
        <h4>Follow Us</h4>
        <div class="social-links">
          <!-- UPDATE: Replace # with real social media links -->
          <a href="#" class="social-link" target="_blank" rel="noopener noreferrer">
            <span class="social-link__icon">f</span> Facebook
          </a>
          <a href="#" class="social-link" target="_blank" rel="noopener noreferrer">
            <span class="social-link__icon">ð•</span> Twitter
          </a>
          <a href="#" class="social-link" target="_blank" rel="noopener noreferrer">
            <span class="social-link__icon">ðŸ“·</span> Instagram
          </a>
          <a href="#" class="social-link" target="_blank" rel="noopener noreferrer">
            <span class="social-link__icon">â–¶</span> YouTube
          </a>
        </div>
      </div>

    </div>

    <!-- Right: contact form -->
    <div class="contact-form-card reveal">
      <h3>Send Us a Message</h3>
      <p>Fill the form below and we will respond within one working day.</p>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="ctcFirst">First Name</label>
          <input class="form-input" type="text" id="ctcFirst" placeholder="First name"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="ctcLast">Last Name</label>
          <input class="form-input" type="text" id="ctcLast" placeholder="Last name"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="ctcEmail">Email Address</label>
          <input class="form-input" type="email" id="ctcEmail" placeholder="your@email.com"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="ctcPhone">Phone Number</label>
          <input class="form-input" type="tel" id="ctcPhone" placeholder="+234 000 000 0000"/>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="ctcSubject">Subject</label>
        <select class="form-input" id="ctcSubject">
          <option value="">Select a subject</option>
          <option>Admissions Enquiry</option>
          <option>Student Results</option>
          <option>Fee Payment</option>
          <option>Academic Matter</option>
          <option>Staff &amp; Employment</option>
          <option>General Enquiry</option>
          <option>Other</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="ctcMessage">Message</label>
        <textarea
          class="form-input"
          id="ctcMessage"
          rows="5"
          placeholder="Write your message here..."
          style="resize:vertical"></textarea>
      </div>

      <button class="btn--send" onclick="submitContactForm()">
        Send Message &rarr;
      </button>

      <!-- Success message -->
      <div class="contact-form-success" id="ctcSuccess">
        <p>âœ… Message Sent!</p>
        <span>
          Thank you for getting in touch. We will respond to your message
          within one working day.
        </span>
      </div>

    </div>

  </div>
</section>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MAP & DIRECTIONS
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section class="map-section" id="map">
  <div class="map-section__inner">

    <div class="map-section__header reveal">
      <div>
        <span class="slabel">Our Location</span>
        <h2 class="stitle">Find <span>Ibeku High School</span></h2>
        <p class="ssub">We are located in Umuahia, the capital of Abia State, South-East Nigeria.</p>
      </div>
      <a href="https://www.google.com/maps/search/Ibeku+High+School+Umuahia+Abia+State+Nigeria" target="_blank" rel="noopener noreferrer" class="btn--directions">ðŸ—º Get Directions on Google Maps</a>
    </div>

    <div class="map-container reveal">
      <!--
        Google Maps Embed â€” Ibeku High School, Umuahia.
        UPDATE: Once the exact GPS coordinates are confirmed, replace
        the query parameter below with the precise coordinates:
        src="https://maps.google.com/maps?q=LATITUDE,LONGITUDE&z=16&output=embed"

        Current embed searches by name which is reliable until coordinates are confirmed.
      -->
      <iframe
        src="https://maps.google.com/maps?q=Ibeku+High+School+Umuahia+Abia+State+Nigeria&z=15&output=embed"
        title="Ibeku High School location on Google Maps"
        allowfullscreen
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>

      <!-- Directions bar overlaid at bottom of map -->
      <div class="map-directions-bar">
        <p>
          <strong>Ibeku High School</strong> &nbsp;â€”&nbsp;
          Umuahia, Abia State, Nigeria
        </p>
        <a href="https://www.google.com/maps/dir/?api=1&destination=Ibeku+High+School+Umuahia+Abia+State+Nigeria" target="_blank" rel="noopener noreferrer" class="btn--directions">ðŸ“ Open in Google Maps</a>
      </div>
    </div>

    <!-- Nearby landmarks -->
    <div class="landmarks">
      <div class="landmark-card reveal">
        <span class="landmark-card__icon" aria-hidden="true">ðŸ¥</span>
        <div class="landmark-card__text">
          <strong>Federal Medical Centre</strong>
          <span>Major landmark in Umuahia â€” ask for directions from FMC Umuahia</span>
        </div>
      </div>
      <div class="landmark-card reveal">
        <span class="landmark-card__icon" aria-hidden="true">ðŸ›ï¸</span>
        <div class="landmark-card__text">
          <strong>Abia State Secretariat</strong>
          <span>Located near the Government House area in central Umuahia</span>
        </div>
      </div>
      <div class="landmark-card reveal">
        <span class="landmark-card__icon" aria-hidden="true">ðŸšŒ</span>
        <div class="landmark-card__text">
          <strong>Umuahia Motor Park</strong>
          <span>Main transport hub â€” taxis and buses available to the school area</span>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     DEPARTMENT CONTACTS
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section class="dept-contacts-section" id="departments">
  <div class="dept-contacts-section__inner wrap">

    <div class="section-header reveal">
      <div>
        <span class="slabel">Direct Lines</span>
        <h2 class="stitle">Department <span>Contacts</span></h2>
        <p class="ssub">For specific enquiries, contact the relevant department directly.</p>
      </div>
    </div>

    <div class="dept-contacts-grid">

      <div class="dept-contact-card reveal">
        <span class="dept-contact-card__icon" aria-hidden="true">ðŸŽ“</span>
        <h3>Admissions Office</h3>
        <p>For all enquiries about applying for JSS 1 or SSS 1, entrance examinations, and new student registration.</p>
        <!-- UPDATE: Replace with real admissions contact -->
        <a href="mailto:admissions@ibekuhighschool.edu.ng" class="dept-contact-card__contact">
          admissions@ibekuhighschool.edu.ng
        </a>
      </div>

      <div class="dept-contact-card reveal">
        <span class="dept-contact-card__icon" aria-hidden="true">ðŸ“Š</span>
        <h3>Results &amp; Examinations</h3>
        <p>For queries about published results, missing scores, or examination-related matters. Contact the VP Academics office.</p>
        <!-- UPDATE: Replace with real exams contact -->
        <a href="mailto:exams@ibekuhighschool.edu.ng" class="dept-contact-card__contact">
          exams@ibekuhighschool.edu.ng
        </a>
      </div>

      <div class="dept-contact-card reveal">
        <span class="dept-contact-card__icon" aria-hidden="true">ðŸ’°</span>
        <h3>Bursary &amp; Fees</h3>
        <p>For school fee payments, receipts, outstanding balances, and financial matters. Contact the school bursar directly.</p>
        <!-- UPDATE: Replace with real bursary contact -->
        <a href="mailto:bursary@ibekuhighschool.edu.ng" class="dept-contact-card__contact">
          bursary@ibekuhighschool.edu.ng
        </a>
      </div>

      <div class="dept-contact-card reveal">
        <span class="dept-contact-card__icon" aria-hidden="true">ðŸ‘¨â€ðŸŽ“</span>
        <h3>Student Welfare</h3>
        <p>For welfare concerns, student discipline matters, and guidance and counselling enquiries. Contact the Guidance Counsellor.</p>
        <!-- UPDATE: Replace with real welfare contact -->
        <a href="mailto:welfare@ibekuhighschool.edu.ng" class="dept-contact-card__contact">
          welfare@ibekuhighschool.edu.ng
        </a>
      </div>

      <div class="dept-contact-card reveal">
        <span class="dept-contact-card__icon" aria-hidden="true">ðŸ’»</span>
        <h3>ICT &amp; Website</h3>
        <p>For technical issues with the school website, online result checker, or digital services. Contact the ICT Coordinator.</p>
        <!-- UPDATE: Replace with real ICT contact -->
        <a href="mailto:ict@ibekuhighschool.edu.ng" class="dept-contact-card__contact">
          ict@ibekuhighschool.edu.ng
        </a>
      </div>

      <div class="dept-contact-card reveal">
        <span class="dept-contact-card__icon" aria-hidden="true">ðŸ¤</span>
        <h3>Old Students Association</h3>
        <p>For alumni affairs, donations, Hall of Fame nominations, and old students reunion enquiries.</p>
        <!-- UPDATE: Replace with real alumni contact -->
        <a href="mailto:alumni@ibekuhighschool.edu.ng" class="dept-contact-card__contact">
          alumni@ibekuhighschool.edu.ng
        </a>
      </div>

    </div>
  </div>
</section>


<?php require_once '../src/includes/footer.php'; ?>

<script>
/* Contact form submit â€” sends to src/api/submit_contact.php */
function submitContactForm() {
  var required = ['ctcFirst', 'ctcLast', 'ctcEmail', 'ctcSubject', 'ctcMessage'];
  var allFilled = required.every(function (id) {
    var el = document.getElementById(id);
    return el && el.value.trim() !== '';
  });

  if (!allFilled) {
    alert('Please fill in all required fields before sending.');
    return;
  }

  var formData = new FormData();
  formData.append('first_name', document.getElementById('ctcFirst').value.trim());
  formData.append('last_name',  document.getElementById('ctcLast').value.trim());
  formData.append('email',      document.getElementById('ctcEmail').value.trim());
  formData.append('phone',      document.getElementById('ctcPhone').value.trim());
  formData.append('subject',    document.getElementById('ctcSubject').value);
  formData.append('message',    document.getElementById('ctcMessage').value.trim());

  fetch('<?php echo API_PATH; ?>submit_contact.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        var successEl = document.getElementById('ctcSuccess');
        successEl.querySelector('span').textContent = data.message;
        successEl.style.display = 'block';
        successEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        required.forEach(function (id) { document.getElementById(id).value = ''; });
        document.getElementById('ctcPhone').value = '';
      } else if (data.errors) {
        var firstError = Object.values(data.errors)[0];
        alert(firstError);
      } else {
        alert(data.message || 'Something went wrong. Please try again.');
      }
    })
    .catch(function (err) {
      console.error('Contact form error:', err);
      alert('A connection error occurred. Please try again.');
    });
}
</script>

