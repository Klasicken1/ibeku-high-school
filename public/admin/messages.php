<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STAFF MESSAGING
   File: public/admin/messages.php

   Accessible to: all admin roles
   Staff can compose messages to other admins, view their
   inbox, and mark messages as read. Unread count appears
   as a badge on the sidebar bell icon.

   Notifications on message receipt:
   - Web Push (primary)  — via push-helper.php + VAPID
   - Email (fallback)    — via sendStaffMessageEmail()
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/config/vapid.php';
require_once dirname(__DIR__, 2) . '/src/includes/push-helper.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin', 'vp_academics',
             'vp_general', 'dean', 'form_teacher', 'subject_teacher']);

$admin = currentAdmin();
$pdo   = getDB();

/* ── Ensure push_subscriptions has user_id column ── */
ensurePushUserIdColumn($pdo);

/* ════════════════════════════════════════════════════════════
   AJAX: Save admin push subscription (JSON POST from browser)
   Fetch POSTs application/json with the PushSubscription object
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
        /* Upsert — update keys if endpoint already exists for this user */
        $pdo->prepare(
            "INSERT INTO push_subscriptions (endpoint, auth, p256dh, user_id)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               auth    = VALUES(auth),
               p256dh  = VALUES(p256dh),
               user_id = VALUES(user_id)"
        )->execute([
            $input['endpoint'],
            $input['keys']['auth'],
            $input['keys']['p256dh'],
            $admin['id'],
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log('[IHS push subscribe] ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'DB error']);
    }
    exit;
}

/* ── Ensure staff_messages table exists ── */
try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS staff_messages (
            id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
            sender_id     INT UNSIGNED     NOT NULL,
            recipient_id  INT UNSIGNED     NOT NULL,
            subject       VARCHAR(255)     NOT NULL,
            body          TEXT             NOT NULL,
            is_read       TINYINT(1)       NOT NULL DEFAULT 0,
            read_at       TIMESTAMP        NULL,
            created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_recipient (recipient_id, is_read),
            KEY idx_sender    (sender_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (PDOException $e) { /* Already exists */ }

$message     = '';
$messageType = '';
$view        = $_GET['view'] ?? 'inbox';

/* ── Check if current admin has push subscription ── */
$isSubscribed = false;
try {
    $subCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?'
    );
    $subCheck->execute([$admin['id']]);
    $isSubscribed = (int) $subCheck->fetchColumn() > 0;
} catch (PDOException $e) { /* push_subscriptions may not have user_id yet */ }

/* ── Handle POST actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'send') {
        $recipientId = (int) ($_POST['recipient_id'] ?? 0);
        $subject     = trim($_POST['subject']        ?? '');
        $body        = trim($_POST['body']           ?? '');

        if ($recipientId <= 0) {
            $message = 'Please select a recipient.'; $messageType = 'error';
        } elseif ($subject === '') {
            $message = 'Subject is required.'; $messageType = 'error';
        } elseif ($body === '') {
            $message = 'Message body is required.'; $messageType = 'error';
        } else {
            try {
                /* Insert message */
                $pdo->prepare(
                    'INSERT INTO staff_messages
                        (sender_id, recipient_id, subject, body)
                     VALUES (?,?,?,?)'
                )->execute([$admin['id'], $recipientId, $subject, $body]);

                $newMsgId = (int) $pdo->lastInsertId();

                /* Load recipient details for notifications */
                $recipStmt = $pdo->prepare(
                    'SELECT full_name, email FROM users WHERE id = ? LIMIT 1'
                );
                $recipStmt->execute([$recipientId]);
                $recipient = $recipStmt->fetch();

                if ($recipient) {
                    /* ── Web Push (primary) ── */
                    $pushResult = sendPushToUser(
                        $pdo,
                        $recipientId,
                        'New message from ' . $admin['full_name'],
                        $subject . ' — ' . mb_substr($body, 0, 80),
                        (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                            . '://' . $_SERVER['HTTP_HOST']
                            . (BASE_PATH . 'admin/messages.php')
                            . '?open=' . $newMsgId . '&view=inbox'
                    );

                    /* ── Email (fallback) ── */
                    $inboxUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                        . '://' . $_SERVER['HTTP_HOST']
                        . (BASE_PATH . 'admin/messages.php')
                        . '?open=' . $newMsgId . '&view=inbox';

                    $emailSent = sendStaffMessageEmail(
                        $recipient['email'],
                        $recipient['full_name'],
                        $admin['full_name'],
                        $subject,
                        $body,
                        $inboxUrl
                    );

                    /* Build notification status string for the success alert */
                    $notifNote = '';
                    if (!empty($pushResult['skipped'])) {
                        $notifNote = $emailSent
                            ? ' A notification email has been sent.'
                            : '';
                    } else {
                        $pushSent = $pushResult['sent'] ?? 0;
                        if ($pushSent > 0 && $emailSent) {
                            $notifNote = ' Push notification and email sent.';
                        } elseif ($pushSent > 0) {
                            $notifNote = ' Push notification sent.';
                        } elseif ($emailSent) {
                            $notifNote = ' Notification email sent.';
                        }
                    }

                    $message = 'Message sent successfully.' . $notifNote;
                } else {
                    $message = 'Message sent successfully.';
                }

                $messageType = 'success';
                $view = 'sent';

            } catch (PDOException $e) {
                error_log('IHS messages send error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }

    } elseif ($action === 'mark_read') {
        $msgId = (int) ($_POST['msg_id'] ?? 0);
        if ($msgId > 0) {
            $pdo->prepare(
                'UPDATE staff_messages SET is_read = 1, read_at = NOW()
                 WHERE id = ? AND recipient_id = ?'
            )->execute([$msgId, $admin['id']]);
        }
        header('Location: messages.php?view=inbox');
        exit;

    } elseif ($action === 'mark_all_read') {
        $pdo->prepare(
            'UPDATE staff_messages SET is_read = 1, read_at = NOW()
             WHERE recipient_id = ? AND is_read = 0'
        )->execute([$admin['id']]);
        $message = 'All messages marked as read.'; $messageType = 'success';
        $view = 'inbox';

    } elseif ($action === 'delete') {
        $msgId = (int) ($_POST['msg_id'] ?? 0);
        if ($msgId > 0) {
            $pdo->prepare(
                'DELETE FROM staff_messages
                 WHERE id = ? AND (sender_id = ? OR recipient_id = ?)'
            )->execute([$msgId, $admin['id'], $admin['id']]);
            $message = 'Message deleted.'; $messageType = 'success';
        }
    }
}

/* ── Open single message and mark read ── */
$openMsg = null;
if (!empty($_GET['open'])) {
    $openId   = (int) $_GET['open'];
    $openStmt = $pdo->prepare(
        'SELECT m.*,
                s.full_name AS sender_name,    s.role AS sender_role,
                r.full_name AS recipient_name, r.role AS recipient_role
         FROM staff_messages m
         JOIN users s ON s.id = m.sender_id
         JOIN users r ON r.id = m.recipient_id
         WHERE m.id = ?
           AND (m.sender_id = ? OR m.recipient_id = ?)
         LIMIT 1'
    );
    $openStmt->execute([$openId, $admin['id'], $admin['id']]);
    $openMsg = $openStmt->fetch();

    if ($openMsg && !$openMsg['is_read'] && $openMsg['recipient_id'] === $admin['id']) {
        $pdo->prepare(
            'UPDATE staff_messages SET is_read = 1, read_at = NOW() WHERE id = ?'
        )->execute([$openId]);
        $openMsg['is_read'] = 1;
    }
}

/* ── Load inbox ── */
$inboxStmt = $pdo->prepare(
    'SELECT m.*, u.full_name AS sender_name, u.role AS sender_role
     FROM staff_messages m
     JOIN users u ON u.id = m.sender_id
     WHERE m.recipient_id = ?
     ORDER BY m.created_at DESC'
);
$inboxStmt->execute([$admin['id']]);
$inbox = $inboxStmt->fetchAll();

/* ── Load sent ── */
$sentStmt = $pdo->prepare(
    'SELECT m.*, u.full_name AS recipient_name, u.role AS recipient_role
     FROM staff_messages m
     JOIN users u ON u.id = m.recipient_id
     WHERE m.sender_id = ?
     ORDER BY m.created_at DESC'
);
$sentStmt->execute([$admin['id']]);
$sent = $sentStmt->fetchAll();

/* ── Load all users for recipient dropdown (exclude self) ── */
$usersStmt = $pdo->prepare(
    "SELECT id, full_name, role, section FROM users
     WHERE id != ? AND is_active = 1
     ORDER BY full_name ASC"
);
$usersStmt->execute([$admin['id']]);
$allUsers = $usersStmt->fetchAll();

$unreadCount = count(array_filter($inbox, fn($m) => !$m['is_read']));

$roleLabels = [
    'superadmin'      => 'Super Admin',
    'principal'       => 'Principal',
    'vp_admin'        => 'VP Administration',
    'vp_academics'    => 'VP Academics',
    'vp_general'      => 'VP General Duties',
    'dean'            => 'Dean of Studies',
    'form_teacher'    => 'Form Teacher',
    'subject_teacher' => 'Subject Teacher',
];

/* VAPID public key for JS subscription */
$vapidPublicKey = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';
$vapidReady     = $vapidPublicKey !== '' && $vapidPublicKey !== 'REPLACE_WITH_YOUR_PUBLIC_KEY';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Messages — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  /* ── Push subscribe banner ── */
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

  /* ── Layout ── */
  .msg-layout { display:grid; grid-template-columns:220px 1fr; gap:20px; align-items:start; }
  @media (max-width:700px) { .msg-layout { grid-template-columns:1fr; } }

  .msg-nav { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  .msg-nav__btn {
    display:flex; align-items:center; gap:10px; width:100%;
    padding:13px 16px; border:none; background:none; font-size:13.5px;
    font-family:'DM Sans',sans-serif; cursor:pointer; color:#6b6b80;
    border-bottom:1px solid #f0eef6; text-decoration:none; font-weight:500;
  }
  .msg-nav__btn:last-child { border-bottom:none; }
  .msg-nav__btn--active { background:#f0ecfa; color:#3d1a6e; font-weight:700; }
  .msg-nav__btn:hover:not(.msg-nav__btn--active) { background:#faf9fd; color:#3d1a6e; }
  .msg-nav__badge {
    margin-left:auto; background:#cc3333; color:#fff;
    font-size:10px; font-weight:700; padding:1px 7px; border-radius:20px;
  }

  .msg-panel { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  .msg-panel__header {
    padding:16px 20px; border-bottom:1px solid #f0eef6;
    display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;
  }
  .msg-panel__title { font-size:15px; font-weight:700; color:#3d1a6e; }

  .msg-list { list-style:none; padding:0; margin:0; }
  .msg-item {
    display:flex; gap:12px; align-items:flex-start;
    padding:14px 20px; border-bottom:1px solid #f0eef6;
    cursor:pointer; text-decoration:none; color:inherit;
    transition:background .12s;
  }
  .msg-item:last-child { border-bottom:none; }
  .msg-item:hover { background:#faf9fd; }
  .msg-item--unread { background:#f4f0fb; }
  .msg-item__avatar {
    width:36px; height:36px; border-radius:50%; flex-shrink:0;
    background:linear-gradient(135deg,#3d1a6e,#4a90d9); color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:13px; font-weight:700;
  }
  .msg-item__body  { flex:1; min-width:0; }
  .msg-item__from  { font-size:13px; font-weight:700; color:#1a1a2e; margin-bottom:2px; }
  .msg-item__subject { font-size:13px; color:#3a3850; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .msg-item__subject--unread { font-weight:600; }
  .msg-item__preview { font-size:12px; color:#9b97b0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .msg-item__meta  { font-size:11.5px; color:#9b97b0; text-align:right; flex-shrink:0; }
  .unread-dot { width:8px; height:8px; border-radius:50%; background:#3d1a6e; flex-shrink:0; margin-top:6px; }

  .msg-open { padding:24px; }
  .msg-open__subject { font-size:18px; font-weight:700; color:#3d1a6e; margin-bottom:12px; font-family:'Playfair Display',serif; }
  .msg-open__meta { font-size:12.5px; color:#9b97b0; margin-bottom:20px; display:flex; gap:14px; flex-wrap:wrap; }
  .msg-open__body {
    font-size:14px; color:#1a1a2e; line-height:1.8;
    background:#faf9fd; border:1px solid #f0eef6; border-radius:8px;
    padding:16px 18px; margin-bottom:20px; white-space:pre-wrap;
  }
  .msg-open__actions { display:flex; gap:8px; flex-wrap:wrap; }

  .btn-reply   { background:#3d1a6e; color:#fff; border:none; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
  .btn-reply:hover { background:#5a2d9e; }
  .btn-back    { background:#f0ecfa; color:#3d1a6e; border:none; padding:9px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
  .btn-delete  { background:#ffe6e6; color:#cc3333; border:none; padding:9px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; }

  .compose-form { padding:24px; }
  .form-group   { margin-bottom:16px; }
  .form-label   { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select, .form-textarea {
    width:100%; padding:10px 13px; border:1.5px solid #e2e0ea;
    border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e;
  }
  .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .btn-send { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-send:hover { background:#5a2d9e; }

  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
  .mark-all-btn { font-size:12px; font-weight:600; color:#4a90d9; background:none; border:none; cursor:pointer; padding:0; }
  .mark-all-btn:hover { text-decoration:underline; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'messages'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Messages</h2>
        <p>Internal staff messaging. Send notices to colleagues and check your inbox.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- ── Push notification subscribe nudge ── -->
      <?php if ($vapidReady && !$isSubscribed): ?>
      <div class="push-nudge" id="pushNudge">
        <div class="push-nudge__icon">🔔</div>
        <div class="push-nudge__text">
          <strong>Enable message notifications</strong>
          <span>Get instant push alerts when you receive a message, even when this tab is closed.</span>
        </div>
        <button class="btn-push-sub" onclick="subscribeToMessages()">Enable Notifications</button>
        <button class="btn-push-dismiss" onclick="dismissNudge()" aria-label="Dismiss">✕</button>
      </div>
      <?php elseif ($isSubscribed): ?>
      <div class="push-subscribed-note">
        ✅ <span>Push notifications are enabled — you'll be alerted when new messages arrive.</span>
      </div>
      <?php endif; ?>

      <div id="pushSuccessNote" style="display:none" class="push-subscribed-note">
        ✅ <span>Push notifications enabled! You'll now be alerted when new messages arrive.</span>
      </div>

      <div class="msg-layout">

        <!-- ── Left nav ── -->
        <div>
          <div class="msg-nav">
            <a href="messages.php?view=inbox"
               class="msg-nav__btn <?php echo $view === 'inbox' ? 'msg-nav__btn--active' : ''; ?>">
              📥 Inbox
              <?php if ($unreadCount > 0): ?>
              <span class="msg-nav__badge"><?php echo $unreadCount; ?></span>
              <?php endif; ?>
            </a>
            <a href="messages.php?view=sent"
               class="msg-nav__btn <?php echo $view === 'sent' ? 'msg-nav__btn--active' : ''; ?>">
              📤 Sent
            </a>
            <a href="messages.php?view=compose"
               class="msg-nav__btn <?php echo $view === 'compose' ? 'msg-nav__btn--active' : ''; ?>">
              ✏️ Compose
            </a>
          </div>
        </div>

        <!-- ── Right panel ── -->
        <div class="msg-panel">

          <?php if ($openMsg): ?>
          <!-- ════ OPEN MESSAGE VIEW ════ -->
          <div class="msg-panel__header">
            <span class="msg-panel__title">Message</span>
            <a href="messages.php?view=<?php echo $openMsg['sender_id'] === $admin['id'] ? 'sent' : 'inbox'; ?>"
               class="btn-back">← Back</a>
          </div>
          <div class="msg-open">
            <div class="msg-open__subject"><?php echo htmlspecialchars($openMsg['subject']); ?></div>
            <div class="msg-open__meta">
              <span>
                <strong>From:</strong>
                <?php echo htmlspecialchars($openMsg['sender_name']); ?>
                (<?php echo htmlspecialchars($roleLabels[$openMsg['sender_role']] ?? $openMsg['sender_role']); ?>)
              </span>
              <span>
                <strong>To:</strong>
                <?php echo htmlspecialchars($openMsg['recipient_name']); ?>
              </span>
              <span><?php echo date('d M Y, g:ia', strtotime($openMsg['created_at'])); ?></span>
            </div>
            <div class="msg-open__body"><?php echo htmlspecialchars($openMsg['body']); ?></div>
            <div class="msg-open__actions">
              <?php if ($openMsg['sender_id'] !== $admin['id']): ?>
              <a href="messages.php?view=compose&reply_to=<?php echo $openMsg['sender_id']; ?>&subject=<?php echo urlencode('Re: ' . $openMsg['subject']); ?>"
                 class="btn-reply">↩ Reply</a>
              <?php endif; ?>
              <a href="messages.php?view=<?php echo $openMsg['sender_id'] === $admin['id'] ? 'sent' : 'inbox'; ?>"
                 class="btn-back">← Back</a>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete this message?')">
                <input type="hidden" name="action"  value="delete"/>
                <input type="hidden" name="msg_id"  value="<?php echo $openMsg['id']; ?>"/>
                <button type="submit" class="btn-delete">Delete</button>
              </form>
            </div>
          </div>

          <?php elseif ($view === 'compose'): ?>
          <!-- ════ COMPOSE VIEW ════ -->
          <div class="msg-panel__header">
            <span class="msg-panel__title">New Message</span>
          </div>
          <div class="compose-form">
            <form method="POST">
              <input type="hidden" name="action" value="send"/>
              <div class="form-group">
                <label class="form-label">To *</label>
                <select class="form-select" name="recipient_id" required>
                  <option value="">Select recipient…</option>
                  <?php foreach ($allUsers as $u): ?>
                  <option value="<?php echo $u['id']; ?>"
                    <?php echo (int) ($_GET['reply_to'] ?? 0) === $u['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($u['full_name']); ?>
                    — <?php echo htmlspecialchars($roleLabels[$u['role']] ?? $u['role']); ?>
                    <?php echo $u['section'] ? ' (' . strtoupper($u['section']) . ')' : ''; ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Subject *</label>
                <input type="text" class="form-input" name="subject" maxlength="255" required
                       value="<?php echo htmlspecialchars($_GET['subject'] ?? ''); ?>"
                       placeholder="e.g. Results entry reminder — JSS 2A"/>
              </div>
              <div class="form-group">
                <label class="form-label">Message *</label>
                <textarea class="form-textarea" name="body" rows="8" required
                          placeholder="Write your message here…"></textarea>
              </div>
              <button type="submit" class="btn-send">Send Message →</button>
            </form>
          </div>

          <?php elseif ($view === 'sent'): ?>
          <!-- ════ SENT VIEW ════ -->
          <div class="msg-panel__header">
            <span class="msg-panel__title">Sent Messages</span>
            <a href="messages.php?view=compose" class="btn-reply">+ New Message</a>
          </div>
          <?php if (empty($sent)): ?>
          <div class="empty-state">No sent messages yet.</div>
          <?php else: ?>
          <ul class="msg-list">
            <?php foreach ($sent as $m): ?>
            <li>
              <a href="messages.php?open=<?php echo $m['id']; ?>&view=sent" class="msg-item">
                <div class="msg-item__avatar">
                  <?php echo strtoupper(substr($m['recipient_name'], 0, 1)); ?>
                </div>
                <div class="msg-item__body">
                  <div class="msg-item__from">To: <?php echo htmlspecialchars($m['recipient_name']); ?></div>
                  <div class="msg-item__subject"><?php echo htmlspecialchars($m['subject']); ?></div>
                  <div class="msg-item__preview">
                    <?php echo htmlspecialchars(mb_substr($m['body'], 0, 60)) . '…'; ?>
                  </div>
                </div>
                <div class="msg-item__meta">
                  <?php echo date('d M', strtotime($m['created_at'])); ?><br/>
                  <small><?php echo $m['is_read'] ? '✓ Read' : 'Unread'; ?></small>
                </div>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <?php else: ?>
          <!-- ════ INBOX VIEW (default) ════ -->
          <div class="msg-panel__header">
            <span class="msg-panel__title">
              Inbox
              <?php if ($unreadCount > 0): ?>
              <span style="background:#3d1a6e;color:#fff;font-size:11px;padding:2px 8px;border-radius:20px;margin-left:6px">
                <?php echo $unreadCount; ?> unread
              </span>
              <?php endif; ?>
            </span>
            <div style="display:flex;gap:10px;align-items:center">
              <?php if ($unreadCount > 0): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="mark_all_read"/>
                <button type="submit" class="mark-all-btn">Mark all read</button>
              </form>
              <?php endif; ?>
              <a href="messages.php?view=compose" class="btn-reply">+ New Message</a>
            </div>
          </div>
          <?php if (empty($inbox)): ?>
          <div class="empty-state">
            Your inbox is empty.<br/>
            <a href="messages.php?view=compose"
               style="color:#4a90d9;margin-top:8px;display:inline-block">
              Send the first message →
            </a>
          </div>
          <?php else: ?>
          <ul class="msg-list">
            <?php foreach ($inbox as $m): ?>
            <li>
              <a href="messages.php?open=<?php echo $m['id']; ?>&view=inbox"
                 class="msg-item <?php echo !$m['is_read'] ? 'msg-item--unread' : ''; ?>">
                <?php if (!$m['is_read']): ?>
                <div class="unread-dot"></div>
                <?php endif; ?>
                <div class="msg-item__avatar">
                  <?php echo strtoupper(substr($m['sender_name'], 0, 1)); ?>
                </div>
                <div class="msg-item__body">
                  <div class="msg-item__from"><?php echo htmlspecialchars($m['sender_name']); ?></div>
                  <div class="msg-item__subject <?php echo !$m['is_read'] ? 'msg-item__subject--unread' : ''; ?>">
                    <?php echo htmlspecialchars($m['subject']); ?>
                  </div>
                  <div class="msg-item__preview">
                    <?php echo htmlspecialchars(mb_substr($m['body'], 0, 60)) . '…'; ?>
                  </div>
                </div>
                <div class="msg-item__meta">
                  <?php echo date('d M', strtotime($m['created_at'])); ?><br/>
                  <?php echo date('g:ia', strtotime($m['created_at'])); ?>
                </div>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <?php endif; ?>

        </div><!-- /.msg-panel -->
      </div><!-- /.msg-layout -->

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    /* ── Push subscription ── */
    const VAPID_PUBLIC_KEY = <?php echo json_encode($vapidPublicKey); ?>;

    function urlBase64ToUint8Array(base64String) {
      const padding = '='.repeat((4 - base64String.length % 4) % 4);
      const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      const raw     = window.atob(base64);
      return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
    }

    async function subscribeToMessages() {
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

        const registration  = await navigator.serviceWorker.ready;
        const subscription  = await registration.pushManager.subscribe({
          userVisibleOnly:      true,
          applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });

        const response = await fetch('messages.php', {
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
        console.error('[IHS push] Subscribe error:', err);
        alert('Something went wrong. Please try again.');
      }
    }

    function dismissNudge() {
      const nudge = document.getElementById('pushNudge');
      if (nudge) nudge.style.display = 'none';
      sessionStorage.setItem('push-nudge-dismissed', '1');
    }

    /* Hide nudge if already dismissed this session */
    if (sessionStorage.getItem('push-nudge-dismissed')) {
      const nudge = document.getElementById('pushNudge');
      if (nudge) nudge.style.display = 'none';
    }
  </script>

</body>
</html>
