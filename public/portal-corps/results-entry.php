<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER RESULTS ENTRY
   File: public/portal-corps/results-entry.php

   Lets a corps member enter/update scores for their assigned
   subject (corps_members.subject_taught), for any class within
   their section (corps_members.section). Unlike staff Subject
   Teachers, corps members' class_arms field is free text rather
   than a structured assignment, so class access is open within
   their section rather than restricted to specific classes —
   the same fallback behaviour staff get when no specific
   teacher_class_assignments rows exist for them.

   Submits to the same src/api/save_result_scores.php used by
   the staff results-entry page, which has been extended to
   accept corps sessions too.
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

$corpsMember = requireCorpsLogin();
$pdo         = getDB();

refreshCorpsSession($pdo);
$corpsMember = currentCorpsMember();

if (($corpsMember['status'] ?? 'active') !== 'active') {
    header('Location: dashboard.php');
    exit;
}

/* ── Grade levels available, based on the corps member's section ── */
$allGradeLevels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3',
                    'SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];

$section = $corpsMember['section'] ?? 'both';
if ($section === 'js') {
    $gradeLevelOptions = array_filter($allGradeLevels, fn($k) => str_starts_with($k, 'JSS'), ARRAY_FILTER_USE_KEY);
} elseif ($section === 'ss') {
    $gradeLevelOptions = array_filter($allGradeLevels, fn($k) => str_starts_with($k, 'SSS'), ARRAY_FILTER_USE_KEY);
} else {
    $gradeLevelOptions = $allGradeLevels; /* 'both' — open to everything */
}

/* ── Classes per grade level, from class_arms ── */
$classRows = $pdo->query(
    "SELECT grade_level, class FROM class_arms WHERE is_active = 1 ORDER BY grade_level ASC, class ASC"
)->fetchAll();
$classesByGradeLevel = [];
foreach ($classRows as $row) {
    if (array_key_exists($row['grade_level'], $gradeLevelOptions)) {
        $classesByGradeLevel[$row['grade_level']][] = $row['class'];
    }
}

/* ── Resolve their assigned subject ── */
$subjectName = trim($corpsMember['subject_taught'] ?? '');
$subjectId   = null;
if ($subjectName !== '') {
    $subjStmt = $pdo->prepare('SELECT id FROM subjects WHERE name = ? AND is_active = 1 LIMIT 1');
    $subjStmt->execute([$subjectName]);
    $subjectId = $subjStmt->fetchColumn() ?: null;
}

/* ── Selected filters ── */
$selectedGradeLevel = $_GET['grade_level'] ?? '';
$selectedClass       = $_GET['class']       ?? '';
$selectedSession     = $_GET['session']    ?? '2025/2026';
$selectedTerm        = $_GET['term']       ?? 'first';

$students = [];
$existingScores = [];

if ($subjectId && $selectedGradeLevel && $selectedClass && array_key_exists($selectedGradeLevel, $gradeLevelOptions)) {
    $studentStmt = $pdo->prepare(
        'SELECT id, admission_number, first_name, last_name, other_name
         FROM   students
         WHERE  grade_level = ? AND class = ? AND is_active = 1
         ORDER  BY last_name ASC, first_name ASC'
    );
    $studentStmt->execute([$selectedGradeLevel, $selectedClass]);
    $students = $studentStmt->fetchAll();

    if (!empty($students)) {
        $studentIds = array_column($students, 'id');
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));

        $scoreStmt = $pdo->prepare(
            "SELECT r.student_id, rs.ca1_score, rs.ca2_score, rs.exam_score, rs.position
             FROM   results r
             JOIN   result_scores rs ON rs.result_id = r.id
             WHERE  r.student_id IN ($placeholders)
             AND    r.session = ? AND r.term = ? AND rs.subject_id = ?"
        );
        $scoreStmt->execute([...$studentIds, $selectedSession, $selectedTerm, $subjectId]);
        foreach ($scoreStmt->fetchAll() as $row) {
            $existingScores[$row['student_id']] = $row;
        }
    }
}

function ordinalSuffix(int $n): string {
    if ($n % 100 >= 11 && $n % 100 <= 13) return $n . 'th';
    return $n . match ($n % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
}

$pageTitle = 'Enter Results — Corps Portal — Ibeku High School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo $pageTitle; ?></title>
<link rel="stylesheet" href="../assets/css/corps-portal.css"/>
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
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:9px 22px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
  .btn-filter:hover { background:#3a7dc4; }

  .position-note {
    background:#f0ecfa; color:#3d1a6e; padding:10px 16px;
    border-radius:8px; font-size:12.5px; margin-bottom:16px;
  }
  .role-note { background:#f0ecfa; color:#3d1a6e; padding:10px 16px; border-radius:8px; font-size:12.5px; margin-bottom:20px; }
  .role-note--warn { background:#fff3e6; color:#8a4a00; }

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
  .grade-pill { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
  .grade-A { background:#e6f9ed; color:#1a7a3a; }
  .grade-B { background:#e6f0ff; color:#1a5a9a; }
  .grade-C { background:#fffbe6; color:#8a6a00; }
  .grade-D { background:#fff3e6; color:#8a4a00; }
  .grade-E { background:#ffe6e6; color:#8a1a00; }
  .grade-F { background:#ffcccc; color:#cc0000; }

  .position-cell { text-align:center; }
  .position-pill { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; background:#f0ecfa; color:#3d1a6e; }
  .position-pending { font-size:12px; color:#9b97b0; }

  .save-bar { padding:18px 22px; border-top:1px solid #f0eef6; display:flex; justify-content:space-between; align-items:center; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-save:disabled { background:#c8c4dc; cursor:not-allowed; }

  .save-status { font-size:13px; }
  .save-status--success { color:#1a7a3a; }
  .save-status--error { color:#cc3333; }

  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
  .page-wrap { max-width:1000px; margin:0 auto; padding:24px 20px; }
</style>
</head>
<body>

<?php include dirname(__DIR__, 2) . '/src/includes/corps-nav.php'; ?>

<div class="page-wrap">

  <h2 style="font-family:'Playfair Display',serif;color:#3d1a6e;margin-bottom:6px">Enter Results</h2>
  <p style="color:#6b6b80;margin-bottom:20px">Select a grade level, class, session, and term to enter or update student scores for your assigned subject.</p>

  <?php if (!$subjectId): ?>
  <div class="role-note role-note--warn">
    ⚠️ No subject is currently assigned to your profile (or it doesn't match an active subject in the system).
    Please contact the school office or your Principal to have your assigned subject set correctly before you can enter results.
  </div>
  <?php else: ?>
  <div class="role-note">
    You are entering results as an NYSC Corps Member for <strong><?php echo htmlspecialchars($subjectName); ?></strong>.
    You can select any class within your section (<?php echo htmlspecialchars(sectionLabel($section)); ?>).
  </div>

  <form method="GET" class="filter-bar" id="filterForm">
    <div class="filter-group">
      <label for="grade_level">Grade Level</label>
      <select name="grade_level" id="grade_level" required>
        <option value="">Select grade level</option>
        <?php foreach ($gradeLevelOptions as $key => $label): ?>
        <option value="<?php echo $key; ?>" <?php echo $selectedGradeLevel === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label for="class">Class</label>
      <select name="class" id="class" required>
        <option value="">Select class</option>
        <?php if ($selectedGradeLevel && !empty($classesByGradeLevel[$selectedGradeLevel])): ?>
          <?php foreach ($classesByGradeLevel[$selectedGradeLevel] as $cls): ?>
          <option value="<?php echo htmlspecialchars($cls); ?>" <?php echo $selectedClass === $cls ? 'selected' : ''; ?>><?php echo htmlspecialchars($cls); ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
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

  <?php if ($selectedGradeLevel && $selectedClass): ?>
    <?php if (empty($students)): ?>
    <div class="entry-table-wrap">
      <div class="empty-state">No active students found in <?php echo htmlspecialchars($selectedGradeLevel . ' ' . $selectedClass); ?>.</div>
    </div>
    <?php else: ?>

    <div class="position-note">
      📊 <strong>Position in class</strong> is calculated automatically after saving — ranked by total score
      (1st Test + 2nd Test + Exam) for this subject, within <?php echo htmlspecialchars($selectedGradeLevel . ' ' . $selectedClass); ?>.
      Students with equal totals share the same position.
    </div>

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
            <th class="score-col">Position</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student):
            $existing = $existingScores[$student['id']] ?? null;
            $ca1  = $existing['ca1_score']  ?? '';
            $ca2  = $existing['ca2_score']  ?? '';
            $exam = $existing['exam_score'] ?? '';
            $position = $existing['position'] ?? null;
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
            <td class="total-cell total-display">—</td>
            <td class="grade-cell"><span class="grade-pill grade-display">—</span></td>
            <td class="position-cell">
              <?php if ($position !== null): ?>
              <span class="position-pill"><?php echo ordinalSuffix((int) $position); ?></span>
              <?php else: ?>
              <span class="position-pending">—</span>
              <?php endif; ?>
            </td>
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
    <div class="empty-state">Select a grade level and class above, then click "Load Class" to begin entering scores.</div>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>

<script>
  document.getElementById('grade_level')?.addEventListener('change', function () {
    document.getElementById('filterForm').submit();
  });

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

  function clampInput(input) {
    var val  = parseFloat(input.value);
    var max  = parseFloat(input.getAttribute('max'));
    var min  = parseFloat(input.getAttribute('min') || '0');
    if (!isNaN(val)) {
      if (val > max) { input.value = max; input.classList.add('invalid'); }
      else if (val < min) { input.value = min; input.classList.add('invalid'); }
      else { input.classList.remove('invalid'); }
    }
  }

  function recalcRow(row) {
    row.querySelectorAll('.score-input').forEach(clampInput);
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
      var hasInvalid = false;
      document.querySelectorAll('#scoreTable tbody tr').forEach(function (row) {
        recalcRow(row);
        if (row.querySelector('.score-input.invalid')) hasInvalid = true;
      });
      if (hasInvalid) {
        var statusEl = document.getElementById('saveStatus');
        statusEl.textContent = 'Please correct scores highlighted in red before saving.';
        statusEl.className = 'save-status save-status--error';
        return;
      }

      var rows = document.querySelectorAll('#scoreTable tbody tr');
      var formData = new FormData();
      formData.append('grade_level', <?php echo json_encode($selectedGradeLevel); ?>);
      formData.append('class',       <?php echo json_encode($selectedClass); ?>);
      formData.append('subject_id',  <?php echo json_encode($subjectId); ?>);
      formData.append('session',     <?php echo json_encode($selectedSession); ?>);
      formData.append('term',        <?php echo json_encode($selectedTerm); ?>);

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
          if (data.success) {
            setTimeout(function () { window.location.reload(); }, 1100);
          } else {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save All Scores';
          }
        })
        .catch(function () {
          statusEl.textContent = 'A connection error occurred. Please try again.';
          statusEl.className = 'save-status save-status--error';
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save All Scores';
        });
    });
  }
</script>

</body>
</html>