<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT PORTAL DASHBOARD
   File: public/portal/dashboard.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/config/vapid.php';
require_once dirname(__DIR__, 2) . '/src/includes/push-helper.php';
require_once dirname(__DIR__, 2) . '/src/includes/auth.php';

$student = requireStudentLogin();
$pdo     = getDB();

ensurePushStudentIdColumn($pdo);

/* ════════════════════════════════════════════════════════════
   AJAX: Save student push subscription (JSON POST from browser)
   Fetch POSTs application/json with the PushSubscription object.
   Mirrors the staff pattern in admin/messages.php.
   ════════════════════════════════════════════════════════════ */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (
        empty($input['endpoint']) ||
        empty($input['keys']['auth']) ||
        empty($input['keys']['p256dh'])
    ) {
        echo json_encode(['success' => false, 'error' => 'Invalid subscription data']);
        exit;
    }

    try {
        $pdo->prepare(
            "INSERT INTO push_subscriptions (endpoint, auth, p256dh, student_id)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               auth       = VALUES(auth),
               p256dh     = VALUES(p256dh),
               student_id = VALUES(student_id)"
        )->execute([
            $input['endpoint'],
            $input['keys']['auth'],
            $input['keys']['p256dh'],
            $student['id'],
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log('[IHS student push subscribe] ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'DB error']);
    }
    exit;
}

/* ── Refresh session from DB on every dashboard load ── */
refreshStudentSession($pdo);
$student = currentStudent();

/* ── Check if this student already has a push subscription ── */
$isStudentSubscribed = false;
try {
    $subCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM push_subscriptions WHERE student_id = ?'
    );
    $subCheck->execute([$student['id']]);
    $isStudentSubscribed = (int) $subCheck->fetchColumn() > 0;
} catch (PDOException $e) { /* push_subscriptions may not have student_id yet */ }

$vapidPublicKey = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';
$vapidReady     = $vapidPublicKey !== '' && $vapidPublicKey !== 'REPLACE_WITH_YOUR_PUBLIC_KEY';

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
     WHERE student_id = ? AND is_published = 1
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
  <style>
    /* ── Push subscribe banner (mirrors admin/messages.php styling) ── */
    .push-nudge {
      background: linear-gradient(135deg, #3d1a6e, #4a3080);
      border-radius: 12px;
      padding: 14px 18px;
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .push-nudge__icon { font-size: 1.4rem; flex-shrink: 0; }
    .push-nudge__text { flex: 1; color: #fff; min-width: 200px; }
    .push-nudge__text strong { display: block; font-size: 13.5px; margin-bottom: 2px; }
    .push-nudge__text span   { font-size: 12px; color: rgba(255,255,255,.7); }
    .btn-push-sub {
      background: #e8a020; color: #fff; border: none;
      padding: 9px 20px; border-radius: 8px; font-size: 13px;
      font-weight: 700; font-family: 'DM Sans', sans-serif;
      cursor: pointer; white-space: nowrap; flex-shrink: 0;
    }
    .btn-push-sub:hover { background: #d4911a; }
    .btn-push-dismiss {
      background: none; border: none; color: rgba(255,255,255,.5);
      font-size: 1rem; cursor: pointer; flex-shrink: 0;
    }
    .push-subscribed-note {
      background: #e6f9ed; border: 1px solid #b2dfce; border-radius: 10px;
      padding: 10px 16px; font-size: 13px; color: #1a7a3a;
      display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
    }
  </style>
</head>
<body>

<?php include dirname(__DIR__, 2) . '/src/includes/portal-nav.php'; ?>

<main class="portal-main">
  <div class="portal-inner">

    <!-- ── Push notification subscribe nudge ── -->
    <?php if ($vapidReady && !$isStudentSubscribed): ?>
    <div class="push-nudge" id="pushNudge">
      <div class="push-nudge__icon">🔔</div>
      <div class="push-nudge__text">
        <strong>Enable notice notifications</strong>
        <span>Get instant alerts when the school sends you a notice, even when this tab is closed.</span>
      </div>
      <button class="btn-push-sub" onclick="subscribeToStudentNotices()">Enable Notifications</button>
      <button class="btn-push-dismiss" onclick="dismissStudentPushNudge()" aria-label="Dismiss">✕</button>
    </div>
    <?php elseif ($isStudentSubscribed): ?>
    <div class="push-subscribed-note">
      ✅ <span>Push notifications are enabled — you'll be alerted when the school sends you a notice.</span>
    </div>
    <?php endif; ?>

    <div id="pushSuccessNote" style="display:none" class="push-subscribed-note">
      ✅ <span>Push notifications enabled! You'll now be alerted when the school sends you a notice.</span>
    </div>

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

<script>
  /* ── Student push subscription ──────────────────────────
     Self-contained: registers its own service worker rather
     than relying on main.js (which the portal never loads),
     so this works even if the student never visits the public
     site first. ── */
  const VAPID_PUBLIC_KEY = <?php echo json_encode($vapidPublicKey); ?>;

  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw     = window.atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
  }

  async function ihsRegisterStudentSW() {
    if (!('serviceWorker' in navigator)) return null;
    try {
      /* sw.js lives at public/sw.js — one level up from public/portal/.
         Scope '../' resolves to the public/ root on both localhost and
         production, matching the scope main.js already registers under. */
      return await navigator.serviceWorker.register('../sw.js', { scope: '../' });
    } catch (err) {
      console.warn('[IHS] Student SW registration failed:', err);
      return null;
    }
  }

  async function subscribeToStudentNotices() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      alert('Push notifications are not supported in this browser.');
      return;
    }
    if (!VAPID_PUBLIC_KEY) {
      alert('Push notifications are not configured on this server yet.');
      return;
    }

    try {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        alert('Permission denied. You can change this in your browser settings.');
        return;
      }

      const registration = await ihsRegisterStudentSW();
      if (!registration) {
        alert('Could not set up notifications on this device. Please try again.');
        return;
      }
      await navigator.serviceWorker.ready;

      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly:      true,
        applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
      });

      const response = await fetch('dashboard.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(subscription.toJSON()),
      });

      const result = await response.json();
      if (result.success) {
        const nudge = document.getElementById('pushNudge');
        if (nudge) nudge.style.display = 'none';
        const note = document.getElementById('pushSuccessNote');
        if (note) note.style.display = 'flex';
      } else {
        alert('Could not save subscription. Please try again.');
      }
    } catch (err) {
      console.error('[IHS push] Student subscribe error:', err);
      alert('Something went wrong. Please try again.');
    }
  }

  function dismissStudentPushNudge() {
    const nudge = document.getElementById('pushNudge');
    if (nudge) nudge.style.display = 'none';
    sessionStorage.setItem('student-push-nudge-dismissed', '1');
  }

  if (sessionStorage.getItem('student-push-nudge-dismissed')) {
    const nudge = document.getElementById('pushNudge');
    if (nudge) nudge.style.display = 'none';
  }
</script>

</body>
</html>