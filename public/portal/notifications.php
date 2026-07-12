<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT PORTAL NOTIFICATIONS
   File: public/portal/notifications.php

   Shows all notices issued to the student by school staff:
   Suspension, Expulsion, Promotion, Demotion, Retention,
   Behavioural Remark.
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/auth.php';

$student = requireStudentLogin();
$pdo     = getDB();

/* ── Open a single notice and mark read ── */
$openNotif = null;
if (!empty($_GET['open'])) {
    $openId   = (int) $_GET['open'];
    $openStmt = $pdo->prepare(
        'SELECT n.*, u.full_name AS issued_by_name, u.role AS issued_by_role
         FROM student_notifications n
         JOIN users u ON u.id = n.issued_by
         WHERE n.id = ? AND n.student_id = ?
         LIMIT 1'
    );
    $openStmt->execute([$openId, $student['id']]);
    $openNotif = $openStmt->fetch();

    if ($openNotif && !$openNotif['is_read']) {
        $pdo->prepare(
            'UPDATE student_notifications
             SET is_read = 1, read_at = NOW()
             WHERE id = ?'
        )->execute([$openId]);
        $openNotif['is_read'] = 1;
    }
}

/* ── Load all notifications ── */
$stmt = $pdo->prepare(
    'SELECT n.*, u.full_name AS issued_by_name
     FROM student_notifications n
     JOIN users u ON u.id = n.issued_by
     WHERE n.student_id = ?
     ORDER BY n.created_at DESC'
);
$stmt->execute([$student['id']]);
$notifications = $stmt->fetchAll();

$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

$typeLabels = [
    'suspension'         => ['label' => 'Suspension Notice',   'color' => '#cc3333', 'bg' => '#ffe6e6', 'icon' => '⛔'],
    'expulsion'          => ['label' => 'Expulsion Notice',     'color' => '#8a0000', 'bg' => '#ffd0d0', 'icon' => '🚫'],
    'promotion'          => ['label' => 'Promotion Notice',     'color' => '#1a7a3a', 'bg' => '#e6f9ed', 'icon' => '🎉'],
    'demotion'           => ['label' => 'Demotion Notice',      'color' => '#8a4a00', 'bg' => '#fff3e6', 'icon' => '⚠️'],
    'retention'          => ['label' => 'Retention Notice',     'color' => '#8a4a00', 'bg' => '#fff3e6', 'icon' => '📋'],
    'behavioural_remark' => ['label' => 'Behavioural Remark',  'color' => '#4a2d8a', 'bg' => '#f0ecfa', 'icon' => '📝'],
];

$roleLabels = [
    'superadmin'      => 'Administration',
    'principal'       => 'Principal',
    'vp_admin'        => 'VP Administration',
    'vp_academics'    => 'VP Academics',
    'vp_general'      => 'VP General Duties',
    'dean'            => 'Dean of Studies',
    'form_teacher'    => 'Form Teacher',
    'subject_teacher' => 'Subject Teacher',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Notices — Ibeku High School Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/portal.css"/>
  <style>
    /* ── Notice layout ── */
    .notices-layout { display:grid; grid-template-columns:280px 1fr; gap:20px; align-items:start; }
    @media (max-width:700px) { .notices-layout { grid-template-columns:1fr; } }

    .notice-panel { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
    .notice-panel__header {
      padding:14px 18px; border-bottom:1px solid #f0eef6;
      font-size:14px; font-weight:700; color:#3d1a6e;
      display:flex; align-items:center; justify-content:space-between;
    }

    /* List */
    .notice-list { list-style:none; padding:0; margin:0; }
    .notice-list-item {
      display:flex; gap:10px; align-items:flex-start;
      padding:13px 18px; border-bottom:1px solid #f0eef6;
      text-decoration:none; color:inherit; transition:background .12s;
    }
    .notice-list-item:last-child { border-bottom:none; }
    .notice-list-item:hover { background:#faf9fd; }
    .notice-list-item--unread { background:#f4f0fb; }
    .notice-list-item--active { background:#ede9f7 !important; }

    .notice-list-item__icon {
      width:36px; height:36px; border-radius:10px; flex-shrink:0;
      display:flex; align-items:center; justify-content:center;
      font-size:1.1rem;
    }
    .notice-list-item__body { flex:1; min-width:0; }
    .notice-list-item__type { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px; }
    .notice-list-item__title { font-size:13px; font-weight:600; color:#1a1a2e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .notice-list-item__date  { font-size:11.5px; color:#9b97b0; margin-top:2px; }
    .unread-dot { width:7px; height:7px; border-radius:50%; background:#3d1a6e; flex-shrink:0; margin-top:5px; }

    /* Open notice */
    .notice-open { padding:24px; }
    .notice-open__type-badge {
      display:inline-flex; align-items:center; gap:6px;
      font-size:11.5px; font-weight:700; text-transform:uppercase;
      letter-spacing:.05em; padding:4px 12px; border-radius:20px;
      margin-bottom:14px;
    }
    .notice-open__title {
      font-family:'Playfair Display', serif;
      font-size:1.4rem; color:#3d1a6e; font-weight:700;
      margin-bottom:12px; line-height:1.3;
    }
    .notice-open__meta {
      display:flex; gap:16px; flex-wrap:wrap;
      font-size:12.5px; color:#9b97b0; margin-bottom:20px;
    }
    .notice-open__body {
      font-size:14px; color:#1a1a2e; line-height:1.8;
      background:#faf9fd; border:1px solid #f0eef6;
      border-radius:10px; padding:18px 20px;
      white-space:pre-wrap;
    }
    .notice-back {
      display:inline-block; margin-bottom:16px;
      font-size:13px; color:#4a90d9; text-decoration:none; font-weight:600;
    }
    .notice-back:hover { text-decoration:underline; }
    .empty-panel { padding:50px 20px; text-align:center; color:#9b97b0; font-size:13.5px; }
  </style>
</head>
<body>

<?php include dirname(__DIR__, 2) . '/src/includes/portal-nav.php'; ?>

<main class="portal-main">
  <div class="portal-inner">

    <div class="page-hero">
      <h1 class="page-hero__title">
        Notices
        <?php if ($unreadCount > 0): ?>
        <span style="background:#3d1a6e;color:#fff;font-size:13px;padding:2px 10px;border-radius:20px;margin-left:8px;vertical-align:middle">
          <?php echo $unreadCount; ?> unread
        </span>
        <?php endif; ?>
      </h1>
      <p class="page-hero__sub">Official notices and communications from your school.</p>
    </div>

    <?php if (empty($notifications)): ?>
    <div class="empty-portal">
      <div class="empty-portal__icon">🔔</div>
      <h2>No notices yet</h2>
      <p>Official notices from your school will appear here.</p>
    </div>
    <?php else: ?>

    <div class="notices-layout">

      <!-- ── List panel ── -->
      <div class="notice-panel">
        <div class="notice-panel__header">
          All Notices
          <span style="font-size:12px;font-weight:400;color:#9b97b0"><?php echo count($notifications); ?> total</span>
        </div>
        <ul class="notice-list">
          <?php foreach ($notifications as $n):
            $t      = $typeLabels[$n['type']] ?? ['label' => $n['type'], 'color' => '#3d1a6e', 'bg' => '#f0ecfa', 'icon' => '📄'];
            $active = $openNotif && $openNotif['id'] === $n['id'];
          ?>
          <li>
            <a href="notifications.php?open=<?php echo $n['id']; ?>"
               class="notice-list-item
                      <?php echo !$n['is_read'] ? 'notice-list-item--unread' : ''; ?>
                      <?php echo $active ? 'notice-list-item--active' : ''; ?>">
              <div class="notice-list-item__icon" style="background:<?php echo $t['bg']; ?>">
                <?php echo $t['icon']; ?>
              </div>
              <div class="notice-list-item__body">
                <div class="notice-list-item__type" style="color:<?php echo $t['color']; ?>">
                  <?php echo $t['label']; ?>
                </div>
                <div class="notice-list-item__title"><?php echo htmlspecialchars($n['title']); ?></div>
                <div class="notice-list-item__date"><?php echo date('d M Y', strtotime($n['created_at'])); ?></div>
              </div>
              <?php if (!$n['is_read']): ?>
              <div class="unread-dot"></div>
              <?php endif; ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- ── Open notice panel ── -->
      <div class="notice-panel">
        <?php if ($openNotif):
          $t = $typeLabels[$openNotif['type']] ?? ['label' => $openNotif['type'], 'color' => '#3d1a6e', 'bg' => '#f0ecfa', 'icon' => '📄'];
        ?>
        <div class="notice-open">
          <a href="notifications.php" class="notice-back">← Back to all notices</a>

          <div class="notice-open__type-badge"
               style="color:<?php echo $t['color']; ?>;background:<?php echo $t['bg']; ?>">
            <?php echo $t['icon']; ?> <?php echo $t['label']; ?>
          </div>

          <h2 class="notice-open__title"><?php echo htmlspecialchars($openNotif['title']); ?></h2>

          <div class="notice-open__meta">
            <span>
              <strong>Issued by:</strong>
              <?php echo htmlspecialchars($openNotif['issued_by_name']); ?>
              (<?php echo htmlspecialchars($roleLabels[$openNotif['issued_by_role']] ?? $openNotif['issued_by_role']); ?>)
            </span>
            <span><?php echo date('d F Y, g:ia', strtotime($openNotif['created_at'])); ?></span>
          </div>

          <div class="notice-open__body"><?php echo htmlspecialchars($openNotif['body']); ?></div>
        </div>

        <?php else: ?>
        <div class="empty-panel">
          Select a notice from the list to read it.
        </div>
        <?php endif; ?>
      </div>

    </div>
    <?php endif; ?>

  </div>
</main>

</body>
</html>