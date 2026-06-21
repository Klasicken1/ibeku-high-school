<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';

/* Already logged in? Go straight to dashboard. */
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare(
                'SELECT id, full_name, email, password, role, section,
                        department, class_assigned, is_active
                 FROM   users
                 WHERE  email = ?
                 LIMIT  1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Invalid email or password.';
            } elseif ((int) $user['is_active'] !== 1) {
                $error = 'This account is currently inactive. Contact the system administrator.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Invalid email or password.';
            } else {
                /* ── Success — populate the session ── */
                $_SESSION['admin_id']      = (int) $user['id'];
                $_SESSION['admin_name']    = $user['full_name'];
                $_SESSION['admin_email']   = $user['email'];
                $_SESSION['admin_role']    = $user['role'];
                $_SESSION['admin_section'] = $user['section'];
                $_SESSION['admin_dept']    = $user['department'];
                $_SESSION['admin_class']   = $user['class_assigned'];

                /* Update last_login */
                $updateStmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
                $updateStmt->execute([$user['id']]);

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('IHS admin login error: ' . $e->getMessage());
            $error = 'A server error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Login — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family:'DM Sans', sans-serif;
    background: linear-gradient(135deg, #3d1a6e 0%, #1a0835 60%, #0d1f40 100%);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
  }
  .login-card {
    background:#ffffff;
    border-radius:18px;
    width:100%;
    max-width:420px;
    padding:44px 38px;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
  }
  .login-card__logo {
    width:60px; height:60px;
    border-radius:50%;
    background:#3d1a6e;
    color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-family:'Playfair Display', serif;
    font-weight:900;
    font-size:22px;
    margin:0 auto 18px;
  }
  .login-card h1 {
    font-family:'Playfair Display', serif;
    font-size:1.5rem;
    color:#3d1a6e;
    text-align:center;
    margin-bottom:4px;
  }
  .login-card p.sub {
    text-align:center;
    font-size:13px;
    color:#6b6b80;
    margin-bottom:28px;
  }
  .form-group { margin-bottom:18px; }
  .form-label {
    display:block;
    font-size:12.5px;
    font-weight:600;
    color:#3d1a6e;
    margin-bottom:6px;
  }
  .form-input {
    width:100%;
    padding:11px 14px;
    border:1.5px solid #e2e0ea;
    border-radius:8px;
    font-size:14px;
    font-family:'DM Sans', sans-serif;
    transition:border-color .2s;
  }
  .form-input:focus {
    outline:none;
    border-color:#4a90d9;
  }
  .btn-login {
    width:100%;
    background:#3d1a6e;
    color:#fff;
    border:none;
    padding:13px;
    border-radius:8px;
    font-size:14.5px;
    font-weight:700;
    font-family:'DM Sans', sans-serif;
    cursor:pointer;
    transition:background .2s;
    margin-top:6px;
  }
  .btn-login:hover { background:#5a2d9e; }
  .error-box {
    background:#ffe6e6;
    border:1px solid #ffcccc;
    color:#cc3333;
    padding:11px 14px;
    border-radius:8px;
    font-size:13px;
    margin-bottom:18px;
    text-align:center;
  }
  .back-link {
    display:block;
    text-align:center;
    margin-top:22px;
    font-size:12.5px;
    color:#6b6b80;
    text-decoration:none;
  }
  .back-link:hover { color:#3d1a6e; }
</style>
</head>
<body>

  <div class="login-card">
    <div class="login-card__logo">IHS</div>
    <h1>Admin Login</h1>
    <p class="sub">Ibeku High School — Staff Portal</p>

    <?php if ($error !== ''): ?>
    <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input class="form-input" type="email" id="email" name="email" required autofocus
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"/>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-input" type="password" id="password" name="password" required/>
      </div>
      <button class="btn-login" type="submit">Sign In</button>
    </form>

    <a href="<?php echo dirname($_SERVER['SCRIPT_NAME'], 2); ?>/index.php" class="back-link">← Back to school website</a>
  </div>

</body>
</html>