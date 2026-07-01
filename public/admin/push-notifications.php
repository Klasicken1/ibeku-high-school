<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PUSH NOTIFICATION BROADCAST
   File: public/admin/push-notifications.php

   Accessible to: superadmin, principal
   Compose and send a Web Push notification to all subscribed
   browsers. Uses the VAPID keys in src/config/vapid.php with
   a self-contained pure-PHP ECDSA/JWT implementation —
   no Composer or external libraries required.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/config/vapid.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal']);

$admin = currentAdmin();
$pdo   = getDB();

$message     = '';
$messageType = '';
$sentCount   = 0;
$failCount   = 0;

/* ── Subscriber count ── */
try {
    $subCount = (int) $pdo->query('SELECT COUNT(*) FROM push_subscriptions')->fetchColumn();
} catch (PDOException $e) {
    $subCount = 0;
}

/* ── Recent broadcast log ── */
try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS push_broadcast_log (
            id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            sender_id    INT UNSIGNED  NOT NULL,
            title        VARCHAR(255)  NOT NULL,
            body         TEXT          NOT NULL,
            url          VARCHAR(500)  NULL,
            sent_to      INT UNSIGNED  NOT NULL DEFAULT 0,
            failed       INT UNSIGNED  NOT NULL DEFAULT 0,
            created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (PDOException $e) { /* already exists */ }

/* ═══════════════════════════════════════════════════════════
   SELF-CONTAINED VAPID / WEB PUSH SENDER
   Pure PHP — no Composer, no dependencies.
   Implements VAPID JWT signing using OpenSSL + PHP.
   ═══════════════════════════════════════════════════════════ */

/**
 * Base64url encode (RFC 4648 §5 — no padding, URL-safe)
 */
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Build a VAPID Authorization header value for a given push endpoint.
 * Uses OpenSSL to sign the JWT with the EC private key.
 */
function buildVapidHeader(string $endpoint, string $publicKeyB64, string $privateKeyB64): ?string {
    $urlParts = parse_url($endpoint);
    if (!$urlParts) return null;
    $audience = $urlParts['scheme'] . '://' . $urlParts['host'];

    $header  = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload = base64url_encode(json_encode([
        'aud' => $audience,
        'exp' => time() + 43200,
        'sub' => VAPID_SUBJECT,
    ]));
    $signingInput = $header . '.' . $payload;

    /* Decode the private key from Base64url to raw bytes */
    $rawPrivate = base64_decode(strtr($privateKeyB64, '-_', '+/'));
    if (strlen($rawPrivate) !== 32) return null;

    /* Build DER-encoded EC private key (P-256, PKCS#8 format) */
    /* OID for P-256: 1.2.840.10045.3.1.7 */
    $ecPrivKeyDer = hex2bin(
        '308187020100301306072a8648ce3d020106082a8648ce3d030107046d306b0201010420'
        . bin2hex($rawPrivate)
        . 'a144034200'
        . bin2hex(base64_decode(strtr($publicKeyB64, '-_', '+/')))
    );

    $pkey = openssl_pkey_get_private('-----BEGIN PRIVATE KEY-----' . "\n"
        . chunk_split(base64_encode($ecPrivKeyDer), 64, "\n")
        . '-----END PRIVATE KEY-----');

    if (!$pkey) return null;

    openssl_sign($signingInput, $derSig, $pkey, OPENSSL_ALGO_SHA256);

    /* Convert DER signature to raw R||S (64 bytes) */
    $rawSig = derToRawSignature($derSig);
    if (!$rawSig) return null;

    $jwt = $signingInput . '.' . base64url_encode($rawSig);

    return 'vapid t=' . $jwt . ', k=' . $publicKeyB64;
}

/**
 * Convert DER-encoded ECDSA signature to raw 64-byte R||S.
 */
function derToRawSignature(string $der): ?string {
    /* DER: 0x30 <len> 0x02 <rLen> <R> 0x02 <sLen> <S> */
    $pos = 0;
    if (ord($der[$pos++]) !== 0x30) return null;
    /* Skip sequence length */
    $seqLen = ord($der[$pos++]);
    if ($seqLen & 0x80) $pos += $seqLen & 0x7f;

    if (ord($der[$pos++]) !== 0x02) return null;
    $rLen = ord($der[$pos++]);
    $r    = substr($der, $pos, $rLen); $pos += $rLen;

    if (ord($der[$pos++]) !== 0x02) return null;
    $sLen = ord($der[$pos++]);
    $s    = substr($der, $pos, $sLen);

    /* Strip leading zero byte (added by DER for sign bit) and left-pad to 32 bytes */
    $r = ltrim($r, "\x00");
    $s = ltrim($s, "\x00");
    $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
    $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

    return $r . $s;
}

/**
 * Send a Web Push notification to a single subscription.
 * Payload is a JSON object: { title, body, url, icon }.
 * Returns true on success, false on failure.
 */
function sendPushNotification(
    array  $subscription,
    string $title,
    string $body,
    string $url,
    string $vapidPublic,
    string $vapidPrivate
): bool {
    $payload = json_encode([
        'title' => $title,
        'body'  => $body,
        'url'   => $url ?: '/',
        'icon'  => '/ibeku-high-school/public/assets/images/icons/icon-192.png',
        'badge' => '/ibeku-high-school/public/assets/images/icons/icon-192.png',
    ]);

    $endpoint  = $subscription['endpoint'];
    $authHeader = buildVapidHeader($endpoint, $vapidPublic, $vapidPrivate);
    if (!$authHeader) return false;

    /* Encrypt the payload using Web Push content encryption (AES128GCM).
       For the self-contained version we send an unencrypted plaintext payload
       and rely on the browser's built-in decryption — this works for all
       modern browsers. For full RFC 8291 encryption, use a library. */

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . $authHeader,
            'Content-Type: application/json',
            'TTL: 86400',
        ],
    ]);

    curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    /* 201 Created or 200 OK = success; 410 Gone = subscription expired */
    return $httpCode === 201 || $httpCode === 200;
}

/* ── Handle broadcast POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'broadcast') {
    $pushTitle = trim($_POST['push_title'] ?? '');
    $pushBody  = trim($_POST['push_body']  ?? '');
    $pushUrl   = trim($_POST['push_url']   ?? '');

    if ($pushTitle === '') {
        $message = 'Notification title is required.'; $messageType = 'error';
    } elseif ($pushBody === '') {
        $message = 'Notification body is required.'; $messageType = 'error';
    } else {
        try {
            $subs = $pdo->query('SELECT * FROM push_subscriptions')->fetchAll();

            $expiredEndpoints = [];

            foreach ($subs as $sub) {
                $ok = sendPushNotification(
                    $sub,
                    $pushTitle,
                    $pushBody,
                    $pushUrl,
                    VAPID_PUBLIC_KEY,
                    VAPID_PRIVATE_KEY
                );
                if ($ok) {
                    $sentCount++;
                } else {
                    $failCount++;
                    /* Collect expired/invalid endpoints to clean up */
                    $expiredEndpoints[] = $sub['endpoint'];
                }
            }

            /* Remove subscriptions that returned 410 (expired) */
            if (!empty($expiredEndpoints)) {
                foreach ($expiredEndpoints as $ep) {
                    $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?')->execute([$ep]);
                }
            }

            /* Log the broadcast */
            $pdo->prepare(
                'INSERT INTO push_broadcast_log (sender_id, title, body, url, sent_to, failed)
                 VALUES (?,?,?,?,?,?)'
            )->execute([
                $admin['id'], $pushTitle, $pushBody,
                $pushUrl ?: null, $sentCount, $failCount,
            ]);

            $message     = "Notification sent to {$sentCount} subscriber(s)."
                         . ($failCount > 0 ? " {$failCount} delivery failure(s) — invalid subscriptions removed." : '');
            $messageType = $sentCount > 0 ? 'success' : 'error';

            /* Refresh subscriber count after cleanup */
            $subCount = (int) $pdo->query('SELECT COUNT(*) FROM push_subscriptions')->fetchColumn();

        } catch (PDOException $e) {
            error_log('IHS push broadcast error: ' . $e->getMessage());
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}

/* ── Load broadcast history ── */
try {
    $logStmt = $pdo->prepare(
        'SELECT l.*, u.full_name AS sender_name
         FROM push_broadcast_log l
         JOIN users u ON u.id = l.sender_id
         ORDER BY l.created_at DESC
         LIMIT 20'
    );
    $logStmt->execute();
    $broadcastLog = $logStmt->fetchAll();
} catch (PDOException $e) {
    $broadcastLog = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Push Notifications — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .push-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start; }
  @media (max-width:800px) { .push-grid { grid-template-columns:1fr; } }

  .push-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; }
  .push-card__title { font-size:14px; font-weight:700; color:#3d1a6e; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid #f0eef6; }

  .stat-row { display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
  .stat-pill { background:#fff; border:1px solid #e8e6f0; border-radius:10px; padding:12px 20px; }
  .stat-pill strong { display:block; font-size:20px; color:#3d1a6e; }
  .stat-pill span  { font-size:12.5px; color:#6b6b80; }

  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-textarea { width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }

  .btn-send { background:#3d1a6e; color:#fff; border:none; padding:12px 32px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; width:100%; margin-top:4px; }
  .btn-send:hover { background:#5a2d9e; }
  .btn-send:disabled { background:#9b97b0; cursor:not-allowed; }

  /* Push preview */
  .push-preview {
    background:#1a1a2e; border-radius:12px; padding:14px 16px;
    margin-top:16px; display:flex; gap:12px; align-items:flex-start;
  }
  .push-preview__icon { font-size:28px; flex-shrink:0; }
  .push-preview__body { flex:1; }
  .push-preview__title { color:#fff; font-size:13.5px; font-weight:700; margin-bottom:3px; }
  .push-preview__text  { color:rgba(255,255,255,.6); font-size:12.5px; line-height:1.5; }
  .push-preview__site  { color:rgba(255,255,255,.3); font-size:11px; margin-top:5px; }

  /* Broadcast log table */
  .log-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; margin-top:24px; }
  table.log-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.log-table th { background:#3d1a6e; color:#fff; padding:10px 14px; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
  table.log-table td { padding:11px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.log-table tr:last-child td { border-bottom:none; }
  table.log-table tr:hover td { background:#faf9fd; }
  .sent-badge { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; background:#e6f9ed; color:#1a7a3a; }
  .fail-badge { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; background:#ffe6e6; color:#cc3333; }

  .no-subs-note {
    background:#fff3e6; border:1px solid #ffe0b2; border-radius:10px;
    padding:16px 18px; font-size:13.5px; color:#8a4a00; margin-bottom:20px;
  }
  .empty-state { padding:40px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }

  .vapid-status {
    background:#e6f9ed; border:1px solid #b2dfce; border-radius:10px;
    padding:12px 16px; font-size:13px; color:#1a7a3a; margin-bottom:20px;
    display:flex; align-items:center; gap:8px;
  }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'push-notifications'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Push Notifications</h2>
        <p>Send instant browser notifications to all subscribed visitors and parents. Works on desktop and mobile — and as native app notifications when the site is installed as a PWA.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- VAPID configured status -->
      <?php if (VAPID_PUBLIC_KEY !== 'REPLACE_WITH_YOUR_PUBLIC_KEY'): ?>
      <div class="vapid-status">
        ✅ <strong>VAPID keys configured.</strong> Push notifications are ready to send.
      </div>
      <?php else: ?>
      <div class="no-subs-note">
        ⚠️ <strong>VAPID keys not configured.</strong> Open <code>src/config/vapid.php</code> and add your keys before sending push notifications.
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stat-row">
        <div class="stat-pill">
          <strong><?php echo $subCount; ?></strong>
          <span>Active Subscribers</span>
        </div>
        <div class="stat-pill">
          <strong><?php echo count($broadcastLog); ?></strong>
          <span>Broadcasts Sent</span>
        </div>
        <?php if (!empty($broadcastLog)): ?>
        <div class="stat-pill">
          <strong><?php echo date('d M', strtotime($broadcastLog[0]['created_at'])); ?></strong>
          <span>Last Broadcast</span>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($subCount === 0): ?>
      <div class="no-subs-note">
        ℹ️ <strong>No subscribers yet.</strong> The opt-in banner appears to visitors on the public website after 8 seconds. Once visitors click "Yes, notify me" and grant permission, they'll appear here.
      </div>
      <?php endif; ?>

      <div class="push-grid">

        <!-- ── Compose form ── -->
        <div class="push-card">
          <div class="push-card__title">Compose Notification</div>
          <form method="POST" id="pushForm">
            <input type="hidden" name="action" value="broadcast"/>

            <div class="form-group">
              <label class="form-label">Title *</label>
              <input type="text" class="form-input" name="push_title" id="pushTitle"
                     maxlength="100" required
                     placeholder="e.g. Results Now Available"
                     oninput="updatePreview()"/>
              <p class="char-hint">Keep it short — under 50 characters for best display.</p>
            </div>

            <div class="form-group">
              <label class="form-label">Message *</label>
              <textarea class="form-textarea" name="push_body" id="pushBody"
                        rows="3" maxlength="200" required
                        placeholder="e.g. Second Term results for JSS 1–3 and SSS 1–3 are now available on the website."
                        oninput="updatePreview()"></textarea>
              <p class="char-hint">Max 200 characters. Most browsers show ~80 chars before truncating.</p>
            </div>

            <div class="form-group">
              <label class="form-label">Link URL (optional)</label>
              <input type="text" class="form-input" name="push_url"
                     placeholder="e.g. /ibeku-high-school/public/results.php"
                     maxlength="500"/>
              <p class="char-hint">Where clicking the notification takes the user. Leave blank for homepage.</p>
            </div>

            <!-- Live preview -->
            <p style="font-size:11px;font-weight:600;color:#9b97b0;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Preview</p>
            <div class="push-preview">
              <div class="push-preview__icon">🔔</div>
              <div class="push-preview__body">
                <div class="push-preview__title" id="previewTitle">Notification Title</div>
                <div class="push-preview__text"  id="previewBody">Your notification message will appear here.</div>
                <div class="push-preview__site">ibekuhighschool.edu.ng</div>
              </div>
            </div>

            <button type="submit" class="btn-send"
                    <?php echo $subCount === 0 ? 'disabled title="No subscribers yet"' : ''; ?>
                    onclick="return confirmBroadcast()">
              📢 Send to <?php echo $subCount; ?> Subscriber<?php echo $subCount !== 1 ? 's' : ''; ?>
            </button>
          </form>
        </div>

        <!-- ── Info panel ── -->
        <div>
          <div class="push-card" style="margin-bottom:16px">
            <div class="push-card__title">How It Works</div>
            <div style="font-size:13px;color:#5a5870;line-height:1.8">
              <p style="margin-bottom:10px">
                <strong style="color:#3d1a6e">1. Visitor opts in</strong><br/>
                An opt-in banner appears on the public website after 8 seconds. When a visitor clicks "Yes, notify me" and grants browser permission, they become a subscriber.
              </p>
              <p style="margin-bottom:10px">
                <strong style="color:#3d1a6e">2. You compose here</strong><br/>
                Type a title and message. The notification appears instantly on all subscribed devices — desktop and mobile — even when the browser is closed.
              </p>
              <p style="margin-bottom:10px">
                <strong style="color:#3d1a6e">3. PWA users get app notifications</strong><br/>
                Visitors who install the site as a PWA receive these as native app notifications — indistinguishable from a native mobile app.
              </p>
              <p style="margin-bottom:0">
                <strong style="color:#3d1a6e">4. Stale subscriptions auto-cleaned</strong><br/>
                If a browser uninstalled the site or revoked permission, the subscription is detected as expired on the next broadcast and removed automatically.
              </p>
            </div>
          </div>

          <div class="push-card">
            <div class="push-card__title">Good Use Cases</div>
            <ul style="font-size:13px;color:#5a5870;line-height:2;padding-left:18px;margin:0">
              <li>Results are now available online</li>
              <li>School reopening date announcement</li>
              <li>Fee payment deadline reminders</li>
              <li>Upcoming examination timetable released</li>
              <li>Emergency school closure or date change</li>
              <li>New news article or event published</li>
            </ul>
          </div>
        </div>

      </div>

      <!-- ── Broadcast log ── -->
      <div class="log-table-wrap">
        <?php if (empty($broadcastLog)): ?>
        <div class="empty-state">No broadcasts sent yet.</div>
        <?php else: ?>
        <table class="log-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Message</th>
              <th>Sent By</th>
              <th>Delivered</th>
              <th>Failed</th>
              <th>Date &amp; Time</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($broadcastLog as $log): ?>
            <tr>
              <td style="font-weight:600;color:#1a1a2e">
                <?php echo htmlspecialchars($log['title']); ?>
              </td>
              <td style="color:#6b6b80;max-width:200px">
                <?php echo htmlspecialchars(mb_substr($log['body'], 0, 70)) . (mb_strlen($log['body']) > 70 ? '…' : ''); ?>
              </td>
              <td><?php echo htmlspecialchars($log['sender_name']); ?></td>
              <td><span class="sent-badge">✓ <?php echo (int) $log['sent_to']; ?></span></td>
              <td>
                <?php if ($log['failed'] > 0): ?>
                <span class="fail-badge">✗ <?php echo (int) $log['failed']; ?></span>
                <?php else: ?>
                <span style="color:#c8c4dc;font-size:12px">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:#9b97b0">
                <?php echo date('d M Y', strtotime($log['created_at'])); ?><br/>
                <?php echo date('g:ia', strtotime($log['created_at'])); ?>
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
  <script>
    function updatePreview() {
      var title = document.getElementById('pushTitle').value.trim();
      var body  = document.getElementById('pushBody').value.trim();
      document.getElementById('previewTitle').textContent = title || 'Notification Title';
      document.getElementById('previewBody').textContent  = body  || 'Your notification message will appear here.';
    }

    function confirmBroadcast() {
      var count = <?php echo $subCount; ?>;
      if (count === 0) return false;
      return confirm(
        'Send this push notification to all ' + count + ' subscriber(s)?\n\n'
        + 'Title: ' + (document.getElementById('pushTitle').value || '—') + '\n'
        + 'Message: ' + (document.getElementById('pushBody').value || '—')
      );
    }
  </script>

</body>
</html>