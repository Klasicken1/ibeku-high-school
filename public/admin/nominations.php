<?php
/* ============================================================
   IBEKU HIGH SCHOOL — HALL OF FAME NOMINATIONS INBOX
   File: public/admin/nominations.php

   Accessible to: superadmin only
   Review nominations submitted via the public hall-of-fame.php
   nomination form. Convert approved nominations into Hall of
   Fame entries directly from this page.
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
    $action = trim($_POST['action']       ?? '');
    $id     = (int) ($_POST['nomination_id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $pdo->prepare('DELETE FROM hall_of_fame_nominations WHERE id = ?')->execute([$id]);
            $message = 'Nomination deleted.'; $messageType = 'success';
        } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }

    } elseif ($action === 'update_status' && $id > 0) {
        $newStatus = trim($_POST['new_status'] ?? '');
        $notes     = trim($_POST['notes']      ?? '');
        $validStatuses = ['new','reviewed','converted','declined'];
        if (!in_array($newStatus, $validStatuses, true)) {
            $message = 'Invalid status.'; $messageType = 'error';
        } else {
            try {
                $pdo->prepare(
                    'UPDATE hall_of_fame_nominations SET status=?, notes=? WHERE id=?'
                )->execute([$newStatus, $notes ?: null, $id]);
                $message = 'Nomination updated.'; $messageType = 'success';
            } catch (PDOException $e) { $message = 'A server error occurred.'; $messageType = 'error'; }
        }

    } elseif ($action === 'convert' && $id > 0) {
        /* ── Convert nomination directly into a Hall of Fame entry ── */
        $nomStmt = $pdo->prepare('SELECT * FROM hall_of_fame_nominations WHERE id = ? LIMIT 1');
        $nomStmt->execute([$id]);
        $nom = $nomStmt->fetch();

        if (!$nom) {
            $message = 'Nomination not found.'; $messageType = 'error';
        } else {
            try {
                $pdo->prepare(
                    'INSERT INTO hall_of_fame
                        (full_name, category, class_year, achievement, nominated_by, is_published)
                     VALUES (?,?,?,?,?,0)'
                )->execute([
                    $nom['nominee_name'],
                    $nom['category'] ?: 'alumni',
                    $nom['nominee_class_year'],
                    $nom['reason'],
                    $nom['nominator_name'],
                ]);
                $pdo->prepare(
                    'UPDATE hall_of_fame_nominations SET status = ? WHERE id = ?'
                )->execute(['converted', $id]);
                $message = 'Nomination converted to Hall of Fame entry (unpublished). Review and publish it from Hall of Fame management.';
                $messageType = 'success';
            } catch (PDOException $e) {
                error_log('IHS nominations convert error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

/* ── Status config ── */
$statuses = [
    'new'       => ['label' => 'New',       'bg' => '#e6f0ff', 'color' => '#1a5a9a'],
    'reviewed'  => ['label' => 'Reviewed',  'bg' => '#f0ecfa', 'color' => '#3d1a6e'],
    'converted' => ['label' => 'Converted', 'bg' => '#e6f9ed', 'color' => '#1a7a3a'],
    'declined'  => ['label' => 'Declined',  'bg' => '#ffe6e6', 'color' => '#cc3333'],
];

$filterStatus = $_GET['status'] ?? '';
$where  = ['1=1'];
$params = [];
if ($filterStatus) { $where[] = 'status = ?'; $params[] = $filterStatus; }
$whereSQL = implode(' AND ', $where);

$nominations = $pdo->prepare(
    "SELECT * FROM hall_of_fame_nominations WHERE $whereSQL ORDER BY created_at DESC"
);
$nominations->execute($params);
$nomList = $nominations->fetchAll();

/* ── Stats ── */
$statCounts = [];
foreach (array_keys($statuses) as $st) {
    $sc = $pdo->prepare('SELECT COUNT(*) FROM hall_of_fame_nominations WHERE status = ?');
    $sc->execute([$st]);
    $statCounts[$st] = (int) $sc->fetchColumn();
}

/* ── View single nomination ── */
$viewId  = (int) ($_GET['view'] ?? 0);
$viewNom = null;
if ($viewId > 0) {
    $vs = $pdo->prepare('SELECT * FROM hall_of_fame_nominations WHERE id = ? LIMIT 1');
    $vs->execute([$viewId]);
    $viewNom = $vs->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Hall of Fame Nominations — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .stats-row { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
  .stat-pill { background:#fff; border:1px solid #e8e6f0; border-radius:10px; padding:10px 16px; font-size:12.5px; cursor:pointer; text-decoration:none; display:block; }
  .stat-pill strong { display:block; font-size:17px; }
  .stat-pill--active { border-color:#3d1a6e; background:#f0ecfa; }
  .filter-tabs { display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap; }
  .filter-tab { padding:7px 16px; border-radius:20px; font-size:12.5px; font-weight:600; text-decoration:none; color:#6b6b80; background:#fff; border:1px solid #e8e6f0; }
  .filter-tab--active { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }

  .nom-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.nom-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.nom-table th { background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
  table.nom-table td { padding:11px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.nom-table tr:last-child td { border-bottom:none; }
  table.nom-table tr:hover td { background:#faf9fd; }

  .status-badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 10px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--view    { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--convert { background:#e6f9ed; color:#1a7a3a; }
  .action-btn--delete  { background:#ffe6e6; color:#cc3333; }
  .actions-cell { display:flex; gap:5px; }

  .detail-panel { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:24px; }
  .detail-panel h3 { font-size:15px; color:#3d1a6e; margin-bottom:16px; }
  .detail-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; margin-bottom:20px; }
  .detail-item__label { font-size:11px; font-weight:700; color:#9b97b0; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
  .detail-item__value { font-size:13.5px; color:#1a1a2e; }
  .reason-box { background:#faf9fd; border:1px solid #f0eef6; border-radius:8px; padding:12px 14px; font-size:13px; color:#1a1a2e; line-height:1.6; margin-bottom:20px; }
  .update-form { border-top:1px solid #f0eef6; padding-top:18px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
  .update-form label { display:block; font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; margin-bottom:5px; }
  .update-form select, .update-form textarea { padding:8px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; }
  .update-form textarea { min-width:240px; resize:vertical; }
  .btn-update  { background:#3d1a6e; color:#fff; border:none; padding:9px 22px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
  .btn-convert { background:#1a7a3a; color:#fff; border:none; padding:9px 22px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'nominations'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Hall of Fame Nominations</h2>
        <p>
          Review nominations submitted via the public website.
          <a href="hall-of-fame-admin.php" style="color:#4a90d9;margin-left:8px">← Back to Hall of Fame Management</a>
        </p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Status stats -->
      <div class="stats-row">
        <?php foreach ($statuses as $key => $cfg): ?>
        <a href="?status=<?php echo $key; ?>"
           class="stat-pill <?php echo $filterStatus === $key ? 'stat-pill--active' : ''; ?>"
           style="border-left:3px solid <?php echo $cfg['color']; ?>">
          <strong style="color:<?php echo $cfg['color']; ?>"><?php echo $statCounts[$key] ?? 0; ?></strong>
          <?php echo $cfg['label']; ?>
        </a>
        <?php endforeach; ?>
        <a href="nominations.php" class="stat-pill <?php echo !$filterStatus ? 'stat-pill--active' : ''; ?>">
          <strong><?php echo array_sum($statCounts); ?></strong>All
        </a>
      </div>

      <!-- Detail view -->
      <?php if ($viewNom):
        $st = $statuses[$viewNom['status']] ?? $statuses['new'];
      ?>
      <div class="detail-panel">
        <h3>
          Nomination — <?php echo htmlspecialchars($viewNom['nominee_name']); ?>
          <span class="status-badge" style="background:<?php echo $st['bg']; ?>;color:<?php echo $st['color']; ?>;margin-left:8px">
            <?php echo $st['label']; ?>
          </span>
          <a href="nominations.php" style="font-size:12.5px;color:#9b97b0;text-decoration:none;margin-left:12px;font-weight:400">✕ Close</a>
        </h3>

        <div class="detail-grid">
          <div><div class="detail-item__label">Nominee</div><div class="detail-item__value"><?php echo htmlspecialchars($viewNom['nominee_name']); ?></div></div>
          <div><div class="detail-item__label">Class Year</div><div class="detail-item__value"><?php echo htmlspecialchars($viewNom['nominee_class_year'] ?: '—'); ?></div></div>
          <div><div class="detail-item__label">Category</div><div class="detail-item__value"><?php echo htmlspecialchars($viewNom['category'] ?: '—'); ?></div></div>
          <div><div class="detail-item__label">Submitted By</div><div class="detail-item__value"><?php echo htmlspecialchars($viewNom['nominator_name']); ?></div></div>
          <div><div class="detail-item__label">Nominator Email</div><div class="detail-item__value"><?php echo htmlspecialchars($viewNom['nominator_email']); ?></div></div>
          <div><div class="detail-item__label">Submitted</div><div class="detail-item__value"><?php echo date('d M Y, g:ia', strtotime($viewNom['created_at'])); ?></div></div>
        </div>

        <div class="detail-item__label" style="margin-bottom:6px">Reason for Nomination</div>
        <div class="reason-box"><?php echo nl2br(htmlspecialchars($viewNom['reason'])); ?></div>

        <?php if ($viewNom['notes']): ?>
        <div class="detail-item__label" style="margin-bottom:6px">Internal Notes</div>
        <div class="reason-box" style="background:#fffbe6;border-color:#ffe9a0"><?php echo nl2br(htmlspecialchars($viewNom['notes'])); ?></div>
        <?php endif; ?>

        <form method="POST" class="update-form">
          <input type="hidden" name="action" value="update_status"/>
          <input type="hidden" name="nomination_id" value="<?php echo $viewNom['id']; ?>"/>
          <div>
            <label>Update Status</label>
            <select name="new_status">
              <?php foreach ($statuses as $key => $cfg): ?>
              <option value="<?php echo $key; ?>" <?php echo $viewNom['status'] === $key ? 'selected' : ''; ?>><?php echo $cfg['label']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>Notes (internal)</label>
            <textarea name="notes" rows="2" placeholder="e.g. Verified with school records..."><?php echo htmlspecialchars($viewNom['notes'] ?? ''); ?></textarea>
          </div>
          <button type="submit" class="btn-update">Save</button>
        </form>

        <?php if ($viewNom['status'] !== 'converted' && $viewNom['status'] !== 'declined'): ?>
        <form method="POST" style="margin-top:10px"
              onsubmit="return confirm('Convert this nomination into an unpublished Hall of Fame entry? You can review and publish it from Hall of Fame management.')">
          <input type="hidden" name="action" value="convert"/>
          <input type="hidden" name="nomination_id" value="<?php echo $viewNom['id']; ?>"/>
          <button type="submit" class="btn-convert">🏆 Convert to Hall of Fame Entry</button>
          <span style="font-size:12px;color:#9b97b0;margin-left:8px">Creates an unpublished entry you can review before publishing.</span>
        </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Table -->
      <div class="nom-table-wrap">
        <?php if (empty($nomList)): ?>
        <div class="empty-state">No nominations found.</div>
        <?php else: ?>
        <table class="nom-table">
          <thead>
            <tr>
              <th>Nominee</th>
              <th>Category</th>
              <th>Submitted By</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($nomList as $nom):
              $st = $statuses[$nom['status']] ?? $statuses['new'];
            ?>
            <tr>
              <td style="font-weight:600;color:#1a1a2e"><?php echo htmlspecialchars($nom['nominee_name']); ?></td>
              <td style="font-size:12px;color:#6b6b80"><?php echo htmlspecialchars($nom['category'] ?: '—'); ?></td>
              <td>
                <div style="font-size:13px"><?php echo htmlspecialchars($nom['nominator_name']); ?></div>
                <div style="font-size:11.5px;color:#9b97b0"><?php echo htmlspecialchars($nom['nominator_email']); ?></div>
              </td>
              <td>
                <span class="status-badge" style="background:<?php echo $st['bg']; ?>;color:<?php echo $st['color']; ?>">
                  <?php echo $st['label']; ?>
                </span>
              </td>
              <td style="font-size:12px;color:#9b97b0"><?php echo date('d M Y', strtotime($nom['created_at'])); ?></td>
              <td>
                <div class="actions-cell">
                  <a href="?view=<?php echo $nom['id']; ?><?php echo $filterStatus ? '&status=' . urlencode($filterStatus) : ''; ?>"
                     class="action-btn action-btn--view">View</a>
                  <?php if ($nom['status'] !== 'converted' && $nom['status'] !== 'declined'): ?>
                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('Convert to Hall of Fame entry?')">
                    <input type="hidden" name="action" value="convert"/>
                    <input type="hidden" name="nomination_id" value="<?php echo $nom['id']; ?>"/>
                    <button type="submit" class="action-btn action-btn--convert">Convert</button>
                  </form>
                  <?php endif; ?>
                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('Delete this nomination permanently?')">
                    <input type="hidden" name="action" value="delete"/>
                    <input type="hidden" name="nomination_id" value="<?php echo $nom['id']; ?>"/>
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