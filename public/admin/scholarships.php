<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SCHOLARSHIPS MANAGEMENT
   File: public/admin/scholarships.php

   Accessible to: superadmin only
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
    $id     = (int) ($_POST['scholarship_id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $pdo->prepare('DELETE FROM scholarships WHERE id = ?')->execute([$id]);
            $message = 'Scholarship deleted.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif ($action === 'toggle_publish' && $id > 0) {
        try {
            $pdo->prepare('UPDATE scholarships SET is_published = NOT is_published WHERE id = ?')->execute([$id]);
            $message = 'Scholarship visibility updated.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif (in_array($action, ['create', 'update'], true)) {
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $eligibility = trim($_POST['eligibility'] ?? '');
        $contact     = trim($_POST['contact_info']?? '');
        $icon        = trim($_POST['icon']        ?? '🎓');
        $colorTheme  = trim($_POST['color_theme'] ?? 'purple');
        $sortOrder   = (int) ($_POST['sort_order']  ?? 0);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        $validThemes = ['purple', 'blue', 'gold'];
        if (!in_array($colorTheme, $validThemes, true)) $colorTheme = 'purple';

        if ($title === '') {
            $message = 'Title is required.'; $messageType = 'error';
        } elseif ($description === '') {
            $message = 'Description is required.'; $messageType = 'error';
        } else {
            try {
                if ($action === 'create') {
                    $pdo->prepare(
                        'INSERT INTO scholarships
                            (title, description, eligibility, contact_info, icon, color_theme, sort_order, is_published)
                         VALUES (?,?,?,?,?,?,?,?)'
                    )->execute([
                        $title, $description, $eligibility ?: null, $contact ?: null,
                        $icon, $colorTheme, $sortOrder, $isPublished,
                    ]);
                    $message = 'Scholarship added.'; $messageType = 'success';
                } else {
                    $pdo->prepare(
                        'UPDATE scholarships SET title=?, description=?, eligibility=?,
                         contact_info=?, icon=?, color_theme=?, sort_order=?, is_published=?
                         WHERE id=?'
                    )->execute([
                        $title, $description, $eligibility ?: null, $contact ?: null,
                        $icon, $colorTheme, $sortOrder, $isPublished, $id,
                    ]);
                    $message = 'Scholarship updated.'; $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log('IHS scholarships error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

$editScholarship = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM scholarships WHERE id = ? LIMIT 1');
    $es->execute([(int) $_GET['edit']]);
    $editScholarship = $es->fetch();
}

$scholarships = $pdo->query(
    'SELECT * FROM scholarships ORDER BY sort_order ASC, id ASC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Scholarships — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
  .btn-new { background:#3d1a6e; color:#fff; text-decoration:none; padding:10px 20px; border-radius:8px; font-size:13.5px; font-weight:700; }
  .btn-new:hover { background:#5a2d9e; }
  .btn-new.cancel { background:#cc3333; }
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
  .schol-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
  .schol-card { background:#fff; border:1px solid #e8e6f0; border-radius:12px; padding:18px; }
  .schol-card__header { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
  .schol-card__icon { font-size:22px; }
  .schol-card__title { font-size:14px; font-weight:700; color:#1a1a2e; }
  .schol-card__desc { font-size:12.5px; color:#6b6b80; line-height:1.5; margin-bottom:8px; }
  .schol-card__contact { font-size:12px; color:#9b97b0; margin-bottom:8px; }
  .theme-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:5px; }
  .theme-dot--purple { background:#3d1a6e; }
  .theme-dot--blue   { background:#4a90d9; }
  .theme-dot--gold   { background:#e8a020; }
  .badge--pub  { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; background:#e6f9ed; color:#1a7a3a; margin-bottom:10px; }
  .badge--hide { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; background:#ffe6e6; color:#cc3333; margin-bottom:10px; }
  .schol-card__actions { display:flex; gap:6px; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 11px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit   { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--toggle { background:#fff3e6; color:#8a4a00; }
  .action-btn--delete { background:#ffe6e6; color:#cc3333; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; background:#fff; border:1px solid #e8e6f0; border-radius:14px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'scholarships'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Scholarships &amp; Bursaries</h2>
          <p>Manage scholarship listings shown on the Students page.</p>
        </div>
        <a href="?<?php echo isset($_GET['add']) || $editScholarship ? '' : 'add=1'; ?>"
           class="btn-new <?php echo isset($_GET['add']) || $editScholarship ? 'cancel' : ''; ?>">
          <?php echo isset($_GET['add']) || $editScholarship ? '✕ Cancel' : '+ Add Scholarship'; ?>
        </a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- ── Form ── -->
      <?php if (isset($_GET['add']) || $editScholarship): ?>
      <div class="form-panel">
        <div class="form-panel__title"><?php echo $editScholarship ? 'Edit Scholarship' : 'Add New Scholarship'; ?></div>
        <form method="POST">
          <input type="hidden" name="action" value="<?php echo $editScholarship ? 'update' : 'create'; ?>"/>
          <?php if ($editScholarship): ?>
          <input type="hidden" name="scholarship_id" value="<?php echo $editScholarship['id']; ?>"/>
          <?php endif; ?>

          <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" class="form-input" name="title" required maxlength="200"
                   value="<?php echo htmlspecialchars($editScholarship['title'] ?? ''); ?>"
                   placeholder="e.g. OSA Academic Excellence Award"/>
          </div>

          <div class="form-group">
            <label class="form-label">Description *</label>
            <textarea class="form-textarea" name="description" rows="3" required
                      placeholder="Description of the scholarship..."><?php echo htmlspecialchars($editScholarship['description'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Eligibility</label>
            <textarea class="form-textarea" name="eligibility" rows="2"
                      placeholder="Who can apply..."><?php echo htmlspecialchars($editScholarship['eligibility'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Contact Information</label>
            <input type="text" class="form-input" name="contact_info" maxlength="200"
                   value="<?php echo htmlspecialchars($editScholarship['contact_info'] ?? ''); ?>"
                   placeholder="e.g. Contact: OSA Secretary via school office"/>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label class="form-label">Icon (emoji)</label>
              <input type="text" class="form-input" name="icon" maxlength="10"
                     value="<?php echo htmlspecialchars($editScholarship['icon'] ?? '🎓'); ?>"/>
            </div>
            <div class="form-group">
              <label class="form-label">Colour Theme</label>
              <select class="form-select" name="color_theme">
                <option value="purple" <?php echo ($editScholarship['color_theme'] ?? 'purple') === 'purple' ? 'selected' : ''; ?>>Purple</option>
                <option value="blue"   <?php echo ($editScholarship['color_theme'] ?? '') === 'blue'   ? 'selected' : ''; ?>>Blue</option>
                <option value="gold"   <?php echo ($editScholarship['color_theme'] ?? '') === 'gold'   ? 'selected' : ''; ?>>Gold</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-input" name="sort_order" min="0"
                     value="<?php echo (int) ($editScholarship['sort_order'] ?? count($scholarships)); ?>"/>
            </div>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_published" name="is_published"
                     <?php echo ($editScholarship ? $editScholarship['is_published'] : 1) ? 'checked' : ''; ?>/>
              <label for="is_published" style="font-size:13.5px">Published — visible on the Students page</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $editScholarship ? 'Save Changes' : 'Add Scholarship'; ?></button>
            <a href="scholarships.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- ── Grid ── -->
      <?php if (empty($scholarships)): ?>
      <div class="empty-state">No scholarships yet. <a href="?add=1" style="color:#4a90d9">Add the first one →</a></div>
      <?php else: ?>
      <div class="schol-grid">
        <?php foreach ($scholarships as $sch): ?>
        <div class="schol-card">
          <div class="schol-card__header">
            <span class="schol-card__icon"><?php echo htmlspecialchars($sch['icon'] ?? '🎓'); ?></span>
            <span class="schol-card__title"><?php echo htmlspecialchars($sch['title']); ?></span>
          </div>
          <div class="schol-card__desc">
            <?php echo htmlspecialchars(mb_substr($sch['description'], 0, 90)) . (mb_strlen($sch['description']) > 90 ? '…' : ''); ?>
          </div>
          <?php if ($sch['contact_info']): ?>
          <div class="schol-card__contact"><?php echo htmlspecialchars($sch['contact_info']); ?></div>
          <?php endif; ?>
          <div style="margin-bottom:8px;font-size:12px">
            <span class="theme-dot theme-dot--<?php echo htmlspecialchars($sch['color_theme']); ?>"></span>
            <?php echo ucfirst(htmlspecialchars($sch['color_theme'])); ?> theme
          </div>
          <span class="badge--<?php echo $sch['is_published'] ? 'pub' : 'hide'; ?>">
            <?php echo $sch['is_published'] ? 'Published' : 'Hidden'; ?>
          </span>
          <div class="schol-card__actions">
            <a href="?edit=<?php echo $sch['id']; ?>" class="action-btn action-btn--edit">Edit</a>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle_publish"/>
              <input type="hidden" name="scholarship_id" value="<?php echo $sch['id']; ?>"/>
              <button type="submit" class="action-btn action-btn--toggle">
                <?php echo $sch['is_published'] ? 'Hide' : 'Show'; ?>
              </button>
            </form>
            <form method="POST" style="display:inline"
                  onsubmit="return confirm('Delete this scholarship?')">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="scholarship_id" value="<?php echo $sch['id']; ?>"/>
              <button type="submit" class="action-btn action-btn--delete">Delete</button>
            </form>
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