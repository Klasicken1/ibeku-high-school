<?php
/* ============================================================
   IBEKU HIGH SCHOOL - ADMIN CORPS MESSAGES
   File: public/admin/corps-messages.php
   Send messages to corps members + push notifications
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/config/vapid.php';
require_once dirname(__DIR__, 2) . '/src/includes/push-helper.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'dean']);

$admin = currentAdmin();
$pdo   = getDB();

/* Pre-select a corps member if coming from corps list */
$preselectedId = (int) ($_GET['id'] ?? 0);

$message = ''; $messageType = '';

/* ── Handle JSON push subscription from corps portal ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    if (!empty($input['endpoint']) && !empty($input['keys']['auth']) && !empty($input['keys']['p256dh']) && !empty($input['corps_id'])) {
        try {
            ensurePushUserIdColumn($pdo);
            $pdo->prepare(
                "INSERT INTO push_subscriptions (endpoint, auth, p256dh, corps_id)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE auth=VALUES(auth), p256dh=VALUES(p256dh), corps_id=VALUES(corps_id)"
            )->execute([$input['endpoint'], $input['keys']['auth'], $input['keys']['p256dh'], (int)$input['corps_id']]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false]);
        }
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

/* ── Send message ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action     = $_POST['action'];
    $subject    = trim($_POST['subject'] ?? '');
    $body       = trim($_POST['body']    ?? '');
    $recipientIds = array_map('intval', $_POST['recipient_ids'] ?? []);

    if ($action === 'send_message') {
        if ($subject === '') { $message = 'Subject is required.'; $messageType = 'error'; }
        elseif ($body === '') { $message = 'Message body is required.'; $messageType = 'error'; }
        elseif (empty($recipientIds)) { $message = 'Please select at least one recipient.'; $messageType = 'error'; }
        else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO corps_messages
                        (corps_member_id, sender_type, sender_id, subject, body)
                     VALUES (?, 'admin', ?, ?, ?)"
                );
                $sent = 0;
                foreach ($recipientIds as $rid) {
                    $stmt->execute([$rid, $admin['id'], $subject, $body]);
                    $sent++;

                    /* Push notification to corps member */
                    try {
                        $subStmt = $pdo->prepare(
                            'SELECT * FROM push_subscriptions WHERE corps_id = ?'
                        );
                        $subStmt->execute([$rid]);
                        $subs = $subStmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($subs as $sub) {
                            sendWebPushNotification(
                                $sub,
                                'New message from ' . $admin['full_name'],
                                $subject . ' — ' . mb_substr($body, 0, 80),
                                (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_PATH . 'portal-corps/messages.php',
                                VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY
                            );
                        }
                    } catch (Exception $e) { /* push optional */ }
                }
                $message = 'Message sent to ' . $sent . ' corps member(s).';
                $messageType = 'success';
            } catch (PDOException $e) {
                error_log('IHS corps-messages: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

/* ── Load thread if viewing a specific message ── */
$openMsg = null; $thread = [];
if (!empty($_GET['open'])) {
    $openId   = (int) $_GET['open'];
    $openStmt = $pdo->prepare(
        "SELECT m.*, c.full_name AS corps_name, c.state_code
         FROM corps_messages m
         JOIN corps_members c ON c.id = m.corps_member_id
         WHERE m.id = ? LIMIT 1"
    );
    $openStmt->execute([$openId]);
    $openMsg = $openStmt->fetch(PDO::FETCH_ASSOC);

    if ($openMsg) {
        $tStmt = $pdo->prepare(
            "SELECT m.*, c.full_name AS corps_name, u.full_name AS admin_name
             FROM corps_messages m
             JOIN corps_members c ON c.id = m.corps_member_id
             LEFT JOIN users u ON u.id = m.sender_id
             WHERE (m.id = ? OR m.parent_id = ?)
             ORDER BY m.created_at ASC"
        );
        $tStmt->execute([$openMsg['parent_id'] ?? $openId, $openMsg['parent_id'] ?? $openId]);
        $thread = $tStmt->fetchAll(PDO::FETCH_ASSOC);

        /* Mark read */
        $pdo->prepare(
            "UPDATE corps_messages SET is_read=1, read_at=NOW()
             WHERE id=? AND sender_type='corps'"
        )->execute([$openId]);
    }
}

/* ── Load all message threads ── */
$threadsStmt = $pdo->query(
    "SELECT m.*, c.full_name AS corps_name, c.state_code
     FROM corps_messages m
     JOIN corps_members c ON c.id = m.corps_member_id
     WHERE m.parent_id IS NULL
     ORDER BY m.created_at DESC
     LIMIT 100"
);
$threads = $threadsStmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Load active corps members for compose ── */
$membersStmt = $pdo->query(
    "SELECT id, state_code, full_name, section FROM corps_members
     WHERE status='active' ORDER BY full_name ASC"
);
$allMembers = $membersStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Corps Messages - Admin - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/admin-layout.css"/>
  <style>
    .page-grid{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start}
    @media(max-width:900px){.page-grid{grid-template-columns:1fr}}
    .msg-list-card{background:#fff;border:1px solid #e8e6f0;border-radius:14px;overflow:hidden}
    .card-header{padding:14px 18px;font-size:14px;font-weight:700;color:#3d1a6e;border-bottom:1px solid #f0eef6;display:flex;justify-content:space-between;align-items:center}
    .msg-item{display:flex;gap:10px;align-items:flex-start;padding:12px 18px;border-bottom:1px solid #f0eef6;text-decoration:none;color:#1a1a2e;transition:background .12s;cursor:pointer}
    .msg-item:last-child{border-bottom:none}
    .msg-item:hover{background:#faf9fd}
    .msg-item--unread{background:#f4f0fb}
    .msg-item__avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#3d1a6e,#4a90d9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
    .msg-item__body{flex:1;min-width:0}
    .msg-item__name{font-size:12.5px;font-weight:700;color:#1a1a2e;margin-bottom:2px}
    .msg-item__subject{font-size:12.5px;color:#3a3850;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .msg-item__date{font-size:11px;color:#9b97b0;white-space:nowrap}
    .unread-dot{width:7px;height:7px;border-radius:50%;background:#3d1a6e;flex-shrink:0;margin-top:5px}
    .main-card{background:#fff;border:1px solid #e8e6f0;border-radius:14px;overflow:hidden}
    .thread-msg{margin-bottom:16px}
    .thread-msg__meta{font-size:12px;color:#9b97b0;margin-bottom:6px;display:flex;gap:12px}
    .thread-msg__body{font-size:13.5px;color:#1a1a2e;line-height:1.8;background:#faf9fd;border:1px solid #f0eef6;border-radius:8px;padding:14px 16px;white-space:pre-wrap}
    .thread-msg__body--corps{background:#f0ecfa;border-color:#d8d0ee}
    .compose-form{padding:22px}
    .compose-form__title{font-size:14px;font-weight:700;color:#3d1a6e;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid #f0eef6}
    .form-group{margin-bottom:14px}
    .form-label{display:block;font-size:12px;font-weight:600;color:#3d1a6e;margin-bottom:5px;text-transform:uppercase;letter-spacing:.03em}
    .form-input,.form-textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e0ea;border-radius:8px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#1a1a2e}
    .form-input:focus,.form-textarea:focus{outline:none;border-color:#4a90d9}
    .member-select{max-height:200px;overflow-y:auto;border:1.5px solid #e2e0ea;border-radius:8px}
    .member-select-row{display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid #f0eef6;font-size:13px;cursor:pointer;transition:background .1s}
    .member-select-row:last-child{border-bottom:none}
    .member-select-row:hover{background:#faf9fd}
    .member-select-row input{accent-color:#3d1a6e}
    .btn-send{background:#3d1a6e;color:#fff;border:none;padding:10px 24px;border-radius:8px;font-size:13.5px;font-weight:700;cursor:pointer;width:100%;margin-top:4px}
    .btn-send:hover{background:#5a2d9e}
    .sel-all{font-size:12px;font-weight:600;color:#4a90d9;background:none;border:none;cursor:pointer;padding:0;margin-bottom:8px}
    .reply-section{padding:20px;border-top:1px solid #f0eef6}
    .reply-section__title{font-size:13px;font-weight:700;color:#3d1a6e;margin-bottom:10px}
    .empty-panel{padding:50px 20px;text-align:center;color:#9b97b0;font-size:13.5px}
    .view-toggle{display:flex;gap:8px;margin-bottom:0}
    .view-btn{padding:6px 14px;border-radius:6px;font-size:12.5px;font-weight:600;text-decoration:none;border:none;cursor:pointer;background:#f0ecfa;color:#3d1a6e}
    .view-btn--active{background:#3d1a6e;color:#fff}
  </style>
</head>
<body>
<?php renderAdminSidebar($admin, 'corps-messages'); ?>
<div class="admin-content">
  <div class="admin-content__inner">

    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px">
      <div>
        <h2>Corps Messages</h2>
        <p>Send messages to corps members and manage conversations.</p>
      </div>
      <div class="view-toggle">
        <button class="view-btn <?php echo !isset($_GET['open']) && !isset($_GET['compose']) ? 'view-btn--active' : ''; ?>"
                onclick="showView('inbox')">Inbox</button>
        <button class="view-btn <?php echo isset($_GET['compose']) ? 'view-btn--active' : ''; ?>"
                onclick="showView('compose')">Compose</button>
      </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="page-grid">

      <!-- Thread list -->
      <div class="msg-list-card">
        <div class="card-header">All Conversations</div>
        <?php if (empty($threads)): ?>
        <div style="padding:30px 20px;text-align:center;color:#9b97b0;font-size:13px">No messages yet.</div>
        <?php else: ?>
        <?php foreach ($threads as $t): ?>
        <a href="corps-messages.php?open=<?php echo $t['id']; ?>"
           class="msg-item <?php echo $t['sender_type'] === 'corps' && !$t['is_read'] ? 'msg-item--unread' : ''; ?>">
          <?php if ($t['sender_type'] === 'corps' && !$t['is_read']): ?>
          <div class="unread-dot"></div>
          <?php endif; ?>
          <div class="msg-item__avatar"><?php echo strtoupper(substr($t['corps_name'], 0, 1)); ?></div>
          <div class="msg-item__body">
            <div class="msg-item__name"><?php echo htmlspecialchars($t['corps_name']); ?></div>
            <div class="msg-item__subject"><?php echo htmlspecialchars($t['subject']); ?></div>
          </div>
          <div class="msg-item__date"><?php echo date('d M', strtotime($t['created_at'])); ?></div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Main panel -->
      <div class="main-card">

        <?php if ($openMsg): ?>
        <!-- Thread view -->
        <div style="padding:22px">
          <div style="font-family:'Playfair Display',serif;font-size:1.3rem;color:#3d1a6e;font-weight:700;margin-bottom:6px">
            <?php echo htmlspecialchars($thread[0]['subject'] ?? $openMsg['subject']); ?>
          </div>
          <div style="font-size:12.5px;color:#9b97b0;margin-bottom:20px">
            Conversation with <strong><?php echo htmlspecialchars($openMsg['corps_name']); ?></strong>
            (<?php echo htmlspecialchars($openMsg['state_code']); ?>)
          </div>
          <?php foreach ($thread as $t): ?>
          <div class="thread-msg">
            <div class="thread-msg__meta">
              <span><strong><?php echo $t['sender_type'] === 'admin' ? htmlspecialchars($t['admin_name'] ?? 'Admin') : htmlspecialchars($t['corps_name']); ?></strong></span>
              <span><?php echo date('d M Y, g:ia', strtotime($t['created_at'])); ?></span>
            </div>
            <div class="thread-msg__body <?php echo $t['sender_type'] === 'corps' ? 'thread-msg__body--corps' : ''; ?>">
              <?php echo htmlspecialchars($t['body']); ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <!-- Reply -->
        <div class="reply-section">
          <div class="reply-section__title">Reply</div>
          <form method="POST">
            <input type="hidden" name="action" value="send_message"/>
            <input type="hidden" name="recipient_ids[]" value="<?php echo $openMsg['corps_member_id']; ?>"/>
            <input type="hidden" name="subject" value="Re: <?php echo htmlspecialchars($openMsg['subject']); ?>"/>
            <textarea class="form-textarea" name="body" rows="4" placeholder="Write your reply..." required></textarea>
            <button type="submit" class="btn-send" style="margin-top:10px">Send Reply</button>
          </form>
        </div>

        <?php else: ?>
        <!-- Compose -->
        <div class="compose-form" id="composeForm">
          <div class="compose-form__title">New Message</div>
          <form method="POST">
            <input type="hidden" name="action" value="send_message"/>
            <div class="form-group">
              <label class="form-label">Recipients *</label>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <button type="button" class="sel-all" onclick="toggleAll(true)">Select All</button>
                <button type="button" class="sel-all" onclick="toggleAll(false)" style="color:#9b97b0">Deselect All</button>
              </div>
              <div class="member-select">
                <?php foreach ($allMembers as $m): ?>
                <label class="member-select-row">
                  <input type="checkbox" name="recipient_ids[]" value="<?php echo $m['id']; ?>"
                         <?php echo $m['id'] === $preselectedId ? 'checked' : ''; ?> class="member-cb"/>
                  <span><?php echo htmlspecialchars($m['full_name']); ?></span>
                  <span style="font-size:11.5px;color:#9b97b0;margin-left:auto"><?php echo htmlspecialchars($m['state_code']); ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Subject *</label>
              <input type="text" class="form-input" name="subject" maxlength="255" required
                     placeholder="e.g. Clearance update for October"/>
            </div>
            <div class="form-group">
              <label class="form-label">Message *</label>
              <textarea class="form-textarea" name="body" rows="6" required
                        placeholder="Write your message here..."></textarea>
            </div>
            <button type="submit" class="btn-send">Send Message</button>
          </form>
        </div>
        <?php if (!$openMsg && !isset($_GET['compose'])): ?>
        <div class="empty-panel" id="inboxHint">Select a conversation or compose a new message.</div>
        <?php endif; ?>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>
<script>
function toggleAll(checked){
  document.querySelectorAll('.member-cb').forEach(function(cb){cb.checked=checked});
}
function showView(v){
  if(v==='compose') window.location.href='corps-messages.php?compose=1';
  else window.location.href='corps-messages.php';
}
</script>
<script src="../assets/js/admin.js"></script>
</body>
</html>