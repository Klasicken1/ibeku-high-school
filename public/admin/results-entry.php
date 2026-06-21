<?php
/* ============================================================
   IBEKU HIGH SCHOOL — RESULT SCORE ENTRY
   File: public/admin/results-entry.php

   Accessible to: superadmin, subject_teacher, form_teacher, vp_academics
   Subject Teachers see all subjects in the dropdown for context,
   but save_result_scores.php enforces server-side that they can
   only save scores for the subject matching their department.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';

requireRole(['superadmin', 'subject_teacher', 'form_teacher', 'vp_academics']);

$admin = currentAdmin();
$pdo   = getDB();

/* ── Section restriction: non-superadmin locked to their own section ── */
$lockedSection = ($admin['role'] !== 'superadmin' && $admin['section'] !== 'both')
    ? $admin['section']
    : null;

$allClasses = ['JSS1' => 'JSS 1', 'JSS2' => 'JSS 2', 'JSS3' => 'JSS 3',
               'SSS1' => 'SSS 1', 'SSS2' => 'SSS 2', 'SSS3' => 'SSS 3'];

$classOptions = $allClasses;
if ($lockedSection === 'js') {
    $classOptions = array_filter($allClasses, fn($k) => str_starts_with($k, 'JSS'), ARRAY_FILTER_USE_KEY);
} elseif ($lockedSection === 'ss') {
    $classOptions = array_filter($allClasses, fn($k) => str_starts_with($k, 'SSS'), ARRAY_FILTER_USE_KEY);
}

/* ── Load all subjects for the dropdown ── */
$subjects = $pdo->query('SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name ASC')->fetchAll();

/* ── Selected filters (from query string, GET) ── */
$selectedClass   = $_GET['class']      ?? '';
$selectedSubject = (int) ($_GET['subject_id'] ?? 0);
$selectedSession = $_GET['session']    ?? '2025/2026';
$selectedTerm    = $_GET['term']       ?? 'first';

$students = [];
$existingScores = [];

if ($selectedClass && $selectedSubject && array_key_exists($selectedClass, $classOptions)) {
    /* ── Load students in this class ── */
    $studentStmt = $pdo->prepare(
        'SELECT id, admission_number, first_name, last_name, other_name
         FROM   students
         WHERE  current_class = ? AND is_active = 1
         ORDER  BY last_name ASC, first_name ASC'
    );
    $studentStmt->execute([$selectedClass]);
    $students = $studentStmt->fetchAll();

    /* ── Load any existing scores for these students, this subject/term/session ── */
    if (!empty($students)) {
        $studentIds = array_column($students, 'id');
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));

        $scoreStmt = $pdo->prepare(
            "SELECT r.student_id, rs.ca1_score, rs.ca2_score, rs.exam_score
             FROM   results r
             JOIN   result_scores rs ON rs.result_id = r.id
             WHERE  r.student_id IN ($placeholders)
             AND    r.session = ?
             AND    r.term    = ?
             AND    rs.subject_id = ?"
        );
        $scoreStmt->execute([...$studentIds, $selectedSession, $selectedTerm, $selectedSubject]);
        foreach ($scoreStmt->fetchAll() as $row) {
            $existingScores[$row['student_id']] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Enter Results — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-timetables.css">
<style>
  .filter-bar {
    background:#fff; border:1px solid #e8e6f0; border-radius:14px;
    padding:20px 22px; margin-bottom:24px;
    display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;
  }
  .filter-group { display:flex; flex-direction:column; gap:5px; min-width:140px; }
  .filter-group label { font-size:11.5px; font-weight:600; color:#3d1a6e; text-transform:uppercase; letter-spacing:.04em; }
  .filter-group select, .filter-group input {
    padding:8px 10px; border:1.5px solid #e2e0ea; border-radius:7px;
    font-size:13px; font-family:'DM Sans', sans-serif;
  }
  .btn-filter {
    background:#4a90d9; color:#fff; border:none; padding:9px 22px;
    border-radius:7px; font-size:13px; font-weight:600; cursor:pointer;
  }
  .btn-filter:hover { background:#3a7dc4; }

  .entry-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.entry-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.entry-table th {
    background:#3d1a6e; color:#fff; padding:11px 12px; text-align:left; font-size:11.5px;
    text-transform:uppercase; letter-spacing:.04em;
  }
  table.entry-table th.score-col { text-align:center; width:90px; }
  table.entry-table td { padding:9px 12px; border-bottom:1px solid #f0eef6; }
  table.entry-table tr:last-child td { border-bottom:none; }
  table.entry-table tr:nth-child(even) td { background:#faf9fd; }

  .score-input {
    width:60px; padding:6px 8px; border:1.5px solid #e2e0ea; border-radius:6px;
    font-size:13px; text-align:center; font-family:'DM Sans', sans-serif;
  }
  .score-input:focus { outline:none; border-color:#4a90d9; }
  .score-input.invalid { border-color:#cc3333; background:#ffe6e6; }

  .total-cell { font-weight:700; color:#3d1a6e; text-align:center; }
  .grade-cell { text-align:center; }
  .grade-pill {
    display:inline-block; padding:2px 10px; border-radius:20px;
    font-size:11px; font-weight:700;
  }
  .grade-A { background:#e6f9ed; color:#1a7a3a; }
  .grade-B { background:#e6f0ff; color:#1a5a9a; }
  .grade-C { background:#fffbe6; color:#8a6a00; }
  .grade-D { background:#fff3e6; color:#8a4a00; }
  .grade-E { background:#ffe6e6; color:#8a1a00; }
  .grade-F { background:#ffcccc; color:#cc0000; }

  .save-bar {
    padding:18px 22px; border-top:1px solid #f0eef6;
    display:flex; justify-content:space-between; align-items:center;
  }
  .btn-save {
    background:#3d1a6e; color:#fff; border:none; padding:11px 28px;
    border-radius:8px; font-size:14px; font-weight:700; cursor:pointer;
  }
  .btn-save:hover { background:#5a2d9e; }
  .btn-save:disabled { background:#c8c4dc; cursor:not-allowed; }

  .save-status { font-size:13px; }
  .save-status--success { color:#1a7a3a; }
  .save-status--error { color:#cc3333; }

  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
  .role-note {
    background:#f0ecfa; color:#3d1a6e; padding:10px 16px; border-radius:8px;
    font-size:12.5px; margin-bottom:20px;
  }
</style>
</head>
<body>

  <div class="topbar">
    <div class="topbar__brand">
      <div class="topbar__logo">IHS</div>
      <h1>Ibeku High School — Admin Panel</h1>
    </div>
    <a href="index.php" class="back-link">← Back to Dashboard</a>
  </div>

  <div class="main">

    <div class="page-header">
      <h2>Enter Results</h2>
      <p>Select a class, subject, session, and term to enter or update student scores. Results stay in draft until published.</p>
    </div>

    <?php if ($admin['role'] === 'subject_teacher'): ?>
    <div class="role-note">
      You are signed in as a Subject Teacher for <strong><?php echo htmlspecialchars($admin['dept'] ?? 'no subject assigned'); ?></strong>.
      You can only save scores for that subject — selecting another subject in the dropdown is for viewing only and will be rejected on save.
    </div>
    <?php endif; ?>

    <form method="GET" class="filter-bar" id="filterForm">
      <div class="filter-group">
        <label for="class">Class</label>
        <select name="class" id="class" required>
          <option value="">Select class</option>
          <?php foreach ($classOptions as $key => $label): ?>
          <option value="<?php echo $key; ?>" <?php echo $selectedClass === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-group">
        <label for="subject_id">Subject</label>
        <select name="subject_id" id="subject_id" required>
          <option value="">Select subject</option>
          <?php foreach ($subjects as $subj): ?>
          <option value="<?php echo $subj['id']; ?>" <?php echo $selectedSubject === (int) $subj['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($subj['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-group">
        <label for="session">Session</label>
        <input type="text" name="session" id="session" value="<?php echo htmlspecialchars($selectedSession); ?>" pattern="\d{4}/\d{4}" placeholder="2025/2026" required/>
      </div>

      <div class="filter-group">
        <label for="term">Term</label>
        <select name="term" id="term" required>
          <option value="first"  <?php echo $selectedTerm === 'first'  ? 'selected' : ''; ?>>First Term</option>
          <option value="second" <?php echo $selectedTerm === 'second' ? 'selected' : ''; ?>>Second Term</option>
          <option value="third"  <?php echo $selectedTerm === 'third'  ? 'selected' : ''; ?>>Third Term</option>
        </select>
      </div>

      <button type="submit" class="btn-filter">Load Class</button>
    </form>

    <?php if ($selectedClass && $selectedSubject): ?>

      <?php if (empty($students)): ?>
      <div class="entry-table-wrap">
        <div class="empty-state">No active students found in <?php echo htmlspecialchars($selectedClass); ?>.</div>
      </div>
      <?php else: ?>

      <div class="entry-table-wrap">
        <table class="entry-table" id="scoreTable">
          <thead>
            <tr>
              <th>Admission No.</th>
              <th>Student Name</th>
              <th class="score-col">1st Test (15)</th>
              <th class="score-col">2nd Test (15)</th>
              <th class="score-col">Exam (70)</th>
              <th class="score-col">Total</th>
              <th class="score-col">Grade</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $student):
              $existing = $existingScores[$student['id']] ?? null;
              $ca1  = $existing['ca1_score']  ?? '';
              $ca2  = $existing['ca2_score']  ?? '';
              $exam = $existing['exam_score'] ?? '';
              $fullName = trim($student['first_name'] . ' ' . ($student['other_name'] ? $student['other_name'] . ' ' : '') . $student['last_name']);
            ?>
            <tr data-student-id="<?php echo $student['id']; ?>">
              <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
              <td><?php echo htmlspecialchars($fullName); ?></td>
              <td class="score-col">
                <input type="number" class="score-input ca1-input" min="0" max="15" step="0.5" value="<?php echo htmlspecialchars((string) $ca1); ?>"/>
              </td>
              <td class="score-col">
                <input type="number" class="score-input ca2-input" min="0" max="15" step="0.5" value="<?php echo htmlspecialchars((string) $ca2); ?>"/>
              </td>
              <td class="score-col">
                <input type="number" class="score-input exam-input" min="0" max="70" step="0.5" value="<?php echo htmlspecialchars((string) $exam); ?>"/>
              </td>
              <td class="total-cell total-display">0</td>
              <td class="grade-cell"><span class="grade-pill grade-display">—</span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="save-bar">
          <span class="save-status" id="saveStatus"></span>
          <button type="button" class="btn-save" id="saveBtn">Save All Scores</button>
        </div>
      </div>

      <?php endif; ?>

    <?php else: ?>
    <div class="entry-table-wrap">
      <div class="empty-state">Select a class and subject above, then click "Load Class" to begin entering scores.</div>
    </div>
    <?php endif; ?>

  </div>

  <script>
    /* ── Live grade calculation as teacher types ── */
    function gradeFromTotal(total) {
      if (total >= 75) return { grade: 'A1', letter: 'A' };
      if (total >= 70) return { grade: 'B2', letter: 'B' };
      if (total >= 65) return { grade: 'B3', letter: 'B' };
      if (total >= 60) return { grade: 'C4', letter: 'C' };
      if (total >= 55) return { grade: 'C5', letter: 'C' };
      if (total >= 50) return { grade: 'C6', letter: 'C' };
      if (total >= 45) return { grade: 'D7', letter: 'D' };
      if (total >= 40) return { grade: 'E8', letter: 'E' };
      return { grade: 'F9', letter: 'F' };
    }

    function recalcRow(row) {
      var ca1  = parseFloat(row.querySelector('.ca1-input').value)  || 0;
      var ca2  = parseFloat(row.querySelector('.ca2-input').value)  || 0;
      var exam = parseFloat(row.querySelector('.exam-input').value) || 0;
      var total = ca1 + ca2 + exam;

      row.querySelector('.total-display').textContent = total.toFixed(1);

      var gradeInfo = gradeFromTotal(total);
      var gradeEl = row.querySelector('.grade-display');
      gradeEl.textContent = gradeInfo.grade;
      gradeEl.className = 'grade-pill grade-display grade-' + gradeInfo.letter;
    }

    document.querySelectorAll('#scoreTable tbody tr').forEach(function (row) {
      recalcRow(row);
      row.querySelectorAll('.score-input').forEach(function (input) {
        input.addEventListener('input', function () { recalcRow(row); });
      });
    });

    var saveBtn = document.getElementById('saveBtn');
    if (saveBtn) {
      saveBtn.addEventListener('click', function () {
        var rows = document.querySelectorAll('#scoreTable tbody tr');
        var formData = new FormData();

        formData.append('class',      <?php echo json_encode($selectedClass); ?>);
        formData.append('subject_id', <?php echo json_encode($selectedSubject); ?>);
        formData.append('session',    <?php echo json_encode($selectedSession); ?>);
        formData.append('term',       <?php echo json_encode($selectedTerm); ?>);

        rows.forEach(function (row) {
          formData.append('student_id[]',  row.dataset.studentId);
          formData.append('score_ca1[]',   row.querySelector('.ca1-input').value  || 0);
          formData.append('score_ca2[]',   row.querySelector('.ca2-input').value  || 0);
          formData.append('score_exam[]',  row.querySelector('.exam-input').value || 0);
        });

        var statusEl = document.getElementById('saveStatus');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';

        fetch('../../src/api/save_result_scores.php', { method: 'POST', body: formData })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            statusEl.textContent = data.message;
            statusEl.className = 'save-status ' + (data.success ? 'save-status--success' : 'save-status--error');
          })
          .catch(function () {
            statusEl.textContent = 'A connection error occurred. Please try again.';
            statusEl.className = 'save-status save-status--error';
          })
          .finally(function () {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save All Scores';
          });
      });
    }
  </script>

</body>
</html>