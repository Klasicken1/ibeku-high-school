<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMISSIONS ENQUIRY API
   File: src/api/submit_admission.php

   Accepts POST request with:
     parent_first, parent_last, parent_email, parent_phone,
     student_first, student_last, dob (optional), gender (optional),
     entry_class, session, previous_school (optional), message (optional)

   Returns JSON:
     { "success": true,  "message": "..." }
     { "success": false, "message": "...", "errors": {...} }

   Called by: public/admissions.php (submitAdmissionForm)
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
$parentFirst    = trim($_POST['parent_first']    ?? '');
$parentLast     = trim($_POST['parent_last']     ?? '');
$parentEmail    = trim($_POST['parent_email']    ?? '');
$parentPhone    = trim($_POST['parent_phone']    ?? '');
$studentFirst   = trim($_POST['student_first']   ?? '');
$studentLast    = trim($_POST['student_last']    ?? '');
$dob            = trim($_POST['dob']             ?? '');
$gender         = trim($_POST['gender']          ?? '');
$entryClass     = trim($_POST['entry_class']     ?? '');
$session        = trim($_POST['session']         ?? '');
$previousSchool = trim($_POST['previous_school'] ?? '');
$message        = trim($_POST['message']         ?? '');

/* ── Server-side validation ── */
$errors = [];

if ($parentFirst === '')  $errors['parent_first']  = 'Parent first name is required.';
if ($parentLast === '')   $errors['parent_last']   = 'Parent last name is required.';
if ($parentEmail === '' || !filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
    $errors['parent_email'] = 'A valid email address is required.';
}
if ($parentPhone === '' || !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $parentPhone)) {
    $errors['parent_phone'] = 'A valid phone number is required.';
}
if ($studentFirst === '') $errors['student_first'] = 'Student first name is required.';
if ($studentLast === '')  $errors['student_last']  = 'Student last name is required.';

/* entry_class must be exactly JSS1 or SSS1 — strip the descriptive suffix
   the dropdown sends, e.g. "JSS 1 — Junior Secondary" → "JSS1" */
$entryClassNormalised = strtoupper(str_replace(' ', '', explode('—', $entryClass)[0] ?? ''));
if (!in_array($entryClassNormalised, ['JSS1', 'SSS1'], true)) {
    $errors['entry_class'] = 'Please select an entry level.';
}

if ($session === '' || !preg_match('/^\d{4}\/\d{4}$/', $session)) {
    $errors['session'] = 'Please select an academic session.';
}

/* DOB optional, but if present must be a valid past date */
$dobFormatted = null;
if ($dob !== '') {
    $dobTime = strtotime($dob);
    if ($dobTime === false || $dobTime > time()) {
        $errors['dob'] = 'Please enter a valid date of birth.';
    } else {
        $dobFormatted = date('Y-m-d', $dobTime);
    }
}

/* Gender optional, but if present must be valid */
$genderNormalised = null;
if ($gender !== '') {
    $genderLower = strtolower($gender);
    if (!in_array($genderLower, ['male', 'female'], true)) {
        $errors['gender'] = 'Please select a valid gender.';
    } else {
        $genderNormalised = $genderLower;
    }
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
        'INSERT INTO admissions
            (parent_first, parent_last, parent_email, parent_phone,
             student_first, student_last, date_of_birth, gender,
             entry_class, session, previous_school, message, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "new")'
    );
    $stmt->execute([
        $parentFirst, $parentLast, $parentEmail, $parentPhone,
        $studentFirst, $studentLast, $dobFormatted, $genderNormalised,
        $entryClassNormalised, $session, $previousSchool ?: null, $message ?: null,
    ]);

    /* ── Email notification — best effort, never blocks success ──
       PHP's mail() has no SMTP-auth concept, so gating on
       $_ENV['MAIL_USER'] (unused by mail() anyway) meant this
       never actually fired. cPanel shared hosting has a working
       local mail transport out of the box — no SMTP credentials
       needed — so this now fires on production and is skipped on
       localhost (no MTA configured there, and attempting it can
       hang or throw warnings on XAMPP). Failures are logged but
       never block the success response, since the enquiry is
       already safely saved in the DB either way. */
    $isLocalEnv = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost';

    if (!$isLocalEnv) {
        $mailTo      = $_ENV['MAIL_FROM'] ?? 'admissions@ibekuhighschool.edu.ng';
        $mailSubject = 'New Admissions Enquiry: ' . $studentFirst . ' ' . $studentLast;
        $mailBody    = "New admissions enquiry from the school website:\n\n"
                     . "── Parent / Guardian ──\n"
                     . "Name: {$parentFirst} {$parentLast}\n"
                     . "Email: {$parentEmail}\n"
                     . "Phone: {$parentPhone}\n\n"
                     . "── Student ──\n"
                     . "Name: {$studentFirst} {$studentLast}\n"
                     . "Entry Level: {$entryClassNormalised}\n"
                     . "Session: {$session}\n"
                     . "Previous School: " . ($previousSchool ?: 'Not provided') . "\n\n"
                     . "Message:\n" . ($message ?: 'None provided') . "\n";
        $mailHeaders = "From: " . ($_ENV['MAIL_FROM_NAME'] ?? 'Ibeku High School')
                     . " <" . ($_ENV['MAIL_FROM'] ?? 'noreply@ibekuhighschool.edu.ng') . ">\r\n"
                     . "Reply-To: {$parentEmail}\r\n";

        $mailSent = @mail($mailTo, $mailSubject, $mailBody, $mailHeaders);
        if (!$mailSent) {
            error_log('IHS submit_admission: mail() returned false for ' . $mailTo);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Thank you. Our admissions office will contact you at the email or phone number provided within 48 hours.',
    ]);

} catch (PDOException $e) {
    error_log('IHS submit_admission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again or call the school office directly.',
    ]);
}