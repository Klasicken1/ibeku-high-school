<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER MESSAGES (PORTAL)
   File: public/portal-corps/messages.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

$corpsMember = requireCorpsLogin();
$pdo         = getDB();

$message     = '';
$messageType = '';

/* ── Reply to a message ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parentId = (int) ($_POST['parent_id'] ?? 0);
    $body     = trim($_POST['body'] ?? '');

    if ($parentId > 0 && $body !== '') {
        /* Verify parent belongs to this corps member */
        $check = $pdo->prepare(
            'SELECT id, subject FROM corps_messages WHERE id = ? AND corps_member_id = ? LIMIT 1'
        );
        $check->execute([$parentId, $corpsMember['id']]);
        $parent = $check->fetch(PDO::FETCH_ASSOC);

        if ($parent) {
            $pdo->prepare(
                "INSERT INTO corps_messages
                    (corps_member_id, sender_type, sender_id, subject, body, parent_id)
                 VALUES (?, 'corps', NULL, ?, ?, ?)"
            )->execute([
                $corpsMember['id'],
                'Re: ' . $parent['subject'],
                $body,
                $parentId,
            ]);
            $message = 'Reply sent.'; $messageType = 'success';
        }
    }
}

/* ── Open single message ── */
$openMsg = null;
if (!empty($_GET['open'])) {
    $openId   = (int) $_GET['open'];
    $openStmt = $pdo->prepare(
        "SELECT m.*, u.full_name AS admin_name
         FROM corps_messages m
         LEFT JOIN users u ON u.id = m.sender_id
         WHERE m.id = ? AND m.corps_member_id = ?
         LIMIT 1"
    );
    $openStmt->execute([$openId, $corpsMember['id']]);
    $openMsg = $openStmt->fetch(PDO::FETCH_ASSOC);

    if ($openMsg && !$openMsg['is_read'] && $openMsg['sender_type'] === 'admin') {
        $pdo->prepare(
            'UPDATE corps_messages SET is_read = 1, read_at = NOW() WHERE id = ?'
        )->execute([$openId]);
    }

    /* Load thread replies */
    $threadStmt = $pdo->prepare(
        "SELECT m.*, u.full_name AS admin_name
         FROM corps_messages m
         LEFT JOIN users u ON u.id = m.sender_id
         WHERE (m.id = ? OR m.parent_id = ?) AND m.corps_member_id = ?
         ORDER BY m.created_at ASC"
    );
    $threadStmt->execute([$openId, $openId, $corpsMember['id']]);
    $thread = $threadStmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ── Load inbox (top-level messages only) ── */
$inboxStmt = $pdo->prepare(
    "SELECT m.*, u.full_name AS admin_name
     FROM corps_messages m
     LEFT JOIN users u ON u.id = m.sender_id
     WHERE m.corps_member_id = ? AND m.parent_id IS NULL
     ORDER BY m.created_at DESC"
);
$inboxStmt->execute([$corpsMember['id']]);
$inbox = $inboxStmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = 0;
foreach ($inbox as $msg) {
    if ($msg['sender_type'] === 'admin' && !$msg['is_read']) $unreadCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Messages - Corps Portal - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/corps-portal.css"/>
</head>
<body>
<?php include dirname(__DIR__, 2) . '/src/includes/corps-nav.php'; ?>
<main class="corps-main">
  <div class="corps-inner">

    <div class="page-hero">
      <h1 class="page-hero__title">
        Messages
        <?php if ($unreadCount > 0): ?>
        <span style="background:#3d1a6e;color:#fff;font-size:13px;padding:2px 10px;border-radius:20px;margin-left:8px;vertical-align:middle">
          <?php echo $unreadCount; ?> unread
        </span>
        <?php endif; ?>
      </h1>
      <p class="page-hero__sub">Messages from school administration.</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (empty($inbox) && !$openMsg): ?>
    <div class="empty-portal">
      <div class="empty-portal__icon">&#128172;</div>
      <h2>No messages yet</h2>
      <p>Messages from school administration will appear here.</p>
    </div>
    <?php else: ?>

    <div class="msg-layout">
      <!-- List -->
      <div class="msg-list-panel">
        <div class="msg-list-panel__header">Inbox</div>
        <?php if (empty($inbox)): ?>
        <div style="padding:20px;font-size:13px;color:#9b97b0;text-align:center">No messages yet.</div>
        <?php else: ?>
        <?php foreach ($inbox as $msg): ?>
        <a href="messages.php?open=<?php echo $msg['id']; ?>"
           class="msg-item <?php echo (!$msg['is_read'] && $msg['sender_type'] === 'admin') ? 'msg-item--unread' : ''; ?>">
          <?php if (!$msg['is_read'] && $msg['sender_type'] === 'admin'): ?>
          <div class="unread-dot"></div>
          <?php endif; ?>
          <div class="msg-item__avatar">
            <?php echo $msg['sender_type'] === 'admin' ? strtoupper(substr($msg['admin_name'] ?? 'A', 0, 1)) : 'Y'; ?>
          </div>
          <div class="msg-item__body">
            <div class="msg-item__from">
              <?php echo $msg['sender_type'] === 'admin' ? htmlspecialchars($msg['admin_name'] ?? 'Admin') : 'You'; ?>
            </div>
            <div class="msg-item__subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
          </div>
          <div class="msg-item__date"><?php echo date('d M', strtotime($msg['created_at'])); ?></div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Open message / thread -->
      <div class="msg-panel">
        <?php if ($openMsg): ?>
        <div class="msg-open">
          <div class="msg-open__subject"><?php echo htmlspecialchars($thread[0]['subject'] ?? $openMsg['subject']); ?></div>

          <!-- Thread -->
          <?php foreach ($thread as $t): ?>
          <div style="margin-bottom:16px;<?php echo $t['sender_type'] === 'corps' ? 'padding-left:20px' : ''; ?>">
            <div class="msg-open__meta">
              <span><strong><?php echo $t['sender_type'] === 'admin' ? htmlspecialchars($t['admin_name'] ?? 'Admin') : 'You'; ?></strong></span>
              <span><?php echo date('d M Y, g:ia', strtotime($t['created_at'])); ?></span>
            </div>
            <div class="msg-open__body" style="<?php echo $t['sender_type'] === 'corps' ? 'background:#f0ecfa;border-color:#d8d0ee' : ''; ?>">
              <?php echo htmlspecialchars($t['body']); ?>
            </div>
          </div>
          <?php endforeach; ?>

          <!-- Reply form -->
          <div class="reply-form">
            <form method="POST">
              <input type="hidden" name="parent_id" value="<?php echo $openMsg['id']; ?>"/>
              <div class="form-group">
                <label class="form-label">Reply</label>
                <textarea name="body" class="form-textarea" rows="4"
                          placeholder="Write your reply..." required></textarea>
              </div>
              <button type="submit" class="btn-reply">Send Reply</button>
            </form>
          </div>
        </div>
        <?php else: ?>
        <div style="padding:50px 20px;text-align:center;color:#9b97b0;font-size:13.5px">
          Select a message to read it.
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php endif; ?>
  </div>
</main>
</body>
</html>