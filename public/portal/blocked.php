<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PORTAL ACCESS BLOCKED
   File: public/portal/blocked.php

   Shown when a student's portal_blocked flag is set to 1.
   Student can still see this page and submit a contact message.
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/auth.php';

portalSessionStart();

/* Must be logged in to see this page */
if (!studentLoggedIn()) {
    header('Location: login.php');
    exit;
}

$student = currentStudent();

/* If unblocked, redirect to dashboard */
if (!$student['portal_blocked']) {
    /* Re-check from DB in case admin just unblocked */
    $pdo   = getDB();
    $fresh = $pdo->prepare('SELECT portal_blocked FROM students WHERE id = ? LIMIT 1');
    $fresh->execute([$student['id']]);
    $row   = $fresh->fetch();
    if (!$row || !$row['portal_blocked']) {
        header('Location: dashboard.php');
        exit;
    }
}

$submitted = false;
$formError = '';

/* ── Contact form submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo     = $pdo ?? getDB();
    $msgText = trim($_POST['message'] ?? '');

    if (mb_strlen($msgText) < 10) {
        $formError = 'Please enter a message of at least 10 characters.';
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO contact_messages
                    (name, email, phone, subject, message, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            )->execute([
                $student['first_name'] . ' ' . $student['last_name'],
                '',   /* students have no email field */
                '',
                'Portal Access Query — ' . $student['admission_number'],
                $msgText,
            ]);
            $submitted = true;
        } catch (PDOException $e) {
            error_log('[IHS portal blocked contact] ' . $e->getMessage());
            $formError = 'Could not submit your message. Please call the school directly.';
        }
    }
}

$reason = $student['portal_blocked_reason'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Access Restricted — Ibeku High School Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #f4f3f9;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    .card {
      background: #fff;
      border-radius: 20px;
      padding: 2.5rem 2rem;
      max-width: 500px;
      width: 100%;
      box-shadow: 0 4px 32px rgba(61,26,110,.08);
      text-align: center;
    }

    .block-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      display: block;
    }

    .badge-restricted {
      display: inline-block;
      background: #ffe6e6;
      color: #cc3333;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      padding: 3px 12px;
      border-radius: 20px;
      margin-bottom: 1rem;
    }

    h1 {
      font-family: 'Playfair Display', serif;
      font-size: 1.6rem;
      color: #3d1a6e;
      margin-bottom: 0.75rem;
    }

    .student-name {
      font-size: 0.9rem;
      color: #9b97b0;
      margin-bottom: 1rem;
    }

    .reason-box {
      background: #fff8e6;
      border: 1px solid #ffe0b2;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 0.875rem;
      color: #8a4a00;
      margin-bottom: 1.5rem;
      text-align: left;
    }
    .reason-box strong { display: block; margin-bottom: 4px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }

    hr.divider { border: none; border-top: 1px solid #f0eef6; margin: 1.5rem 0; }

    /* Contact info */
    .contact-section { margin-bottom: 1.5rem; }
    .contact-section h2 {
      font-size: 1rem;
      font-weight: 700;
      color: #3d1a6e;
      margin-bottom: 0.75rem;
    }
    .contact-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background: #f8f7fc;
      border: 1px solid #e8e6f0;
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 8px;
      font-size: 0.9rem;
      color: #1a1a2e;
      font-weight: 600;
      text-decoration: none;
    }
    .contact-row:hover { background: #f0ecfa; }
    .contact-row .icon { font-size: 1.1rem; }

    /* Message form */
    .msg-form h2 {
      font-size: 1rem;
      font-weight: 700;
      color: #3d1a6e;
      margin-bottom: 0.75rem;
      text-align: left;
    }
    textarea {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid #e2e0ea;
      border-radius: 10px;
      font-size: 0.9rem;
      font-family: 'DM Sans', sans-serif;
      color: #1a1a2e;
      resize: vertical;
      margin-bottom: 10px;
    }
    textarea:focus { outline: none; border-color: #4a90d9; }

    .btn-submit {
      width: 100%;
      background: #3d1a6e;
      color: #fff;
      border: none;
      padding: 11px;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 700;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
    }
    .btn-submit:hover { background: #5a2d9e; }

    .success-box {
      background: #e6f9ed;
      border: 1px solid #b2dfce;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 0.875rem;
      color: #1a7a3a;
      margin-bottom: 1rem;
    }
    .error-box {
      background: #fff0f0;
      border: 1px solid #ffd0d0;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 0.875rem;
      color: #cc3333;
      margin-bottom: 1rem;
    }

    .logout-link {
      display: block;
      margin-top: 1.5rem;
      font-size: 0.8rem;
      color: #9b97b0;
      text-decoration: none;
      text-align: center;
    }
    .logout-link:hover { color: #3d1a6e; }
  </style>
</head>
<body>
  <div class="card">

    <span class="block-icon">🔒</span>
    <span class="badge-restricted">Access Restricted</span>

    <h1>Portal Access Blocked</h1>
    <p class="student-name">
      <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
      &nbsp;·&nbsp; <?php echo htmlspecialchars($student['admission_number']); ?>
    </p>

    <?php if ($reason): ?>
    <div class="reason-box">
      <strong>Reason given by school:</strong>
      <?php echo htmlspecialchars($reason); ?>
    </div>
    <?php else: ?>
    <p style="font-size:.875rem;color:#6b6b80;margin-bottom:1.5rem">
      Your access to this portal has been restricted by school administration.
      Please contact the school for more information.
    </p>
    <?php endif; ?>

    <hr class="divider"/>

    <!-- Contact info -->
    <div class="contact-section">
      <h2>Contact the School</h2>
      <a href="tel:+2348000000000" class="contact-row">
        <span class="icon">📞</span>
        <span>+234 800 000 0000</span>
      </a>
      <a href="tel:+2348000000001" class="contact-row">
        <span class="icon">📞</span>
        <span>+234 800 000 0001</span>
      </a>
    </div>

    <hr class="divider"/>

    <!-- Message form -->
    <div class="msg-form">
      <h2>Send a Message to the School</h2>

      <?php if ($submitted): ?>
      <div class="success-box">
        ✅ Your message has been sent. The school office will follow up with you soon.
      </div>
      <?php else: ?>

      <?php if ($formError): ?>
      <div class="error-box"><?php echo htmlspecialchars($formError); ?></div>
      <?php endif; ?>

      <form method="POST">
        <textarea name="message" rows="4"
                  placeholder="Describe your situation and request…"
                  required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
        <button type="submit" class="btn-submit">Send Message</button>
      </form>
      <?php endif; ?>
    </div>

    <a href="logout.php" class="logout-link">Sign out</a>

  </div>
</body>
</html>