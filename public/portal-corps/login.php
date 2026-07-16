<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER PORTAL LOGIN
   File: public/portal-corps/login.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

corpsSessionStart();

if (corpsLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stateCode = strtoupper(trim($_POST['state_code'] ?? ''));
    $password  = $_POST['password'] ?? '';

    if ($stateCode === '' || $password === '') {
        $error = 'Please enter your state code and password.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "SELECT * FROM corps_members
             WHERE state_code = ? AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$stateCode]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            $error = 'State code not found or account is inactive.';
        } elseif (empty($member['password'])) {
            if ($password !== $stateCode) {
                $error = 'Incorrect password. Your default password is your state code.';
            } else {
                loginCorpsMember($member);
                header('Location: dashboard.php');
                exit;
            }
        } elseif (!password_verify($password, $member['password'])) {
            $error = 'Incorrect password.';
        } else {
            loginCorpsMember($member);
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Corps Portal - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#3d1a6e 0%,#1a0e3a 50%,#0d1b3e 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem}
    .wrap{width:100%;max-width:420px}
    .brand{text-align:center;margin-bottom:2rem}
    .brand__crest{width:64px;height:64px;background:rgba(255,255,255,.12);border:2px solid rgba(255,255,255,.2);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:900;color:#fff;letter-spacing:2px;margin-bottom:1rem}
    .brand__name{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;color:#fff;display:block;margin-bottom:4px}
    .brand__sub{font-size:0.8rem;color:rgba(255,255,255,.55)}
    .badge{display:inline-block;background:#e8a020;color:#fff;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:3px 12px;border-radius:20px;margin-top:8px}
    .card{background:#fff;border-radius:20px;padding:2.25rem 2rem;box-shadow:0 24px 60px rgba(0,0,0,.35)}
    .card__title{font-family:'Playfair Display',serif;font-size:1.35rem;font-weight:700;color:#3d1a6e;margin-bottom:.35rem}
    .card__sub{font-size:.85rem;color:#9b97b0;margin-bottom:1.75rem}
    .error{background:#fff0f0;border:1px solid #ffd0d0;border-left:4px solid #cc3333;border-radius:8px;padding:10px 14px;font-size:.85rem;color:#cc3333;margin-bottom:1.25rem}
    .form-group{margin-bottom:1.1rem}
    .form-label{display:block;font-size:.75rem;font-weight:600;color:#3d1a6e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
    .form-input{width:100%;padding:11px 14px;border:1.5px solid #e2e0ea;border-radius:10px;font-size:.95rem;font-family:'DM Sans',sans-serif;color:#1a1a2e;transition:border-color .15s}
    .form-input:focus{outline:none;border-color:#4a90d9;box-shadow:0 0 0 3px rgba(74,144,217,.12)}
    .hint{font-size:.75rem;color:#9b97b0;margin-top:5px}
    .btn{width:100%;background:#3d1a6e;color:#fff;border:none;padding:13px;border-radius:10px;font-size:1rem;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;margin-top:.5rem;transition:background .2s}
    .btn:hover{background:#5a2d9e}
    .divider{text-align:center;margin:1.5rem 0 1rem;font-size:.8rem;color:#c8c4dc;position:relative}
    .divider::before,.divider::after{content:'';position:absolute;top:50%;width:40%;height:1px;background:#e8e6f0}
    .divider::before{left:0}.divider::after{right:0}
    .back{display:block;text-align:center;font-size:.85rem;color:#4a90d9;text-decoration:none}
    .back:hover{text-decoration:underline}
    .footer{text-align:center;margin-top:1.5rem;font-size:.75rem;color:rgba(255,255,255,.35)}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="brand">
      <div class="brand__crest">IHS</div>
      <span class="brand__name">Ibeku High School</span>
      <span class="brand__sub">Corps Member Portal</span>
      <div><span class="badge">NYSC</span></div>
    </div>
    <div class="card">
      <h1 class="card__title">Corps Member Login</h1>
      <p class="card__sub">Enter your state code and password to access your portal.</p>
      <?php if ($error): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <form method="POST" autocomplete="on">
        <div class="form-group">
          <label class="form-label" for="state_code">State Code</label>
          <input type="text" id="state_code" name="state_code" class="form-input"
                 value="<?php echo htmlspecialchars(strtoupper($_POST['state_code'] ?? '')); ?>"
                 placeholder="e.g. AB/25C/0245" autocomplete="username" required/>
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-input"
                 placeholder="Enter your password" autocomplete="current-password" required/>
          <p class="hint">First time? Your default password is your state code.</p>
        </div>
        <button type="submit" class="btn">Sign In</button>
      </form>
      <div class="divider">or</div>
      <a href="../index.php" class="back">Back to school website</a>
    </div>
    <p class="footer">Having trouble? Contact the school office or your supervisor.</p>
  </div>
</body>
</html>