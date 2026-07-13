/* ============================================================
   IBEKU HIGH SCHOOL — RESULTS PAGE JAVASCRIPT
   File: public/assets/js/pages/results.js
   ============================================================ */

'use strict';

var CLASSES_BY_GRADE_LEVEL = {
  JSS1: ['A', 'B', 'C', 'D', 'E'],
  JSS2: ['A', 'B', 'C', 'D', 'E'],
  JSS3: ['A', 'B', 'C', 'D', 'E'],
  SSS1: ['A', 'B', 'C', 'D', 'E'],
  SSS2: ['A', 'B', 'C', 'D', 'E'],
  SSS3: ['A', 'B', 'C', 'D', 'E']
};

(function () {
  var gradeLevelSelect = document.getElementById('rcGradeLevel');
  var classSelect       = document.getElementById('rcClass');
  if (!gradeLevelSelect || !classSelect) return;

  gradeLevelSelect.addEventListener('change', function () {
    var gl = gradeLevelSelect.value;
    classSelect.innerHTML = '<option value="">Select class</option>';
    if (gl && CLASSES_BY_GRADE_LEVEL[gl]) {
      CLASSES_BY_GRADE_LEVEL[gl].forEach(function (cls) {
        var opt = document.createElement('option');
        opt.value = cls;
        opt.textContent = cls;
        classSelect.appendChild(opt);
      });
    }
  });
}());


/* ============================================================
   RESULT CHECKER
   ============================================================ */

function fillDemo(admissionNumber) {
  var input = document.getElementById('rcId');
  if (input) { input.value = admissionNumber; input.focus(); }
}

var STATUS_CONFIG = {
  expelled:    { label: 'Expelled',    bg: '#ffe6e6', color: '#cc3333', icon: '🚫' },
  graduated:   { label: 'Graduated',   bg: '#e6f0ff', color: '#1a5a9a', icon: '🎓' },
  deceased:    { label: 'Deceased',    bg: '#f4f3f9', color: '#6b6b80', icon: '✝' },
  transferred: { label: 'Transferred', bg: '#fff3e6', color: '#8a4a00', icon: '🔄' },
};

var EVENT_CONFIG = {
  promotion:     { label: 'Promoted',     color: '#1a7a3a', icon: '⬆️' },
  retention:     { label: 'Retained',     color: '#8a6a00', icon: '🔄' },
  demotion:      { label: 'Demoted',      color: '#cc3333', icon: '⬇️' },
  expulsion:     { label: 'Expelled',     color: '#cc0000', icon: '🚫' },
  graduation:    { label: 'Graduated',    color: '#1a5a9a', icon: '🎓' },
  reinstatement: { label: 'Reinstated',   color: '#3d1a6e', icon: '✅' },
};

function renderResultFull(student, subjects, history) {
  setText('rcPanelName', student.name);
  setText('rcPanelTerm', student.term + ' ' + student.session);

  var existingNotice = document.getElementById('rcStatusNotice');
  if (existingNotice) existingNotice.remove();

  if (student.status && student.status !== 'active') {
    var cfg = STATUS_CONFIG[student.status] || { label: student.status, bg: '#f4f3f9', color: '#6b6b80', icon: 'ℹ️' };
    var notice = document.createElement('div');
    notice.id = 'rcStatusNotice';
    notice.style.cssText = [
      'background:' + cfg.bg,
      'color:' + cfg.color,
      'border:1px solid ' + cfg.color + '33',
      'border-radius:10px',
      'padding:12px 16px',
      'font-size:13px',
      'font-weight:600',
      'margin-bottom:14px',
      'display:flex',
      'align-items:center',
      'gap:10px',
    ].join(';');
    var iconSpan = '<span style="font-size:18px">' + cfg.icon + '</span>';
    var textDiv  = '<div><div>' + cfg.label + '</div>';
    if (student.status_reason) {
      textDiv += '<div style="font-weight:400;margin-top:2px;font-size:12.5px">' + esc(student.status_reason) + '</div>';
    }
    if (student.status_changed_at) {
      textDiv += '<div style="font-weight:400;margin-top:2px;font-size:12px;opacity:.8">' + esc(student.status_changed_at) + '</div>';
    }
    textDiv += '</div>';
    notice.innerHTML = iconSpan + textDiv;

    var panel = document.getElementById('rcPanel');
    var header = panel ? panel.querySelector('.result-panel__header') : null;
    if (header) header.insertAdjacentElement('afterend', notice);
  }

  var metaEl = document.getElementById('rcPanelMeta');
  if (metaEl) {
    var positionText = student.class_position
      ? (student.class_position + ' of ' + student.class_total_students)
      : 'N/A';
    if (student.grade_level_position) {
      positionText += ' (' + student.grade_level_position + ' of ' +
        student.grade_level_total_students + ' in ' + student.grade_level_only + ' overall)';
    }
    metaEl.innerHTML =
      '<div class="result-panel__meta-item"><p>Class</p><strong>'    + esc(student.class)    + '</strong></div>' +
      '<div class="result-panel__meta-item"><p>Position</p><strong>' + esc(positionText)      + '</strong></div>' +
      '<div class="result-panel__meta-item"><p>Average</p><strong>'  + esc(String(student.average_score)) + '%</strong></div>';
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

  var existingHistory = document.getElementById('rcHistorySection');
  if (existingHistory) existingHistory.remove();

  if (history && history.length > 0) {
    var historySection = document.createElement('div');
    historySection.id = 'rcHistorySection';
    historySection.style.cssText = 'margin-top:16px;border-top:1px solid #f0eef6;padding-top:14px';

    var histTitle = document.createElement('div');
    histTitle.style.cssText = 'font-size:12px;font-weight:700;color:#3d1a6e;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px';
    histTitle.textContent = 'Academic History';
    historySection.appendChild(histTitle);

    var timeline = document.createElement('div');
    timeline.style.cssText = 'display:flex;flex-direction:column;gap:8px';

    history.forEach(function (event) {
      var cfg = EVENT_CONFIG[event.event_type] || { label: event.event_type, color: '#6b6b80', icon: '•' };
      var row = document.createElement('div');
      row.style.cssText = [
        'display:flex', 'gap:10px', 'align-items:flex-start',
        'background:#faf9fd', 'border-radius:8px', 'padding:8px 12px', 'font-size:12.5px',
      ].join(';');

      var icon = '<span style="font-size:14px;flex-shrink:0">' + cfg.icon + '</span>';
      var detail = '<div style="flex:1">';
      detail += '<span style="font-weight:700;color:' + cfg.color + '">' + cfg.label + '</span>';
      if (event.from && event.to && event.from !== event.to) {
        detail += ' <span style="color:#6b6b80">' + esc(event.from) + ' → ' + esc(event.to) + '</span>';
      }
      if (event.reason) {
        detail += '<div style="color:#9b97b0;margin-top:2px">' + esc(event.reason) + '</div>';
      }
      detail += '</div>';
      var date = '<span style="color:#9b97b0;font-size:11.5px;flex-shrink:0">' + esc(event.date) + '</span>';

      row.innerHTML = icon + detail + date;
      timeline.appendChild(row);
    });

    historySection.appendChild(timeline);

    var panel = document.getElementById('rcPanel');
    var footer = panel ? panel.querySelector('.result-panel__footer') : null;
    if (footer) footer.insertAdjacentElement('beforebegin', historySection);
    else if (panel) panel.appendChild(historySection);
  }

  var panel    = document.getElementById('rcPanel');
  var notFound = document.getElementById('rcNotFound');
  if (panel)    panel.classList.add('show');
  if (notFound) notFound.style.display = 'none';
  if (panel)    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  window._lastResult = { student: student, subjects: subjects, history: history };
}

function showNotFoundFull(message) {
  var panel    = document.getElementById('rcPanel');
  var notFound = document.getElementById('rcNotFound');
  if (panel)    panel.classList.remove('show');
  if (notFound) {
    notFound.style.display = 'block';
    var msgEl = notFound.querySelector('span');
    if (msgEl && message) msgEl.textContent = message;
  }
}

function resetOutputFull() {
  var panel    = document.getElementById('rcPanel');
  var notFound = document.getElementById('rcNotFound');
  if (panel)    panel.classList.remove('show');
  if (notFound) notFound.style.display = 'none';

  var notice  = document.getElementById('rcStatusNotice');
  var history = document.getElementById('rcHistorySection');
  if (notice)  notice.remove();
  if (history) history.remove();
}

function lookupResultFull(admissionNo) {
  var btn = document.getElementById('checkBtn');
  if (btn) { btn.textContent = 'Checking…'; btn.disabled = true; }

  var session = document.getElementById('rcSession');
  var term    = document.getElementById('rcTerm');

  var formData = new FormData();
  formData.append('admission_number', admissionNo);
  formData.append('session',          session ? session.value : '');
  formData.append('term',             term    ? term.value    : '');

  /* ── Uses window.IHS_API set by header.php — works on localhost and production ── */
  fetch(window.IHS_API + 'check_result.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) renderResultFull(data.student, data.subjects, data.history || []);
      else              showNotFoundFull(data.message);
    })
    .catch(function (err) {
      console.error('Result checker error:', err);
      showNotFoundFull('A connection error occurred. Please try again.');
    })
    .finally(function () {
      if (btn) { btn.textContent = 'Check My Results →'; btn.disabled = false; }
    });
}

function checkResultFull() {
  var id      = document.getElementById('rcId');
  var session = document.getElementById('rcSession');
  var term    = document.getElementById('rcTerm');

  if (!id) return;
  var admissionNo = id.value.trim().toUpperCase();
  resetOutputFull();

  if (!admissionNo) { alert('Please enter your Admission Number.'); id.focus(); return; }
  if (!session || !session.value) { alert('Please select your academic session.'); return; }
  if (!term || !term.value) { alert('Please select your term.'); return; }

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
  var panel = document.getElementById('rcPanel');
  if (!panel || !panel.classList.contains('show')) {
    alert('Please check a result first before printing.');
    return;
  }

  var cached = window._lastResult;
  if (!cached) {
    alert('No result loaded. Please check a result first.');
    return;
  }

  var student  = cached.student;
  var subjects = cached.subjects;

  setText('rsPrintTitle', (student.term + ' ' + student.session).toUpperCase() + ' ACADEMIC REPORT');
  setText('rsPrintName',     student.name);
  setText('rsPrintAdmNo',    student.admission_number);
  setText('rsPrintClass',    student.class);
  setText('rsPrintSession',  student.session);
  setText('rsPrintTerm',     student.term);
  setText('rsPrintTotal',    String(student.class_total_students || ''));

  var positionText = student.class_position
    ? (student.class_position + ' out of ' + student.class_total_students)
    : 'N/A';
  setText('rsPrintPosition', positionText);

  var gradeLevelPositionText = student.grade_level_position
    ? (student.grade_level_position + ' out of ' + student.grade_level_total_students + ' (' + student.grade_level_only + ' overall)')
    : 'Not yet calculated';
  setText('rsPrintGradeLevelPosition', gradeLevelPositionText);

  setText('rsPrintAvg',              student.average_score !== null ? student.average_score + '%' : '—');
  setText('rsPrintResumption',       student.next_term_resumption || 'To be announced');
  setText('rsPrintTeacherComment',   student.form_teacher_comment || '');
  setText('rsPrintPrincipalComment', student.principal_comment    || '');
  setText('rsPrintTotalScore',       (student.total_score || 0) + ' / ' + (subjects.length * 100));
  setText('rsPrintDate', new Date().toLocaleDateString('en-GB', {
    day: '2-digit', month: 'long', year: 'numeric'
  }));

  var tbody = document.getElementById('rsPrintSubjects');
  if (tbody) {
    tbody.innerHTML = subjects.map(function (s) {
      return '<tr>' +
        '<td>' + esc(s.name)  + '</td>' +
        '<td>' + s.ca1        + '</td>' +
        '<td>' + s.ca2        + '</td>' +
        '<td>' + s.exam       + '</td>' +
        '<td><strong>' + s.score + '</strong></td>' +
        '<td><strong>' + esc(s.grade) + '</strong></td>' +
        '<td>' + esc(s.remark || remarkFromGrade(s.grade)) + '</td>' +
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