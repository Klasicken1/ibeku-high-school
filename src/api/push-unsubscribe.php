<?php
/* ============================================================
   IBEKU HIGH SCHOOL — REMOVE PUSH SUBSCRIPTION
   File: src/api/push-unsubscribe.php

   Called by main.js when the user opts out of notifications.
   Removes the subscription from DB by endpoint.
   ============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

$pdo = getDB();

$raw = file_get_contents('php://input');
if (!$raw) {
    echo json_encode(['success' => false, 'message' => 'No data received.']);
    exit;
}

$data = json_decode($raw, true);
if (empty($data['endpoint'])) {
    echo json_encode(['success' => false, 'message' => 'No endpoint provided.']);
    exit;
}

try {
    $pdo->prepare(
        'DELETE FROM push_subscriptions WHERE endpoint = ?'
    )->execute([$data['endpoint']]);

    echo json_encode(['success' => true, 'message' => 'Unsubscribed.']);

} catch (PDOException $e) {
    error_log('IHS push-unsubscribe error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}