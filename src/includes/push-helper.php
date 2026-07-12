<?php
/* ============================================================
   IBEKU HIGH SCHOOL — WEB PUSH & EMAIL HELPER
   File: src/includes/push-helper.php

   Shared notification utilities used by:
   - public/admin/push-notifications.php  (broadcast to all)
   - public/admin/messages.php            (targeted per-user)

   REQUIRES before including this file:
     require_once .../src/config/vapid.php   (VAPID constants)
     require_once .../src/config/database.php (for sendPushToUser)

   All functions are guarded with function_exists() so this file
   is safe to include alongside push-notifications.php without
   redeclaration errors.
   ============================================================ */

/* ── Base64url encode (RFC 4648 §5) ─────────────────────── */
if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

/* ── Convert DER ECDSA signature → raw 64-byte R||S ─────── */
if (!function_exists('derToRawSignature')) {
    function derToRawSignature(string $der): ?string {
        $pos = 0;
        if (ord($der[$pos++]) !== 0x30) return null;
        $seqLen = ord($der[$pos++]);
        if ($seqLen & 0x80) $pos += $seqLen & 0x7f;

        if (ord($der[$pos++]) !== 0x02) return null;
        $rLen = ord($der[$pos++]);
        $r    = substr($der, $pos, $rLen); $pos += $rLen;

        if (ord($der[$pos++]) !== 0x02) return null;
        $sLen = ord($der[$pos++]);
        $s    = substr($der, $pos, $sLen);

        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }
}

/* ── Build VAPID Authorization header for a push endpoint ── */
if (!function_exists('buildVapidHeader')) {
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

        $rawPrivate = base64_decode(strtr($privateKeyB64, '-_', '+/'));
        if (strlen($rawPrivate) !== 32) return null;

        $ecPrivKeyDer = hex2bin(
            '308187020100301306072a8648ce3d020106082a8648ce3d030107046d306b0201010420'
            . bin2hex($rawPrivate)
            . 'a144034200'
            . bin2hex(base64_decode(strtr($publicKeyB64, '-_', '+/')))
        );

        $pkey = openssl_pkey_get_private(
            '-----BEGIN PRIVATE KEY-----' . "\n"
            . chunk_split(base64_encode($ecPrivKeyDer), 64, "\n")
            . '-----END PRIVATE KEY-----'
        );
        if (!$pkey) return null;

        openssl_sign($signingInput, $derSig, $pkey, OPENSSL_ALGO_SHA256);
        $rawSig = derToRawSignature($derSig);
        if (!$rawSig) return null;

        return 'vapid t=' . $signingInput . '.' . base64url_encode($rawSig) . ', k=' . $publicKeyB64;
    }
}

/* ── Send a single Web Push notification ────────────────── */
if (!function_exists('sendWebPushNotification')) {
    function sendWebPushNotification(
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

        $endpoint   = $subscription['endpoint'];
        $authHeader = buildVapidHeader($endpoint, $vapidPublic, $vapidPrivate);
        if (!$authHeader) return false;

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

        return $httpCode === 201 || $httpCode === 200;
    }
}

/* ── Ensure push_subscriptions has user_id column ────────── */
if (!function_exists('ensurePushUserIdColumn')) {
    function ensurePushUserIdColumn(PDO $pdo): void {
        try {
            $pdo->exec(
                "ALTER TABLE push_subscriptions
                 ADD COLUMN user_id INT UNSIGNED NULL DEFAULT NULL,
                 ADD INDEX  idx_push_user (user_id)"
            );
        } catch (PDOException $e) {
            /* Column already exists — fine */
        }
    }
}

/* ── Send push notification to a specific admin user ─────── */
if (!function_exists('sendPushToUser')) {
    function sendPushToUser(
        PDO    $pdo,
        int    $userId,
        string $title,
        string $body,
        string $url = ''
    ): array {
        if (
            !defined('VAPID_PUBLIC_KEY') ||
            VAPID_PUBLIC_KEY === 'REPLACE_WITH_YOUR_PUBLIC_KEY'
        ) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        ensurePushUserIdColumn($pdo);

        try {
            $stmt = $pdo->prepare(
                'SELECT * FROM push_subscriptions WHERE user_id = ?'
            );
            $stmt->execute([$userId]);
            $subs = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[IHS push] Failed to load subscriptions: ' . $e->getMessage());
            return ['sent' => 0, 'failed' => 0];
        }

        $sent = 0; $failed = 0; $expired = [];

        foreach ($subs as $sub) {
            $ok = sendWebPushNotification(
                $sub, $title, $body, $url,
                VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY
            );
            if ($ok) {
                $sent++;
            } else {
                $failed++;
                $expired[] = $sub['endpoint'];
            }
        }

        foreach ($expired as $ep) {
            $pdo->prepare(
                'DELETE FROM push_subscriptions WHERE endpoint = ?'
            )->execute([$ep]);
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}

/* ── Send email notification to a staff member ───────────── */
if (!function_exists('sendStaffMessageEmail')) {
    function sendStaffMessageEmail(
        string $toEmail,
        string $toName,
        string $fromName,
        string $subject,
        string $previewBody,
        string $inboxUrl
    ): bool {
        $emailSubject = 'New message from ' . $fromName . ': ' . $subject;
        $safePreview  = htmlspecialchars(mb_substr($previewBody, 0, 200));
        $safeFrom     = htmlspecialchars($fromName);
        $safeTo       = htmlspecialchars($toName);
        $safeSubject  = htmlspecialchars($subject);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>{$emailSubject}</title>
</head>
<body style="margin:0;padding:0;background:#f4f3f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f3f9;padding:32px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:12px;overflow:hidden;max-width:560px;width:100%;">

        <tr>
          <td style="background:#3d1a6e;padding:24px 32px;text-align:center;">
            <div style="background:rgba(255,255,255,.12);display:inline-block;padding:6px 18px;
                        border-radius:8px;font-size:20px;font-weight:700;color:#fff;
                        font-family:Georgia,serif;letter-spacing:2px;">IHS</div>
            <p style="color:rgba(255,255,255,.7);font-size:12px;margin:6px 0 0;">
              Ibeku High School — Staff Portal
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:28px 32px 8px;">
            <p style="margin:0 0 4px;font-size:13px;color:#9b97b0;">Hi {$safeTo},</p>
            <h1 style="margin:0;font-size:20px;color:#3d1a6e;font-family:Georgia,serif;line-height:1.3;">
              You have a new message
            </h1>
          </td>
        </tr>

        <tr>
          <td style="padding:12px 32px 20px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f8f7fc;border:1px solid #e8e6f0;border-radius:8px;">
              <tr>
                <td style="padding:16px 18px;">
                  <p style="margin:0 0 6px;font-size:12px;color:#9b97b0;text-transform:uppercase;
                             letter-spacing:.05em;font-weight:600;">From</p>
                  <p style="margin:0 0 12px;font-size:14px;color:#1a1a2e;font-weight:700;">
                    {$safeFrom}
                  </p>
                  <p style="margin:0 0 6px;font-size:12px;color:#9b97b0;text-transform:uppercase;
                             letter-spacing:.05em;font-weight:600;">Subject</p>
                  <p style="margin:0 0 12px;font-size:14px;color:#1a1a2e;font-weight:600;">
                    {$safeSubject}
                  </p>
                  <p style="margin:0 0 6px;font-size:12px;color:#9b97b0;text-transform:uppercase;
                             letter-spacing:.05em;font-weight:600;">Preview</p>
                  <p style="margin:0;font-size:14px;color:#3a3850;line-height:1.7;">
                    {$safePreview}&hellip;
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:4px 32px 28px;text-align:center;">
            <a href="{$inboxUrl}"
               style="display:inline-block;background:#3d1a6e;color:#fff;text-decoration:none;
                      padding:12px 28px;border-radius:8px;font-size:14px;font-weight:700;">
              Open Message →
            </a>
          </td>
        </tr>

        <tr>
          <td style="padding:0 32px;">
            <hr style="border:none;border-top:1px solid #f0eef6;"/>
          </td>
        </tr>

        <tr>
          <td style="padding:16px 32px;text-align:center;font-size:11.5px;color:#9b97b0;line-height:1.6;">
            Ibeku High School, Umuahia, Abia State<br/>
            This is an internal staff notification. Do not reply to this email.
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        $headers = implode("\r\n", [
            'From: Ibeku High School <noreply@ibekuhighschool.edu.ng>',
            'Reply-To: noreply@ibekuhighschool.edu.ng',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . PHP_VERSION,
        ]);

        return mail($toEmail, $emailSubject, $html, $headers);
    }
}