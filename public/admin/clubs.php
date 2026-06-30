<?php
/* ============================================================
   IBEKU HIGH SCHOOL — CLUBS & SOCIETIES MANAGEMENT
   File: public/admin/clubs.php

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
    $id     = (int) ($_POST['club_id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $pdo->prepare('DELETE FROM clubs WHERE id = ?')->execute([$id]);
            $message = 'Club deleted.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif ($action === 'toggle_publish' && $id > 0) {
        try {
            $pdo->prepare('UPDATE clubs SET is_published = NOT is_published WHERE id = ?')->execute([$id]);
            $message = 'Club visibility updated.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif (in_array($action, ['create', 'update'], true)) {
        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon        = trim($_POST['icon']        ?? '🎯');
        $patron      = trim($_POST['patron']      ?? '');
        $sortOrder   = (int) ($_POST['sort_order'] ?? 0);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        if ($name === '') {
            $message = 'Club name is required.'; $messageType = 'error';
        } else {
            try {
                if ($action === 'create') {
                    $pdo->prepare(
                        'INSERT INTO clubs (name, description, icon, patron, sort_order, is_published)
                         VALUES (?,?,?,?,?,?)'
                    )->execute([$name, $description ?: null, $icon, $patron ?: null, $sortOrder, $isPublished]);
                    $message = 'Club added.'; $messageType = 'success';
                } else {
                    $pdo->prepare(
                        'UPDATE clubs SET name=?, description=?, icon=?, patron=?, sort_order=?, is_published=?
                         WHERE id=?'
                    )->execute([$name, $description ?: null, $icon, $patron ?: null, $sortOrder, $isPublished, $id]);
                    $message = 'Club updated.'; $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log('IHS clubs error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

$editClub = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM clubs WHERE id = ? LIMIT 1');
    $es->execute([(int) $_GET['edit']]);
    $editClub = $es->fetch();
}

$clubs = $pdo->query('SELECT * FROM clubs ORDER BY sort_order ASC, name ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Clubs & Societies — Admin — Ibeku High School</title>
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
  .form-input, .form-textarea { width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; }
  .checkbox-row input { width:16px; height:16px; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }
  .btn-group { display:flex; gap:12px; margin-top:20px; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; }
  .clubs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
  .club-card { background:#fff; border:1px solid #e8e6f0; border-radius:12px; padding:18px; }
  .club-card__header { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
  .club-card__icon { font-size:24px; }
  .club-card__name { font-size:14px; font-weight:700; color:#1a1a2e; }
  .club-card__desc { font-size:12.5px; color:#6b6b80; line-height:1.5; margin-bottom:10px; }
  .club-card__patron { font-size:12px; color:#9b97b0; margin-bottom:10px; }
  .badge--pub  { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; background:#e6f9ed; color:#1a7a3a; margin-bottom:10px; }
  .badge--hide { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; background:#ffe6e6; color:#cc3333; margin-bottom:10px; }
  .club-card__actions { display:flex; gap:6px; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 11px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit   { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--toggle { background:#fff3e6; color:#8a4a00; }
  .action-btn--delete { background:#ffe6e6; color:#cc3333; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; background:#fff; border:1px solid #e8e6f0; border-radius:14px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'clubs'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Clubs &amp; Societies</h2>
          <p>Manage clubs shown on the Academics page co-curricular section.</p>
        </div>
        <a href="?<?php echo isset($_GET['add']) || $editClub ? '' : 'add=1'; ?>"
           class="btn-new <?php echo isset($_GET['add']) || $editClub ? 'cancel' : ''; ?>">
          <?php echo isset($_GET['add']) || $editClub ? '✕ Cancel' : '+ Add Club'; ?>
        </a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- ── Form ── -->
      <?php if (isset($_GET['add']) || $editClub): ?>
      <div class="form-panel">
        <div class="form-panel__title"><?php echo $editClub ? 'Edit Club' : 'Add New Club'; ?></div>
        <form method="POST">
          <input type="hidden" name="action" value="<?php echo $editClub ? 'update' : 'create'; ?>"/>
          <?php if ($editClub): ?>
          <input type="hidden" name="club_id" value="<?php echo $editClub['id']; ?>"/>
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Club Name *</label>
              <input type="text" class="form-input" name="name" required maxlength="150"
                     value="<?php echo htmlspecialchars($editClub['name'] ?? ''); ?>"
                     placeholder="e.g. Science Club"/>
            </div>
            <div class="form-group">
              <label class="form-label">Icon (emoji)</label>
              <input type="text" class="form-input" name="icon" maxlength="10"
                     value="<?php echo htmlspecialchars($editClub['icon'] ?? '🎯'); ?>"
                     placeholder="e.g. 🔬"/>
              <p class="char-hint">Paste a single emoji character.</p>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" name="description" rows="3"
                      placeholder="Brief description of the club's activities..."><?php echo htmlspecialchars($editClub['description'] ?? ''); ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Patron / Teacher in Charge</label>
              <input type="text" class="form-input" name="patron" maxlength="150"
                     value="<?php echo htmlspecialchars($editClub['patron'] ?? ''); ?>"
                     placeholder="e.g. Mr. Okafor"/>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-input" name="sort_order" min="0" max="999"
                     value="<?php echo (int) ($editClub['sort_order'] ?? count($clubs)); ?>"/>
              <p class="char-hint">Lower numbers appear first.</p>
            </div>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_published" name="is_published"
                     <?php echo ($editClub ? $editClub['is_published'] : 1) ? 'checked' : ''; ?>/>
              <label for="is_published" style="font-size:13.5px">Published — visible on the Academics page</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $editClub ? 'Save Changes' : 'Add Club'; ?></button>
            <a href="clubs.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- ── Grid ── -->
      <?php if (empty($clubs)): ?>
      <div class="empty-state">
        No clubs yet. <a href="?add=1" style="color:#4a90d9">Add the first one →</a>
      </div>
      <?php else: ?>
      <div class="clubs-grid">
        <?php foreach ($clubs as $club): ?>
        <div class="club-card">
          <div class="club-card__header">
            <span class="club-card__icon"><?php echo htmlspecialchars($club['icon'] ?? '🎯'); ?></span>
            <span class="club-card__name"><?php echo htmlspecialchars($club['name']); ?></span>
          </div>
          <?php if ($club['description']): ?>
          <div class="club-card__desc"><?php echo htmlspecialchars(mb_substr($club['description'], 0, 100)) . (mb_strlen($club['description']) > 100 ? '…' : ''); ?></div>
          <?php endif; ?>
          <?php if ($club['patron']): ?>
          <div class="club-card__patron">👤 <?php echo htmlspecialchars($club['patron']); ?></div>
          <?php endif; ?>
          <span class="badge--<?php echo $club['is_published'] ? 'pub' : 'hide'; ?>">
            <?php echo $club['is_published'] ? 'Published' : 'Hidden'; ?>
          </span>
          <div class="club-card__actions">
            <a href="?edit=<?php echo $club['id']; ?>" class="action-btn action-btn--edit">Edit</a>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle_publish"/>
              <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>"/>
              <button type="submit" class="action-btn action-btn--toggle">
                <?php echo $club['is_published'] ? 'Hide' : 'Show'; ?>
              </button>
            </form>
            <form method="POST" style="display:inline"
                  onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($club['name'])); ?>?')">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>"/>
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