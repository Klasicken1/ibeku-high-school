<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMIN CHANGE PASSWORD
   File: public/admin/change-password.php

   Self-service password change for any logged-in admin/staff
   account. Unlike students, users.password is always a hash
   (NOT NULL) from account creation, so there's no "default
   password" edge case to handle here.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireLogin();

$admin = currentAdmin();
$pdo   = getDB();

$success    = false;
$error      = '';
$fieldError = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    /* ── Validation ── */
    if ($current === '') {
        $fieldError['current'] = 'Please enter your current password.';
    }
    if ($new === '') {
        $fieldError['new'] = 'Please enter a new password.';
    } elseif (strlen($new) < 6) {
        $fieldError['new'] = 'Password must be at least 6 characters.';
    }
    if ($confirm === '') {
        $fieldError['confirm'] = 'Please confirm your new password.';
    } elseif ($new !== $confirm) {
        $fieldError['confirm'] = 'Passwords do not match.';
    }

    if (empty($fieldError)) {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$admin['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $error = 'Account not found. Please log in again.';
        } elseif (!password_verify($current, $row['password'])) {
            $fieldError['current'] = 'Current password is incorrect.';
        } elseif (password_verify($new, $row['password'])) {
            $fieldError['new'] = 'New password must be different from your current password.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([$newHash, $admin['id']]);
            $success = true;
        }
    }

    if (!empty($fieldError) && empty($error)) {
        $error = 'Please correct the errors below.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Change Password — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .pw-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:26px; max-width:480px; }

  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }

  .form-input-wrap { position:relative; }
  .form-input {
    width:100%; padding:10px 42px 10px 13px; border:1.5px solid #e2e0ea; border-radius:8px;
    font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e;
  }
  .form-input:focus { outline:none; border-color:#4a90d9; }
  .form-input--error { border-color:#cc3333 !important; }

  .toggle-pw {
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    background:none; border:none; cursor:pointer; font-size:1rem; color:#9b97b0; padding:0; line-height:1;
  }

  .field-error { font-size:0.78rem; color:#cc3333; margin-top:4px; display:block; }

  .strength-bar { height:4px; border-radius:2px; background:#f0eef6; margin-top:6px; overflow:hidden; }
  .strength-bar__fill { height:100%; border-radius:2px; width:0%; transition:width .3s, background .3s; }
  .strength-label { font-size:0.72rem; color:#9b97b0; margin-top:3px; display:block; }

  .alert-error {
    background:#fff0f0; border:1px solid #ffd0d0; border-left:4px solid #cc3333;
    border-radius:8px; padding:10px 14px; font-size:0.85rem; color:#cc3333; margin-bottom:1.25rem;
  }

  .alert-success {
    background:#e6f9ed; border:1px solid #b2dfce; border-radius:12px; padding:1.5rem; text-align:center;
  }
  .alert-success__icon { font-size:2.5rem; display:block; margin-bottom:.75rem; }
  .alert-success__title { font-family:'Playfair Display', serif; font-size:1.2rem; color:#1a7a3a; margin-bottom:.5rem; }
  .alert-success__body { font-size:0.875rem; color:#2a6a3a; margin-bottom:1rem; }

  .btn-submit {
    width:100%; background:#3d1a6e; color:#fff; border:none; padding:11px; border-radius:9px;
    font-size:14px; font-weight:700; font-family:'DM Sans',sans-serif; cursor:pointer;
    margin-top:.5rem; transition:background .2s;
  }
  .btn-submit:hover { background:#5a2d9e; }

  .tips {
    background:#f8f7fc; border:1px solid #e8e6f0; border-radius:10px; padding:12px 16px;
    margin-bottom:1.5rem; font-size:0.82rem; color:#6b6b80; line-height:1.7;
  }
  .tips strong { color:#3d1a6e; display:block; margin-bottom:4px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'change-password'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Change Password</h2>
        <p>Keep your admin account secure with a strong password.</p>
      </div>

      <div class="pw-card">

        <?php if ($success): ?>
        <div class="alert-success">
          <span class="alert-success__icon">🔐</span>
          <h2 class="alert-success__title">Password Changed</h2>
          <p class="alert-success__body">
            Your password has been updated successfully. Use your new password next time you log in.
          </p>
          <a href="index.php" class="btn-submit" style="display:inline-block;text-decoration:none;padding:10px 28px;width:auto">
            Back to Dashboard
          </a>
        </div>

        <?php else: ?>

        <div class="tips">
          <strong>Password tips:</strong>
          At least 6 characters · Mix letters and numbers · Avoid names or dates of birth
        </div>

        <?php if ($error && empty($fieldError)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" novalidate>

          <div class="form-group">
            <label class="form-label" for="current_password">Current Password *</label>
            <div class="form-input-wrap">
              <input type="password" id="current_password" name="current_password"
                     class="form-input <?php echo isset($fieldError['current']) ? 'form-input--error' : ''; ?>"
                     autocomplete="current-password" required/>
              <button type="button" class="toggle-pw" onclick="toggleVisibility('current_password', this)" aria-label="Show/hide password">👁</button>
            </div>
            <?php if (isset($fieldError['current'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($fieldError['current']); ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label class="form-label" for="new_password">New Password *</label>
            <div class="form-input-wrap">
              <input type="password" id="new_password" name="new_password"
                     class="form-input <?php echo isset($fieldError['new']) ? 'form-input--error' : ''; ?>"
                     autocomplete="new-password" oninput="updateStrength(this.value)" required/>
              <button type="button" class="toggle-pw" onclick="toggleVisibility('new_password', this)" aria-label="Show/hide password">👁</button>
            </div>
            <div class="strength-bar"><div class="strength-bar__fill" id="strengthFill"></div></div>
            <span class="strength-label" id="strengthLabel"></span>
            <?php if (isset($fieldError['new'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($fieldError['new']); ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm New Password *</label>
            <div class="form-input-wrap">
              <input type="password" id="confirm_password" name="confirm_password"
                     class="form-input <?php echo isset($fieldError['confirm']) ? 'form-input--error' : ''; ?>"
                     autocomplete="new-password" required/>
              <button type="button" class="toggle-pw" onclick="toggleVisibility('confirm_password', this)" aria-label="Show/hide password">👁</button>
            </div>
            <?php if (isset($fieldError['confirm'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($fieldError['confirm']); ?></span>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn-submit">Update Password →</button>

        </form>
        <?php endif; ?>

      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    function toggleVisibility(id, btn) {
      var input = document.getElementById(id);
      if (!input) return;
      if (input.type === 'password') { input.type = 'text'; btn.textContent = '🙈'; }
      else { input.type = 'password'; btn.textContent = '👁'; }
    }

    function updateStrength(value) {
      var fill  = document.getElementById('strengthFill');
      var label = document.getElementById('strengthLabel');
      if (!fill || !label) return;

      var score = 0;
      if (value.length >= 6)  score++;
      if (value.length >= 10) score++;
      if (/[A-Z]/.test(value)) score++;
      if (/[0-9]/.test(value)) score++;
      if (/[^A-Za-z0-9]/.test(value)) score++;

      var configs = [
        { pct: '0%',   bg: '#e8e6f0', text: '' },
        { pct: '20%',  bg: '#cc3333', text: 'Very weak' },
        { pct: '40%',  bg: '#e8a020', text: 'Weak' },
        { pct: '60%',  bg: '#4a90d9', text: 'Fair' },
        { pct: '80%',  bg: '#1a7a3a', text: 'Strong' },
        { pct: '100%', bg: '#1a7a3a', text: 'Very strong' },
      ];

      var cfg = configs[score] || configs[0];
      fill.style.width      = cfg.pct;
      fill.style.background = cfg.bg;
      label.textContent     = cfg.text;
      label.style.color     = cfg.bg;
    }
  </script>

</body>
</html>