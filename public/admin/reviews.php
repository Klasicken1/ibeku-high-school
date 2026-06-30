<?php
/* ============================================================
   IBEKU HIGH SCHOOL — REVIEWS MANAGEMENT
   File: public/admin/reviews.php

   Accessible to: superadmin, principal, vp_general
   Approve or reject visitor reviews submitted via the public
   website. Approved reviews appear on the homepage.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_general']);

$admin = currentAdmin();
$pdo   = getDB();

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = trim($_POST['action']    ?? '');
    $reviewId = (int) ($_POST['review_id'] ?? 0);

    if ($reviewId > 0) {
        try {
            if ($action === 'approve') {
                $pdo->prepare(
                    'UPDATE reviews SET status=\'approved\', reviewed_by=?, reviewed_at=NOW() WHERE id=?'
                )->execute([$admin['id'], $reviewId]);
                $message = 'Review approved — it will appear on the homepage.'; $messageType = 'success';

            } elseif ($action === 'reject') {
                $pdo->prepare(
                    'UPDATE reviews SET status=\'rejected\', reviewed_by=?, reviewed_at=NOW() WHERE id=?'
                )->execute([$admin['id'], $reviewId]);
                $message = 'Review rejected.'; $messageType = 'success';

            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM reviews WHERE id=?')->execute([$reviewId]);
                $message = 'Review deleted.'; $messageType = 'success';

            } elseif ($action === 'pending') {
                $pdo->prepare(
                    'UPDATE reviews SET status=\'pending\', reviewed_by=NULL, reviewed_at=NULL WHERE id=?'
                )->execute([$reviewId]);
                $message = 'Review moved back to pending.'; $messageType = 'success';
            }
        } catch (PDOException $e) {
            error_log('IHS reviews error: ' . $e->getMessage());
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}

/* ── Stats ── */
$statuses = ['pending', 'approved', 'rejected'];
$statCounts = [];
foreach ($statuses as $st) {
    $sc = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE status = ? AND is_verified = 1');
    $sc->execute([$st]);
    $statCounts[$st] = (int) $sc->fetchColumn();
}
$unverifiedCount = (int) $pdo->query('SELECT COUNT(*) FROM reviews WHERE is_verified = 0')->fetchColumn();

$filterStatus = $_GET['status'] ?? 'pending';
$where  = ['is_verified = 1'];
$params = [];
if ($filterStatus && in_array($filterStatus, $statuses, true)) {
    $where[]  = 'status = ?';
    $params[] = $filterStatus;
}
$whereSQL = implode(' AND ', $where);

$reviews = $pdo->prepare(
    "SELECT r.*, u.full_name AS reviewed_by_name
     FROM reviews r
     LEFT JOIN users u ON u.id = r.reviewed_by
     WHERE $whereSQL
     ORDER BY r.created_at DESC"
);
$reviews->execute($params);
$reviewList = $reviews->fetchAll();

$relationshipLabels = [
    'parent'  => 'Parent / Guardian',
    'student' => 'Current Student',
    'alumnus' => 'Alumni',
    'staff'   => 'Staff Member',
    'visitor' => 'Visitor',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Reviews — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .stats-row { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
  .stat-pill { background:#fff; border:1px solid #e8e6f0; border-radius:10px; padding:10px 16px; font-size:12.5px; text-decoration:none; display:block; }
  .stat-pill strong { display:block; font-size:17px; color:#3d1a6e; }
  .stat-pill--active { border-color:#3d1a6e; background:#f0ecfa; }
  .filter-tabs { display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap; }
  .filter-tab { padding:7px 16px; border-radius:20px; font-size:12.5px; font-weight:600; text-decoration:none; color:#6b6b80; background:#fff; border:1px solid #e8e6f0; }
  .filter-tab--active { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }
  .review-card { background:#fff; border:1px solid #e8e6f0; border-radius:12px; padding:20px; margin-bottom:16px; }
  .review-card__header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; flex-wrap:wrap; gap:8px; }
  .review-card__name { font-size:15px; font-weight:700; color:#1a1a2e; }
  .review-card__meta { font-size:12px; color:#9b97b0; margin-top:2px; }
  .stars { color:#e8a020; font-size:16px; letter-spacing:1px; }
  .relationship-badge { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; background:#f0ecfa; color:#3d1a6e; margin-left:6px; }
  .review-card__text { font-size:13.5px; color:#3a3850; line-height:1.7; margin-bottom:14px; }
  .review-card__actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  .action-btn { font-size:12px; font-weight:600; padding:7px 14px; border-radius:7px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--approve { background:#e6f9ed; color:#1a7a3a; }
  .action-btn--approve:hover { background:#d4f2dd; }
  .action-btn--reject  { background:#fff3e6; color:#8a4a00; }
  .action-btn--reject:hover  { background:#ffe9d0; }
  .action-btn--pending { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--delete  { background:#ffe6e6; color:#cc3333; }
  .action-btn--delete:hover  { background:#ffd6d6; }
  .reviewed-by { font-size:11.5px; color:#9b97b0; margin-left:auto; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; background:#fff; border:1px solid #e8e6f0; border-radius:14px; }
  .unverified-note { background:#fff3e6; border:1px solid #ffe0b2; border-radius:10px; padding:12px 16px; font-size:13px; color:#8a4a00; margin-bottom:20px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'reviews'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Reviews &amp; Testimonials</h2>
        <p>Approve or reject visitor reviews. Approved reviews appear in the testimonials section on the homepage (latest 3 shown).</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($unverifiedCount > 0): ?>
      <div class="unverified-note">
        ⚠️ <strong><?php echo $unverifiedCount; ?></strong> review(s) are awaiting email verification from the submitter and won't appear here until verified.
      </div>
      <?php endif; ?>

      <!-- Status filter tabs -->
      <div class="filter-tabs">
        <a href="?status=pending"
           class="filter-tab <?php echo $filterStatus === 'pending' ? 'filter-tab--active' : ''; ?>">
          Pending <?php if ($statCounts['pending'] > 0): ?><span style="background:#e8a020;color:#fff;font-size:10px;padding:1px 7px;border-radius:20px;margin-left:5px"><?php echo $statCounts['pending']; ?></span><?php endif; ?>
        </a>
        <a href="?status=approved"
           class="filter-tab <?php echo $filterStatus === 'approved' ? 'filter-tab--active' : ''; ?>">
          Approved (<?php echo $statCounts['approved']; ?>)
        </a>
        <a href="?status=rejected"
           class="filter-tab <?php echo $filterStatus === 'rejected' ? 'filter-tab--active' : ''; ?>">
          Rejected (<?php echo $statCounts['rejected']; ?>)
        </a>
      </div>

      <!-- Reviews list -->
      <?php if (empty($reviewList)): ?>
      <div class="empty-state">
        No <?php echo $filterStatus; ?> reviews.
        <?php if ($filterStatus === 'pending'): ?>
        Reviews submitted via the public website will appear here once verified by the submitter.
        <?php endif; ?>
      </div>
      <?php else: ?>
      <?php foreach ($reviewList as $r): ?>
      <div class="review-card">
        <div class="review-card__header">
          <div>
            <div class="review-card__name">
              <?php echo htmlspecialchars($r['reviewer_name']); ?>
              <span class="relationship-badge">
                <?php echo htmlspecialchars($relationshipLabels[$r['relationship']] ?? $r['relationship']); ?>
              </span>
            </div>
            <div class="review-card__meta">
              <?php echo htmlspecialchars($r['reviewer_email']); ?>
              &nbsp;·&nbsp;
              <?php echo date('d M Y, g:ia', strtotime($r['created_at'])); ?>
            </div>
          </div>
          <div class="stars">
            <?php echo str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']); ?>
          </div>
        </div>

        <div class="review-card__text"><?php echo nl2br(htmlspecialchars($r['review_text'])); ?></div>

        <div class="review-card__actions">
          <?php if ($r['status'] !== 'approved'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="approve"/>
            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>"/>
            <button type="submit" class="action-btn action-btn--approve">✓ Approve</button>
          </form>
          <?php endif; ?>

          <?php if ($r['status'] !== 'rejected'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="reject"/>
            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>"/>
            <button type="submit" class="action-btn action-btn--reject">✗ Reject</button>
          </form>
          <?php endif; ?>

          <?php if ($r['status'] !== 'pending'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="pending"/>
            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>"/>
            <button type="submit" class="action-btn action-btn--pending">↩ Move to Pending</button>
          </form>
          <?php endif; ?>

          <form method="POST" style="display:inline"
                onsubmit="return confirm('Delete this review permanently?')">
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>"/>
            <button type="submit" class="action-btn action-btn--delete">Delete</button>
          </form>

          <?php if ($r['reviewed_by_name']): ?>
          <span class="reviewed-by">
            <?php echo $r['status'] === 'approved' ? '✓ Approved' : '✗ Rejected'; ?>
            by <?php echo htmlspecialchars($r['reviewed_by_name']); ?>
            on <?php echo date('d M Y', strtotime($r['reviewed_at'])); ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>