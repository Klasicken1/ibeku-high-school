<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PREFECTS MANAGEMENT
   File: public/admin/prefects-admin.php

   Accessible to: superadmin, principal
   Add, edit, publish/unpublish, delete prefect profiles.
   Profiles are shown on students.php prefects section.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal']);

$admin = currentAdmin();
$pdo   = getDB();

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $id     = (int) ($_POST['prefect_id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $row = $pdo->prepare('SELECT photo FROM prefects WHERE id = ? LIMIT 1');
            $row->execute([$id]);
            $photo = $row->fetchColumn();
            if ($photo) {
                $path = dirname(__DIR__) . '/assets/images/staff/' . $photo;
                if (file_exists($path)) unlink($path);
            }
            $pdo->prepare('DELETE FROM prefects WHERE id = ?')->execute([$id]);
            $message = 'Prefect deleted.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif ($action === 'toggle_active' && $id > 0) {
        try {
            $pdo->prepare('UPDATE prefects SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
            $message = 'Prefect status updated.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif (in_array($action, ['create', 'update'], true)) {
        $fullName  = trim($_POST['full_name']  ?? '');
        $role      = trim($_POST['role']       ?? '');
        $section   = trim($_POST['section']    ?? 'ss');
        $session   = trim($_POST['session']    ?? '');
        $quote     = trim($_POST['quote']      ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive  = isset($_POST['is_active']) ? 1 : 0;

        if ($fullName === '') {
            $message = 'Full name is required.'; $messageType = 'error';
        } elseif ($role === '') {
            $message = 'Role is required.'; $messageType = 'error';
        } elseif ($session === '') {
            $message = 'Session is required.'; $messageType = 'error';
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
                    $photoFilename = 'prefect_' . uniqid('', true) . '.' . $ext;
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
                            'INSERT INTO prefects
                                (full_name, role, section, session, quote, photo, is_active, sort_order)
                             VALUES (?,?,?,?,?,?,?,?)'
                        )->execute([
                            $fullName, $role, $section, $session,
                            $quote ?: null, $photoFilename, $isActive, $sortOrder,
                        ]);
                        $message = 'Prefect added.'; $messageType = 'success';
                    } else {
                        if ($photoFilename) {
                            $oldRow = $pdo->prepare('SELECT photo FROM prefects WHERE id = ? LIMIT 1');
                            $oldRow->execute([$id]);
                            $oldPhoto = $oldRow->fetchColumn();
                            if ($oldPhoto) {
                                $oldPath = dirname(__DIR__) . '/assets/images/staff/' . $oldPhoto;
                                if (file_exists($oldPath)) unlink($oldPath);
                            }
                            $pdo->prepare(
                                'UPDATE prefects SET full_name=?, role=?, section=?, session=?,
                                 quote=?, photo=?, is_active=?, sort_order=? WHERE id=?'
                            )->execute([
                                $fullName, $role, $section, $session,
                                $quote ?: null, $photoFilename, $isActive, $sortOrder, $id,
                            ]);
                        } else {
                            $pdo->prepare(
                                'UPDATE prefects SET full_name=?, role=?, section=?, session=?,
                                 quote=?, is_active=?, sort_order=? WHERE id=?'
                            )->execute([
                                $fullName, $role, $section, $session,
                                $quote ?: null, $isActive, $sortOrder, $id,
                            ]);
                        }
                        $message = 'Prefect updated.'; $messageType = 'success';
                    }
                } catch (PDOException $e) {
                    error_log('IHS prefects error: ' . $e->getMessage());
                    $message = 'A server error occurred.'; $messageType = 'error';
                }
            }
        }
    }
}

$editPrefect = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM prefects WHERE id = ? LIMIT 1');
    $es->execute([(int) $_GET['edit']]);
    $editPrefect = $es->fetch();
}

/* Get available sessions from DB + current session from settings */
$settings       = getSettings();
$currentSession = $settings['current_session'] ?? '2025/2026';
$sessions       = $pdo->query(
    "SELECT DISTINCT session FROM prefects ORDER BY session DESC"
)->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($currentSession, $sessions, true)) {
    array_unshift($sessions, $currentSession);
}

$filterSession = $_GET['session'] ?? $currentSession;
$prefects = $pdo->prepare(
    "SELECT * FROM prefects WHERE session = ? ORDER BY sort_order ASC, full_name ASC"
);
$prefects->execute([$filterSession]);
$prefectList = $prefects->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Prefects — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
  .btn-new { background:#3d1a6e; color:#fff; text-decoration:none; padding:10px 20px; border-radius:8px; font-size:13.5px; font-weight:700; }
  .btn-new:hover { background:#5a2d9e; }
  .btn-new.cancel { background:#cc3333; }
  .session-tabs { display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap; }
  .session-tab { padding:7px 16px; border-radius:20px; font-size:12.5px; font-weight:600; text-decoration:none; color:#6b6b80; background:#fff; border:1px solid #e8e6f0; }
  .session-tab--active { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }
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
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }
  .btn-group { display:flex; gap:12px; margin-top:20px; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; }
  .prefects-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
  .prefect-card { background:#fff; border:1px solid #e8e6f0; border-radius:12px; overflow:hidden; }
  .prefect-card__photo { width:100%; height:150px; object-fit:cover; display:block; background:#f4f3f9; }
  .prefect-card__initials { width:100%; height:150px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#3d1a6e,#4a90d9); color:#fff; font-size:28px; font-weight:700; font-family:'Playfair Display',serif; }
  .prefect-card__body { padding:12px; }
  .prefect-card__name { font-size:14px; font-weight:700; color:#1a1a2e; margin-bottom:2px; }
  .prefect-card__role { font-size:12.5px; color:#6b6b80; margin-bottom:6px; }
  .badge { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; margin-bottom:8px; }
  .badge--ss { background:#f0ecfa; color:#3d1a6e; }
  .badge--js { background:#e6f0ff; color:#1a5a9a; }
  .badge--active { background:#e6f9ed; color:#1a7a3a; }
  .badge--inactive { background:#ffe6e6; color:#cc3333; }
  .prefect-card__actions { display:flex; gap:5px; flex-wrap:wrap; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 10px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit   { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--toggle { background:#fff3e6; color:#8a4a00; }
  .action-btn--delete { background:#ffe6e6; color:#cc3333; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; background:#fff; border:1px solid #e8e6f0; border-radius:14px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'prefects-admin'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Prefects &amp; Student Leaders</h2>
          <p>Manage prefect profiles shown on the Students page. Organised by academic session.</p>
        </div>
        <a href="?<?php echo isset($_GET['add']) || $editPrefect ? 'session=' . urlencode($filterSession) : 'add=1&session=' . urlencode($filterSession); ?>"
           class="btn-new <?php echo isset($_GET['add']) || $editPrefect ? 'cancel' : ''; ?>">
          <?php echo isset($_GET['add']) || $editPrefect ? '✕ Cancel' : '+ Add Prefect'; ?>
        </a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Session tabs -->
      <div class="session-tabs">
        <?php foreach ($sessions as $sess): ?>
        <a href="?session=<?php echo urlencode($sess); ?>"
           class="session-tab <?php echo $filterSession === $sess ? 'session-tab--active' : ''; ?>">
          <?php echo htmlspecialchars($sess); ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- ── Form ── -->
      <?php if (isset($_GET['add']) || $editPrefect): ?>
      <div class="form-panel">
        <div class="form-panel__title"><?php echo $editPrefect ? 'Edit Prefect' : 'Add New Prefect'; ?></div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?php echo $editPrefect ? 'update' : 'create'; ?>"/>
          <?php if ($editPrefect): ?>
          <input type="hidden" name="prefect_id" value="<?php echo $editPrefect['id']; ?>"/>
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" class="form-input" name="full_name" required maxlength="150"
                     value="<?php echo htmlspecialchars($editPrefect['full_name'] ?? ''); ?>"
                     placeholder="e.g. Chidinma Okafor"/>
            </div>
            <div class="form-group">
              <label class="form-label">Role *</label>
              <input type="text" class="form-input" name="role" required maxlength="100"
                     value="<?php echo htmlspecialchars($editPrefect['role'] ?? ''); ?>"
                     placeholder="e.g. Head Boy, Head Girl, Sports Prefect"/>
            </div>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label class="form-label">Section *</label>
              <select class="form-select" name="section">
                <option value="ss" <?php echo ($editPrefect['section'] ?? 'ss') === 'ss' ? 'selected' : ''; ?>>Senior Secondary (SS)</option>
                <option value="js" <?php echo ($editPrefect['section'] ?? '') === 'js' ? 'selected' : ''; ?>>Junior Secondary (JS)</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Session *</label>
              <input type="text" class="form-input" name="session" required maxlength="12"
                     value="<?php echo htmlspecialchars($editPrefect['session'] ?? $filterSession); ?>"
                     placeholder="e.g. 2025/2026"/>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-input" name="sort_order" min="0"
                     value="<?php echo (int) ($editPrefect['sort_order'] ?? count($prefectList)); ?>"/>
              <p class="char-hint">Head Boy = 1, Head Girl = 2, etc.</p>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Quote (optional)</label>
            <textarea class="form-textarea" name="quote" rows="2"
                      placeholder="A short quote from this prefect shown on their card..."><?php echo htmlspecialchars($editPrefect['quote'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Photo (JPG, PNG or WEBP — max 3MB)</label>
            <?php if (!empty($editPrefect['photo'])): ?>
            <div style="margin-bottom:8px">
              <img src="../assets/images/staff/<?php echo htmlspecialchars($editPrefect['photo']); ?>"
                   alt="Current photo" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #e8e6f0"/>
            </div>
            <?php endif; ?>
            <input type="file" class="form-input" name="photo" accept="image/jpeg,image/png,image/webp"/>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_active" name="is_active"
                     <?php echo ($editPrefect ? $editPrefect['is_active'] : 1) ? 'checked' : ''; ?>/>
              <label for="is_active" style="font-size:13.5px">Active — visible on the Students page</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $editPrefect ? 'Save Changes' : 'Add Prefect'; ?></button>
            <a href="prefects-admin.php?session=<?php echo urlencode($filterSession); ?>" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- ── Grid ── -->
      <?php if (empty($prefectList)): ?>
      <div class="empty-state">
        No prefects for <?php echo htmlspecialchars($filterSession); ?>.
        <a href="?add=1&session=<?php echo urlencode($filterSession); ?>" style="color:#4a90d9">Add the first one →</a>
      </div>
      <?php else: ?>
      <div class="prefects-grid">
        <?php foreach ($prefectList as $p): ?>
        <div class="prefect-card">
          <?php if (!empty($p['photo'])): ?>
          <img src="../assets/images/staff/<?php echo htmlspecialchars($p['photo']); ?>"
               alt="<?php echo htmlspecialchars($p['full_name']); ?>"
               class="prefect-card__photo"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="prefect-card__initials" style="display:none">
            <?php echo strtoupper(substr($p['full_name'], 0, 1)); ?>
          </div>
          <?php else: ?>
          <div class="prefect-card__initials">
            <?php echo strtoupper(substr($p['full_name'], 0, 1)); ?>
          </div>
          <?php endif; ?>
          <div class="prefect-card__body">
            <div class="prefect-card__name"><?php echo htmlspecialchars($p['full_name']); ?></div>
            <div class="prefect-card__role"><?php echo htmlspecialchars($p['role']); ?></div>
            <span class="badge badge--<?php echo $p['section']; ?>"><?php echo strtoupper($p['section']); ?></span>
            <span class="badge badge--<?php echo $p['is_active'] ? 'active' : 'inactive'; ?>" style="margin-left:4px">
              <?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
            <div class="prefect-card__actions" style="margin-top:8px">
              <a href="?edit=<?php echo $p['id']; ?>&session=<?php echo urlencode($filterSession); ?>"
                 class="action-btn action-btn--edit">Edit</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_active"/>
                <input type="hidden" name="prefect_id" value="<?php echo $p['id']; ?>"/>
                <button type="submit" class="action-btn action-btn--toggle">
                  <?php echo $p['is_active'] ? 'Hide' : 'Show'; ?>
                </button>
              </form>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($p['full_name'])); ?>?')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="prefect_id" value="<?php echo $p['id']; ?>"/>
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