<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER CHANGE PASSWORD
   File: public/portal-corps/change-password.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

$corpsMember = requireCorpsLogin();
$pdo         = getDB();

$success    = false;
$fieldError = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '')           $fieldError['current'] = 'Please enter your current password.';
    if ($new === '')               $fieldError['new']     = 'Please enter a new password.';
    elseif (strlen($new) < 6)     $fieldError['new']     = 'Password must be at least 6 characters.';
    if ($confirm === '')           $fieldError['confirm'] = 'Please confirm your new password.';
    elseif ($new !== $confirm)     $fieldError['confirm'] = 'Passwords do not match.';

    if (empty($fieldError)) {
        $stmt = $pdo->prepare('SELECT password, state_code FROM corps_members WHERE id = ? LIMIT 1');
        $stmt->execute([$corpsMember['id']]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        $currentValid = false;
        if (!empty($row['password'])) {
            $currentValid = password_verify($current, $row['password']);
        } else {
            $currentValid = ($current === $row['state_code']);
        }

        if (!$currentValid) {
            $fieldError['current'] = 'Current password is incorrect.';
        } elseif ($new === $row['state_code']) {
            $fieldError['new'] = 'New password cannot be the same as your state code.';
        } else {
            $pdo->prepare('UPDATE corps_members SET password = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $corpsMember['id']]);
            $success = true;
            refreshCorpsSession($pdo);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Change Password - Corps Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/corps-portal.css"/>
  <style>
    .pw-card{background:#fff;border:1px solid var(--border);border-radius:16px;padding:2rem;max-width:480px}
    .pw-card__title{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;color:var(--purple);margin-bottom:.5rem}
    .pw-card__sub{font-size:.875rem;color:var(--muted);margin-bottom:1.5rem;line-height:1.6}
    .form-group{margin-bottom:1.1rem}
    .form-label{display:block;font-size:.75rem;font-weight:600;color:var(--purple);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
    .form-input-wrap{position:relative}
    .form-input{width:100%;padding:10px 42px 10px 13px;border:1.5px solid #e2e0ea;border-radius:9px;font-size:.95rem;font-family:'DM Sans',sans-serif;color:var(--text);transition:border-color .15s}
    .form-input:focus{outline:none;border-color:var(--blue)}
    .form-input--error{border-color:#cc3333!important}
    .toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--light);padding:0}
    .field-error{font-size:.78rem;color:#cc3333;margin-top:4px;display:block}
    .strength-bar{height:4px;border-radius:2px;background:#f0eef6;margin-top:6px;overflow:hidden}
    .strength-bar__fill{height:100%;border-radius:2px;width:0%;transition:width .3s,background .3s}
    .strength-label{font-size:.72rem;color:var(--light);margin-top:3px;display:block}
    .btn-submit{width:100%;background:var(--purple);color:#fff;border:none;padding:12px;border-radius:9px;font-size:1rem;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;margin-top:.5rem}
    .btn-submit:hover{background:var(--purple-l)}
    .btn-back{display:inline-block;margin-top:.75rem;font-size:.875rem;color:var(--blue);text-decoration:none;font-weight:600;text-align:center;width:100%}
    .success-box{background:#e6f9ed;border:1px solid #b2dfce;border-radius:12px;padding:1.5rem;text-align:center}
    .success-box__icon{font-size:2.5rem;display:block;margin-bottom:.75rem}
    .success-box__title{font-family:'Playfair Display',serif;font-size:1.2rem;color:#1a7a3a;margin-bottom:.5rem}
  </style>
</head>
<body>
<?php include dirname(__DIR__, 2) . '/src/includes/corps-nav.php'; ?>
<main class="corps-main">
  <div class="corps-inner">
    <div class="page-hero">
      <h1 class="page-hero__title">Change Password</h1>
      <p class="page-hero__sub">Keep your portal account secure.</p>
    </div>
    <div class="pw-card">
      <?php if ($success): ?>
      <div class="success-box">
        <span class="success-box__icon">&#128274;</span>
        <h2 class="success-box__title">Password Changed</h2>
        <p style="font-size:.875rem;color:#2a6a3a;margin-bottom:1rem">Your password has been updated successfully.</p>
        <a href="dashboard.php" class="btn-submit" style="display:inline-block;text-decoration:none;padding:10px 28px;width:auto">Back to Dashboard</a>
      </div>
      <?php else: ?>
      <h2 class="pw-card__title">Set New Password</h2>
      <p class="pw-card__sub">Default password is your state code (<strong><?php echo htmlspecialchars($corpsMember['state_code']); ?></strong>). Choose a stronger password.</p>
      <form method="POST" autocomplete="off" novalidate>
        <div class="form-group">
          <label class="form-label">Current Password *</label>
          <div class="form-input-wrap">
            <input type="password" name="current_password" class="form-input <?php echo isset($fieldError['current']) ? 'form-input--error' : ''; ?>" required/>
            <button type="button" class="toggle-pw" onclick="toggleVis(this.previousElementSibling)">&#128065;</button>
          </div>
          <?php if (isset($fieldError['current'])): ?><span class="field-error"><?php echo htmlspecialchars($fieldError['current']); ?></span><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">New Password *</label>
          <div class="form-input-wrap">
            <input type="password" name="new_password" class="form-input <?php echo isset($fieldError['new']) ? 'form-input--error' : ''; ?>" oninput="updateStrength(this.value)" required/>
            <button type="button" class="toggle-pw" onclick="toggleVis(this.previousElementSibling)">&#128065;</button>
          </div>
          <div class="strength-bar"><div class="strength-bar__fill" id="strengthFill"></div></div>
          <span class="strength-label" id="strengthLabel"></span>
          <?php if (isset($fieldError['new'])): ?><span class="field-error"><?php echo htmlspecialchars($fieldError['new']); ?></span><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password *</label>
          <div class="form-input-wrap">
            <input type="password" name="confirm_password" class="form-input <?php echo isset($fieldError['confirm']) ? 'form-input--error' : ''; ?>" required/>
            <button type="button" class="toggle-pw" onclick="toggleVis(this.previousElementSibling)">&#128065;</button>
          </div>
          <?php if (isset($fieldError['confirm'])): ?><span class="field-error"><?php echo htmlspecialchars($fieldError['confirm']); ?></span><?php endif; ?>
        </div>
        <button type="submit" class="btn-submit">Update Password</button>
        <a href="profile.php" class="btn-back">Back to Profile</a>
      </form>
      <?php endif; ?>
    </div>
  </div>
</main>
<script>
function toggleVis(input){input.type=input.type==='password'?'text':'password'}
function updateStrength(v){
  var f=document.getElementById('strengthFill'),l=document.getElementById('strengthLabel'),s=0;
  if(v.length>=6)s++;if(v.length>=10)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  var c=[{p:'0%',b:'#e8e6f0',t:''},{p:'20%',b:'#cc3333',t:'Very weak'},{p:'40%',b:'#e8a020',t:'Weak'},{p:'60%',b:'#4a90d9',t:'Fair'},{p:'80%',b:'#1a7a3a',t:'Strong'},{p:'100%',b:'#1a7a3a',t:'Very strong'}];
  f.style.width=c[s].p;f.style.background=c[s].b;l.textContent=c[s].t;l.style.color=c[s].b;
}
</script>
</body>
</html>