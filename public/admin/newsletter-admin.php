<?php
/* ============================================================
   IBEKU HIGH SCHOOL — NEWSLETTER SUBSCRIBERS
   File: public/admin/newsletter-admin.php

   Accessible to: superadmin, principal, vp_general
   View all newsletter subscribers, export list, delete
   individual subscribers, and send a broadcast email.
   Uses PHP mail() — works on cPanel without extra config.
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

$message     = '';
$messageType = '';

/* ── Handle actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'delete') {
        $subId = (int) ($_POST['subscriber_id'] ?? 0);
        if ($subId > 0) {
            try {
                $pdo->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$subId]);
                $message = 'Subscriber removed.'; $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }

    } elseif ($action === 'delete_selected') {
        $ids = array_map('intval', $_POST['selected_ids'] ?? []);
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("DELETE FROM subscribers WHERE id IN ($placeholders)")->execute($ids);
                $message = count($ids) . ' subscriber(s) removed.'; $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }

    } elseif ($action === 'broadcast') {
        $subject  = trim($_POST['email_subject'] ?? '');
        $body     = trim($_POST['email_body']    ?? '');

        if ($subject === '') {
            $message = 'Email subject is required.'; $messageType = 'error';
        } elseif ($body === '') {
            $message = 'Email body is required.'; $messageType = 'error';
        } else {
            try {
                $subscribers = $pdo->query(
                    "SELECT email FROM subscribers WHERE is_active = 1"
                )->fetchAll(PDO::FETCH_COLUMN);

                $sentCount = 0;
                $fromEmail = $_site['school_email'] ?? 'noreply@ibekuhighschool.edu.ng';
                $fromName  = $_site['school_name']  ?? 'Ibeku High School';

                $headers = implode("\r\n", [
                    'From: ' . $fromName . ' <' . $fromEmail . '>',
                    'Reply-To: ' . $fromEmail,
                    'MIME-Version: 1.0',
                    'Content-Type: text/html; charset=UTF-8',
                    'X-Mailer: PHP/' . PHP_VERSION,
                ]);

                $htmlBody = buildEmailHtml($subject, $body, $fromName);

                foreach ($subscribers as $email) {
                    if (mail($email, $subject, $htmlBody, $headers)) {
                        $sentCount++;
                    }
                }

                /* Log the broadcast */
                try {
                    $pdo->exec(
                        "CREATE TABLE IF NOT EXISTS newsletter_log (
                            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                            sender_id   INT UNSIGNED NOT NULL,
                            subject     VARCHAR(255) NOT NULL,
                            sent_to     INT UNSIGNED NOT NULL DEFAULT 0,
                            created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                    $pdo->prepare(
                        'INSERT INTO newsletter_log (sender_id, subject, sent_to) VALUES (?,?,?)'
                    )->execute([$admin['id'], $subject, $sentCount]);
                } catch (PDOException $e) { /* log table optional */ }

                $message = "Email sent to {$sentCount} subscriber(s).";
                $messageType = $sentCount > 0 ? 'success' : 'error';

            } catch (PDOException $e) {
                error_log('IHS newsletter broadcast error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

/* ── Export as CSV ── */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    requireRole(['superadmin', 'principal', 'vp_general']);
    $rows = $pdo->query(
        "SELECT email, subscribed_at FROM subscribers
         WHERE is_active = 1 ORDER BY subscribed_at DESC"
    )->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ihs-subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Subscribed At']);
    foreach ($rows as $row) {
        fputcsv($out, [$row['email'], $row['subscribed_at']]);
    }
    fclose($out);
    exit;
}

/* ── Load subscribers ── */
$filterStatus = $_GET['status'] ?? 'active';
$searchTerm   = trim($_GET['search'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($filterStatus === 'active') {
    $where[]  = 'is_active = 1';
} elseif ($filterStatus === 'inactive') {
    $where[]  = 'is_active = 0';
}
if ($searchTerm) {
    $where[]  = 'email LIKE ?';
    $params[] = '%' . $searchTerm . '%';
}
$whereSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE $whereSQL");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

$subStmt = $pdo->prepare(
    "SELECT * FROM subscribers WHERE $whereSQL
     ORDER BY subscribed_at DESC LIMIT ? OFFSET ?"
);
$subStmt->execute([...$params, $perPage, $offset]);
$subscribers = $subStmt->fetchAll();

/* ── Stats ── */
$activeCount   = (int) $pdo->query("SELECT COUNT(*) FROM subscribers WHERE is_active = 1")->fetchColumn();
$inactiveCount = (int) $pdo->query("SELECT COUNT(*) FROM subscribers WHERE is_active = 0")->fetchColumn();

/* ── Broadcast log ── */
$broadcastLog = [];
try {
    $logStmt = $pdo->prepare(
        "SELECT l.*, u.full_name AS sender_name
         FROM newsletter_log l
         JOIN users u ON u.id = l.sender_id
         ORDER BY l.created_at DESC LIMIT 10"
    );
    $logStmt->execute();
    $broadcastLog = $logStmt->fetchAll();
} catch (PDOException $e) { /* log table may not exist yet */ }

/**
 * Build a simple branded HTML email body.
 */
function buildEmailHtml(string $subject, string $body, string $schoolName): string {
    $safeBody = nl2br(htmlspecialchars($body));
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background:#f4f3f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f3f9;padding:32px 0;">
    <tr><td align="center">
      <table width="580" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;max-width:580px;width:100%;">

        <!-- Header -->
        <tr>
          <td style="background:#3d1a6e;padding:28px 32px;text-align:center;">
            <div style="background:rgba(255,255,255,.12);display:inline-block;padding:8px 20px;border-radius:8px;font-size:22px;font-weight:700;color:#fff;font-family:Georgia,serif;letter-spacing:2px;">IHS</div>
            <p style="color:rgba(255,255,255,.8);font-size:13px;margin:8px 0 0;">{$schoolName}</p>
          </td>
        </tr>

        <!-- Subject -->
        <tr>
          <td style="padding:28px 32px 8px;">
            <h1 style="margin:0;font-size:22px;color:#3d1a6e;font-family:Georgia,serif;line-height:1.3;">{$subject}</h1>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:12px 32px 28px;font-size:15px;color:#3a3850;line-height:1.8;">
            {$safeBody}
          </td>
        </tr>

        <!-- Divider -->
        <tr>
          <td style="padding:0 32px;">
            <hr style="border:none;border-top:1px solid #f0eef6;"/>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:20px 32px;text-align:center;font-size:12px;color:#9b97b0;line-height:1.6;">
            <p style="margin:0 0 6px;">{$schoolName}, Umuahia, Abia State</p>
            <p style="margin:0;">You are receiving this email because you subscribed to school updates on our website.<br/>
            <a href="<?php echo $protocol . '://' . $_SERVER['HTTP_HOST'] . BASE_PATH . 'unsubscribe.php?email=' . rawurlencode($email); ?>" style="color:#4a90d9">Unsubscribe</a></p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Newsletter — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .nl-grid { display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start; }
  @media (max-width:900px) { .nl-grid { grid-template-columns:1fr; } }

  .stats-row { display:flex; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
  .stat-pill { background:#fff; border:1px solid #e8e6f0; border-radius:10px; padding:10px 18px; }
  .stat-pill strong { display:block; font-size:18px; color:#3d1a6e; }
  .stat-pill span   { font-size:12.5px; color:#6b6b80; }

  .toolbar { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center; }
  .filter-tab { padding:7px 16px; border-radius:20px; font-size:12.5px; font-weight:600; text-decoration:none; color:#6b6b80; background:#fff; border:1px solid #e8e6f0; }
  .filter-tab--active { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }
  .search-box { padding:7px 12px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:200px; }
  .search-box:focus { outline:none; border-color:#4a90d9; }
  .btn-export { background:#4a90d9; color:#fff; border:none; padding:7px 16px; border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer; text-decoration:none; }
  .btn-export:hover { background:#3a7dc4; }
  .btn-delete-sel { background:#ffe6e6; color:#cc3333; border:1px solid #ffcccc; padding:7px 14px; border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer; }

  .sub-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.sub-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.sub-table th { background:#3d1a6e; color:#fff; padding:10px 14px; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
  table.sub-table td { padding:10px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.sub-table tr:last-child td { border-bottom:none; }
  table.sub-table tr:hover td { background:#faf9fd; }
  table.sub-table input[type=checkbox] { accent-color:#3d1a6e; width:15px; height:15px; }

  .badge--active { background:#e6f9ed; color:#1a7a3a; display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; }
  .action-btn { font-size:11.5px; font-weight:600; padding:4px 10px; border-radius:6px; border:none; cursor:pointer; }
  .action-btn--delete { background:#ffe6e6; color:#cc3333; }

  .pagination { display:flex; gap:6px; justify-content:center; margin-top:16px; flex-wrap:wrap; }
  .pagination a, .pagination span { padding:6px 12px; border-radius:7px; font-size:13px; font-weight:600; border:1px solid #e8e6f0; text-decoration:none; color:#3d1a6e; background:#fff; }
  .pagination a:hover { background:#f0ecfa; }
  .pagination .current { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }

  /* Compose card */
  .compose-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:22px; position:sticky; top:20px; }
  .compose-card__title { font-size:14px; font-weight:700; color:#3d1a6e; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid #f0eef6; }
  .form-group { margin-bottom:14px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-textarea { width:100%; padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .btn-send-email { background:#3d1a6e; color:#fff; border:none; padding:10px 24px; border-radius:8px; font-size:13.5px; font-weight:700; cursor:pointer; width:100%; margin-top:4px; }
  .btn-send-email:hover { background:#5a2d9e; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:3px; }

  /* Log */
  .log-section { margin-top:20px; }
  .log-section__title { font-size:13px; font-weight:700; color:#3d1a6e; margin-bottom:10px; }
  .log-item { background:#fff; border:1px solid #e8e6f0; border-radius:8px; padding:10px 14px; margin-bottom:8px; font-size:12.5px; }
  .log-item__subject { font-weight:600; color:#1a1a2e; margin-bottom:3px; }
  .log-item__meta    { color:#9b97b0; }

  .empty-state { padding:40px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'newsletter-admin'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Newsletter &amp; Subscribers</h2>
        <p>View subscribers, export the list, and send broadcast emails to everyone who subscribed on the website.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-pill"><strong><?php echo $activeCount; ?></strong><span>Active Subscribers</span></div>
        <div class="stat-pill"><strong><?php echo $inactiveCount; ?></strong><span>Unsubscribed</span></div>
        <div class="stat-pill"><strong><?php echo $activeCount + $inactiveCount; ?></strong><span>Total All-Time</span></div>
      </div>

      <div class="nl-grid">

        <!-- ── Subscriber list ── -->
        <div>
          <form method="GET" style="display:inline">
            <div class="toolbar">
              <a href="?status=active"
                 class="filter-tab <?php echo $filterStatus === 'active' ? 'filter-tab--active' : ''; ?>">
                Active (<?php echo $activeCount; ?>)
              </a>
              <a href="?status=inactive"
                 class="filter-tab <?php echo $filterStatus === 'inactive' ? 'filter-tab--active' : ''; ?>">
                Unsubscribed (<?php echo $inactiveCount; ?>)
              </a>
              <a href="?status=all"
                 class="filter-tab <?php echo $filterStatus === 'all' ? 'filter-tab--active' : ''; ?>">
                All
              </a>
              <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>"/>
              <input type="text" name="search" class="search-box"
                     value="<?php echo htmlspecialchars($searchTerm); ?>"
                     placeholder="Search email…"/>
              <button type="submit" class="btn-export">Search</button>
              <a href="?export=csv&status=active" class="btn-export">⬇ Export CSV</a>
            </div>
          </form>

          <form method="POST" id="bulkForm">
            <div style="margin-bottom:10px;display:flex;gap:8px;align-items:center">
              <button type="button" onclick="toggleAll(true)"
                      style="font-size:11.5px;color:#3d1a6e;background:none;border:none;cursor:pointer;font-weight:600">Select All</button>
              <button type="button" onclick="toggleAll(false)"
                      style="font-size:11.5px;color:#6b6b80;background:none;border:none;cursor:pointer">Deselect All</button>
              <button type="submit" name="action" value="delete_selected"
                      class="btn-delete-sel"
                      onclick="return confirm('Remove selected subscribers permanently?')">
                Remove Selected
              </button>
              <span style="font-size:12px;color:#9b97b0;margin-left:4px" id="selCount">0 selected</span>
            </div>

            <div class="sub-table-wrap">
              <?php if (empty($subscribers)): ?>
              <div class="empty-state">No subscribers found.</div>
              <?php else: ?>
              <table class="sub-table">
                <thead>
                  <tr>
                    <th style="width:36px"></th>
                    <th>Email Address</th>
                    <th>Status</th>
                    <th>Subscribed</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($subscribers as $sub): ?>
                  <tr>
                    <td>
                      <input type="checkbox" name="selected_ids[]"
                             value="<?php echo $sub['id']; ?>"
                             class="sub-cb"
                             onchange="updateSelCount()"/>
                    </td>
                    <td style="font-weight:500;color:#1a1a2e">
                      <?php echo htmlspecialchars($sub['email']); ?>
                    </td>
                    <td>
                      <span class="badge--active">
                        <?php echo $sub['is_active'] ? 'Active' : 'Unsubscribed'; ?>
                      </span>
                    </td>
                    <td style="font-size:12px;color:#9b97b0">
                      <?php echo date('d M Y', strtotime($sub['subscribed_at'])); ?>
                    </td>
                    <td>
                      <button type="submit" name="action" value="delete"
                              onclick="document.getElementById('delId').value=<?php echo $sub['id']; ?>;return confirm('Remove this subscriber?')"
                              class="action-btn action-btn--delete">Remove</button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <input type="hidden" name="action" value="delete" id="delId" name="subscriber_id"/>
              <?php endif; ?>
            </div>
          </form>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php
            $qBase = http_build_query(array_filter(['status' => $filterStatus, 'search' => $searchTerm]));
            for ($p = 1; $p <= $totalPages; $p++):
            ?>
            <?php if ($p === $page): ?>
            <span class="current"><?php echo $p; ?></span>
            <?php else: ?>
            <a href="?<?php echo $qBase; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
            <?php endif; ?>
            <?php endfor; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- ── Compose & log ── -->
        <div>
          <div class="compose-card">
            <div class="compose-card__title">Send Newsletter Email</div>
            <form method="POST">
              <input type="hidden" name="action" value="broadcast"/>
              <div class="form-group">
                <label class="form-label">Subject *</label>
                <input type="text" class="form-input" name="email_subject"
                       maxlength="200" required
                       placeholder="e.g. Second Term Results Now Available"/>
              </div>
              <div class="form-group">
                <label class="form-label">Message *</label>
                <textarea class="form-textarea" name="email_body" rows="8" required
                          placeholder="Write your email message here. Plain text — will be wrapped in the school's branded email template automatically."></textarea>
                <p class="char-hint">Plain text only. Line breaks are preserved.</p>
              </div>
              <button type="submit" class="btn-send-email"
                      onclick="return confirm('Send this email to <?php echo $activeCount; ?> active subscriber(s)?')">
                Send to <?php echo $activeCount; ?> Subscriber<?php echo $activeCount !== 1 ? 's' : ''; ?> →
              </button>
            </form>
          </div>

          <!-- Broadcast log -->
          <?php if (!empty($broadcastLog)): ?>
          <div class="log-section">
            <div class="log-section__title">Recent Broadcasts</div>
            <?php foreach ($broadcastLog as $log): ?>
            <div class="log-item">
              <div class="log-item__subject"><?php echo htmlspecialchars($log['subject']); ?></div>
              <div class="log-item__meta">
                Sent to <?php echo (int) $log['sent_to']; ?> subscribers
                · <?php echo date('d M Y, g:ia', strtotime($log['created_at'])); ?>
                · By <?php echo htmlspecialchars($log['sender_name']); ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    function toggleAll(checked) {
      document.querySelectorAll('.sub-cb').forEach(function (cb) { cb.checked = checked; });
      updateSelCount();
    }
    function updateSelCount() {
      var n = document.querySelectorAll('.sub-cb:checked').length;
      var el = document.getElementById('selCount');
      if (el) el.textContent = n + ' selected';
    }
  </script>

</body>
</html>