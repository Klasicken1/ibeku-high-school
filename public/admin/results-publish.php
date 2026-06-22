<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PUBLISH RESULTS
   File: public/admin/results-publish.php

   Accessible to: superadmin, vp_academics
   Shows a readiness overview for a grade_level+class+session+term
   — how many students have scores entered per subject — then
   lets the admin publish the whole class in one action via
   src/api/publish_results.php.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'vp_academics']);

$admin = currentAdmin();
$pdo   = getDB();

/* ── Section restriction ── */
$lockedSection = ($admin['role'] !== 'superadmin' && $admin['section'] !== 'both')
    ? $admin['section']
    : null;

$allGradeLevels = ['JSS1' => 'JSS 1', 'JSS2' => 'JSS 2', 'JSS3' => 'JSS 3',
                    'SSS1' => 'SSS 1', 'SSS2' => 'SSS 2', 'SSS3' => 'SSS 3'];

$gradeLevelOptions = $allGradeLevels;
if ($lockedSection === 'js') {
    $gradeLevelOptions = array_filter($allGradeLevels, fn($k) => str_starts_with($k, 'JSS'), ARRAY_FILTER_USE_KEY);
} elseif ($lockedSection === 'ss') {
    $gradeLevelOptions = array_filter($allGradeLevels, fn($k) => str_starts_with($k, 'SSS'), ARRAY_FILTER_USE_KEY);
}

/* ── Load active classes ── */
$allClasses = $pdo->query(
    "SELECT grade_level, class FROM class_arms WHERE is_active = 1 ORDER BY grade_level ASC, class ASC"
)->fetchAll();
$classesByGradeLevel = [];
foreach ($allClasses as $row) {
    $classesByGradeLevel[$row['grade_level']][] = $row['class'];
}

$selectedGradeLevel = $_GET['grade_level'] ?? '';
$selectedClass       = $_GET['class']       ?? '';
$selectedSession     = $_GET['session']     ?? '2025/2026';
$selectedTerm        = $_GET['term']        ?? 'first';

$readiness = [];
$alreadyPublished = 0;

if ($selectedGradeLevel && $selectedClass && array_key_exists($selectedGradeLevel, $gradeLevelOptions)) {
    /* ── Students in this grade level+class ── */
    $studentStmt = $pdo->prepare(
        'SELECT id, admission_number, first_name, last_name, other_name, department
         FROM   students
         WHERE  grade_level = ? AND class = ? AND is_active = 1
         ORDER  BY last_name ASC, first_name ASC'
    );
    $studentStmt->execute([$selectedGradeLevel, $selectedClass]);
    $students = $studentStmt->fetchAll();

    foreach ($students as $student) {
        /* How many subjects are expected for this student's department? */
        $deptCondition = str_starts_with($selectedGradeLevel, 'JSS') ? "section IN ('js','both')" : "section IN ('ss','both')";
        $expectedStmt = $pdo->query("SELECT COUNT(*) FROM subjects WHERE is_active = 1 AND $deptCondition");
        $expectedCount = (int) $expectedStmt->fetchColumn();

        /* How many scores actually entered for this student, this term/session? */
        $enteredStmt = $pdo->prepare(
            "SELECT COUNT(rs.id) AS entered, r.is_published
             FROM   results r
             LEFT JOIN result_scores rs ON rs.result_id = r.id
             WHERE  r.student_id = ? AND r.session = ? AND r.term = ?
             GROUP  BY r.id, r.is_published"
        );
        $enteredStmt->execute([$student['id'], $selectedSession, $selectedTerm]);
        $enteredRow = $enteredStmt->fetch();

        $enteredCount = $enteredRow ? (int) $enteredRow['entered'] : 0;
        $isPublished  = $enteredRow ? (bool) $enteredRow['is_published'] : false;

        if ($isPublished) $alreadyPublished++;

        $readiness[] = [
            'name'     => trim($student['first_name'] . ' ' . ($student['other_name'] ? $student['other_name'] . ' ' : '') . $student['last_name']),
            'admno'    => $student['admission_number'],
            'entered'  => $enteredCount,
            'expected' => $expectedCount,
            'complete' => $enteredCount >= $expectedCount && $expectedCount > 0,
            'published'=> $isPublished,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Publish Results — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
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

  .readiness-summary {
    background:#fff; border:1px solid #e8e6f0; border-radius:14px;
    padding:20px 24px; margin-bottom:20px;
    display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;
  }
  .readiness-summary__text { font-size:13.5px; color:#1a1a2e; }
  .readiness-summary__text strong { color:#3d1a6e; }

  .btn-publish {
    background:#3d1a6e; color:#fff; border:none; padding:12px 28px;
    border-radius:8px; font-size:14px; font-weight:700; cursor:pointer;
  }
  .btn-publish:hover { background:#5a2d9e; }
  .btn-publish:disabled { background:#c8c4dc; cursor:not-allowed; }

  .readiness-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.readiness-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.readiness-table th {
    background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left;
    font-size:11.5px; text-transform:uppercase; letter-spacing:.04em;
  }
  table.readiness-table td { padding:11px 14px; border-bottom:1px solid #f0eef6; }
  table.readiness-table tr:last-child td { border-bottom:none; }

  .badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
  .badge--complete { background:#e6f9ed; color:#1a7a3a; }
  .badge--incomplete { background:#fff3e6; color:#8a4a00; }
  .badge--published { background:#e6f0ff; color:#1a5a9a; }

  .save-status { font-size:13px; margin-top:14px; padding:10px 14px; border-radius:8px; }
  .save-status--success { background:#e6f9ed; color:#1a7a3a; }
  .save-status--error { background:#ffe6e6; color:#cc3333; }

  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'results-publish'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Publish Results</h2>
        <p>Review readiness, then publish a grade level and class's results for the selected term. Position and grade level rank are calculated automatically at the moment of publishing.</p>
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
          <input type="text" name="session" id="session" value="<?php echo htmlspecialchars($selectedSession); ?>" pattern="\d{4}/\d{4}" required/>
        </div>

        <div class="filter-group">
          <label for="term">Term</label>
          <select name="term" id="term" required>
            <option value="first"  <?php echo $selectedTerm === 'first'  ? 'selected' : ''; ?>>First Term</option>
            <option value="second" <?php echo $selectedTerm === 'second' ? 'selected' : ''; ?>>Second Term</option>
            <option value="third"  <?php echo $selectedTerm === 'third'  ? 'selected' : ''; ?>>Third Term</option>
          </select>
        </div>

        <button type="submit" class="btn-filter">Load Readiness</button>
      </form>

      <?php if ($selectedGradeLevel && $selectedClass): ?>

        <?php
        $completeCount = count(array_filter($readiness, fn($r) => $r['complete']));
        $totalCount    = count($readiness);
        ?>

        <div class="readiness-summary">
          <div class="readiness-summary__text">
            <strong><?php echo $completeCount; ?> of <?php echo $totalCount; ?></strong> students have complete scores
            <?php if ($alreadyPublished > 0): ?>
            &nbsp;&middot;&nbsp; <strong><?php echo $alreadyPublished; ?></strong> already published
            <?php endif; ?>
          </div>
          <button type="button" class="btn-publish" id="publishBtn" <?php echo $totalCount === 0 ? 'disabled' : ''; ?>>
            Publish <?php echo htmlspecialchars($selectedGradeLevel . ' ' . $selectedClass); ?> Results
          </button>
        </div>

        <div id="publishStatus"></div>

        <div class="readiness-table-wrap">
          <?php if (empty($readiness)): ?>
          <div class="empty-state">No active students found in <?php echo htmlspecialchars($selectedGradeLevel . ' ' . $selectedClass); ?>.</div>
          <?php else: ?>
          <table class="readiness-table">
            <thead>
              <tr>
                <th>Admission No.</th>
                <th>Student Name</th>
                <th>Scores Entered</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($readiness as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['admno']); ?></td>
                <td><?php echo htmlspecialchars($r['name']); ?></td>
                <td><?php echo $r['entered']; ?> of <?php echo $r['expected']; ?> subjects</td>
                <td>
                  <?php if ($r['published']): ?>
                  <span class="badge badge--published">Published</span>
                  <?php elseif ($r['complete']): ?>
                  <span class="badge badge--complete">Complete</span>
                  <?php else: ?>
                  <span class="badge badge--incomplete">Incomplete</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

      <?php else: ?>
      <div class="readiness-table-wrap">
        <div class="empty-state">Select a grade level, class, session, and term above to review readiness before publishing.</div>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    var classesByGradeLevel = <?php echo json_encode($classesByGradeLevel); ?>;
    var gradeLevelSelect = document.getElementById('grade_level');
    var classSelect       = document.getElementById('class');

    gradeLevelSelect.addEventListener('change', function () {
      var gradeLevelKey = gradeLevelSelect.value;
      classSelect.innerHTML = '<option value="">Select class</option>';
      if (gradeLevelKey && classesByGradeLevel[gradeLevelKey]) {
        classesByGradeLevel[gradeLevelKey].forEach(function (cls) {
          var opt = document.createElement('option');
          opt.value = cls;
          opt.textContent = cls;
          classSelect.appendChild(opt);
        });
      }
    });

    var publishBtn = document.getElementById('publishBtn');
    if (publishBtn) {
      publishBtn.addEventListener('click', function () {
        var incomplete = document.querySelectorAll('.badge--incomplete').length;

        if (incomplete > 0) {
          var proceed = confirm(
            incomplete + ' student(s) have incomplete scores. Publish anyway? ' +
            'Their results will show only the subjects entered so far.'
          );
          if (!proceed) return;
        }

        var formData = new FormData();
        formData.append('mode',        'class');
        formData.append('grade_level', <?php echo json_encode($selectedGradeLevel); ?>);
        formData.append('class',       <?php echo json_encode($selectedClass); ?>);
        formData.append('session',     <?php echo json_encode($selectedSession); ?>);
        formData.append('term',        <?php echo json_encode($selectedTerm); ?>);

        publishBtn.disabled = true;
        publishBtn.textContent = 'Publishing…';

        var statusEl = document.getElementById('publishStatus');

        fetch('../../src/api/publish_results.php', { method: 'POST', body: formData })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            statusEl.innerHTML = '<div class="save-status save-status--' +
              (data.success ? 'success' : 'error') + '">' + data.message + '</div>';
            if (data.success) {
              setTimeout(function () { location.reload(); }, 1200);
            }
          })
          .catch(function () {
            statusEl.innerHTML = '<div class="save-status save-status--error">A connection error occurred. Please try again.</div>';
          })
          .finally(function () {
            publishBtn.disabled = false;
            publishBtn.textContent = 'Publish <?php echo htmlspecialchars($selectedGradeLevel . " " . $selectedClass); ?> Results';
          });
      });
    }
  </script>

</body>
</html>