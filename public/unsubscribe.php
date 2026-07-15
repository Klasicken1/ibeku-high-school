<?php
/* ============================================================
   IBEKU HIGH SCHOOL - NEWSLETTER UNSUBSCRIBE
   File: public/unsubscribe.php

   Handles unsubscribe requests from newsletter emails.
   Accepts ?email=encoded@email.com or ?token=hash
   Sets is_active = 0 in subscribers table.
   Shows a branded confirmation page.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/src/config/database.php';

$pdo = getDB();

$email   = trim($_GET['email'] ?? '');
$token   = trim($_GET['token'] ?? '');
$status  = 'idle'; /* idle | success | notfound | already | error */

/* Decode email if URL-encoded */
if ($email) {
    $email = urldecode($email);
}

/* ── Process unsubscribe ── */
if ($email || $token) {
    try {
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            /* Look up by email */
            $check = $pdo->prepare(
                'SELECT id, is_active FROM subscribers WHERE email = ? LIMIT 1'
            );
            $check->execute([$email]);
            $row = $check->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $status = 'notfound';
            } elseif ($row['is_active'] == 0) {
                $status = 'already';
            } else {
                $pdo->prepare(
                    'UPDATE subscribers SET is_active = 0, unsubscribed_at = NOW() WHERE email = ?'
                )->execute([$email]);
                $status = 'success';
            }
        } else {
            $status = 'notfound';
        }
    } catch (PDOException $e) {
        error_log('IHS unsubscribe error: ' . $e->getMessage());
        $status = 'error';
    }
}

/* Load site settings for branding */
$_site = getSettings();
$schoolName = $_site['school_name'] ?? 'Ibeku High School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Unsubscribe - <?php echo htmlspecialchars($schoolName); ?></title>
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
      max-width: 480px;
      width: 100%;
      box-shadow: 0 4px 32px rgba(61,26,110,.08);
      text-align: center;
    }

    .logo {
      width: 56px; height: 56px;
      background: #3d1a6e;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Playfair Display', serif;
      font-size: 1.1rem;
      font-weight: 900;
      color: #fff;
      letter-spacing: 1px;
      margin: 0 auto 1.25rem;
    }

    .icon { font-size: 2.5rem; display: block; margin-bottom: 1rem; }

    h1 {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      color: #3d1a6e;
      margin-bottom: 0.75rem;
    }

    p {
      font-size: 0.9rem;
      color: #6b6b80;
      line-height: 1.7;
      margin-bottom: 1.5rem;
    }

    .email-display {
      background: #f8f7fc;
      border: 1px solid #e8e6f0;
      border-radius: 8px;
      padding: 8px 16px;
      font-size: 0.875rem;
      color: #3d1a6e;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 1.5rem;
      word-break: break-all;
    }

    .btn {
      display: inline-block;
      background: #3d1a6e;
      color: #fff;
      text-decoration: none;
      padding: 11px 28px;
      border-radius: 9px;
      font-size: 0.9rem;
      font-weight: 700;
      font-family: 'DM Sans', sans-serif;
      transition: background 0.2s;
      border: none;
      cursor: pointer;
    }

    .btn:hover { background: #5a2d9e; }

    .btn-outline {
      background: none;
      border: 1.5px solid #3d1a6e;
      color: #3d1a6e;
      margin-top: 0.75rem;
    }

    .btn-outline:hover { background: #f0ecfa; }

    hr { border: none; border-top: 1px solid #f0eef6; margin: 1.5rem 0; }

    .footer-note {
      margin-top: 2rem;
      font-size: 0.78rem;
      color: #9b97b0;
      text-align: center;
    }

    /* ── Manual unsubscribe form (idle state) ── */
    .form-group { margin-bottom: 1rem; text-align: left; }

    .form-label {
      display: block;
      font-size: 0.75rem;
      font-weight: 600;
      color: #3d1a6e;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 5px;
    }

    .form-input {
      width: 100%;
      padding: 10px 13px;
      border: 1.5px solid #e2e0ea;
      border-radius: 9px;
      font-size: 0.95rem;
      font-family: 'DM Sans', sans-serif;
      color: #1a1a2e;
    }

    .form-input:focus { outline: none; border-color: #4a90d9; }

    .btn-full { width: 100%; }
  </style>
</head>
<body>

  <div class="card">
    <div class="logo">IHS</div>

    <?php if ($status === 'success'): ?>
    <!-- ── Successfully unsubscribed ── -->
    <span class="icon">✅</span>
    <h1>You've been unsubscribed</h1>
    <?php if ($email): ?>
    <div class="email-display"><?php echo htmlspecialchars($email); ?></div>
    <?php endif; ?>
    <p>
      You will no longer receive newsletter emails from
      <?php echo htmlspecialchars($schoolName); ?>.
      We're sorry to see you go.
    </p>
    <hr/>
    <p style="font-size:0.82rem">
      Changed your mind? You can resubscribe anytime from the school website.
    </p>
    <a href="<?php echo BASE_PATH; ?>index.php" class="btn">Back to Website</a>

    <?php elseif ($status === 'already'): ?>
    <!-- ── Already unsubscribed ── -->
    <span class="icon">ℹ️</span>
    <h1>Already unsubscribed</h1>
    <?php if ($email): ?>
    <div class="email-display"><?php echo htmlspecialchars($email); ?></div>
    <?php endif; ?>
    <p>This email address is already unsubscribed from our newsletter.</p>
    <a href="<?php echo BASE_PATH; ?>index.php" class="btn">Back to Website</a>

    <?php elseif ($status === 'notfound'): ?>
    <!-- ── Email not found ── -->
    <span class="icon">❓</span>
    <h1>Email not found</h1>
    <p>
      We could not find that email address in our subscriber list.
      It may have already been removed or was never subscribed.
    </p>
    <hr/>
    <p style="font-size:0.82rem">
      If you are still receiving emails, please enter your address below.
    </p>
    <form method="GET">
      <div class="form-group">
        <label class="form-label" for="email">Your email address</label>
        <input type="email" id="email" name="email" class="form-input"
               placeholder="Enter the email you subscribed with" required/>
      </div>
      <button type="submit" class="btn btn-full">Unsubscribe</button>
    </form>

    <?php elseif ($status === 'error'): ?>
    <!-- ── Server error ── -->
    <span class="icon">⚠️</span>
    <h1>Something went wrong</h1>
    <p>
      We could not process your request right now. Please try again later
      or contact the school directly.
    </p>
    <a href="<?php echo BASE_PATH; ?>contact.php" class="btn">Contact the School</a>

    <?php else: ?>
    <!-- ── Idle: manual unsubscribe form ── -->
    <span class="icon">📧</span>
    <h1>Unsubscribe from Newsletter</h1>
    <p>
      Enter your email address below to unsubscribe from
      <?php echo htmlspecialchars($schoolName); ?> newsletter updates.
    </p>
    <form method="GET">
      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <input type="email" id="email" name="email" class="form-input"
               placeholder="your@email.com" required/>
      </div>
      <button type="submit" class="btn btn-full">Unsubscribe</button>
    </form>
    <hr/>
    <a href="<?php echo BASE_PATH; ?>index.php" class="btn btn-outline">Back to Website</a>

    <?php endif; ?>

  </div>

  <p class="footer-note">
    <?php echo htmlspecialchars($schoolName); ?> &nbsp;·&nbsp;
    Umuahia, Abia State &nbsp;·&nbsp;
    <a href="<?php echo BASE_PATH; ?>index.php" style="color:#9b97b0">Website</a>
  </p>

</body>
</html>