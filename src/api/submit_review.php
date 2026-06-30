<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SUBMIT REVIEW
   File: src/api/submit_review.php

   Accepts POST from the public review form (any public page).
   Validates input, generates a verification token, saves the
   review as unverified, and returns a verification link for
   the user to confirm their submission.

   On cPanel without email, we return the token link in the
   JSON response and the public page shows it as a "confirm"
   step — no email infrastructure needed.
   ============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';

$pdo = getDB();

/* ── Honeypot ── */
if (!empty($_POST['website'])) {
    echo json_encode(['success' => false, 'message' => 'Spam detected.']);
    exit;
}

$name         = trim($_POST['reviewer_name']  ?? '');
$email        = trim($_POST['reviewer_email'] ?? '');
$relationship = trim($_POST['relationship']   ?? 'visitor');
$rating       = (int) ($_POST['rating']       ?? 5);
$reviewText   = trim($_POST['review_text']    ?? '');

$validRelationships = ['parent','student','alumnus','staff','visitor'];
$errors = [];

if ($name === '')  $errors['reviewer_name']  = 'Your name is required.';
if ($email === '') $errors['reviewer_email'] = 'Your email is required.';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['reviewer_email'] = 'Please enter a valid email.';
if (!in_array($relationship, $validRelationships, true)) $errors['relationship'] = 'Please select your relationship to the school.';
if ($rating < 1 || $rating > 5) $errors['rating'] = 'Please select a rating.';
if ($reviewText === '') $errors['review_text'] = 'Please write your review.';
elseif (strlen($reviewText) < 20) $errors['review_text'] = 'Please write a more detailed review (at least 20 characters).';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

/* ── Rate limit: max 1 review per email per 24 hours ── */
$existing = $pdo->prepare(
    "SELECT id FROM reviews
     WHERE reviewer_email = ?
       AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
     LIMIT 1"
);
$existing->execute([$email]);
if ($existing->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'You have already submitted a review recently. Please try again after 24 hours.',
    ]);
    exit;
}

/* ── Generate verification token ── */
$token   = bin2hex(random_bytes(32));
$ipAddr  = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $pdo->prepare(
        'INSERT INTO reviews
            (reviewer_name, reviewer_email, relationship, rating, review_text,
             status, verification_token, is_verified, ip_address)
         VALUES (?,?,?,?,?,\'pending\',?,0,?)'
    )->execute([
        $name, $email, $relationship, $rating, $reviewText, $token, $ipAddr,
    ]);

    /* Build verification URL */
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base     = $host === 'localhost'
        ? $protocol . '://' . $host . '/ibeku-high-school/public/'
        : $protocol . '://' . $host . '/';

    $verifyUrl = $base . 'verify-review.php?token=' . urlencode($token);

    echo json_encode([
        'success'    => true,
        'verify_url' => $verifyUrl,
        'message'    => 'Thank you! One more step — please click the confirmation link below to verify your review.',
    ]);

} catch (PDOException $e) {
    error_log('IHS review submit error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again.']);
}