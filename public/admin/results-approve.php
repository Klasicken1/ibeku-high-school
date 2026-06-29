<?php
/* ============================================================
   IBEKU HIGH SCHOOL — APPROVE RESULTS
   File: public/admin/results-approve.php

   Accessible to: superadmin, form_teacher
   Form teacher reviews scores for their assigned class and
   approves or revokes approval. VP Academics cannot publish
   until the form teacher has approved.

   Form teachers are automatically locked to their assigned
   class. Superadmin can select any class.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'form_teacher']);

$admin = currentAdmin();
$pdo   = getDB();

/* ── Form teacher: force their assigned class ── */
$formTeacherGradeLevel = null;
$formTeacherClass      = null;

if ($admin['role'] === 'form_teacher' && !empty($admin['class_assigned'])) {
    if (preg_match('/^(JSS[123]|SSS[123])([A-Z0-9]+)$/', $admin['class_assigned'], $m)) {
        $formTeacherGradeLevel = $m[1];
        $formTeacherClass      = $m[2];
    }
}

/* ── Load active classes for superadmin dropdown ── */
$allClasses = $pdo->query(
    "SELECT grade_level, class FROM class_arms WHERE is_active = 1 ORDER BY grade_level ASC, class ASC"
)->fetchAll();
$classesByGradeLevel = [];
foreach ($allClasses as $row) {
    $classesByGradeLevel[$row['grade_level']][] = $row['class'];
}

$allGradeLevels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3',
                    'SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];

/* ── Selected filters ── */
$selectedGradeLevel = $formTeacherGradeLevel ?? ($_GET['grade_level'] ?? '');
$selectedClass      = $formTeacherClass      ?? ($_GET['class']       ?? '');
$selectedSession    = $_GET['session']       ?? '2025/2026';
$selectedTerm       = $_GET['term']          ?? 'first';

/* ── Load students and their scores + approval status ── */
$students      = [];
$approvalStats = ['total' => 0, 'approved' => 0, 'is_published' => false];

if ($selectedGradeLevel && $selectedClass) {
    $studentStmt = $pdo->prepare(
        "SELECT s.id, s.admission_number, s.first_name, s.last_name, s.other_name,
                r.id AS result_id, r.is_approved, r.is_published,
                r.approved_at,
                CONCAT(u.full_name) AS approved_by_name
         FROM   students s
         LEFT JOIN results r ON r.student_id = s.id
                             AND r.session = ? AND r.term = ?
         LEFT JOIN users u ON u.id = r.approved_by
         WHERE  s.grade_level = ? AND s.class = ? AND s.is_active = 1
         ORDER  BY s.last_name ASC, s.first_name ASC"
    );
    $studentStmt->execute([$selectedSession, $selectedTerm, $selectedGradeLevel, $selectedClass]);
    $students = $studentStmt->fetchAll();

    foreach ($students as $st) {
        if ($st['result_id']) {
            $approvalStats['total']++;
            if ((int) $st['is_approved'] === 1) $approvalStats['approved']++;
            if ((int) $st['is_published'] === 1) $approvalStats['is_published'] = true;
        }
    }

    /* Load subject scores for display */
    if (!empty($students)) {
        $resultIds = array_filter(array_column($students, 'result_id'));
        if (!empty($resultIds)) {
            $placeholders = implode(',', array_fill(0, count($resultIds), '?'));
            $scoreStmt = $pdo->prepare(
                "SELECT rs.result_id, subj.name AS subject_name,
                        rs.ca1_score, rs.ca2_score, rs.exam_score, rs.grade
                 FROM   result_scores rs
                 JOIN   subjects subj ON subj.id = rs.subject_id
                 WHERE  rs.result_id IN ($placeholders)
                 ORDER  BY subj.name ASC"
            );
            $scoreStmt->execute($resultIds);
            $scoresByResult = [];
            foreach ($scoreStmt->fetchAll() as $score) {
                $scoresByResult[$score['result_id']][] = $score;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Approve Results — Admin — Ibeku High School</title>
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

  .approval-summary {
    background:#fff; border:1px solid #e8e6f0; border-radius:14px;
    padding:20px 24px; margin-bottom:20px;
    display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;
  }
  .approval-summary__text { font-size:13.5px; color:#1a1a2e; }
  .approval-summary__text strong { color:#3d1a6e; }

  .approval-actions { display:flex; gap:10px; flex-wrap:wrap; }

  .btn-approve {
    background:#1a7a3a; color:#fff; border:none; padding:11px 24px;
    border-radius:8px; font-size:13.5px; font-weight:700; cursor:pointer;
  }
  .btn-approve:hover { background:#1a5a2e; }
  .btn-approve:disabled { background:#c8c4dc; cursor:not-allowed; }

  .btn-revoke {
    background:#fff3e6; color:#8a4a00; border:1.5px solid #ffe0b2; padding:11px 22px;
    border-radius:8px; font-size:13.5px; font-weight:700; cursor:pointer;
  }
  .btn-revoke:hover { background:#ffe0b2; }
  .btn-revoke:disabled { background:#f4f3f9; color:#c8c4dc; border-color:#e8e6f0; cursor:not-allowed; }

  .student-card {
    background:#fff; border:1px solid #e8e6f0; border-radius:14px;
    margin-bottom:16px; overflow:hidden;
  }
  .student-card__header {
    display:flex; justify-content:space-between; align-items:center;
    padding:14px 18px; border-bottom:1px solid #f0eef6; flex-wrap:wrap; gap:8px;
  }
  .student-card__name { font-weight:700; color:#1a1a2e; font-size:14px; }
  .student-card__admno { font-size:12px; color:#9b97b0; }

  .badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
  .badge--approved  { background:#e6f9ed; color:#1a7a3a; }
  .badge--pending   { background:#fff3e6; color:#8a4a00; }
  .badge--published { background:#e6f0ff; color:#1a5a9a; }
  .badge--no-scores { background:#f4f3f9; color:#9b97b0; }

  table.score-table { width:100%; border-collapse:collapse; font-size:12.5px; }
  table.score-table th {
    background:#f8f7fc; color:#3d1a6e; padding:8px 14px; text-align:left;
    font-size:11px; text-transform:uppercase; letter-spacing:.04em; font-weight:700;
  }
  table.score-table td { padding:8px 14px; border-bottom:1px solid #f4f3f9; }
  table.score-table tr:last-child td { border-bottom:none; }
  .score-total { font-weight:700; }

  .role-note { background:#f0ecfa; color:#3d1a6e; padding:10px 16px; border-radius:8px; font-size:12.5px; margin-bottom:20px; }
  .published-note { background:#e6f0ff; color:#1a5a9a; padding:10px 16px; border-radius:8px; font-size:12.5px; margin-bottom:20px; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }

  .save-status { font-size:13px; margin-top:12px; padding:10px 14px; border-radius:8px; }
  .save-status--success { background:#e6f9ed; color:#1a7a3a; }
  .save-status--error   { background:#ffe6e6; color:#cc3333; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'results-approve'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Approve Results</h2>
        <p>Review student scores for your class, then approve them to allow the VP Academics to publish.</p>
      </div>

      <?php if ($admin['role'] === 'form_teacher' && $formTeacherGradeLevel): ?>
      <div class="role-note">
        You are reviewing results for <strong><?php echo htmlspecialchars($formTeacherGradeLevel . ' ' . $formTeacherClass); ?></strong>.
        Once you approve, VP Academics will be able to publish the results.
      </div>
      <?php endif; ?>

      <?php if ($approvalStats['is_published']): ?>
      <div class="published-note">
        These results have already been published. Scores are now visible to students and parents on the public result checker.
        Approval cannot be changed after publishing.
      </div>
      <?php endif; ?>

      <!-- Filter form (superadmin only — form teachers see their class automatically) -->
      <?php if ($admin['role'] === 'superadmin'): ?>
      <form method="GET" class="filter-bar">
        <div class="filter-group">
          <label for="grade_level">Grade Level</label>
          <select name="grade_level" id="grade_level" required>
            <option value="">Select grade level</option>
            <?php foreach ($allGradeLevels as $key => $label): ?>
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
        <button type="submit" class="btn-filter">Load Class</button>
      </form>
      <?php else: ?>
      <!-- Form teacher: session + term filter only -->
      <form method="GET" class="filter-bar">
        <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($selectedGradeLevel); ?>"/>
        <input type="hidden" name="class"       value="<?php echo htmlspecialchars($selectedClass); ?>"/>
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
        <button type="submit" class="btn-filter">Load</button>
      </form>
      <?php endif; ?>

      <?php if ($selectedGradeLevel && $selectedClass): ?>

        <?php if (empty($students)): ?>
        <div class="student-card">
          <div class="empty-state">No active students found in <?php echo htmlspecialchars($selectedGradeLevel . ' ' . $selectedClass); ?>.</div>
        </div>

        <?php elseif ($approvalStats['total'] === 0): ?>
        <div class="student-card">
          <div class="empty-state">No results found for this term. Subject teachers need to enter scores first.</div>
        </div>

        <?php else: ?>

        <!-- Approval summary + action buttons -->
        <div class="approval-summary">
          <div class="approval-summary__text">
            <strong><?php echo $approvalStats['approved']; ?> of <?php echo $approvalStats['total']; ?></strong> student results approved
            &nbsp;·&nbsp;
            <?php echo htmlspecialchars($selectedGradeLevel . ' ' . $selectedClass); ?>
            &nbsp;·&nbsp;
            <?php echo ucfirst($selectedTerm); ?> Term <?php echo htmlspecialchars($selectedSession); ?>
          </div>

          <?php if (!$approvalStats['is_published']): ?>
          <div class="approval-actions">
            <button type="button" class="btn-approve" id="approveBtn"
                    onclick="submitApproval('approve')">
              ✓ Approve All Results
            </button>
            <button type="button" class="btn-revoke" id="revokeBtn"
                    onclick="submitApproval('revoke')"
                    <?php echo $approvalStats['approved'] === 0 ? 'disabled' : ''; ?>>
              ✕ Revoke Approval
            </button>
          </div>
          <?php endif; ?>
        </div>

        <div id="approvalStatus"></div>

        <!-- Per-student score cards -->
        <?php foreach ($students as $student):
          $fullName = trim($student['first_name'] . ' ' . ($student['other_name'] ? $student['other_name'] . ' ' : '') . $student['last_name']);
          $scores   = $scoresByResult[$student['result_id']] ?? [];
          $hasResult = !empty($student['result_id']);
        ?>
        <div class="student-card">
          <div class="student-card__header">
            <div>
              <div class="student-card__name"><?php echo htmlspecialchars($fullName); ?></div>
              <div class="student-card__admno"><?php echo htmlspecialchars($student['admission_number']); ?></div>
            </div>
            <div>
              <?php if (!$hasResult || empty($scores)): ?>
                <span class="badge badge--no-scores">No scores entered</span>
              <?php elseif ((int) $student['is_published'] === 1): ?>
                <span class="badge badge--published">Published</span>
              <?php elseif ((int) $student['is_approved'] === 1): ?>
                <span class="badge badge--approved">Approved</span>
                <?php if ($student['approved_at']): ?>
                <span style="font-size:11px;color:#9b97b0;margin-left:6px">
                  <?php echo date('d M Y, g:ia', strtotime($student['approved_at'])); ?>
                  <?php if ($student['approved_by_name']): ?>by <?php echo htmlspecialchars($student['approved_by_name']); ?><?php endif; ?>
                </span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge badge--pending">Pending Approval</span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($scores)): ?>
          <table class="score-table">
            <thead>
              <tr>
                <th>Subject</th>
                <th>1st Test (15)</th>
                <th>2nd Test (15)</th>
                <th>Exam (70)</th>
                <th>Total</th>
                <th>Grade</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($scores as $score):
                $total = (float)$score['ca1_score'] + (float)$score['ca2_score'] + (float)$score['exam_score'];
              ?>
              <tr>
                <td><?php echo htmlspecialchars($score['subject_name']); ?></td>
                <td><?php echo $score['ca1_score']; ?></td>
                <td><?php echo $score['ca2_score']; ?></td>
                <td><?php echo $score['exam_score']; ?></td>
                <td class="score-total"><?php echo number_format($total, 1); ?></td>
                <td><strong><?php echo htmlspecialchars($score['grade']); ?></strong></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div style="padding:14px 18px;color:#9b97b0;font-size:13px;">No subject scores entered yet.</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>

      <?php else: ?>
      <div class="student-card">
        <div class="empty-state">
          <?php if ($admin['role'] === 'form_teacher' && !$formTeacherGradeLevel): ?>
            No class is assigned to your account. Contact the system administrator.
          <?php else: ?>
            Select a grade level, class, and term above to review results.
          <?php endif; ?>
        </div>
      </div>
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

    function submitApproval(action) {
      var approveBtn  = document.getElementById('approveBtn');
      var revokeBtn   = document.getElementById('revokeBtn');
      var statusEl    = document.getElementById('approvalStatus');
      var label       = action === 'approve' ? 'Approving…' : 'Revoking…';

      if (approveBtn) { approveBtn.disabled = true; approveBtn.textContent = label; }
      if (revokeBtn)  { revokeBtn.disabled  = true; }

      var formData = new FormData();
      formData.append('action',      action);
      formData.append('grade_level', <?php echo json_encode($selectedGradeLevel); ?>);
      formData.append('class',       <?php echo json_encode($selectedClass); ?>);
      formData.append('session',     <?php echo json_encode($selectedSession); ?>);
      formData.append('term',        <?php echo json_encode($selectedTerm); ?>);

      fetch('../../src/api/approve_results.php', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          statusEl.innerHTML = '<div class="save-status save-status--' +
            (data.success ? 'success' : 'error') + '">' + data.message + '</div>';
          if (data.success) {
            setTimeout(function () { location.reload(); }, 1000);
          }
        })
        .catch(function () {
          statusEl.innerHTML = '<div class="save-status save-status--error">A connection error occurred. Please try again.</div>';
        })
        .finally(function () {
          if (approveBtn) { approveBtn.disabled = false; approveBtn.textContent = '✓ Approve All Results'; }
          if (revokeBtn)  { revokeBtn.disabled  = false; }
        });
    }
  </script>

</body>
</html>