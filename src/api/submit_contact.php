<?php
/* ============================================================
   IBEKU HIGH SCHOOL — CONTACT FORM API
   File: src/api/submit_contact.php

   Accepts POST request with:
     first_name, last_name, email, phone (optional), subject, message

   Returns JSON:
     { "success": true,  "message": "..." }
     { "success": false, "message": "...", "errors": {...} }

   Called by: public/contact.php (submitContactForm)
   ============================================================ */

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

/* ── Read and trim inputs ── */
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name']  ?? '');
$email     = trim($_POST['email']      ?? '');
$phone     = trim($_POST['phone']      ?? '');
$subject   = trim($_POST['subject']    ?? '');
$message   = trim($_POST['message']    ?? '');

/* ── Server-side validation — never trust the client ── */
$errors = [];

if ($firstName === '') {
    $errors['first_name'] = 'First name is required.';
}
if ($lastName === '') {
    $errors['last_name'] = 'Last name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'A valid email address is required.';
}
if ($subject === '') {
    $errors['subject'] = 'Please select a subject.';
}
if ($message === '') {
    $errors['message'] = 'Please enter a message.';
} elseif (mb_strlen($message) > 5000) {
    $errors['message'] = 'Message is too long (maximum 5000 characters).';
}
if ($phone !== '' && !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $phone)) {
    $errors['phone'] = 'Please enter a valid phone number.';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please correct the errors below.',
        'errors'  => $errors,
    ]);
    exit;
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages
            (first_name, last_name, email, phone, subject, message)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$firstName, $lastName, $email, $phone ?: null, $subject, $message]);

    /* ── Email notification — best effort, never blocks success ──
       PHP's mail() has no SMTP-auth concept, so gating on
       $_ENV['MAIL_USER'] (unused by mail() anyway) meant this
       never actually fired. cPanel shared hosting has a working
       local mail transport out of the box — no SMTP credentials
       needed — so this now fires on production and is skipped on
       localhost (no MTA configured there, and attempting it can
       hang or throw warnings on XAMPP). Failures are logged but
       never block the success response, since the message is
       already safely saved in the DB either way. */
    $isLocalEnv = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost';

    if (!$isLocalEnv) {
        $mailTo      = $_ENV['MAIL_FROM'] ?? 'info@ibekuhighschool.edu.ng';
        $mailSubject = 'New Contact Message: ' . $subject;
        $mailBody    = "New message from the school website contact form:\n\n"
                     . "Name: {$firstName} {$lastName}\n"
                     . "Email: {$email}\n"
                     . "Phone: " . ($phone ?: 'Not provided') . "\n"
                     . "Subject: {$subject}\n\n"
                     . "Message:\n{$message}\n";
        $mailHeaders = "From: " . ($_ENV['MAIL_FROM_NAME'] ?? 'Ibeku High School')
                     . " <" . ($_ENV['MAIL_FROM'] ?? 'noreply@ibekuhighschool.edu.ng') . ">\r\n"
                     . "Reply-To: {$email}\r\n";

        $mailSent = @mail($mailTo, $mailSubject, $mailBody, $mailHeaders);
        if (!$mailSent) {
            error_log('IHS submit_contact: mail() returned false for ' . $mailTo);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for getting in touch. We will respond to your message within one working day.',
    ]);

} catch (PDOException $e) {
    error_log('IHS submit_contact error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again or call the school office directly.',
    ]);
}