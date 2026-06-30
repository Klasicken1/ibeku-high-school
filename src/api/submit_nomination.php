<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SUBMIT HALL OF FAME NOMINATION
   File: src/api/submit_nomination.php

   Accepts POST from the public nomination form on hall-of-fame.php.
   Validates, saves to hall_of_fame_nominations, returns JSON.
   ============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';

$pdo = getDB();

$errors = [];

$nominatorName  = trim($_POST['nominator_name']    ?? '');
$nominatorEmail = trim($_POST['nominator_email']   ?? '');
$nomineeName    = trim($_POST['nominee_name']       ?? '');
$nomineeYear    = trim($_POST['nominee_class_year'] ?? '');
$category       = trim($_POST['category']           ?? '');
$reason         = trim($_POST['reason']             ?? '');

/* Basic validation */
if ($nominatorName === '')  $errors['nominator_name']  = 'Your name is required.';
if ($nominatorEmail === '') $errors['nominator_email'] = 'Your email is required.';
elseif (!filter_var($nominatorEmail, FILTER_VALIDATE_EMAIL)) $errors['nominator_email'] = 'Please enter a valid email.';
if ($nomineeName === '')    $errors['nominee_name']    = 'Nominee\'s name is required.';
if ($reason === '')         $errors['reason']          = 'Please provide a reason for the nomination.';
elseif (strlen($reason) < 30) $errors['reason']       = 'Please provide a more detailed reason (at least 30 characters).';

/* Honeypot spam check */
if (!empty($_POST['website'])) {
    echo json_encode(['success' => false, 'message' => 'Spam detected.']);
    exit;
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $pdo->prepare(
        'INSERT INTO hall_of_fame_nominations
            (nominator_name, nominator_email, nominee_name, nominee_class_year, category, reason)
         VALUES (?,?,?,?,?,?)'
    )->execute([
        $nominatorName, $nominatorEmail,
        $nomineeName, $nomineeYear ?: null,
        $category ?: null, $reason,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your nomination has been received and will be reviewed by the Hall of Fame committee.',
    ]);

} catch (PDOException $e) {
    error_log('IHS nomination submit error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again.',
    ]);
}