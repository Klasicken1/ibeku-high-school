<?php
/* ============================================================
   IBEKU HIGH SCHOOL — NEWSLETTER SUBSCRIBE API
   File: src/api/subscribe.php

   Accepts POST request with:
     email — subscriber email address

   Behaviour:
     - New email           → inserted as active subscriber
     - Existing active      → returns friendly "already subscribed" message
     - Existing inactive     → reactivated (is_active = 1)

   Returns JSON:
     { "success": true,  "message": "..." }
     { "success": false, "message": "..." }

   Called by: public/news.php (subscribeNewsletter)
   ============================================================ */

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

/* ── Read and validate input ── */
$email = strtolower(trim($_POST['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.',
    ]);
    exit;
}

try {
    $pdo = getDB();

    /* ── Check if this email already exists ── */
    $stmtCheck = $pdo->prepare(
        'SELECT id, is_active FROM subscribers WHERE email = ? LIMIT 1'
    );
    $stmtCheck->execute([$email]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        if ((int) $existing['is_active'] === 1) {
            /* Already subscribed and active */
            echo json_encode([
                'success' => true,
                'message' => 'You are already subscribed to school updates.',
            ]);
            exit;
        }

        /* Previously unsubscribed — reactivate */
        $stmtReactivate = $pdo->prepare(
            'UPDATE subscribers
             SET    is_active = 1, subscribed_at = NOW(), unsubscribed_at = NULL
             WHERE  id = ?'
        );
        $stmtReactivate->execute([$existing['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Welcome back! You have been resubscribed to school updates.',
        ]);
        exit;
    }

    /* ── New subscriber ── */
    $stmtInsert = $pdo->prepare(
        'INSERT INTO subscribers (email, is_active) VALUES (?, 1)'
    );
    $stmtInsert->execute([$email]);

    echo json_encode([
        'success' => true,
        'message' => 'Subscribed! You will receive school updates by email.',
    ]);

} catch (PDOException $e) {
    error_log('IHS subscribe error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again.',
    ]);
}