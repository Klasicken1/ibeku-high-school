<?php
/* ============================================================
   IBEKU HIGH SCHOOL — REVIEW VERIFICATION PAGE
   File: public/verify-review.php

   Called when reviewer clicks their verification link.
   Marks the review as verified and moves it to 'pending'
   admin approval. Shows a confirmation message.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../src/config/database.php';
$pdo = getDB();

$token   = trim($_GET['token'] ?? '');
$success = false;
$error   = '';

if ($token === '') {
    $error = 'Invalid or missing verification token.';
} else {
    $review = $pdo->prepare(
        'SELECT * FROM reviews WHERE verification_token = ? LIMIT 1'
    );
    $review->execute([$token]);
    $row = $review->fetch();

    if (!$row) {
        $error = 'This verification link is invalid or has already been used.';
    } elseif ($row['is_verified']) {
        /* Already verified — just show success */
        $success = true;
    } else {
        try {
            $pdo->prepare(
                'UPDATE reviews SET is_verified = 1, status = \'pending\' WHERE verification_token = ?'
            )->execute([$token]);
            $success = true;
        } catch (PDOException $e) {
            error_log('IHS verify review error: ' . $e->getMessage());
            $error = 'A server error occurred. Please try again.';
        }
    }
}

$_site = getSettings();

/* Minimal page — no CSS dependencies, self-contained */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo $success ? 'Review Confirmed' : 'Verification Error'; ?> — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:#f4f3f9; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
  .card {
    background:#fff; border-radius:16px; padding:48px 40px; max-width:480px; width:100%;
    text-align:center; box-shadow:0 8px 32px rgba(61,26,110,.1);
  }
  .icon { font-size:52px; margin-bottom:20px; }
  h1 { font-family:'Playfair Display',serif; font-size:26px; color:#3d1a6e; margin-bottom:12px; }
  p  { font-size:15px; color:#5a5870; line-height:1.7; margin-bottom:20px; }
  a.btn {
    display:inline-block; background:#3d1a6e; color:#fff;
    padding:12px 28px; border-radius:8px; font-size:14px; font-weight:600;
    text-decoration:none; transition:background .2s;
  }
  a.btn:hover { background:#5a2d9e; }
  .school { font-size:12px; color:#9b97b0; margin-top:24px; }
</style>
</head>
<body>
  <div class="card">
    <?php if ($success): ?>
    <div class="icon">✅</div>
    <h1>Review Confirmed!</h1>
    <p>
      Thank you for taking the time to share your experience of <?php echo htmlspecialchars($_site['school_name']); ?>.
      Your review has been submitted and will be reviewed by our team before appearing on the website.
    </p>
    <a href="<?php echo $_SERVER['HTTP_HOST'] === 'localhost' ? '/ibeku-high-school/public/' : '/'; ?>index.php" class="btn">
      Return to Homepage
    </a>
    <?php else: ?>
    <div class="icon">❌</div>
    <h1>Verification Failed</h1>
    <p><?php echo htmlspecialchars($error); ?></p>
    <a href="<?php echo $_SERVER['HTTP_HOST'] === 'localhost' ? '/ibeku-high-school/public/' : '/'; ?>index.php" class="btn">
      Return to Homepage
    </a>
    <?php endif; ?>
    <div class="school"><?php echo htmlspecialchars($_site['school_name']); ?>, Umuahia</div>
  </div>
</body>
</html>