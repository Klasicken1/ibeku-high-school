<?php
/* ============================================================
   IBEKU HIGH SCHOOL — HALL OF FAME MANAGEMENT
   File: public/admin/hall-of-fame-admin.php

   Accessible to: superadmin only
   Add, edit, publish/unpublish, delete Hall of Fame entries.
   Entries power the hall-of-fame.php public page.
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

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $id     = (int) ($_POST['entry_id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $row = $pdo->prepare('SELECT photo FROM hall_of_fame WHERE id = ? LIMIT 1');
            $row->execute([$id]);
            $photo = $row->fetchColumn();
            if ($photo) {
                $path = dirname(__DIR__) . '/assets/images/staff/' . $photo;
                if (file_exists($path)) unlink($path);
            }
            $pdo->prepare('DELETE FROM hall_of_fame WHERE id = ?')->execute([$id]);
            $message = 'Entry deleted.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif ($action === 'toggle_publish' && $id > 0) {
        try {
            $pdo->prepare('UPDATE hall_of_fame SET is_published = NOT is_published WHERE id = ?')->execute([$id]);
            $message = 'Entry visibility updated.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif (in_array($action, ['create', 'update'], true)) {
        $fullName    = trim($_POST['full_name']   ?? '');
        $category    = trim($_POST['category']    ?? 'alumni');
        $classYear   = trim($_POST['class_year']  ?? '');
        $field       = trim($_POST['field']       ?? '');
        $achievement = trim($_POST['achievement'] ?? '');
        $nominatedBy = trim($_POST['nominated_by']?? '');
        $sortOrder   = (int) ($_POST['sort_order']  ?? 0);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        $validCategories = ['alumni','academic','sports','prefect','staff'];
        if ($fullName === '') {
            $message = 'Full name is required.'; $messageType = 'error';
        } elseif ($achievement === '') {
            $message = 'Achievement description is required.'; $messageType = 'error';
        } elseif (!in_array($category, $validCategories, true)) {
            $message = 'Invalid category.'; $messageType = 'error';
        } else {
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
                    $photoFilename = 'hof_' . uniqid('', true) . '.' . $ext;
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
                            'INSERT INTO hall_of_fame
                                (full_name, category, class_year, field, achievement,
                                 photo, nominated_by, is_published, sort_order)
                             VALUES (?,?,?,?,?,?,?,?,?)'
                        )->execute([
                            $fullName, $category, $classYear ?: null, $field ?: null,
                            $achievement, $photoFilename, $nominatedBy ?: null,
                            $isPublished, $sortOrder,
                        ]);
                        $message = 'Entry added to Hall of Fame.'; $messageType = 'success';
                    } else {
                        if ($photoFilename) {
                            $oldRow = $pdo->prepare('SELECT photo FROM hall_of_fame WHERE id = ? LIMIT 1');
                            $oldRow->execute([$id]);
                            $oldPhoto = $oldRow->fetchColumn();
                            if ($oldPhoto) {
                                $oldPath = dirname(__DIR__) . '/assets/images/staff/' . $oldPhoto;
                                if (file_exists($oldPath)) unlink($oldPath);
                            }
                            $pdo->prepare(
                                'UPDATE hall_of_fame SET full_name=?, category=?, class_year=?,
                                 field=?, achievement=?, photo=?, nominated_by=?,
                                 is_published=?, sort_order=? WHERE id=?'
                            )->execute([
                                $fullName, $category, $classYear ?: null, $field ?: null,
                                $achievement, $photoFilename, $nominatedBy ?: null,
                                $isPublished, $sortOrder, $id,
                            ]);
                        } else {
                            $pdo->prepare(
                                'UPDATE hall_of_fame SET full_name=?, category=?, class_year=?,
                                 field=?, achievement=?, nominated_by=?,
                                 is_published=?, sort_order=? WHERE id=?'
                            )->execute([
                                $fullName, $category, $classYear ?: null, $field ?: null,
                                $achievement, $nominatedBy ?: null,
                                $isPublished, $sortOrder, $id,
                            ]);
                        }
                        $message = 'Entry updated.'; $messageType = 'success';
                    }
                } catch (PDOException $e) {
                    error_log('IHS hall_of_fame error: ' . $e->getMessage());
                    $message = 'A server error occurred.'; $messageType = 'error';
                }
            }
        }
    }
}

$editEntry = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM hall_of_fame WHERE id = ? LIMIT 1');
    $es->execute([(int) $_GET['edit']]);
    $editEntry = $es->fetch();
}

$filterCategory = $_GET['category'] ?? '';
$where  = ['1=1'];
$params = [];
if ($filterCategory) { $where[] = 'category = ?'; $params[] = $filterCategory; }
$whereSQL = implode(' AND ', $where);

$entries = $pdo->prepare(
    "SELECT * FROM hall_of_fame WHERE $whereSQL ORDER BY sort_order ASC, full_name ASC"
);
$entries->execute($params);
$entryList = $entries->fetchAll();

$categories = [
    'alumni'   => 'Distinguished Alumni',
    'academic' => 'Academic Star',
    'sports'   => 'Sports Champion',
    'prefect'  => 'Head Prefect',
    'staff'    => 'Legendary Staff',
];

$totalCount = (int) $pdo->query('SELECT COUNT(*) FROM hall_of_fame')->fetchColumn();
$pubCount   = (int) $pdo->query('SELECT COUNT(*) FROM hall_of_fame WHERE is_published = 1')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Hall of Fame — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
  .btn-new { background:#3d1a6e; color:#fff; text-decoration:none; padding:10px 20px; border-radius:8px; font-size:13.5px; font-weight:700; }
  .btn-new:hover { background:#5a2d9e; }
  .btn-new.cancel { background:#cc3333; }
  .stats-row { display:flex; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
  .stat-pill { background:#fff; border:1px solid #e8e6f0; border-radius:10px; padding:10px 18px; font-size:12.5px; color:#6b6b80; }
  .stat-pill strong { color:#3d1a6e; font-size:15px; display:block; }
  .filter-bar { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:14px 18px; margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; }
  .filter-group select { padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:160px; }
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
  .btn-reset { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:8px 14px; border-radius:7px; font-size:12.5px; font-weight:600; text-decoration:none; }
  .form-panel { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:24px; }
  .form-panel__title { font-size:14px; font-weight:700; color:#3d1a6e; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid #f0eef6; }
  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select, .form-textarea { width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  .form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; }
  .checkbox-row input { width:16px; height:16px; }
  .btn-group { display:flex; gap:12px; margin-top:20px; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; }
  .hof-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; }
  .hof-card { background:#fff; border:1px solid #e8e6f0; border-radius:12px; overflow:hidden; }
  .hof-card__photo { width:100%; height:150px; object-fit:cover; display:block; background:#f4f3f9; }
  .hof-card__initials { width:100%; height:150px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#1a0835,#e8a020); color:#fff; font-size:28px; font-weight:700; font-family:'Playfair Display',serif; }
  .hof-card__body { padding:14px; }
  .hof-card__name { font-size:14px; font-weight:700; color:#1a1a2e; margin-bottom:3px; }
  .hof-card__class { font-size:12px; color:#9b97b0; margin-bottom:4px; }
  .hof-card__achievement { font-size:12.5px; color:#6b6b80; line-height:1.5; margin-bottom:8px; }
  .cat-badge { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; margin-bottom:6px; background:#f0ecfa; color:#3d1a6e; }
  .badge--pub  { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; background:#e6f9ed; color:#1a7a3a; margin-bottom:6px; }
  .badge--hide { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; background:#ffe6e6; color:#cc3333; margin-bottom:6px; }
  .hof-card__actions { display:flex; gap:5px; flex-wrap:wrap; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 10px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit   { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--toggle { background:#fff3e6; color:#8a4a00; }
  .action-btn--delete { background:#ffe6e6; color:#cc3333; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; background:#fff; border:1px solid #e8e6f0; border-radius:14px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'hall-of-fame-admin'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Hall of Fame</h2>
          <p>Manage inductees shown on the Hall of Fame public page. Use Nominations to review public submissions.</p>
        </div>
        <a href="?<?php echo isset($_GET['add']) || $editEntry ? '' : 'add=1'; ?>"
           class="btn-new <?php echo isset($_GET['add']) || $editEntry ? 'cancel' : ''; ?>">
          <?php echo isset($_GET['add']) || $editEntry ? '✕ Cancel' : '+ Add Inductee'; ?>
        </a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="stats-row">
        <div class="stat-pill"><strong><?php echo $totalCount; ?></strong>Total Inductees</div>
        <div class="stat-pill"><strong><?php echo $pubCount; ?></strong>Published</div>
        <div class="stat-pill">
          <strong><a href="nominations.php" style="color:#3d1a6e;text-decoration:none">View →</a></strong>
          Nominations Inbox
        </div>
      </div>

      <!-- ── Form ── -->
      <?php if (isset($_GET['add']) || $editEntry): ?>
      <div class="form-panel">
        <div class="form-panel__title"><?php echo $editEntry ? 'Edit Inductee' : 'Add New Inductee'; ?></div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?php echo $editEntry ? 'update' : 'create'; ?>"/>
          <?php if ($editEntry): ?>
          <input type="hidden" name="entry_id" value="<?php echo $editEntry['id']; ?>"/>
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" class="form-input" name="full_name" required maxlength="150"
                     value="<?php echo htmlspecialchars($editEntry['full_name'] ?? ''); ?>"
                     placeholder="e.g. Dr. Chukwuemeka Okafor"/>
            </div>
            <div class="form-group">
              <label class="form-label">Category *</label>
              <select class="form-select" name="category">
                <?php foreach ($categories as $k => $v): ?>
                <option value="<?php echo $k; ?>"
                  <?php echo ($editEntry['category'] ?? 'alumni') === $k ? 'selected' : ''; ?>>
                  <?php echo $v; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Class Year</label>
              <input type="text" class="form-input" name="class_year" maxlength="12"
                     value="<?php echo htmlspecialchars($editEntry['class_year'] ?? ''); ?>"
                     placeholder="e.g. 2005"/>
            </div>
            <div class="form-group">
              <label class="form-label">Field / Profession</label>
              <input type="text" class="form-input" name="field" maxlength="150"
                     value="<?php echo htmlspecialchars($editEntry['field'] ?? ''); ?>"
                     placeholder="e.g. Medicine & Public Health"/>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Achievement Description *</label>
            <textarea class="form-textarea" name="achievement" rows="4" required
                      placeholder="A full description of this person's achievements and why they deserve Hall of Fame recognition..."><?php echo htmlspecialchars($editEntry['achievement'] ?? ''); ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nominated By</label>
              <input type="text" class="form-input" name="nominated_by" maxlength="150"
                     value="<?php echo htmlspecialchars($editEntry['nominated_by'] ?? ''); ?>"
                     placeholder="e.g. School Management, OSA"/>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-input" name="sort_order" min="0"
                     value="<?php echo (int) ($editEntry['sort_order'] ?? 0); ?>"/>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Photo (JPG, PNG or WEBP — max 3MB)</label>
            <?php if (!empty($editEntry['photo'])): ?>
            <div style="margin-bottom:8px">
              <img src="../assets/images/staff/<?php echo htmlspecialchars($editEntry['photo']); ?>"
                   alt="Current photo" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #e8e6f0"/>
            </div>
            <?php endif; ?>
            <input type="file" class="form-input" name="photo" accept="image/jpeg,image/png,image/webp"/>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_published" name="is_published"
                     <?php echo ($editEntry ? $editEntry['is_published'] : 1) ? 'checked' : ''; ?>/>
              <label for="is_published" style="font-size:13.5px">Published — visible on the Hall of Fame page</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $editEntry ? 'Save Changes' : 'Add to Hall of Fame'; ?></button>
            <a href="hall-of-fame-admin.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- ── Filter ── -->
      <form method="GET" class="filter-bar">
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
        <a href="hall-of-fame-admin.php" class="btn-reset">Reset</a>
      </form>

      <!-- ── Grid ── -->
      <?php if (empty($entryList)): ?>
      <div class="empty-state">No Hall of Fame entries yet. <a href="?add=1" style="color:#4a90d9">Add the first one →</a></div>
      <?php else: ?>
      <div class="hof-grid">
        <?php foreach ($entryList as $e): ?>
        <div class="hof-card">
          <?php if (!empty($e['photo'])): ?>
          <img src="../assets/images/staff/<?php echo htmlspecialchars($e['photo']); ?>"
               alt="<?php echo htmlspecialchars($e['full_name']); ?>"
               class="hof-card__photo"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="hof-card__initials" style="display:none">
            <?php echo strtoupper(substr($e['full_name'], 0, 1)); ?>
          </div>
          <?php else: ?>
          <div class="hof-card__initials">
            <?php echo strtoupper(substr($e['full_name'], 0, 1)); ?>
          </div>
          <?php endif; ?>
          <div class="hof-card__body">
            <span class="cat-badge"><?php echo htmlspecialchars($categories[$e['category']] ?? $e['category']); ?></span>
            <div class="hof-card__name"><?php echo htmlspecialchars($e['full_name']); ?></div>
            <?php if ($e['class_year']): ?>
            <div class="hof-card__class">Class of <?php echo htmlspecialchars($e['class_year']); ?></div>
            <?php endif; ?>
            <div class="hof-card__achievement">
              <?php echo htmlspecialchars(mb_substr($e['achievement'], 0, 80)) . (mb_strlen($e['achievement']) > 80 ? '…' : ''); ?>
            </div>
            <span class="badge--<?php echo $e['is_published'] ? 'pub' : 'hide'; ?>">
              <?php echo $e['is_published'] ? 'Published' : 'Hidden'; ?>
            </span>
            <div class="hof-card__actions">
              <a href="?edit=<?php echo $e['id']; ?>" class="action-btn action-btn--edit">Edit</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_publish"/>
                <input type="hidden" name="entry_id" value="<?php echo $e['id']; ?>"/>
                <button type="submit" class="action-btn action-btn--toggle">
                  <?php echo $e['is_published'] ? 'Hide' : 'Show'; ?>
                </button>
              </form>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($e['full_name'])); ?>?')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="entry_id" value="<?php echo $e['id']; ?>"/>
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