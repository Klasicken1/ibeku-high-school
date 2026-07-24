<?php
/* ============================================================
   IBEKU HIGH SCHOOL — MANAGE CLASSES
   File: public/admin/class-arms.php

   Accessible to: superadmin only
   Single source of truth for which classes (A, B, C...) exist
   per grade level. Read by users-create.php (Form Teacher
   assignment dropdown), results-entry.php, and results-publish.php
   so a new class added here propagates everywhere automatically.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'section_admin']);

$admin           = currentAdmin();
$pdo             = getDB();
$isSectionAdmin  = $admin['role'] === 'section_admin';
$adminOwnSection = $admin['section'];

$message = '';
$messageType = '';

$gradeLevels = ['JSS1' => 'JSS 1', 'JSS2' => 'JSS 2', 'JSS3' => 'JSS 3',
                'SSS1' => 'SSS 1', 'SSS2' => 'SSS 2', 'SSS3' => 'SSS 3'];
if ($isSectionAdmin) {
    $prefix = $adminOwnSection === 'ss' ? 'SSS' : 'JSS';
    $gradeLevels = array_filter($gradeLevels, fn($k) => str_starts_with($k, $prefix), ARRAY_FILTER_USE_KEY);
}

/* Helper: does a class_arms row belong to the section admin's own
   section? (based on its grade_level prefix) */
function classArmInSection(string $gradeLevel, string $section): bool {
    return str_starts_with($gradeLevel, $section === 'ss' ? 'SSS' : 'JSS');
}

/* ── Add a new class ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $gradeLevel = trim($_POST['grade_level'] ?? '');
    $class      = strtoupper(trim($_POST['class'] ?? ''));

    if (!array_key_exists($gradeLevel, $gradeLevels)) {
        $message = 'Invalid grade level selected.';
        $messageType = 'error';
    } elseif ($isSectionAdmin && !classArmInSection($gradeLevel, $adminOwnSection)) {
        $message = 'You can only add classes within your own section.';
        $messageType = 'error';
    } elseif ($class === '' || !preg_match('/^[A-Z0-9]{1,5}$/', $class)) {
        $message = 'Class must be 1-5 letters/numbers, e.g. A, B, F.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO class_arms (grade_level, class, is_active) VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE is_active = 1'
            );
            $stmt->execute([$gradeLevel, $class]);
            $message = $gradeLevels[$gradeLevel] . ' ' . htmlspecialchars($class) . ' added.';
            $messageType = 'success';
        } catch (PDOException $e) {
            error_log('IHS class-arms add error: ' . $e->getMessage());
            $message = 'A server error occurred.';
            $messageType = 'error';
        }
    }
}

/* ── Toggle active/inactive (soft delete) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['class_id']) && $_POST['action'] === 'toggle') {
    $classId = (int) $_POST['class_id'];

    $allowed = true;
    if ($isSectionAdmin) {
        $checkStmt = $pdo->prepare('SELECT grade_level FROM class_arms WHERE id = ? LIMIT 1');
        $checkStmt->execute([$classId]);
        $targetGL = $checkStmt->fetchColumn();
        $allowed  = $targetGL && classArmInSection($targetGL, $adminOwnSection);
    }

    if (!$allowed) {
        $message = 'You can only manage classes within your own section.';
        $messageType = 'error';
    } else {
    try {
        $pdo->prepare('UPDATE class_arms SET is_active = NOT is_active WHERE id = ?')->execute([$classId]);
        $message = 'Class status updated.';
        $messageType = 'success';
    } catch (PDOException $e) {
        error_log('IHS class-arms toggle error: ' . $e->getMessage());
        $message = 'A server error occurred.';
        $messageType = 'error';
    }
    }
}

/* ── Permanently delete ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['class_id']) && $_POST['action'] === 'delete') {
    $classId = (int) $_POST['class_id'];

    $allowed = true;
    if ($isSectionAdmin) {
        $checkStmt = $pdo->prepare('SELECT grade_level FROM class_arms WHERE id = ? LIMIT 1');
        $checkStmt->execute([$classId]);
        $targetGL = $checkStmt->fetchColumn();
        $allowed  = $targetGL && classArmInSection($targetGL, $adminOwnSection);
    }

    if (!$allowed) {
        $message = 'You can only manage classes within your own section.';
        $messageType = 'error';
    } else {
    try {
        $pdo->prepare('DELETE FROM class_arms WHERE id = ?')->execute([$classId]);
        $message = 'Class deleted permanently.';
        $messageType = 'success';
    } catch (PDOException $e) {
        error_log('IHS class-arms delete error: ' . $e->getMessage());
        $message = 'A server error occurred.';
        $messageType = 'error';
    }
    }
}

/* ── Load all classes grouped by grade level ── */
$allClasses = $pdo->query('SELECT * FROM class_arms ORDER BY grade_level ASC, class ASC')->fetchAll();
if ($isSectionAdmin) {
    $allClasses = array_values(array_filter($allClasses, fn($c) => classArmInSection($c['grade_level'], $adminOwnSection)));
}
$classesByGradeLevel = [];
foreach ($gradeLevels as $key => $label) {
    $classesByGradeLevel[$key] = array_filter($allClasses, fn($c) => $c['grade_level'] === $key);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Classes — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .grade-level-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:22px 24px; margin-bottom:16px; }
  .grade-level-card__top { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
  .grade-level-card__top h3 { font-size:16px; color:#3d1a6e; }
  .class-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
  .class-pill {
    display:flex; align-items:center; gap:8px;
    background:#f0ecfa; border:1px solid #d8d0ee; border-radius:8px;
    padding:6px 8px 6px 14px; font-size:13px; font-weight:600; color:#3d1a6e;
  }
  .class-pill--inactive { background:#f4f3f9; color:#9b97b0; border-color:#e2e0ea; text-decoration:line-through; }
  .class-pill__btn {
    border:none; background:transparent; cursor:pointer; font-size:12px;
    padding:3px 6px; border-radius:5px; color:inherit;
  }
  .class-pill__btn--toggle:hover { background:#e4dcf6; }
  .class-pill__btn--delete:hover { background:#ffd6d6; color:#cc3333; }

  .add-class-form { display:flex; gap:8px; align-items:center; }
  .add-class-form input {
    width:70px; padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px;
    font-size:13px; text-transform:uppercase; text-align:center;
  }
  .add-class-form input:focus { outline:none; border-color:#4a90d9; }
  .btn-add-class {
    background:#3d1a6e; color:#fff; border:none; padding:8px 18px;
    border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer;
  }
  .btn-add-class:hover { background:#5a2d9e; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'class-arms'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Manage Classes</h2>
        <p>Add or remove classes (A, B, C...) for each grade level. Changes here update the Form Teacher dropdown and student class assignment everywhere in the system.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php foreach ($gradeLevels as $key => $label): ?>
      <div class="grade-level-card">
        <div class="grade-level-card__top">
          <h3><?php echo $label; ?></h3>
        </div>

        <div class="class-pills">
          <?php if (empty($classesByGradeLevel[$key])): ?>
          <span style="color:#9b97b0;font-size:12.5px">No classes added yet.</span>
          <?php endif; ?>
          <?php foreach ($classesByGradeLevel[$key] as $cls): ?>
          <div class="class-pill <?php echo $cls['is_active'] ? '' : 'class-pill--inactive'; ?>">
            <span><?php echo $label; ?> <?php echo htmlspecialchars($cls['class']); ?></span>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle"/>
              <input type="hidden" name="class_id" value="<?php echo $cls['id']; ?>"/>
              <button type="submit" class="class-pill__btn class-pill__btn--toggle" title="<?php echo $cls['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                <?php echo $cls['is_active'] ? '⏸' : '▶'; ?>
              </button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?php echo $label; ?> <?php echo htmlspecialchars($cls['class']); ?> permanently?');">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="class_id" value="<?php echo $cls['id']; ?>"/>
              <button type="submit" class="class-pill__btn class-pill__btn--delete" title="Delete">✕</button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>

        <form method="POST" class="add-class-form">
          <input type="hidden" name="action" value="add"/>
          <input type="hidden" name="grade_level" value="<?php echo $key; ?>"/>
          <input type="text" name="class" placeholder="F" maxlength="5" required/>
          <button type="submit" class="btn-add-class">+ Add Class</button>
        </form>
      </div>
      <?php endforeach; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>