<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT PORTAL DASHBOARD
   File: public/portal/dashboard.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/auth.php';

$student = requireStudentLogin();
$pdo     = getDB();

/* ── Refresh session from DB on every dashboard load ── */
refreshStudentSession($pdo);
$student = currentStudent();

/* ── Unread notifications count ── */
$unreadStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM student_notifications
     WHERE student_id = ? AND is_read = 0'
);
$unreadStmt->execute([$student['id']]);
$unreadCount = (int) $unreadStmt->fetchColumn();

/* ── Latest 3 notifications ── */
$notifStmt = $pdo->prepare(
    'SELECT n.*, u.full_name AS issued_by_name
     FROM student_notifications n
     JOIN users u ON u.id = n.issued_by
     WHERE n.student_id = ?
     ORDER BY n.created_at DESC
     LIMIT 3'
);
$notifStmt->execute([$student['id']]);
$recentNotifs = $notifStmt->fetchAll();

/* ── Latest published results term ── */
$resultStmt = $pdo->prepare(
    "SELECT DISTINCT term, session FROM results
     WHERE student_id = ? AND status = 'published'
     ORDER BY session DESC, FIELD(term,'first','second','third') DESC
     LIMIT 1"
);
$resultStmt->execute([$student['id']]);
$latestResult = $resultStmt->fetch();

/* ── Photo path ── */
$photoSrc = '';
if (!empty($student['photo'])) {
    $photoSrc = '../assets/images/students/' . htmlspecialchars($student['photo']);
}

$typeLabels = [
    'suspension'         => ['label' => 'Suspension Notice',  'color' => '#cc3333', 'bg' => '#ffe6e6', 'icon' => '⛔'],
    'expulsion'          => ['label' => 'Expulsion Notice',   'color' => '#8a0000', 'bg' => '#ffd0d0', 'icon' => '🚫'],
    'promotion'          => ['label' => 'Promotion Notice',   'color' => '#1a7a3a', 'bg' => '#e6f9ed', 'icon' => '🎉'],
    'demotion'           => ['label' => 'Demotion Notice',    'color' => '#8a4a00', 'bg' => '#fff3e6', 'icon' => '⚠️'],
    'retention'          => ['label' => 'Retention Notice',   'color' => '#8a4a00', 'bg' => '#fff3e6', 'icon' => '📋'],
    'behavioural_remark' => ['label' => 'Behavioural Remark', 'color' => '#4a2d8a', 'bg' => '#f0ecfa', 'icon' => '📝'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Dashboard — Ibeku High School Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/portal.css"/>
</head>
<body>

<?php include dirname(__DIR__, 2) . '/src/includes/portal-nav.php'; ?>

<main class="portal-main">
  <div class="portal-inner">

    <!-- ── Welcome banner ── -->
    <div class="welcome-card">
      <div class="welcome-card__photo">
        <?php if ($photoSrc): ?>
        <img src="<?php echo $photoSrc; ?>"
             alt="<?php echo htmlspecialchars($student['first_name']); ?>"
             onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
        <div class="welcome-card__avatar" style="display:none">
          <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
        </div>
        <?php else: ?>
        <div class="welcome-card__avatar">
          <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="welcome-card__info">
        <p class="welcome-card__greeting">Welcome back,</p>
        <h1 class="welcome-card__name">
          <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
        </h1>
        <div class="welcome-card__meta">
          <span><?php echo gradeLabel($student['grade_level']); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($student['class']); ?></span>
          <span><?php echo sectionLabel($student['section']); ?></span>
          <span>Adm: <?php echo htmlspecialchars($student['admission_number']); ?></span>
        </div>
      </div>
      <?php if ($unreadCount > 0): ?>
      <a href="notifications.php" class="welcome-card__notif-badge">
        🔔 <?php echo $unreadCount; ?> new notice<?php echo $unreadCount > 1 ? 's' : ''; ?>
      </a>
      <?php endif; ?>
    </div>

    <!-- ── Quick links ── -->
    <div class="quick-links">
      <a href="results.php" class="quick-link <?php echo $student['results_blocked'] ? 'quick-link--disabled' : ''; ?>">
        <span class="quick-link__icon">📊</span>
        <span class="quick-link__label">My Results</span>
        <?php if ($student['results_blocked']): ?>
        <span class="quick-link__badge quick-link__badge--blocked">Blocked</span>
        <?php elseif ($latestResult): ?>
        <span class="quick-link__badge">
          <?php echo ucfirst($latestResult['term']); ?> Term
        </span>
        <?php endif; ?>
      </a>

      <a href="profile.php" class="quick-link">
        <span class="quick-link__icon">👤</span>
        <span class="quick-link__label">My Profile</span>
      </a>

      <a href="notifications.php" class="quick-link">
        <span class="quick-link__icon">🔔</span>
        <span class="quick-link__label">Notices</span>
        <?php if ($unreadCount > 0): ?>
        <span class="quick-link__badge quick-link__badge--alert"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
      </a>

      <a href="../index.php" class="quick-link">
        <span class="quick-link__icon">🏫</span>
        <span class="quick-link__label">School Website</span>
      </a>
    </div>

    <!-- ── Results blocked warning ── -->
    <?php if ($student['results_blocked']): ?>
    <div class="alert-card alert-card--warning">
      <span class="alert-card__icon">⚠️</span>
      <div class="alert-card__text">
        <strong>Result Access Restricted</strong>
        <p>Your access to results has been restricted by the school.
           <?php if ($student['results_blocked_reason']): ?>
           Reason: <?php echo htmlspecialchars($student['results_blocked_reason']); ?>
           <?php endif; ?>
        </p>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Recent notifications ── -->
    <?php if (!empty($recentNotifs)): ?>
    <section class="section">
      <div class="section__header">
        <h2 class="section__title">Recent Notices</h2>
        <a href="notifications.php" class="section__link">View all →</a>
      </div>
      <div class="notif-list">
        <?php foreach ($recentNotifs as $n):
          $t = $typeLabels[$n['type']] ?? ['label' => $n['type'], 'color' => '#3d1a6e', 'bg' => '#f0ecfa', 'icon' => '📄'];
        ?>
        <a href="notifications.php?open=<?php echo $n['id']; ?>"
           class="notif-item <?php echo !$n['is_read'] ? 'notif-item--unread' : ''; ?>">
          <div class="notif-item__icon" style="background:<?php echo $t['bg']; ?>">
            <?php echo $t['icon']; ?>
          </div>
          <div class="notif-item__body">
            <div class="notif-item__type" style="color:<?php echo $t['color']; ?>">
              <?php echo $t['label']; ?>
            </div>
            <div class="notif-item__title"><?php echo htmlspecialchars($n['title']); ?></div>
            <div class="notif-item__meta">
              <?php echo date('d M Y', strtotime($n['created_at'])); ?>
              &nbsp;·&nbsp; <?php echo htmlspecialchars($n['issued_by_name']); ?>
            </div>
          </div>
          <?php if (!$n['is_read']): ?>
          <div class="notif-item__dot"></div>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </div>
</main>

</body>
</html>