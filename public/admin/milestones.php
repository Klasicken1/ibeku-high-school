<?php
/* ============================================================
   IBEKU HIGH SCHOOL — MILESTONES MANAGEMENT
   File: public/admin/milestones.php

   Accessible to: superadmin only
   Add, edit, reorder, publish/unpublish, delete history
   timeline milestones shown on the About page.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $id     = (int) ($_POST['milestone_id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $pdo->prepare('DELETE FROM milestones WHERE id = ?')->execute([$id]);
            $message = 'Milestone deleted.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif ($action === 'toggle_publish' && $id > 0) {
        try {
            $pdo->prepare('UPDATE milestones SET is_published = NOT is_published WHERE id = ?')->execute([$id]);
            $message = 'Milestone visibility updated.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif (in_array($action, ['create', 'update'], true)) {
        $eraLabel   = trim($_POST['era_label']   ?? '');
        $title      = trim($_POST['title']       ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $sortOrder  = (int) ($_POST['sort_order'] ?? 0);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        if ($eraLabel === '') {
            $message = 'Era label is required (e.g. 1954, 1960s, 2025).'; $messageType = 'error';
        } elseif ($title === '') {
            $message = 'Title is required.'; $messageType = 'error';
        } elseif ($desc === '') {
            $message = 'Description is required.'; $messageType = 'error';
        } else {
            try {
                if ($action === 'create') {
                    $pdo->prepare(
                        'INSERT INTO milestones (era_label, title, description, sort_order, is_published)
                         VALUES (?,?,?,?,?)'
                    )->execute([$eraLabel, $title, $desc, $sortOrder, $isPublished]);
                    $message = 'Milestone added.'; $messageType = 'success';
                } else {
                    $pdo->prepare(
                        'UPDATE milestones SET era_label=?, title=?, description=?, sort_order=?, is_published=?
                         WHERE id=?'
                    )->execute([$eraLabel, $title, $desc, $sortOrder, $isPublished, $id]);
                    $message = 'Milestone updated.'; $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log('IHS milestones error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

$editMilestone = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM milestones WHERE id = ? LIMIT 1');
    $es->execute([(int) $_GET['edit']]);
    $editMilestone = $es->fetch();
}

$milestones = $pdo->query(
    'SELECT * FROM milestones ORDER BY sort_order ASC, id ASC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Milestones — Admin — Ibeku High School</title>
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

  .milestones-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.milestones-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.milestones-table th { background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
  table.milestones-table td { padding:12px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.milestones-table tr:last-child td { border-bottom:none; }
  table.milestones-table tr:hover td { background:#faf9fd; }

  .era-dot { display:inline-block; background:#3d1a6e; color:#fff; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
  .badge--pub  { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; background:#e6f9ed; color:#1a7a3a; }
  .badge--hide { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; background:#ffe6e6; color:#cc3333; }

  .actions-cell { display:flex; gap:6px; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 11px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit   { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--toggle { background:#fff3e6; color:#8a4a00; }
  .action-btn--delete { background:#ffe6e6; color:#cc3333; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
  .info-note { background:#f0ecfa; border:1px solid #d8d0ee; border-radius:10px; padding:12px 16px; font-size:13px; color:#3d1a6e; margin-bottom:20px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'milestones'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>History Milestones</h2>
          <p>Manage the "Key Milestones in Our History" timeline shown on the About page.</p>
        </div>
        <a href="?<?php echo isset($_GET['add']) || $editMilestone ? '' : 'add=1'; ?>"
           class="btn-new <?php echo isset($_GET['add']) || $editMilestone ? 'cancel' : ''; ?>">
          <?php echo isset($_GET['add']) || $editMilestone ? '✕ Cancel' : '+ Add Milestone'; ?>
        </a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="info-note">
        ℹ️ Milestones are displayed in order of their Sort Order. The first milestone appears on the left side of the timeline, the second on the right, and so on alternating. Lower sort numbers appear first.
      </div>

      <!-- ── Form ── -->
      <?php if (isset($_GET['add']) || $editMilestone): ?>
      <div class="form-panel">
        <div class="form-panel__title"><?php echo $editMilestone ? 'Edit Milestone' : 'Add New Milestone'; ?></div>
        <form method="POST">
          <input type="hidden" name="action" value="<?php echo $editMilestone ? 'update' : 'create'; ?>"/>
          <?php if ($editMilestone): ?>
          <input type="hidden" name="milestone_id" value="<?php echo $editMilestone['id']; ?>"/>
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Era Label *</label>
              <input type="text" class="form-input" name="era_label" required maxlength="20"
                     value="<?php echo htmlspecialchars($editMilestone['era_label'] ?? ''); ?>"
                     placeholder="e.g. 1954, 1960s, 2025"/>
              <p class="char-hint">Short label shown on the timeline dot — a year or decade.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-input" name="sort_order" min="0" max="999"
                     value="<?php echo (int) ($editMilestone['sort_order'] ?? count($milestones)); ?>"/>
              <p class="char-hint">Lower numbers appear first on the timeline.</p>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" class="form-input" name="title" required maxlength="200"
                   value="<?php echo htmlspecialchars($editMilestone['title'] ?? ''); ?>"
                   placeholder="e.g. School Founded"/>
          </div>

          <div class="form-group">
            <label class="form-label">Description *</label>
            <textarea class="form-textarea" name="description" rows="3" required
                      placeholder="A brief description of this milestone..."><?php echo htmlspecialchars($editMilestone['description'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_published" name="is_published"
                     <?php echo ($editMilestone ? $editMilestone['is_published'] : 1) ? 'checked' : ''; ?>/>
              <label for="is_published" style="font-size:13.5px">Published — visible on the About page timeline</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $editMilestone ? 'Save Changes' : 'Add Milestone'; ?></button>
            <a href="milestones.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- ── Table ── -->
      <div class="milestones-table-wrap">
        <?php if (empty($milestones)): ?>
        <div class="empty-state">
          No milestones yet. <a href="?add=1" style="color:#4a90d9">Add the first one →</a>
        </div>
        <?php else: ?>
        <table class="milestones-table">
          <thead>
            <tr>
              <th style="width:60px">Order</th>
              <th style="width:80px">Era</th>
              <th>Title &amp; Description</th>
              <th style="width:90px">Status</th>
              <th style="width:160px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($milestones as $ms): ?>
            <tr>
              <td style="color:#9b97b0;font-size:12px"><?php echo (int) $ms['sort_order']; ?></td>
              <td><span class="era-dot"><?php echo htmlspecialchars($ms['era_label']); ?></span></td>
              <td>
                <div style="font-weight:600;color:#1a1a2e;margin-bottom:3px"><?php echo htmlspecialchars($ms['title']); ?></div>
                <div style="font-size:12px;color:#9b97b0;line-height:1.5"><?php echo htmlspecialchars(mb_substr($ms['description'], 0, 100)) . (mb_strlen($ms['description']) > 100 ? '…' : ''); ?></div>
              </td>
              <td>
                <span class="badge--<?php echo $ms['is_published'] ? 'pub' : 'hide'; ?>">
                  <?php echo $ms['is_published'] ? 'Published' : 'Hidden'; ?>
                </span>
              </td>
              <td>
                <div class="actions-cell">
                  <a href="?edit=<?php echo $ms['id']; ?>" class="action-btn action-btn--edit">Edit</a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle_publish"/>
                    <input type="hidden" name="milestone_id" value="<?php echo $ms['id']; ?>"/>
                    <button type="submit" class="action-btn action-btn--toggle">
                      <?php echo $ms['is_published'] ? 'Hide' : 'Show'; ?>
                    </button>
                  </form>
                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('Delete this milestone?')">
                    <input type="hidden" name="action" value="delete"/>
                    <input type="hidden" name="milestone_id" value="<?php echo $ms['id']; ?>"/>
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