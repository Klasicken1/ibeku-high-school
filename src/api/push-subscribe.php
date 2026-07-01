<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SAVE PUSH SUBSCRIPTION
   File: src/api/push-subscribe.php

   Called by main.js after the browser grants push permission.
   Saves (or refreshes) the PushSubscription object in DB.
   Returns JSON.
   ============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

$pdo = getDB();

/* ── Ensure push_subscriptions table exists ── */
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS push_subscriptions (
        id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
        endpoint    TEXT          NOT NULL,
        p256dh      VARCHAR(255)  NOT NULL,
        auth        VARCHAR(100)  NOT NULL,
        created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY idx_endpoint (endpoint(500))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

/* ── Read raw POST body ── */
$raw = file_get_contents('php://input');
if (!$raw) {
    echo json_encode(['success' => false, 'message' => 'No data received.']);
    exit;
}

$data = json_decode($raw, true);
if (!isset($data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid subscription data.']);
    exit;
}

$endpoint = $data['endpoint'];
$p256dh   = $data['keys']['p256dh'];
$auth     = $data['keys']['auth'];

if (strlen($endpoint) > 500 || strlen($p256dh) > 255 || strlen($auth) > 100) {
    echo json_encode(['success' => false, 'message' => 'Subscription data too long.']);
    exit;
}

try {
    $pdo->prepare(
        "INSERT INTO push_subscriptions (endpoint, p256dh, auth)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh),
                                 auth   = VALUES(auth),
                                 updated_at = NOW()"
    )->execute([$endpoint, $p256dh, $auth]);

    echo json_encode(['success' => true, 'message' => 'Subscription saved.']);

} catch (PDOException $e) {
    error_log('IHS push-subscribe error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}