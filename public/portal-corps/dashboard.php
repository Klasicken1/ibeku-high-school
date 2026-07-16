<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER PORTAL DASHBOARD
   File: public/portal-corps/dashboard.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

$corpsMember = requireCorpsLogin();
$pdo         = getDB();

refreshCorpsSession($pdo);
$corpsMember = currentCorpsMember();

/* Load full record */
$stmt = $pdo->prepare('SELECT * FROM corps_members WHERE id = ? LIMIT 1');
$stmt->execute([$corpsMember['id']]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

/* Unread messages */
$unreadStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM corps_messages
     WHERE corps_member_id = ? AND sender_type = 'admin' AND is_read = 0"
);
$unreadStmt->execute([$corpsMember['id']]);
$unreadCount = (int) $unreadStmt->fetchColumn();

/* Latest clearance */
$clearStmt = $pdo->prepare(
    "SELECT * FROM corps_clearance
     WHERE corps_member_id = ? AND is_cleared = 1
     ORDER BY year DESC, month DESC LIMIT 1"
);
$clearStmt->execute([$corpsMember['id']]);
$latestClear = $clearStmt->fetch(PDO::FETCH_ASSOC);

$months = ['','January','February','March','April','May','June',
           'July','August','September','October','November','December'];

$photoSrc = '';
if (!empty($member['photo'])) {
    $photoSrc = '../assets/images/corps/' . htmlspecialchars($member['photo']);
}

/* Default password warning */
$isDefaultPassword = empty($member['password']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard - Corps Portal - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/corps-portal.css"/>
</head>
<body>
<?php include dirname(__DIR__, 2) . '/src/includes/corps-nav.php'; ?>
<main class="corps-main">
  <div class="corps-inner">

    <?php if ($isDefaultPassword): ?>
    <div class="alert alert--error" style="display:flex;align-items:center;gap:10px">
      <span>Your password is still the default (your state code). <a href="change-password.php" style="color:#cc3333;font-weight:700">Change it now</a> to secure your account.</span>
    </div>
    <?php endif; ?>

    <!-- Welcome card -->
    <div class="welcome-card">
      <div class="welcome-card__photo">
        <?php if ($photoSrc): ?>
        <img src="<?php echo $photoSrc; ?>" alt="<?php echo htmlspecialchars($member['full_name']); ?>"
             onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
        <div class="welcome-card__avatar" style="display:none">
          <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
        </div>
        <?php else: ?>
        <div class="welcome-card__avatar">
          <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="welcome-card__info">
        <p class="welcome-card__greeting">Welcome back,</p>
        <h1 class="welcome-card__name"><?php echo htmlspecialchars($member['full_name']); ?></h1>
        <div class="welcome-card__meta">
          <span><?php echo htmlspecialchars($member['state_code']); ?></span>
          <span>Batch <?php echo htmlspecialchars($member['batch']); ?></span>
          <span><?php echo sectionLabel($member['section']); ?></span>
        </div>
      </div>
      <span class="nysc-badge">NYSC Corps Member</span>
    </div>

    <!-- Quick links -->
    <div class="quick-links">
      <a href="clearance.php" class="quick-link">
        <span class="quick-link__icon">📋</span>
        <span class="quick-link__label">My Clearance</span>
        <?php if ($latestClear): ?>
        <span class="quick-link__badge"><?php echo $months[$latestClear['month']] . ' ' . $latestClear['year']; ?></span>
        <?php endif; ?>
      </a>
      <a href="messages.php" class="quick-link">
        <span class="quick-link__icon">💬</span>
        <span class="quick-link__label">Messages</span>
        <?php if ($unreadCount > 0): ?>
        <span class="quick-link__badge quick-link__badge--alert"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
      </a>
      <a href="profile.php" class="quick-link">
        <span class="quick-link__icon">👤</span>
        <span class="quick-link__label">My Profile</span>
      </a>
      <a href="../index.php" class="quick-link">
        <span class="quick-link__icon">🏫</span>
        <span class="quick-link__label">School Website</span>
      </a>
    </div>

    <!-- Profile summary -->
    <div class="detail-card">
      <h3 class="detail-card__title">Posting Details</h3>
      <div class="detail-grid">
        <div class="detail-row">
          <span class="detail-label">Subject Taught</span>
          <span class="detail-value"><?php echo htmlspecialchars($member['subject_taught'] ?? '-'); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Section</span>
          <span class="detail-value"><?php echo sectionLabel($member['section']); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Class Arms</span>
          <span class="detail-value"><?php echo htmlspecialchars($member['class_arms'] ?? '-'); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">CDS Group</span>
          <span class="detail-value"><?php echo htmlspecialchars($member['cds_group'] ?? '-'); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">CDS Day</span>
          <span class="detail-value"><?php echo htmlspecialchars($member['cds_day'] ?? '-'); ?></span>
        </div>
      </div>
    </div>

  </div>
</main>
</body>
</html>