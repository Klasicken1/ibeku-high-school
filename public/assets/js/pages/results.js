/* ============================================================
   IBEKU HIGH SCHOOL — RESULTS PAGE JAVASCRIPT
   File: public/assets/js/pages/results.js

   Handles:
     1. Full-page result checker
     2. Demo ID fill buttons
     3. Print result slip
     4. FAQ accordion

   Phase 2: replace lookupResultFull() body with fetch() API call
   ============================================================ */

'use strict';


/* ============================================================
   DEMO DATA
   Phase 2: remove this entire block — real data comes from DB
   ============================================================ */
var DEMO_RESULTS = {
  'IHS/2024/0421': {
    name:     'Adaeze Okonkwo',
    cls:      'SSS 2',
    term:     'First Term 2024/2025',
    session:  '2024/2025',
    position: '3rd',
    total:    28,
    avg:      '76.3%',
    subjects: [
      { name: 'English Language', score: 78,  grade: 'B3' },
      { name: 'Mathematics',      score: 85,  grade: 'A1' },
      { name: 'Physics',          score: 72,  grade: 'B3' },
      { name: 'Chemistry',        score: 80,  grade: 'A1' },
      { name: 'Biology',          score: 74,  grade: 'B2' },
      { name: 'Economics',        score: 69,  grade: 'B3' }
    ]
  },
  'IHS/2024/0105': {
    name:     'Chukwuemeka Nwosu',
    cls:      'JSS 3',
    term:     'First Term 2024/2025',
    session:  '2024/2025',
    position: '1st',
    total:    32,
    avg:      '83.5%',
    subjects: [
      { name: 'English Language', score: 92,  grade: 'A1' },
      { name: 'Mathematics',      score: 88,  grade: 'A1' },
      { name: 'Basic Science',    score: 85,  grade: 'A1' },
      { name: 'Social Studies',   score: 79,  grade: 'B2' },
      { name: 'Civic Education',  score: 81,  grade: 'A1' },
      { name: 'Business Studies', score: 76,  grade: 'B2' }
    ]
  }
};


/* ============================================================
   RESULT CHECKER
   ============================================================ */

function fillDemo(id) {
  var input = document.getElementById('rcId');
  if (input) { input.value = id; input.focus(); }
}

function renderResultFull(student, subjects) {
  setText('rcPanelName', student.name);
  setText('rcPanelTerm', student.term);

  var metaEl = document.getElementById('rcPanelMeta');
  if (metaEl) {
    metaEl.innerHTML =
      '<div class="result-panel__meta-item"><p>Class</p><strong>'    + esc(student.cls)      + '</strong></div>' +
      '<div class="result-panel__meta-item"><p>Position</p><strong>' + esc(student.position) + ' / ' + esc(String(student.total)) + '</strong></div>' +
      '<div class="result-panel__meta-item"><p>Average</p><strong>'  + esc(student.avg)      + '</strong></div>';
  }

  var subjEl = document.getElementById('rcPanelSubjects');
  if (subjEl) {
    subjEl.innerHTML = subjects.map(function (s) {
      var letter = s.grade.charAt(0);
      return '<div class="result-row">' +
        '<span class="result-row__subject">' + esc(s.name) + '</span>' +
        '<span class="result-row__grade grade--' + letter + '">' + s.score + ' &mdash; ' + esc(s.grade) + '</span>' +
        '</div>';
    }).join('');
  }

  var panel    = document.getElementById('rcPanel');
  var notFound = document.getElementById('rcNotFound');
  if (panel)    panel.classList.add('show');
  if (notFound) notFound.style.display = 'none';
  if (panel)    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function showNotFoundFull() {
  var panel    = document.getElementById('rcPanel');
  var notFound = document.getElementById('rcNotFound');
  if (panel)    panel.classList.remove('show');
  if (notFound) notFound.style.display = 'block';
}

function resetOutputFull() {
  var panel    = document.getElementById('rcPanel');
  var notFound = document.getElementById('rcNotFound');
  if (panel)    panel.classList.remove('show');
  if (notFound) notFound.style.display = 'none';
}

/*
 * PHASE 2 UPGRADE — replace this function body with:
 *
 *   var formData = new FormData();
 *   formData.append('admission_number', admissionNo);
 *   formData.append('class',            studentClass);
 *   formData.append('term',             term);
 *   var btn = document.getElementById('checkBtn');
 *   if (btn) { btn.textContent = 'Checking…'; btn.disabled = true; }
 *   fetch('/src/api/check_result.php', { method: 'POST', body: formData })
 *     .then(function(r) { return r.json(); })
 *     .then(function(data) {
 *       if (data.found) renderResultFull(data.student, data.subjects);
 *       else            showNotFoundFull();
 *     })
 *     .catch(showNotFoundFull)
 *     .finally(function() {
 *       if (btn) { btn.textContent = 'Check My Results →'; btn.disabled = false; }
 *     });
 */
function lookupResultFull(admissionNo) {
  var result = DEMO_RESULTS[admissionNo];
  if (result) renderResultFull(result, result.subjects);
  else        showNotFoundFull();
}

function checkResultFull() {
  var id  = document.getElementById('rcId');
  if (!id) return;
  var admissionNo = id.value.trim().toUpperCase();
  resetOutputFull();
  if (!admissionNo) { alert('Please enter your Admission Number.'); id.focus(); return; }
  lookupResultFull(admissionNo);
}

(function () {
  var input = document.getElementById('rcId');
  if (!input) return;
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); checkResultFull(); }
  });
  input.addEventListener('input', function () {
    var pos = input.selectionStart;
    input.value = input.value.toUpperCase();
    input.setSelectionRange(pos, pos);
  });
}());


/* ============================================================
   PRINT RESULT SLIP
   ============================================================ */
function printResult() {
  var idInput = document.getElementById('rcId');
  var panel   = document.getElementById('rcPanel');

  if (!panel || !panel.classList.contains('show')) {
    alert('Please check a result first before printing.');
    return;
  }

  var admNo  = idInput ? idInput.value.trim().toUpperCase() : '';
  var result = DEMO_RESULTS[admNo];

  if (!result) {
    alert('No result loaded. Please check a result first.');
    return;
  }

  /* ── Populate header ── */
  setText('rsPrintTitle',
    result.term.toUpperCase() + ' ACADEMIC REPORT');

  /* ── Populate student info ── */
  setText('rsPrintName',     result.name);
  setText('rsPrintAdmNo',    admNo);
  setText('rsPrintClass',    result.cls);
  setText('rsPrintSession',  result.session);
  setText('rsPrintTerm',     result.term.replace(result.session, '').trim());
  setText('rsPrintTotal',    String(result.total));
  setText('rsPrintPosition', result.position + ' out of ' + result.total);
  setText('rsPrintAvg',      result.avg);
  setText('rsPrintSubjCount',String(result.subjects.length));
  setText('rsPrintResumption', 'To be announced');
  setText('rsPrintDate', new Date().toLocaleDateString('en-GB', {
    day: '2-digit', month: 'long', year: 'numeric'
  }));

  /* ── Total score ── */
  var totalScore = result.subjects.reduce(function (sum, s) {
    return sum + s.score;
  }, 0);
  setText('rsPrintTotalScore',
    totalScore + ' / ' + (result.subjects.length * 100));

  /* ── Subject table rows — 15 / 15 / 70 distribution ── */
  var tbody = document.getElementById('rsPrintSubjects');
  if (tbody) {
    tbody.innerHTML = result.subjects.map(function (s) {
      var test1  = Math.round(s.score * 0.15);
      var test2  = Math.round(s.score * 0.15);
      var exam   = s.score - test1 - test2;
      var letter = s.grade.charAt(0);
      return '<tr>' +
        '<td>' + esc(s.name)         + '</td>' +
        '<td>' + test1               + '</td>' +
        '<td>' + test2               + '</td>' +
        '<td>' + exam                + '</td>' +
        '<td><strong>' + s.score + '</strong></td>' +
        '<td><strong>' + esc(s.grade) + '</strong></td>' +
        '<td>' + remarkFromGrade(s.grade) + '</td>' +
        '</tr>';
    }).join('');
  }

  window.print();
}


/* ============================================================
   FAQ ACCORDION
   ============================================================ */
function toggleFaq(index) {
  var item   = document.getElementById('faq-' + index);
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


/* ============================================================
   HELPERS
   ============================================================ */
function setText(id, value) {
  var el = document.getElementById(id);
  if (el) el.textContent = value;
}

function esc(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function remarkFromGrade(grade) {
  var map = {
    'A1': 'Excellent', 'B2': 'Very Good', 'B3': 'Good',
    'C4': 'Credit',    'C5': 'Credit',    'C6': 'Credit',
    'D7': 'Pass',      'E8': 'Pass',      'F9': 'Fail'
  };
  return map[grade] || 'Pass';
}