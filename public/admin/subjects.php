<?php
/* ============================================================
   IBEKU HIGH SCHOOL — MANAGE SUBJECTS
   File: public/admin/subjects.php

   Accessible to: superadmin only
   Add, edit (name/section/department), activate/deactivate
   subjects. Changes propagate immediately to results-entry.php
   subject dropdown and save_result_scores.php permission check.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin']);

$admin = currentAdmin();
$pdo   = getDB();

$message = '';
$messageType = '';

/* ── Add new subject ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name    = trim($_POST['name']    ?? '');
    $section = trim($_POST['section'] ?? '');
    $dept    = trim($_POST['department'] ?? 'all');

    $validSections = ['ss', 'js', 'both'];
    $validDepts    = ['sciences', 'arts', 'commercial', 'general', 'all'];

    if ($name === '') {
        $message = 'Subject name is required.'; $messageType = 'error';
    } elseif (!in_array($section, $validSections, true)) {
        $message = 'Please select a valid section.'; $messageType = 'error';
    } elseif (!in_array($dept, $validDepts, true)) {
        $message = 'Please select a valid department.'; $messageType = 'error';
    } else {
        try {
            $pdo->prepare(
                'INSERT INTO subjects (name, section, department, is_active) VALUES (?, ?, ?, 1)'
            )->execute([$name, $section, $dept]);
            $message = htmlspecialchars($name) . ' added successfully.';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = str_contains($e->getMessage(), 'Duplicate') ? 'A subject with that name already exists.' : 'A server error occurred.';
            $messageType = 'error';
        }
    }
}

/* ── Toggle active/inactive ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    try {
        $pdo->prepare('UPDATE subjects SET is_active = NOT is_active WHERE id = ?')->execute([$subjectId]);
        $message = 'Subject status updated.'; $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'A server error occurred.'; $messageType = 'error';
    }
}

/* ── Update subject ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    $name      = trim($_POST['name']    ?? '');
    $section   = trim($_POST['section'] ?? '');
    $dept      = trim($_POST['department'] ?? 'all');

    $validSections = ['ss', 'js', 'both'];
    $validDepts    = ['sciences', 'arts', 'commercial', 'general', 'all'];

    if ($name === '' || !in_array($section, $validSections, true) || !in_array($dept, $validDepts, true)) {
        $message = 'Invalid input — check all fields.'; $messageType = 'error';
    } else {
        try {
            $pdo->prepare(
                'UPDATE subjects SET name = ?, section = ?, department = ? WHERE id = ?'
            )->execute([$name, $section, $dept, $subjectId]);
            $message = 'Subject updated.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = str_contains($e->getMessage(), 'Duplicate') ? 'A subject with that name already exists.' : 'A server error occurred.';
            $messageType = 'error';
        }
    }
}

/* ── Delete subject ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    try {
        $pdo->prepare('DELETE FROM subjects WHERE id = ?')->execute([$subjectId]);
        $message = 'Subject deleted permanently.'; $messageType = 'success';
    } catch (PDOException $e) {
        $message = str_contains($e->getMessage(), 'foreign key') ? 'Cannot delete — this subject has result scores attached. Deactivate it instead.' : 'A server error occurred.';
        $messageType = 'error';
    }
}

/* ── Load all subjects ── */
$filterSection = $_GET['section'] ?? 'all';
$sql = 'SELECT * FROM subjects';
$params = [];
if ($filterSection !== 'all') {
    $sql .= ' WHERE section = ?';
    $params[] = $filterSection;
}
$sql .= ' ORDER BY section ASC, name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subjects = $stmt->fetchAll();

$sectionLabels  = ['ss' => 'Senior Secondary', 'js' => 'Junior Secondary', 'both' => 'Both'];
$deptLabels     = ['sciences' => 'Sciences', 'arts' => 'Arts', 'commercial' => 'Commercial', 'general' => 'General', 'all' => 'All Departments'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Subjects — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row {
    display:flex; justify-content:space-between; align-items:flex-start;
    margin-bottom:22px; flex-wrap:wrap; gap:14px;
  }
  .filter-tabs { display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap; }
  .filter-tab {
    padding:7px 16px; border-radius:20px; font-size:12.5px; font-weight:600;
    text-decoration:none; color:#6b6b80; background:#fff; border:1px solid #e8e6f0;
  }
  .filter-tab--active { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }

  /* Add form */
  .add-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:22px 24px; margin-bottom:24px; }
  .add-card h3 { font-size:14px; color:#3d1a6e; margin-bottom:16px; }
  .add-row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
  .add-row .form-group { margin-bottom:0; }
  .add-row input, .add-row select {
    padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px;
    font-size:13px; font-family:'DM Sans', sans-serif;
  }
  .add-row input:focus, .add-row select:focus { outline:none; border-color:#4a90d9; }
  .add-row input[name="name"] { min-width:220px; }
  .btn-add {
    background:#3d1a6e; color:#fff; border:none; padding:9px 20px;
    border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap;
  }
  .btn-add:hover { background:#5a2d9e; }

  /* Table */
  .subjects-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.subjects-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.subjects-table th {
    background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left;
    font-size:11.5px; text-transform:uppercase; letter-spacing:.04em;
  }
  table.subjects-table td { padding:10px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.subjects-table tr:last-child td { border-bottom:none; }
  table.subjects-table tr:hover td { background:#faf9fd; }

  .badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
  .badge--active { background:#e6f9ed; color:#1a7a3a; }
  .badge--inactive { background:#ffe6e6; color:#cc3333; }
  .badge--ss { background:#f0ecfa; color:#3d1a6e; }
  .badge--js { background:#e6f0ff; color:#1a5a9a; }
  .badge--both { background:#fff3e6; color:#8a4a00; }

  .action-btn {
    border:none; padding:5px 11px; border-radius:6px; font-size:11.5px;
    font-weight:600; cursor:pointer; text-decoration:none;
  }
  .action-btn--toggle-on  { background:#fff3e6; color:#8a4a00; }
  .action-btn--toggle-off { background:#e6f9ed; color:#1a7a3a; }
  .action-btn--delete { background:#ffe6e6; color:#cc3333; margin-left:4px; }

  /* Inline edit */
  .edit-row { display:none; background:#f8f7fc; }
  .edit-row td { padding:10px 14px; }
  .edit-row input, .edit-row select {
    padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px;
    font-size:12.5px; font-family:'DM Sans', sans-serif; margin-right:6px;
  }
  .edit-row input { min-width:180px; }
  .btn-save-inline { background:#3d1a6e; color:#fff; border:none; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:600; cursor:pointer; }
  .btn-cancel-inline { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:7px 14px; border-radius:7px; font-size:12px; font-weight:600; cursor:pointer; margin-left:4px; }

  .empty-state { padding:40px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'subjects'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Manage Subjects</h2>
          <p>Add, edit, or deactivate subjects. Changes update the results entry dropdown immediately.</p>
        </div>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Add new subject -->
      <div class="add-card">
        <h3>Add New Subject</h3>
        <form method="POST" class="add-row">
          <input type="hidden" name="action" value="add"/>
          <div class="form-group">
            <input type="text" name="name" placeholder="Subject name e.g. Physics" maxlength="100" required/>
          </div>
          <div class="form-group">
            <select name="section" required>
              <option value="">Section</option>
              <option value="ss">Senior Secondary</option>
              <option value="js">Junior Secondary</option>
              <option value="both">Both</option>
            </select>
          </div>
          <div class="form-group">
            <select name="department">
              <option value="all">All Departments</option>
              <option value="sciences">Sciences</option>
              <option value="arts">Arts</option>
              <option value="commercial">Commercial</option>
              <option value="general">General</option>
            </select>
          </div>
          <button type="submit" class="btn-add">+ Add Subject</button>
        </form>
      </div>

      <!-- Filter tabs -->
      <div class="filter-tabs">
        <a href="?section=all" class="filter-tab <?php echo $filterSection === 'all' ? 'filter-tab--active' : ''; ?>">All</a>
        <a href="?section=ss"  class="filter-tab <?php echo $filterSection === 'ss'  ? 'filter-tab--active' : ''; ?>">Senior Secondary</a>
        <a href="?section=js"  class="filter-tab <?php echo $filterSection === 'js'  ? 'filter-tab--active' : ''; ?>">Junior Secondary</a>
        <a href="?section=both" class="filter-tab <?php echo $filterSection === 'both' ? 'filter-tab--active' : ''; ?>">Both Sections</a>
      </div>

      <div class="subjects-table-wrap">
        <?php if (empty($subjects)): ?>
        <div class="empty-state">No subjects found.</div>
        <?php else: ?>
        <table class="subjects-table">
          <thead>
            <tr>
              <th>Subject Name</th>
              <th>Section</th>
              <th>Department</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subjects as $s): ?>
            <!-- Display row -->
            <tr id="row-<?php echo $s['id']; ?>">
              <td><?php echo htmlspecialchars($s['name']); ?></td>
              <td><span class="badge badge--<?php echo $s['section']; ?>"><?php echo $sectionLabels[$s['section']] ?? $s['section']; ?></span></td>
              <td><?php echo $deptLabels[$s['department']] ?? $s['department']; ?></td>
              <td><span class="badge badge--<?php echo $s['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
              <td>
                <button type="button" class="action-btn action-btn--toggle-on" onclick="toggleEdit(<?php echo $s['id']; ?>)">Edit</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Toggle active status for <?php echo htmlspecialchars($s['name']); ?>?');">
                  <input type="hidden" name="action" value="toggle"/>
                  <input type="hidden" name="subject_id" value="<?php echo $s['id']; ?>"/>
                  <button type="submit" class="action-btn <?php echo $s['is_active'] ? 'action-btn--toggle-on' : 'action-btn--toggle-off'; ?>">
                    <?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?>
                  </button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?php echo htmlspecialchars($s['name']); ?> permanently? This fails if result scores are attached — deactivate instead.');">
                  <input type="hidden" name="action" value="delete"/>
                  <input type="hidden" name="subject_id" value="<?php echo $s['id']; ?>"/>
                  <button type="submit" class="action-btn action-btn--delete">Delete</button>
                </form>
              </td>
            </tr>
            <!-- Inline edit row -->
            <tr class="edit-row" id="edit-<?php echo $s['id']; ?>">
              <td colspan="5">
                <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                  <input type="hidden" name="action" value="update"/>
                  <input type="hidden" name="subject_id" value="<?php echo $s['id']; ?>"/>
                  <input type="text" name="name" value="<?php echo htmlspecialchars($s['name']); ?>" required maxlength="100"/>
                  <select name="section" required>
                    <option value="ss"   <?php echo $s['section'] === 'ss'   ? 'selected' : ''; ?>>Senior Secondary</option>
                    <option value="js"   <?php echo $s['section'] === 'js'   ? 'selected' : ''; ?>>Junior Secondary</option>
                    <option value="both" <?php echo $s['section'] === 'both' ? 'selected' : ''; ?>>Both</option>
                  </select>
                  <select name="department">
                    <option value="all"        <?php echo $s['department'] === 'all'        ? 'selected' : ''; ?>>All Departments</option>
                    <option value="sciences"   <?php echo $s['department'] === 'sciences'   ? 'selected' : ''; ?>>Sciences</option>
                    <option value="arts"       <?php echo $s['department'] === 'arts'       ? 'selected' : ''; ?>>Arts</option>
                    <option value="commercial" <?php echo $s['department'] === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                    <option value="general"    <?php echo $s['department'] === 'general'    ? 'selected' : ''; ?>>General</option>
                  </select>
                  <button type="submit" class="btn-save-inline">Save</button>
                  <button type="button" class="btn-cancel-inline" onclick="toggleEdit(<?php echo $s['id']; ?>)">Cancel</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    function toggleEdit(id) {
      var displayRow = document.getElementById('row-' + id);
      var editRow    = document.getElementById('edit-' + id);
      var isVisible  = editRow.style.display === 'table-row';
      editRow.style.display = isVisible ? 'none' : 'table-row';
    }
  </script>

</body>
</html>