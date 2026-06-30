<?php
/* ============================================================
   IBEKU HIGH SCHOOL — COMPETITIONS & AWARDS MANAGEMENT
   File: public/admin/awards.php

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
    $id     = (int) ($_POST['award_id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $pdo->prepare('DELETE FROM awards WHERE id = ?')->execute([$id]);
            $message = 'Award deleted.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif ($action === 'toggle_publish' && $id > 0) {
        try {
            $pdo->prepare('UPDATE awards SET is_published = NOT is_published WHERE id = ?')->execute([$id]);
            $message = 'Award visibility updated.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif (in_array($action, ['create', 'update'], true)) {
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $yearLabel   = trim($_POST['year_label']  ?? '');
        $icon        = trim($_POST['icon']        ?? '🏆');
        $badgeText   = trim($_POST['badge_text']  ?? '');
        $sortOrder   = (int) ($_POST['sort_order'] ?? 0);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        if ($title === '') {
            $message = 'Title is required.'; $messageType = 'error';
        } else {
            try {
                if ($action === 'create') {
                    $pdo->prepare(
                        'INSERT INTO awards (title, description, year_label, icon, badge_text, sort_order, is_published)
                         VALUES (?,?,?,?,?,?,?)'
                    )->execute([$title, $description ?: null, $yearLabel ?: null, $icon, $badgeText ?: null, $sortOrder, $isPublished]);
                    $message = 'Award added.'; $messageType = 'success';
                } else {
                    $pdo->prepare(
                        'UPDATE awards SET title=?, description=?, year_label=?, icon=?, badge_text=?, sort_order=?, is_published=?
                         WHERE id=?'
                    )->execute([$title, $description ?: null, $yearLabel ?: null, $icon, $badgeText ?: null, $sortOrder, $isPublished, $id]);
                    $message = 'Award updated.'; $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log('IHS awards error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

$editAward = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM awards WHERE id = ? LIMIT 1');
    $es->execute([(int) $_GET['edit']]);
    $editAward = $es->fetch();
}

$awards = $pdo->query('SELECT * FROM awards ORDER BY sort_order ASC, id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Competitions & Awards — Admin — Ibeku High School</title>
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
  .form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; }
  .checkbox-row input { width:16px; height:16px; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }
  .btn-group { display:flex; gap:12px; margin-top:20px; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; }

  .awards-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.awards-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.awards-table th { background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
  table.awards-table td { padding:12px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.awards-table tr:last-child td { border-bottom:none; }
  table.awards-table tr:hover td { background:#faf9fd; }

  .award-icon { font-size:22px; }
  .award-title { font-weight:600; color:#1a1a2e; margin-bottom:3px; }
  .award-year  { font-size:12px; color:#9b97b0; }
  .badge-pill  { display:inline-block; background:#f0ecfa; color:#3d1a6e; font-size:11px; font-weight:700; padding:2px 9px; border-radius:20px; }
  .badge--pub  { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; background:#e6f9ed; color:#1a7a3a; }
  .badge--hide { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; background:#ffe6e6; color:#cc3333; }
  .actions-cell { display:flex; gap:6px; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 11px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit   { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--toggle { background:#fff3e6; color:#8a4a00; }
  .action-btn--delete { background:#ffe6e6; color:#cc3333; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'awards'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Competitions &amp; Awards</h2>
          <p>Manage the achievements and awards shown on the Academics page.</p>
        </div>
        <a href="?<?php echo isset($_GET['add']) || $editAward ? '' : 'add=1'; ?>"
           class="btn-new <?php echo isset($_GET['add']) || $editAward ? 'cancel' : ''; ?>">
          <?php echo isset($_GET['add']) || $editAward ? '✕ Cancel' : '+ Add Award'; ?>
        </a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- ── Form ── -->
      <?php if (isset($_GET['add']) || $editAward): ?>
      <div class="form-panel">
        <div class="form-panel__title"><?php echo $editAward ? 'Edit Award' : 'Add New Award'; ?></div>
        <form method="POST">
          <input type="hidden" name="action" value="<?php echo $editAward ? 'update' : 'create'; ?>"/>
          <?php if ($editAward): ?>
          <input type="hidden" name="award_id" value="<?php echo $editAward['id']; ?>"/>
          <?php endif; ?>

          <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" class="form-input" name="title" required maxlength="200"
                   value="<?php echo htmlspecialchars($editAward['title'] ?? ''); ?>"
                   placeholder="e.g. Abia State Science Quiz Championship"/>
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" name="description" rows="3"
                      placeholder="Details about this competition or award..."><?php echo htmlspecialchars($editAward['description'] ?? ''); ?></textarea>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label class="form-label">Year / Period Label</label>
              <input type="text" class="form-input" name="year_label" maxlength="50"
                     value="<?php echo htmlspecialchars($editAward['year_label'] ?? ''); ?>"
                     placeholder="e.g. 2022 · 2023 · 2024"/>
            </div>
            <div class="form-group">
              <label class="form-label">Icon (emoji)</label>
              <input type="text" class="form-input" name="icon" maxlength="10"
                     value="<?php echo htmlspecialchars($editAward['icon'] ?? '🏆'); ?>"/>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-input" name="sort_order" min="0" max="999"
                     value="<?php echo (int) ($editAward['sort_order'] ?? count($awards)); ?>"/>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Badge Text</label>
            <input type="text" class="form-input" name="badge_text" maxlength="100"
                   value="<?php echo htmlspecialchars($editAward['badge_text'] ?? ''); ?>"
                   placeholder="e.g. 🥇 3× State Champions"/>
            <p class="char-hint">Short label displayed as a coloured badge on the award card.</p>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_published" name="is_published"
                     <?php echo ($editAward ? $editAward['is_published'] : 1) ? 'checked' : ''; ?>/>
              <label for="is_published" style="font-size:13.5px">Published — visible on the Academics page</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $editAward ? 'Save Changes' : 'Add Award'; ?></button>
            <a href="awards.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- ── Table ── -->
      <div class="awards-table-wrap">
        <?php if (empty($awards)): ?>
        <div class="empty-state">
          No awards yet. <a href="?add=1" style="color:#4a90d9">Add the first one →</a>
        </div>
        <?php else: ?>
        <table class="awards-table">
          <thead>
            <tr>
              <th style="width:40px"></th>
              <th>Title &amp; Description</th>
              <th>Year / Period</th>
              <th>Badge</th>
              <th style="width:90px">Status</th>
              <th style="width:160px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($awards as $award): ?>
            <tr>
              <td class="award-icon"><?php echo htmlspecialchars($award['icon'] ?? '🏆'); ?></td>
              <td>
                <div class="award-title"><?php echo htmlspecialchars($award['title']); ?></div>
                <?php if ($award['description']): ?>
                <div class="award-year"><?php echo htmlspecialchars(mb_substr($award['description'], 0, 80)) . (mb_strlen($award['description']) > 80 ? '…' : ''); ?></div>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:#6b6b80"><?php echo htmlspecialchars($award['year_label'] ?? '—'); ?></td>
              <td>
                <?php if ($award['badge_text']): ?>
                <span class="badge-pill"><?php echo htmlspecialchars($award['badge_text']); ?></span>
                <?php else: ?>
                <span style="color:#c8c4dc;font-size:12px">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge--<?php echo $award['is_published'] ? 'pub' : 'hide'; ?>">
                  <?php echo $award['is_published'] ? 'Published' : 'Hidden'; ?>
                </span>
              </td>
              <td>
                <div class="actions-cell">
                  <a href="?edit=<?php echo $award['id']; ?>" class="action-btn action-btn--edit">Edit</a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle_publish"/>
                    <input type="hidden" name="award_id" value="<?php echo $award['id']; ?>"/>
                    <button type="submit" class="action-btn action-btn--toggle">
                      <?php echo $award['is_published'] ? 'Hide' : 'Show'; ?>
                    </button>
                  </form>
                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('Delete this award?')">
                    <input type="hidden" name="action" value="delete"/>
                    <input type="hidden" name="award_id" value="<?php echo $award['id']; ?>"/>
                    <button type="submit" class="action-btn action-btn--delete">Delete</button>
                  </form>
                </div>
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
</body>
</html>