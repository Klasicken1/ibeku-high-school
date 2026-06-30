<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STAFF DIRECTORY MANAGEMENT
   File: public/admin/staff.php

   Accessible to: superadmin only
   Add, edit, publish/unpublish, delete staff profiles.
   These profiles power about.php and academics.php — no
   duplication, single source of truth.
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

$message     = '';
$messageType = '';

/* ── Handle POST actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = trim($_POST['action']   ?? '');
    $staffId  = (int) ($_POST['staff_id'] ?? 0);

    if ($action === 'delete' && $staffId > 0) {
        try {
            /* Delete photo file if it exists */
            $row = $pdo->prepare('SELECT photo FROM staff WHERE id = ? LIMIT 1');
            $row->execute([$staffId]);
            $existing = $row->fetchColumn();
            if ($existing) {
                $filePath = dirname(__DIR__) . '/assets/images/staff/' . $existing;
                if (file_exists($filePath)) unlink($filePath);
            }
            $pdo->prepare('DELETE FROM staff WHERE id = ?')->execute([$staffId]);
            $message = 'Staff member deleted.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif ($action === 'toggle_publish' && $staffId > 0) {
        try {
            $pdo->prepare('UPDATE staff SET is_published = NOT is_published WHERE id = ?')->execute([$staffId]);
            $message = 'Staff member visibility updated.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif (in_array($action, ['create', 'update'], true)) {
        $fullName   = trim($_POST['full_name']   ?? '');
        $role       = trim($_POST['role']        ?? '');
        $department = trim($_POST['department']  ?? '');
        $section    = trim($_POST['section']     ?? 'both');
        $category   = trim($_POST['category']    ?? 'support');
        $bio        = trim($_POST['bio']         ?? '');
        $sortOrder  = (int) ($_POST['sort_order'] ?? 0);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        $validSections  = ['ss', 'js', 'both'];
        $validCategories = ['administration', 'sciences', 'arts', 'commercial', 'support'];

        if ($fullName === '') {
            $message = 'Full name is required.'; $messageType = 'error';
        } elseif ($role === '') {
            $message = 'Role/position is required.'; $messageType = 'error';
        } elseif (!in_array($section, $validSections, true)) {
            $message = 'Invalid section.'; $messageType = 'error';
        } elseif (!in_array($category, $validCategories, true)) {
            $message = 'Invalid category.'; $messageType = 'error';
        } else {
            /* Handle photo upload */
            $photoFilename = null;
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
                    $message = 'Photo must be JPG, PNG or WEBP.'; $messageType = 'error';
                } elseif ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
                    $message = 'Photo must be under 3MB.'; $messageType = 'error';
                } else {
                    $uploadDir = dirname(__DIR__) . '/assets/images/staff/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $photoFilename = 'staff_' . uniqid('', true) . '.' . $ext;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoFilename)) {
                        $message = 'Failed to save photo.'; $messageType = 'error';
                        $photoFilename = null;
                    }
                }
            }

            if ($message === '') {
                try {
                    if ($action === 'create') {
                        $pdo->prepare(
                            'INSERT INTO staff
                                (full_name, role, department, section, category, bio, photo,
                                 is_published, sort_order)
                             VALUES (?,?,?,?,?,?,?,?,?)'
                        )->execute([
                            $fullName, $role, $department ?: null, $section,
                            $category, $bio ?: null, $photoFilename,
                            $isPublished, $sortOrder,
                        ]);
                        $message = 'Staff member added.'; $messageType = 'success';

                    } else {
                        /* Update — only overwrite photo if a new one was uploaded */
                        if ($photoFilename) {
                            /* Delete old photo */
                            $oldRow = $pdo->prepare('SELECT photo FROM staff WHERE id = ? LIMIT 1');
                            $oldRow->execute([$staffId]);
                            $oldPhoto = $oldRow->fetchColumn();
                            if ($oldPhoto) {
                                $oldPath = dirname(__DIR__) . '/assets/images/staff/' . $oldPhoto;
                                if (file_exists($oldPath)) unlink($oldPath);
                            }
                            $pdo->prepare(
                                'UPDATE staff SET full_name=?, role=?, department=?, section=?,
                                 category=?, bio=?, photo=?, is_published=?, sort_order=?
                                 WHERE id=?'
                            )->execute([
                                $fullName, $role, $department ?: null, $section,
                                $category, $bio ?: null, $photoFilename,
                                $isPublished, $sortOrder, $staffId,
                            ]);
                        } else {
                            $pdo->prepare(
                                'UPDATE staff SET full_name=?, role=?, department=?, section=?,
                                 category=?, bio=?, is_published=?, sort_order=?
                                 WHERE id=?'
                            )->execute([
                                $fullName, $role, $department ?: null, $section,
                                $category, $bio ?: null,
                                $isPublished, $sortOrder, $staffId,
                            ]);
                        }
                        $message = 'Staff member updated.'; $messageType = 'success';
                    }
                } catch (PDOException $e) {
                    error_log('IHS staff error: ' . $e->getMessage());
                    $message = 'A server error occurred.'; $messageType = 'error';
                }
            }
        }
    }
}

/* ── Load staff for edit mode ── */
$editStaff = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM staff WHERE id = ? LIMIT 1');
    $es->execute([(int) $_GET['edit']]);
    $editStaff = $es->fetch();
}

/* ── Filters ── */
$filterSection  = $_GET['section']  ?? '';
$filterCategory = $_GET['category'] ?? '';

$where  = ['1=1'];
$params = [];
if ($filterSection) { $where[] = 'section = ?';  $params[] = $filterSection; }
if ($filterCategory){ $where[] = 'category = ?'; $params[] = $filterCategory; }
$whereSQL = implode(' AND ', $where);

$staffList = $pdo->prepare(
    "SELECT * FROM staff WHERE $whereSQL ORDER BY sort_order ASC, full_name ASC"
);
$staffList->execute($params);
$staff = $staffList->fetchAll();

$categories = [
    'administration' => 'Administration',
    'sciences'       => 'Sciences',
    'arts'           => 'Arts',
    'commercial'     => 'Commercial',
    'support'        => 'Support Staff',
];
$sections = ['ss' => 'Senior Secondary', 'js' => 'Junior Secondary', 'both' => 'Both'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Staff Directory — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
  .btn-new { background:#3d1a6e; color:#fff; text-decoration:none; padding:10px 20px; border-radius:8px; font-size:13.5px; font-weight:700; }
  .btn-new:hover { background:#5a2d9e; }
  .btn-new.active { background:#cc3333; }

  .filter-bar { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:14px 18px; margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; }
  .filter-group select { padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:130px; }
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
  .btn-reset { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:8px 14px; border-radius:7px; font-size:12.5px; font-weight:600; text-decoration:none; }

  /* Staff grid */
  .staff-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:16px; }
  .staff-card { background:#fff; border:1px solid #e8e6f0; border-radius:12px; overflow:hidden; }
  .staff-card__photo { width:100%; height:160px; object-fit:cover; display:block; background:#f4f3f9; }
  .staff-card__initials {
    width:100%; height:160px; display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#3d1a6e,#4a90d9); color:#fff;
    font-size:32px; font-weight:700; font-family:'Playfair Display',serif;
  }
  .staff-card__body { padding:14px; }
  .staff-card__name { font-size:14px; font-weight:700; color:#1a1a2e; margin-bottom:3px; }
  .staff-card__role { font-size:12.5px; color:#6b6b80; margin-bottom:8px; }
  .staff-card__tags { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:10px; }
  .tag { font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; }
  .tag--ss   { background:#f0ecfa; color:#3d1a6e; }
  .tag--js   { background:#e6f0ff; color:#1a5a9a; }
  .tag--both { background:#fff3e6; color:#8a4a00; }
  .tag--pub  { background:#e6f9ed; color:#1a7a3a; }
  .tag--hide { background:#ffe6e6; color:#cc3333; }
  .staff-card__actions { display:flex; gap:6px; flex-wrap:wrap; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 11px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit      { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--toggle    { background:#fff3e6; color:#8a4a00; }
  .action-btn--delete    { background:#ffe6e6; color:#cc3333; }

  /* Create/Edit form */
  .form-panel { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:24px; }
  .form-panel__title { font-size:14px; font-weight:700; color:#3d1a6e; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid #f0eef6; }
  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select, .form-textarea { width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; }
  .checkbox-row input { width:16px; height:16px; }
  .btn-group { display:flex; gap:12px; margin-top:20px; flex-wrap:wrap; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; display:inline-block; }

  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; background:#fff; border:1px solid #e8e6f0; border-radius:14px; }
  .results-count { font-size:13px; color:#6b6b80; margin-bottom:14px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'staff'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Staff Directory</h2>
          <p>Manage all staff profiles shown on the About and Academics pages.</p>
        </div>
        <a href="?<?php echo $editStaff ? '' : 'add=1'; ?>"
           class="btn-new <?php echo isset($_GET['add']) || $editStaff ? 'active' : ''; ?>"
           id="toggleFormBtn">
          <?php echo (isset($_GET['add']) || $editStaff) ? '✕ Cancel' : '+ Add Staff Member'; ?>
        </a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- ── Create / Edit Form ── -->
      <?php if (isset($_GET['add']) || $editStaff): ?>
      <div class="form-panel">
        <div class="form-panel__title"><?php echo $editStaff ? 'Edit Staff Member' : 'Add New Staff Member'; ?></div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?php echo $editStaff ? 'update' : 'create'; ?>"/>
          <?php if ($editStaff): ?>
          <input type="hidden" name="staff_id" value="<?php echo $editStaff['id']; ?>"/>
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" class="form-input" name="full_name" required maxlength="150"
                     value="<?php echo htmlspecialchars($editStaff['full_name'] ?? ''); ?>"
                     placeholder="e.g. Dr. Chukwuemeka Okafor"/>
            </div>
            <div class="form-group">
              <label class="form-label">Role / Position *</label>
              <input type="text" class="form-input" name="role" required maxlength="150"
                     value="<?php echo htmlspecialchars($editStaff['role'] ?? ''); ?>"
                     placeholder="e.g. Head of Department, Sciences"/>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Department</label>
              <input type="text" class="form-input" name="department" maxlength="100"
                     value="<?php echo htmlspecialchars($editStaff['department'] ?? ''); ?>"
                     placeholder="e.g. Science, Arts, Administration"/>
            </div>
            <div class="form-group">
              <label class="form-label">Section</label>
              <select class="form-select" name="section">
                <?php foreach ($sections as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo ($editStaff['section'] ?? 'both') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Category</label>
              <select class="form-select" name="category">
                <?php foreach ($categories as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo ($editStaff['category'] ?? 'support') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-input" name="sort_order" min="0" max="999"
                     value="<?php echo (int) ($editStaff['sort_order'] ?? 0); ?>"/>
              <p style="font-size:11.5px;color:#9b97b0;margin-top:4px">Lower numbers appear first. Principals = 1, VPs = 2, etc.</p>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Bio (optional)</label>
            <textarea class="form-textarea" name="bio" rows="3"
                      placeholder="A brief description shown on the staff directory..."><?php echo htmlspecialchars($editStaff['bio'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Photo (JPG, PNG or WEBP — max 3MB)</label>
            <?php if (!empty($editStaff['photo'])): ?>
            <div style="margin-bottom:8px">
              <img src="../assets/images/staff/<?php echo htmlspecialchars($editStaff['photo']); ?>"
                   alt="Current photo" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e8e6f0"/>
              <p style="font-size:11.5px;color:#9b97b0;margin-top:4px">Upload a new photo to replace the current one.</p>
            </div>
            <?php endif; ?>
            <input type="file" class="form-input" name="photo" accept="image/jpeg,image/png,image/webp"/>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_published" name="is_published"
                     <?php echo ($editStaff ? $editStaff['is_published'] : 1) ? 'checked' : ''; ?>/>
              <label for="is_published" style="font-size:13.5px">Published — visible on the public staff directory</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $editStaff ? 'Save Changes' : 'Add Staff Member'; ?></button>
            <a href="staff.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- ── Filters ── -->
      <form method="GET" class="filter-bar">
        <div class="filter-group">
          <label>Section</label>
          <select name="section">
            <option value="">All Sections</option>
            <?php foreach ($sections as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filterSection === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Category</label>
          <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filterCategory === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-filter">Filter</button>
        <a href="staff.php" class="btn-reset">Reset</a>
      </form>

      <p class="results-count">
        <strong><?php echo count($staff); ?></strong> staff member<?php echo count($staff) !== 1 ? 's' : ''; ?>
      </p>

      <!-- ── Staff Grid ── -->
      <?php if (empty($staff)): ?>
      <div class="empty-state">
        No staff members found.
        <?php if (!isset($_GET['add'])): ?>
        <a href="?add=1" style="color:#4a90d9">Add the first one →</a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="staff-grid">
        <?php foreach ($staff as $m): ?>
        <div class="staff-card">
          <?php if (!empty($m['photo'])): ?>
          <img src="../assets/images/staff/<?php echo htmlspecialchars($m['photo']); ?>"
               alt="<?php echo htmlspecialchars($m['full_name']); ?>"
               class="staff-card__photo"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="staff-card__initials" style="display:none">
            <?php echo strtoupper(substr($m['full_name'], 0, 1)); ?>
          </div>
          <?php else: ?>
          <div class="staff-card__initials">
            <?php echo strtoupper(substr($m['full_name'], 0, 1)); ?>
          </div>
          <?php endif; ?>

          <div class="staff-card__body">
            <div class="staff-card__name"><?php echo htmlspecialchars($m['full_name']); ?></div>
            <div class="staff-card__role"><?php echo htmlspecialchars($m['role']); ?></div>
            <div class="staff-card__tags">
              <span class="tag tag--<?php echo $m['section']; ?>">
                <?php echo $sections[$m['section']] ?? $m['section']; ?>
              </span>
              <span class="tag tag--<?php echo $m['is_published'] ? 'pub' : 'hide'; ?>">
                <?php echo $m['is_published'] ? 'Published' : 'Hidden'; ?>
              </span>
            </div>
            <div class="staff-card__actions">
              <a href="?edit=<?php echo $m['id']; ?>" class="action-btn action-btn--edit">Edit</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_publish"/>
                <input type="hidden" name="staff_id" value="<?php echo $m['id']; ?>"/>
                <button type="submit" class="action-btn action-btn--toggle">
                  <?php echo $m['is_published'] ? 'Hide' : 'Show'; ?>
                </button>
              </form>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($m['full_name'])); ?>? This cannot be undone.')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="staff_id" value="<?php echo $m['id']; ?>"/>
                <button type="submit" class="action-btn action-btn--delete">Delete</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>