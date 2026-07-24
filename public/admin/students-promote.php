<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PROMOTE / RETAIN / EXPEL STUDENTS
   File: public/admin/students-promote.php

   Accessible to:
     Form teacher — can promote/retain students in their class
     Principal, superadmin — can also expel students
     Superadmin — can also demote students

   Supports:
     Single student action via ?id=X
     Bulk class promotion via ?bulk=1&grade_level=X&class=Y
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'form_teacher', 'section_admin']);

$admin           = currentAdmin();
$pdo             = getDB();
$isSectionAdmin  = $admin['role'] === 'section_admin';
$adminOwnSection = $admin['section'];

$allGradeLevels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3',
                    'SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];

$promotionMap = [
    'JSS1' => 'JSS2', 'JSS2' => 'JSS3', 'JSS3' => 'SSS1',
    'SSS1' => 'SSS2', 'SSS2' => 'SSS3', 'SSS3' => null, // null = graduation
];

/* ── Form teacher restriction ── */
$formTeacherGradeLevel = null;
$formTeacherClass      = null;
if ($admin['role'] === 'form_teacher' && !empty($admin['class_assigned'])) {
    if (preg_match('/^(JSS[123]|SSS[123])([A-Z0-9]+)$/', $admin['class_assigned'], $m)) {
        $formTeacherGradeLevel = $m[1];
        $formTeacherClass      = $m[2];
    }
}

/* ── Load active classes ── */
$allClassRows = $pdo->query(
    "SELECT grade_level, class FROM class_arms WHERE is_active = 1 ORDER BY grade_level ASC, class ASC"
)->fetchAll();
$classesByGradeLevel = [];
foreach ($allClassRows as $row) {
    $classesByGradeLevel[$row['grade_level']][] = $row['class'];
}

/* ── Determine mode ── */
$isBulk    = isset($_GET['bulk']);
$studentId = (int) ($_GET['id'] ?? 0);

/* ── Load single student ── */
$student = null;
if ($studentId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student) { header('Location: students.php'); exit; }

    /* Form teacher can only access their class */
    if ($formTeacherGradeLevel &&
        ($student['grade_level'] !== $formTeacherGradeLevel || $student['class'] !== $formTeacherClass)) {
        header('Location: students.php'); exit;
    }

    /* Section admin can only promote students currently in their own
       section — evaluated on the student's CURRENT grade level, so
       the normal JSS3 → SSS1 graduation still works for a JS admin. */
    if ($isSectionAdmin) {
        $studentCurrentSection = str_starts_with($student['grade_level'], 'JSS') ? 'js' : 'ss';
        if ($studentCurrentSection !== $adminOwnSection) {
            header('Location: students.php'); exit;
        }
    }
}

/* ── Bulk: load class ── */
$bulkGradeLevel = $formTeacherGradeLevel ?? ($_GET['grade_level'] ?? '');
$bulkClass      = $formTeacherClass      ?? ($_GET['class']       ?? '');
$bulkStudents   = [];

if ($isBulk && $bulkGradeLevel && $bulkClass) {
    /* Form teacher restriction */
    if ($formTeacherGradeLevel && ($bulkGradeLevel !== $formTeacherGradeLevel || $bulkClass !== $formTeacherClass)) {
        header('Location: students.php'); exit;
    }
    /* Section admin restriction — same current-section logic as
       single-student mode above */
    if ($isSectionAdmin) {
        $bulkCurrentSection = str_starts_with($bulkGradeLevel, 'JSS') ? 'js' : 'ss';
        if ($bulkCurrentSection !== $adminOwnSection) {
            header('Location: students.php'); exit;
        }
    }
    $bulkStmt = $pdo->prepare(
        "SELECT id, admission_number, first_name, last_name, other_name, grade_level, class
         FROM   students
         WHERE  grade_level = ? AND class = ? AND is_active = 1 AND status = 'active'
         ORDER  BY last_name ASC, first_name ASC"
    );
    $bulkStmt->execute([$bulkGradeLevel, $bulkClass]);
    $bulkStudents = $bulkStmt->fetchAll();
}

$message     = '';
$messageType = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Promote Students — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .promote-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:20px; }
  .promote-card h3 { font-size:14px; font-weight:700; color:#3d1a6e; margin-bottom:4px; }
  .promote-card p { font-size:13px; color:#6b6b80; margin-bottom:16px; }

  .action-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:12px; margin-bottom:20px; }
  .action-option {
    border:2px solid #e8e6f0; border-radius:12px; padding:16px;
    cursor:pointer; text-align:center; transition:border-color .15s, background .15s;
  }
  .action-option:has(input:checked) { border-color:#3d1a6e; background:#f0ecfa; }
  .action-option input { display:none; }
  .action-option__icon { font-size:24px; margin-bottom:6px; }
  .action-option__label { font-size:13px; font-weight:700; color:#1a1a2e; }
  .action-option__hint  { font-size:11.5px; color:#9b97b0; margin-top:3px; }
  .action-option--danger:has(input:checked) { border-color:#cc3333; background:#ffe6e6; }

  .reason-field { margin-top:14px; display:none; }
  .reason-field--show { display:block; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; }
  .form-input { width:100%; padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; }
  .form-input:focus { outline:none; border-color:#4a90d9; }
  .form-select { width:100%; padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; }

  .student-list { margin-bottom:16px; }
  .student-row {
    display:flex; align-items:center; gap:12px; padding:10px 14px;
    border:1px solid #e8e6f0; border-radius:10px; margin-bottom:8px;
    background:#fff;
  }
  .student-row input[type=checkbox] { width:16px; height:16px; cursor:pointer; }
  .student-row__name { flex:1; font-size:13.5px; font-weight:600; color:#1a1a2e; }
  .student-row__admno { font-size:12px; color:#9b97b0; }
  .student-row__action select { padding:5px 8px; border:1.5px solid #e2e0ea; border-radius:6px; font-size:12.5px; font-family:'DM Sans',sans-serif; }

  .bulk-controls { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
  .btn-bulk { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:7px 14px; border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer; }
  .btn-bulk:hover { background:#e4dcf6; }

  .filter-bar { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:16px 20px; margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; }
  .filter-group select { padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:130px; }
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }

  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; display:inline-block; }
  .btn-group { display:flex; gap:12px; margin-top:20px; flex-wrap:wrap; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'students'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Promote / Retain Students</h2>
        <p><a href="students.php" style="color:#4a90d9;text-decoration:none">← Back to Students</a></p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if (!$isBulk && !$studentId): ?>
      <!-- Class selector for bulk promotion -->
      <form method="GET" class="filter-bar">
        <input type="hidden" name="bulk" value="1"/>
        <?php if (!$formTeacherGradeLevel): ?>
        <div class="filter-group">
          <label>Grade Level</label>
          <select name="grade_level" id="gl_select" required>
            <option value="">Select grade level</option>
            <?php foreach ($allGradeLevels as $k => $v): ?>
            <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Class</label>
          <select name="class" id="class_select" required>
            <option value="">Select class</option>
          </select>
        </div>
        <?php else: ?>
        <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($formTeacherGradeLevel); ?>"/>
        <input type="hidden" name="class" value="<?php echo htmlspecialchars($formTeacherClass); ?>"/>
        <div style="font-size:13.5px;color:#3d1a6e;font-weight:600;align-self:center">
          Your class: <?php echo htmlspecialchars($formTeacherGradeLevel . ' ' . $formTeacherClass); ?>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn-filter">Load Class</button>
      </form>
      <?php endif; ?>

      <!-- ════════════ SINGLE STUDENT ════════════ -->
      <?php if ($student): ?>
      <?php
      $nextGradeLevel  = $promotionMap[$student['grade_level']] ?? null;
      $isSSS3          = $student['grade_level'] === 'SSS3';
      $canExpel        = in_array($admin['role'], ['superadmin','principal'], true);
      $canDemote       = $admin['role'] === 'superadmin';
      ?>
      <div class="promote-card">
        <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
        <p>
          <?php echo htmlspecialchars($student['admission_number']); ?> &nbsp;·&nbsp;
          Current: <?php echo htmlspecialchars(($allGradeLevels[$student['grade_level']] ?? $student['grade_level']) . ' ' . $student['class']); ?>
        </p>

        <form method="POST" action="../../src/api/promote_students.php" id="singleForm">
          <input type="hidden" name="student_ids[]" value="<?php echo $student['id']; ?>"/>
          <input type="hidden" name="redirect" value="students-edit.php?id=<?php echo $student['id']; ?>"/>

          <div class="action-grid">
            <?php if (!$isSSS3): ?>
            <label class="action-option">
              <input type="radio" name="bulk_action" value="promote" required/>
              <div class="action-option__icon">⬆️</div>
              <div class="action-option__label">Promote</div>
              <div class="action-option__hint">→ <?php echo htmlspecialchars($allGradeLevels[$nextGradeLevel] ?? ''); ?></div>
            </label>
            <?php else: ?>
            <label class="action-option">
              <input type="radio" name="bulk_action" value="graduate" required/>
              <div class="action-option__icon">🎓</div>
              <div class="action-option__label">Graduate</div>
              <div class="action-option__hint">Completed SSS3</div>
            </label>
            <?php endif; ?>

            <label class="action-option">
              <input type="radio" name="bulk_action" value="retain"/>
              <div class="action-option__icon">🔄</div>
              <div class="action-option__label">Retain</div>
              <div class="action-option__hint">Repeat current class</div>
            </label>

            <?php if ($canDemote): ?>
            <label class="action-option">
              <input type="radio" name="bulk_action" value="demote"/>
              <div class="action-option__icon">⬇️</div>
              <div class="action-option__label">Demote</div>
              <div class="action-option__hint">Move down one level</div>
            </label>
            <?php endif; ?>

            <?php if ($canExpel): ?>
            <label class="action-option action-option--danger">
              <input type="radio" name="bulk_action" value="expel"/>
              <div class="action-option__icon">🚫</div>
              <div class="action-option__label">Expel</div>
              <div class="action-option__hint">Remove from school</div>
            </label>
            <?php endif; ?>
          </div>

          <!-- Target class for promotion/demotion -->
          <div id="targetClassField" style="display:none;margin-bottom:14px">
            <label class="form-label">Assign to Class</label>
            <select class="form-select" name="target_class" id="targetClassSelect">
              <option value="">Same class letter</option>
              <?php foreach (range('A','E') as $cls): ?>
              <option value="<?php echo $cls; ?>"><?php echo $cls; ?></option>
              <?php endforeach; ?>
            </select>
            <p style="font-size:11.5px;color:#9b97b0;margin-top:4px">Leave blank to keep the same class letter.</p>
          </div>

          <div class="reason-field" id="reasonField">
            <label class="form-label">Reason / Notes</label>
            <textarea class="form-input" name="reason" rows="2" placeholder="Optional reason or notes"></textarea>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save">Apply Action</button>
            <a href="students-edit.php?id=<?php echo $student['id']; ?>" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>

      <!-- ════════════ BULK PROMOTION ════════════ -->
      <?php elseif ($isBulk && !empty($bulkStudents)): ?>
      <?php
      $nextGL   = $promotionMap[$bulkGradeLevel] ?? null;
      $isSSS3   = $bulkGradeLevel === 'SSS3';
      $canExpel = in_array($admin['role'], ['superadmin','principal'], true);
      ?>
      <div class="promote-card">
        <h3>Bulk Action — <?php echo htmlspecialchars(($allGradeLevels[$bulkGradeLevel] ?? $bulkGradeLevel) . ' ' . $bulkClass); ?></h3>
        <p><?php echo count($bulkStudents); ?> active students. Set an action for each student, or use the bulk controls to set all at once.</p>

        <form method="POST" action="../../src/api/promote_students.php" id="bulkForm">
          <input type="hidden" name="redirect" value="students.php?grade_level=<?php echo urlencode($bulkGradeLevel); ?>&class=<?php echo urlencode($bulkClass); ?>"/>
          <input type="hidden" name="bulk_grade_level" value="<?php echo htmlspecialchars($bulkGradeLevel); ?>"/>
          <input type="hidden" name="bulk_class" value="<?php echo htmlspecialchars($bulkClass); ?>"/>

          <div class="bulk-controls">
            <button type="button" class="btn-bulk" onclick="setBulkAction('promote')">
              ⬆️ Promote All
            </button>
            <button type="button" class="btn-bulk" onclick="setBulkAction('retain')">
              🔄 Retain All
            </button>
            <?php if ($isSSS3): ?>
            <button type="button" class="btn-bulk" onclick="setBulkAction('graduate')">
              🎓 Graduate All
            </button>
            <?php endif; ?>
          </div>

          <div class="student-list">
            <?php foreach ($bulkStudents as $s):
              $fullName = trim($s['first_name'] . ' ' . ($s['other_name'] ? $s['other_name'] . ' ' : '') . $s['last_name']);
            ?>
            <div class="student-row">
              <input type="checkbox" name="student_ids[]" value="<?php echo $s['id']; ?>" checked class="bulk-cb"/>
              <div class="student-row__name">
                <?php echo htmlspecialchars($fullName); ?>
                <div class="student-row__admno"><?php echo htmlspecialchars($s['admission_number']); ?></div>
              </div>
              <div class="student-row__action">
                <select name="actions[<?php echo $s['id']; ?>]" class="action-select">
                  <?php if (!$isSSS3): ?>
                  <option value="promote">Promote → <?php echo htmlspecialchars($allGradeLevels[$nextGL] ?? ''); ?></option>
                  <?php else: ?>
                  <option value="graduate">Graduate</option>
                  <?php endif; ?>
                  <option value="retain">Retain</option>
                  <?php if ($canExpel): ?>
                  <option value="expel">Expel</option>
                  <?php endif; ?>
                </select>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="form-label" style="margin-bottom:5px">Target Class for Promoted Students</div>
          <select class="form-select" name="target_class" style="max-width:200px;margin-bottom:14px">
            <option value="">Same class letter</option>
            <?php foreach (range('A','E') as $cls): ?>
            <option value="<?php echo $cls; ?>"><?php echo $cls; ?></option>
            <?php endforeach; ?>
          </select>
          <p style="font-size:11.5px;color:#9b97b0;margin-bottom:14px">Leave blank to keep each student in the same class letter in their new grade level.</p>

          <div class="form-group">
            <label class="form-label">Reason / Notes (applies to all)</label>
            <textarea class="form-input" name="reason" rows="2" placeholder="e.g. End of 2025/2026 academic session — Third Term promotion"></textarea>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save">Apply to Selected Students</button>
            <a href="students.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>

      <?php elseif ($isBulk && empty($bulkStudents) && $bulkGradeLevel && $bulkClass): ?>
      <div class="promote-card">
        <div style="text-align:center;color:#6b6b80;padding:30px 0">
          No active students found in <?php echo htmlspecialchars(($allGradeLevels[$bulkGradeLevel] ?? $bulkGradeLevel) . ' ' . $bulkClass); ?>.
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    var classesByGradeLevel = <?php echo json_encode($classesByGradeLevel); ?>;
    var glSelect    = document.getElementById('gl_select');
    var classSelect = document.getElementById('class_select');

    if (glSelect && classSelect) {
      glSelect.addEventListener('change', function () {
        var gl = glSelect.value;
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

    /* Single student — show reason + target class based on action */
    var radios = document.querySelectorAll('input[name="bulk_action"]');
    var reasonField      = document.getElementById('reasonField');
    var targetClassField = document.getElementById('targetClassField');

    radios.forEach(function (radio) {
      radio.addEventListener('change', function () {
        var val = radio.value;
        if (reasonField) reasonField.classList.add('reason-field--show');
        if (targetClassField) {
          targetClassField.style.display = (val === 'promote' || val === 'demote') ? 'block' : 'none';
        }
      });
    });

    /* Bulk — set all action selects at once */
    function setBulkAction(action) {
      document.querySelectorAll('.action-select').forEach(function (sel) {
        for (var i = 0; i < sel.options.length; i++) {
          if (sel.options[i].value === action) {
            sel.selectedIndex = i;
            break;
          }
        }
      });
    }
  </script>

</body>
</html>