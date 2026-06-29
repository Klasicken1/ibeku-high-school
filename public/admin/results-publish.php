<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PUBLISH RESULTS
   File: public/admin/results-publish.php

   Accessible to: superadmin, vp_academics

   Two modes on this page:
   1. CLASS PUBLISH — select a grade level + class, review
      readiness and approval status, then publish. Blocked if
      form teacher has not approved.
   2. GRADE LEVEL CUMULATIVE — after all classes in a grade
      level are individually published, calculate the overall
      grade level ranking across all classes combined.
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

/* ── Active mode: 'class' or 'cumulative' ── */
$activeMode = $_GET['mode'] ?? 'class';
if (!in_array($activeMode, ['class', 'cumulative'], true)) $activeMode = 'class';

$selectedGradeLevel = $_GET['grade_level'] ?? '';
$selectedClass       = $_GET['class']       ?? '';
$selectedSession     = $_GET['session']     ?? '2025/2026';
$selectedTerm        = $_GET['term']        ?? 'first';

/* ════════════════════════════════════════════════════════════
   MODE: CLASS — readiness + approval data
   ════════════════════════════════════════════════════════════ */
$readiness        = [];
$alreadyPublished = 0;
$approvedCount    = 0;

if ($activeMode === 'class' && $selectedGradeLevel && $selectedClass && array_key_exists($selectedGradeLevel, $gradeLevelOptions)) {

    $studentStmt = $pdo->prepare(
        'SELECT id, admission_number, first_name, last_name, other_name
         FROM   students
         WHERE  grade_level = ? AND class = ? AND is_active = 1
         ORDER  BY last_name ASC, first_name ASC'
    );
    $studentStmt->execute([$selectedGradeLevel, $selectedClass]);
    $students = $studentStmt->fetchAll();

    $deptCondition = str_starts_with($selectedGradeLevel, 'JSS') ? "section IN ('js','both')" : "section IN ('ss','both')";
    $expectedCount = (int) $pdo->query("SELECT COUNT(*) FROM subjects WHERE is_active = 1 AND $deptCondition")->fetchColumn();

    foreach ($students as $student) {
        $enteredStmt = $pdo->prepare(
            "SELECT COUNT(rs.id) AS entered, r.is_published, r.is_approved
             FROM   results r
             LEFT JOIN result_scores rs ON rs.result_id = r.id
             WHERE  r.student_id = ? AND r.session = ? AND r.term = ?
             GROUP  BY r.id, r.is_published, r.is_approved"
        );
        $enteredStmt->execute([$student['id'], $selectedSession, $selectedTerm]);
        $enteredRow = $enteredStmt->fetch();

        $enteredCount = $enteredRow ? (int) $enteredRow['entered']     : 0;
        $isPublished  = $enteredRow ? (bool) $enteredRow['is_published'] : false;
        $isApproved   = $enteredRow ? (bool) $enteredRow['is_approved']  : false;

        if ($isPublished) $alreadyPublished++;
        if ($isApproved)  $approvedCount++;

        $readiness[] = [
            'name'      => trim($student['first_name'] . ' ' . ($student['other_name'] ? $student['other_name'] . ' ' : '') . $student['last_name']),
            'admno'     => $student['admission_number'],
            'entered'   => $enteredCount,
            'expected'  => $expectedCount,
            'complete'  => $enteredCount >= $expectedCount && $expectedCount > 0,
            'published' => $isPublished,
            'approved'  => $isApproved,
        ];
    }
}

/* ════════════════════════════════════════════════════════════
   MODE: CUMULATIVE — per-class publish status for a grade level
   ════════════════════════════════════════════════════════════ */
$classStatuses       = [];
$allClassesPublished = false;
$cumulativeDone      = false;

if ($activeMode === 'cumulative' && $selectedGradeLevel && array_key_exists($selectedGradeLevel, $gradeLevelOptions)) {

    $classStmt = $pdo->prepare(
        "SELECT ca.class,
                COUNT(DISTINCT s.id) AS total_students,
                COUNT(DISTINCT CASE WHEN r.is_published = 1 THEN r.id END) AS published_count,
                COUNT(DISTINCT CASE WHEN r.grade_level_position IS NOT NULL THEN r.id END) AS ranked_count
         FROM   class_arms ca
         LEFT JOIN students s  ON s.grade_level = ca.grade_level AND s.class = ca.class AND s.is_active = 1
         LEFT JOIN results  r  ON r.student_id = s.id AND r.session = ? AND r.term = ?
         WHERE  ca.grade_level = ? AND ca.is_active = 1
         GROUP  BY ca.class
         ORDER  BY ca.class ASC"
    );
    $classStmt->execute([$selectedSession, $selectedTerm, $selectedGradeLevel]);
    $classStatuses = $classStmt->fetchAll();

    $unpublished = array_filter($classStatuses, fn($cs) =>
        (int) $cs['total_students'] > 0 && (int) $cs['published_count'] < (int) $cs['total_students']
    );
    $allClassesPublished = empty($unpublished) && !empty($classStatuses);

    $ranked = array_filter($classStatuses, fn($cs) => (int) $cs['ranked_count'] > 0);
    $cumulativeDone = !empty($ranked);
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
  .mode-tabs {
    display:flex; gap:0; margin-bottom:24px;
    background:#fff; border:1px solid #e8e6f0; border-radius:12px; overflow:hidden;
  }
  .mode-tab {
    flex:1; padding:13px 20px; text-align:center; font-size:13.5px; font-weight:600;
    color:#6b6b80; text-decoration:none; border-right:1px solid #e8e6f0;
    transition:background .15s, color .15s;
  }
  .mode-tab:last-child { border-right:none; }
  .mode-tab--active { background:#3d1a6e; color:#fff; }
  .mode-tab:not(.mode-tab--active):hover { background:#f4f3f9; color:#3d1a6e; }

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
  .readiness-summary__text { font-size:13.5px; color:#1a1a2e; line-height:1.7; }
  .readiness-summary__text strong { color:#3d1a6e; }

  .btn-publish {
    background:#3d1a6e; color:#fff; border:none; padding:12px 28px;
    border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; white-space:nowrap;
  }
  .btn-publish:hover { background:#5a2d9e; }
  .btn-publish:disabled { background:#c8c4dc; cursor:not-allowed; }

  .btn-cumulative {
    background:#1a7a3a; color:#fff; border:none; padding:12px 28px;
    border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; white-space:nowrap;
  }
  .btn-cumulative:hover { background:#155e2d; }
  .btn-cumulative:disabled { background:#c8c4dc; cursor:not-allowed; }

  .readiness-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.readiness-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.readiness-table th {
    background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left;
    font-size:11.5px; text-transform:uppercase; letter-spacing:.04em;
  }
  table.readiness-table td { padding:11px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.readiness-table tr:last-child td { border-bottom:none; }

  .badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
  .badge--complete   { background:#e6f9ed; color:#1a7a3a; }
  .badge--incomplete { background:#fff3e6; color:#8a4a00; }
  .badge--published  { background:#e6f0ff; color:#1a5a9a; }
  .badge--approved   { background:#e6f9ed; color:#1a7a3a; }
  .badge--pending    { background:#ffe6e6; color:#cc3333; }
  .badge--ranked     { background:#f0ecfa; color:#3d1a6e; }

  .approval-warning {
    background:#ffe6e6; border:1px solid #ffcccc; color:#cc3333;
    padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:16px;
  }

  .cumulative-info {
    background:#f0ecfa; border:1px solid #d8d0ee; color:#3d1a6e;
    padding:14px 18px; border-radius:10px; font-size:13px; margin-bottom:20px; line-height:1.6;
  }

  .save-status { font-size:13px; margin-top:14px; padding:10px 14px; border-radius:8px; }
  .save-status--success { background:#e6f9ed; color:#1a7a3a; }
  .save-status--error   { background:#ffe6e6; color:#cc3333; }

  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'results-publish'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Publish Results</h2>
        <p>Publish individual classes after form teacher approval, then calculate grade level rankings once all classes are published.</p>
      </div>

      <!-- Mode tabs -->
      <?php
      $baseParams = http_build_query([
          'grade_level' => $selectedGradeLevel,
          'session'     => $selectedSession,
          'term'        => $selectedTerm,
      ]);
      ?>
      <div class="mode-tabs">
        <a href="?mode=class&<?php echo $baseParams; ?><?php echo $selectedClass ? '&class=' . urlencode($selectedClass) : ''; ?>"
           class="mode-tab <?php echo $activeMode === 'class' ? 'mode-tab--active' : ''; ?>">
          📋 Publish Class
        </a>
        <a href="?mode=cumulative&<?php echo $baseParams; ?>"
           class="mode-tab <?php echo $activeMode === 'cumulative' ? 'mode-tab--active' : ''; ?>">
          🏆 Grade Level Rankings
        </a>
      </div>

      <?php if ($activeMode === 'class'): ?>
      <!-- ════════════════════════════════════════
           CLASS PUBLISH MODE
           ════════════════════════════════════════ -->

      <form method="GET" class="filter-bar" id="filterForm">
        <input type="hidden" name="mode" value="class"/>
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
        $totalCount    = count($readiness);
        $completeCount = count(array_filter($readiness, fn($r) => $r['complete']));
        $pendingApproval = count(array_filter($readiness, fn($r) => !$r['approved'] && !$r['published'] && $r['entered'] > 0));
        $allApproved   = $totalCount > 0 && $approvedCount >= $totalCount;
        ?>

        <?php if ($pendingApproval > 0): ?>
        <div class="approval-warning">
          ⚠ <strong><?php echo $pendingApproval; ?> student result(s) are pending form teacher approval.</strong>
          Publishing is blocked until the Form Teacher approves all results for this class.
          Ask the Form Teacher to visit the <strong>Approve Results</strong> page.
        </div>
        <?php endif; ?>

        <div class="readiness-summary">
          <div class="readiness-summary__text">
            <strong><?php echo $completeCount; ?> of <?php echo $totalCount; ?></strong> students have complete scores
            &nbsp;·&nbsp;
            <strong><?php echo $approvedCount; ?> of <?php echo $totalCount; ?></strong> approved by form teacher
            <?php if ($alreadyPublished > 0): ?>
            &nbsp;·&nbsp; <strong><?php echo $alreadyPublished; ?></strong> already published
            <?php endif; ?>
          </div>
          <button type="button" class="btn-publish" id="publishBtn"
                  <?php echo ($totalCount === 0 || !$allApproved) ? 'disabled' : ''; ?>>
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
                <th>Score Status</th>
                <th>Approval</th>
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
                <td>
                  <?php if ($r['published']): ?>
                  <span class="badge badge--published">Published</span>
                  <?php elseif ($r['approved']): ?>
                  <span class="badge badge--approved">Approved</span>
                  <?php elseif ($r['entered'] > 0): ?>
                  <span class="badge badge--pending">Awaiting Approval</span>
                  <?php else: ?>
                  <span style="color:#c8c4dc;font-size:11px">—</span>
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

      <?php else: ?>
      <!-- ════════════════════════════════════════
           CUMULATIVE RANKINGS MODE
           ════════════════════════════════════════ -->

      <div class="cumulative-info">
        <strong>How this works:</strong> After you have published each class individually (using the Publish Class tab),
        use this page to calculate the overall grade level ranking — this places every student in
        <?php echo $selectedGradeLevel ? htmlspecialchars($allGradeLevels[$selectedGradeLevel] ?? $selectedGradeLevel) : 'the grade level'; ?>
        on a single ranked list, regardless of which class they are in.
        The result checker will then show both their class position and their overall grade level position.
      </div>

      <form method="GET" class="filter-bar" id="cumulativeFilterForm">
        <input type="hidden" name="mode" value="cumulative"/>
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
        <button type="submit" class="btn-filter">Check Status</button>
      </form>

      <?php if ($selectedGradeLevel && !empty($classStatuses)): ?>

        <div class="readiness-summary">
          <div class="readiness-summary__text">
            <?php if ($allClassesPublished): ?>
              <strong>All classes published</strong> — ready to calculate grade level rankings.
              <?php if ($cumulativeDone): ?>
              Rankings have already been calculated. You can recalculate to update them.
              <?php endif; ?>
            <?php else: ?>
              <strong>Some classes are not yet published.</strong>
              Publish all classes first using the Publish Class tab.
            <?php endif; ?>
          </div>
          <button type="button" class="btn-cumulative" id="cumulativeBtn"
                  <?php echo !$allClassesPublished ? 'disabled' : ''; ?>>
            <?php echo $cumulativeDone ? 'Recalculate ' : 'Calculate '; ?>
            <?php echo htmlspecialchars($allGradeLevels[$selectedGradeLevel] ?? $selectedGradeLevel); ?> Rankings
          </button>
        </div>

        <div id="cumulativeStatus"></div>

        <div class="readiness-table-wrap">
          <table class="readiness-table">
            <thead>
              <tr>
                <th>Class</th>
                <th>Total Students</th>
                <th>Published</th>
                <th>Grade Level Ranked</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($classStatuses as $cs): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($selectedGradeLevel . ' ' . $cs['class']); ?></strong></td>
                <td><?php echo (int) $cs['total_students']; ?></td>
                <td><?php echo (int) $cs['published_count']; ?></td>
                <td><?php echo (int) $cs['ranked_count']; ?></td>
                <td>
                  <?php if ((int) $cs['total_students'] === 0): ?>
                    <span style="color:#c8c4dc;font-size:11px">No students</span>
                  <?php elseif ((int) $cs['ranked_count'] > 0): ?>
                    <span class="badge badge--ranked">Ranked</span>
                  <?php elseif ((int) $cs['published_count'] >= (int) $cs['total_students']): ?>
                    <span class="badge badge--published">Published</span>
                  <?php else: ?>
                    <span class="badge badge--pending">Not Published</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($selectedGradeLevel): ?>
      <div class="readiness-table-wrap">
        <div class="empty-state">No active classes found for <?php echo htmlspecialchars($allGradeLevels[$selectedGradeLevel] ?? $selectedGradeLevel); ?>.</div>
      </div>
      <?php else: ?>
      <div class="readiness-table-wrap">
        <div class="empty-state">Select a grade level, session, and term above to check ranking status.</div>
      </div>
      <?php endif; ?>

      <?php endif; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    var classesByGradeLevel = <?php echo json_encode($classesByGradeLevel); ?>;
    var gradeLevelSelect = document.getElementById('grade_level');
    var classSelect       = document.getElementById('class');

    if (gradeLevelSelect && classSelect) {
      gradeLevelSelect.addEventListener('change', function () {
        var gl = gradeLevelSelect.value;
        classSelect.innerHTML = '<option value="">Select class</option>';
        if (gl && classesByGradeLevel[gl]) {
          classesByGradeLevel[gl].forEach(function (cls) {
            var opt = document.createElement('option');
            opt.value = cls; opt.textContent = cls;
            classSelect.appendChild(opt);
          });
        }
      });
    }

    /* ── Class publish ── */
    var publishBtn = document.getElementById('publishBtn');
    if (publishBtn) {
      publishBtn.addEventListener('click', function () {
        var incomplete = document.querySelectorAll('.badge--incomplete').length;
        if (incomplete > 0) {
          var proceed = confirm(incomplete + ' student(s) have incomplete scores. Publish anyway?');
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
            if (data.success) setTimeout(function () { location.reload(); }, 1200);
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

    /* ── Cumulative ranking ── */
    var cumulativeBtn = document.getElementById('cumulativeBtn');
    if (cumulativeBtn) {
      cumulativeBtn.addEventListener('click', function () {
        var formData = new FormData();
        formData.append('mode',        'grade_level_cumulative');
        formData.append('grade_level', <?php echo json_encode($selectedGradeLevel); ?>);
        formData.append('session',     <?php echo json_encode($selectedSession); ?>);
        formData.append('term',        <?php echo json_encode($selectedTerm); ?>);

        cumulativeBtn.disabled = true;
        cumulativeBtn.textContent = 'Calculating…';

        var statusEl = document.getElementById('cumulativeStatus');

        fetch('../../src/api/publish_results.php', { method: 'POST', body: formData })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            statusEl.innerHTML = '<div class="save-status save-status--' +
              (data.success ? 'success' : 'error') + '">' + data.message + '</div>';
            if (data.success) setTimeout(function () { location.reload(); }, 1200);
          })
          .catch(function () {
            statusEl.innerHTML = '<div class="save-status save-status--error">A connection error occurred. Please try again.</div>';
          })
          .finally(function () {
            cumulativeBtn.disabled = false;
            cumulativeBtn.textContent = '<?php echo $cumulativeDone ? "Recalculate" : "Calculate"; ?> <?php echo htmlspecialchars($allGradeLevels[$selectedGradeLevel] ?? $selectedGradeLevel); ?> Rankings';
          });
      });
    }
  </script>

</body>
</html>