<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ALUMNI MANAGEMENT
   File: public/admin/alumni.php

   Accessible to: superadmin only
   Add, edit, feature, publish/unpublish, delete alumni.
   Featured alumni appear on the homepage and students.php.
   All published alumni appear on the hall-of-fame alumni wall.
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
    $id     = (int) ($_POST['alumni_id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $row = $pdo->prepare('SELECT photo FROM alumni WHERE id = ? LIMIT 1');
            $row->execute([$id]);
            $photo = $row->fetchColumn();
            if ($photo) {
                $path = dirname(__DIR__) . '/assets/images/staff/' . $photo;
                if (file_exists($path)) unlink($path);
            }
            $pdo->prepare('DELETE FROM alumni WHERE id = ?')->execute([$id]);
            $message = 'Alumni deleted.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif ($action === 'toggle_publish' && $id > 0) {
        try {
            $pdo->prepare('UPDATE alumni SET is_published = NOT is_published WHERE id = ?')->execute([$id]);
            $message = 'Alumni visibility updated.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif ($action === 'toggle_featured' && $id > 0) {
        try {
            $pdo->prepare('UPDATE alumni SET is_featured = NOT is_featured WHERE id = ?')->execute([$id]);
            $message = 'Alumni featured status updated.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif (in_array($action, ['create', 'update'], true)) {
        $fullName   = trim($_POST['full_name']  ?? '');
        $classYear  = trim($_POST['class_year'] ?? '');
        $field      = trim($_POST['field']      ?? '');
        $bio        = trim($_POST['bio']        ?? '');
        $sortOrder  = (int) ($_POST['sort_order']  ?? 0);
        $isFeatured  = isset($_POST['is_featured'])  ? 1 : 0;
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        if ($fullName === '') {
            $message = 'Full name is required.'; $messageType = 'error';
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
                    $photoFilename = 'alumni_' . uniqid('', true) . '.' . $ext;
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
                            'INSERT INTO alumni
                                (full_name, class_year, field, bio, photo,
                                 is_featured, is_published, sort_order)
                             VALUES (?,?,?,?,?,?,?,?)'
                        )->execute([
                            $fullName, $classYear ?: null, $field ?: null,
                            $bio ?: null, $photoFilename,
                            $isFeatured, $isPublished, $sortOrder,
                        ]);
                        $message = 'Alumni added.'; $messageType = 'success';
                    } else {
                        if ($photoFilename) {
                            $oldRow = $pdo->prepare('SELECT photo FROM alumni WHERE id = ? LIMIT 1');
                            $oldRow->execute([$id]);
                            $oldPhoto = $oldRow->fetchColumn();
                            if ($oldPhoto) {
                                $oldPath = dirname(__DIR__) . '/assets/images/staff/' . $oldPhoto;
                                if (file_exists($oldPath)) unlink($oldPath);
                            }
                            $pdo->prepare(
                                'UPDATE alumni SET full_name=?, class_year=?, field=?, bio=?,
                                 photo=?, is_featured=?, is_published=?, sort_order=? WHERE id=?'
                            )->execute([
                                $fullName, $classYear ?: null, $field ?: null,
                                $bio ?: null, $photoFilename,
                                $isFeatured, $isPublished, $sortOrder, $id,
                            ]);
                        } else {
                            $pdo->prepare(
                                'UPDATE alumni SET full_name=?, class_year=?, field=?, bio=?,
                                 is_featured=?, is_published=?, sort_order=? WHERE id=?'
                            )->execute([
                                $fullName, $classYear ?: null, $field ?: null,
                                $bio ?: null, $isFeatured, $isPublished, $sortOrder, $id,
                            ]);
                        }
                        $message = 'Alumni updated.'; $messageType = 'success';
                    }
                } catch (PDOException $e) {
                    error_log('IHS alumni error: ' . $e->getMessage());
                    $message = 'A server error occurred.'; $messageType = 'error';
                }
            }
        }
    }
}

$editAlumni = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM alumni WHERE id = ? LIMIT 1');
    $es->execute([(int) $_GET['edit']]);
    $editAlumni = $es->fetch();
}

$filterFeatured = $_GET['featured'] ?? '';
$where  = ['1=1'];
$params = [];
if ($filterFeatured === '1') { $where[] = 'is_featured = 1'; }
$whereSQL = implode(' AND ', $where);

$alumniList = $pdo->prepare(
    "SELECT * FROM alumni WHERE $whereSQL ORDER BY sort_order ASC, full_name ASC"
);
$alumniList->execute($params);
$alumniAll = $alumniList->fetchAll();

$totalCount    = (int) $pdo->query('SELECT COUNT(*) FROM alumni')->fetchColumn();
$featuredCount = (int) $pdo->query('SELECT COUNT(*) FROM alumni WHERE is_featured = 1')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Alumni — Admin — Ibeku High School</title>
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
  .filter-tabs { display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap; }
  .filter-tab { padding:7px 16px; border-radius:20px; font-size:12.5px; font-weight:600; text-decoration:none; color:#6b6b80; background:#fff; border:1px solid #e8e6f0; }
  .filter-tab--active { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }
  .form-panel { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:24px; }
  .form-panel__title { font-size:14px; font-weight:700; color:#3d1a6e; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid #f0eef6; }
  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-textarea { width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
  .checkbox-row input { width:16px; height:16px; }
  .btn-group { display:flex; gap:12px; margin-top:20px; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; }
  .alumni-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; }
  .alumni-card { background:#fff; border:1px solid #e8e6f0; border-radius:12px; overflow:hidden; }
  .alumni-card__photo { width:100%; height:140px; object-fit:cover; display:block; background:#f4f3f9; }
  .alumni-card__initials { width:100%; height:140px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#3d1a6e,#4a90d9); color:#fff; font-size:28px; font-weight:700; font-family:'Playfair Display',serif; }
  .alumni-card__body { padding:14px; }
  .alumni-card__name { font-size:14px; font-weight:700; color:#1a1a2e; margin-bottom:2px; }
  .alumni-card__class { font-size:12px; color:#9b97b0; margin-bottom:4px; }
  .alumni-card__field { font-size:12px; background:#f0ecfa; color:#3d1a6e; padding:2px 8px; border-radius:20px; display:inline-block; margin-bottom:8px; }
  .badges { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:10px; }
  .badge { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; }
  .badge--pub      { background:#e6f9ed; color:#1a7a3a; }
  .badge--hide     { background:#ffe6e6; color:#cc3333; }
  .badge--featured { background:#fff3e6; color:#8a4a00; }
  .alumni-card__actions { display:flex; gap:5px; flex-wrap:wrap; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 10px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit     { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--toggle   { background:#fff3e6; color:#8a4a00; }
  .action-btn--feature  { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--delete   { background:#ffe6e6; color:#cc3333; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; background:#fff; border:1px solid #e8e6f0; border-radius:14px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'alumni'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Alumni Directory</h2>
          <p>Manage alumni profiles. Featured alumni appear on the homepage and students page.</p>
        </div>
        <a href="?<?php echo isset($_GET['add']) || $editAlumni ? '' : 'add=1'; ?>"
           class="btn-new <?php echo isset($_GET['add']) || $editAlumni ? 'cancel' : ''; ?>">
          <?php echo isset($_GET['add']) || $editAlumni ? '✕ Cancel' : '+ Add Alumni'; ?>
        </a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="stats-row">
        <div class="stat-pill"><strong><?php echo $totalCount; ?></strong>Total Alumni</div>
        <div class="stat-pill"><strong><?php echo $featuredCount; ?></strong>Featured</div>
      </div>

      <div class="filter-tabs">
        <a href="alumni.php" class="filter-tab <?php echo $filterFeatured === '' ? 'filter-tab--active' : ''; ?>">All</a>
        <a href="?featured=1" class="filter-tab <?php echo $filterFeatured === '1' ? 'filter-tab--active' : ''; ?>">Featured Only</a>
      </div>

      <!-- ── Form ── -->
      <?php if (isset($_GET['add']) || $editAlumni): ?>
      <div class="form-panel">
        <div class="form-panel__title"><?php echo $editAlumni ? 'Edit Alumni' : 'Add New Alumni'; ?></div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?php echo $editAlumni ? 'update' : 'create'; ?>"/>
          <?php if ($editAlumni): ?>
          <input type="hidden" name="alumni_id" value="<?php echo $editAlumni['id']; ?>"/>
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" class="form-input" name="full_name" required maxlength="150"
                     value="<?php echo htmlspecialchars($editAlumni['full_name'] ?? ''); ?>"
                     placeholder="e.g. Dr. Chukwuemeka Okafor"/>
            </div>
            <div class="form-group">
              <label class="form-label">Class Year</label>
              <input type="text" class="form-input" name="class_year" maxlength="12"
                     value="<?php echo htmlspecialchars($editAlumni['class_year'] ?? ''); ?>"
                     placeholder="e.g. 2005"/>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Field / Profession</label>
              <input type="text" class="form-input" name="field" maxlength="150"
                     value="<?php echo htmlspecialchars($editAlumni['field'] ?? ''); ?>"
                     placeholder="e.g. Medicine, Law, Engineering"/>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-input" name="sort_order" min="0"
                     value="<?php echo (int) ($editAlumni['sort_order'] ?? 0); ?>"/>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Bio</label>
            <textarea class="form-textarea" name="bio" rows="3"
                      placeholder="Brief description of their achievements..."><?php echo htmlspecialchars($editAlumni['bio'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Photo (JPG, PNG or WEBP — max 3MB)</label>
            <?php if (!empty($editAlumni['photo'])): ?>
            <div style="margin-bottom:8px">
              <img src="../assets/images/staff/<?php echo htmlspecialchars($editAlumni['photo']); ?>"
                   alt="Current photo" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #e8e6f0"/>
            </div>
            <?php endif; ?>
            <input type="file" class="form-input" name="photo" accept="image/jpeg,image/png,image/webp"/>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_featured" name="is_featured"
                     <?php echo ($editAlumni ? $editAlumni['is_featured'] : 0) ? 'checked' : ''; ?>/>
              <label for="is_featured" style="font-size:13.5px">Featured — shown on homepage and students page notable alumni section</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="is_published" name="is_published"
                     <?php echo ($editAlumni ? $editAlumni['is_published'] : 1) ? 'checked' : ''; ?>/>
              <label for="is_published" style="font-size:13.5px">Published — shown on the alumni wall</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $editAlumni ? 'Save Changes' : 'Add Alumni'; ?></button>
            <a href="alumni.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- ── Grid ── -->
      <?php if (empty($alumniAll)): ?>
      <div class="empty-state">No alumni yet. <a href="?add=1" style="color:#4a90d9">Add the first one →</a></div>
      <?php else: ?>
      <div class="alumni-grid">
        <?php foreach ($alumniAll as $a): ?>
        <div class="alumni-card">
          <?php if (!empty($a['photo'])): ?>
          <img src="../assets/images/staff/<?php echo htmlspecialchars($a['photo']); ?>"
               alt="<?php echo htmlspecialchars($a['full_name']); ?>"
               class="alumni-card__photo"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="alumni-card__initials" style="display:none">
            <?php echo strtoupper(substr($a['full_name'], 0, 1)); ?>
          </div>
          <?php else: ?>
          <div class="alumni-card__initials">
            <?php echo strtoupper(substr($a['full_name'], 0, 1)); ?>
          </div>
          <?php endif; ?>
          <div class="alumni-card__body">
            <div class="alumni-card__name"><?php echo htmlspecialchars($a['full_name']); ?></div>
            <?php if ($a['class_year']): ?>
            <div class="alumni-card__class">Class of <?php echo htmlspecialchars($a['class_year']); ?></div>
            <?php endif; ?>
            <?php if ($a['field']): ?>
            <span class="alumni-card__field"><?php echo htmlspecialchars($a['field']); ?></span>
            <?php endif; ?>
            <div class="badges">
              <span class="badge badge--<?php echo $a['is_published'] ? 'pub' : 'hide'; ?>">
                <?php echo $a['is_published'] ? 'Published' : 'Hidden'; ?>
              </span>
              <?php if ($a['is_featured']): ?>
              <span class="badge badge--featured">Featured</span>
              <?php endif; ?>
            </div>
            <div class="alumni-card__actions">
              <a href="?edit=<?php echo $a['id']; ?>" class="action-btn action-btn--edit">Edit</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_featured"/>
                <input type="hidden" name="alumni_id" value="<?php echo $a['id']; ?>"/>
                <button type="submit" class="action-btn action-btn--feature">
                  <?php echo $a['is_featured'] ? '★ Unfeature' : '☆ Feature'; ?>
                </button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_publish"/>
                <input type="hidden" name="alumni_id" value="<?php echo $a['id']; ?>"/>
                <button type="submit" class="action-btn action-btn--toggle">
                  <?php echo $a['is_published'] ? 'Hide' : 'Show'; ?>
                </button>
              </form>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($a['full_name'])); ?>?')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="alumni_id" value="<?php echo $a['id']; ?>"/>
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