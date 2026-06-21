<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMIN DASHBOARD
   File: public/admin/index.php
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';

requireLogin();

$admin = currentAdmin();
$roleDisplay = roleLabel($admin['role'], $admin['section']);

/* Pull a couple of live numbers for the dashboard cards */
$pdo = getDB();

$studentCount = (int) $pdo->query('SELECT COUNT(*) FROM students WHERE is_active = 1')->fetchColumn();
$newAdmissions = (int) $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'new'")->fetchColumn();
$unreadMessages = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
$subscriberCount = (int) $pdo->query('SELECT COUNT(*) FROM subscribers WHERE is_active = 1')->fetchColumn();

/* Flash error message from a blocked requireRole() redirect, if any */
$flashError = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Dashboard — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family:'DM Sans', sans-serif;
    background:#f4f3f9;
    color:#1a1a2e;
  }

  /* ── Top bar ── */
  .topbar {
    background:#3d1a6e;
    color:#fff;
    padding:14px 28px;
    display:flex;
    align-items:center;
    justify-content:space-between;
  }
  .topbar__brand {
    display:flex;
    align-items:center;
    gap:12px;
  }
  .topbar__logo {
    width:36px; height:36px;
    border-radius:50%;
    background:rgba(255,255,255,.15);
    display:flex; align-items:center; justify-content:center;
    font-family:'Playfair Display', serif;
    font-weight:900;
    font-size:14px;
  }
  .topbar__brand h1 {
    font-size:15px;
    font-weight:700;
  }
  .topbar__user {
    display:flex;
    align-items:center;
    gap:16px;
    font-size:13px;
  }
  .topbar__user-info { text-align:right; }
  .topbar__user-info strong { display:block; font-size:13.5px; }
  .topbar__user-info span { font-size:11.5px; color:rgba(255,255,255,.6); }
  .logout-btn {
    background:rgba(255,255,255,.12);
    color:#fff;
    border:1px solid rgba(255,255,255,.25);
    padding:7px 16px;
    border-radius:7px;
    font-size:12.5px;
    font-weight:600;
    text-decoration:none;
    transition:background .2s;
  }
  .logout-btn:hover { background:rgba(255,255,255,.22); }

  /* ── Main content ── */
  .main { padding:32px 28px; max-width:1200px; margin:0 auto; }

  .welcome h2 {
    font-family:'Playfair Display', serif;
    font-size:1.7rem;
    color:#3d1a6e;
    margin-bottom:4px;
  }
  .welcome p {
    font-size:13.5px;
    color:#6b6b80;
    margin-bottom:28px;
  }

  .flash-error {
    background:#ffe6e6;
    border:1px solid #ffcccc;
    color:#cc3333;
    padding:12px 16px;
    border-radius:8px;
    font-size:13.5px;
    margin-bottom:24px;
  }

  /* ── Stat cards ── */
  .stats-grid {
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:18px;
    margin-bottom:36px;
  }
  .stat-card {
    background:#fff;
    border-radius:14px;
    padding:20px 22px;
    border:1px solid #e8e6f0;
  }
  .stat-card__num {
    font-family:'Playfair Display', serif;
    font-size:2rem;
    font-weight:900;
    color:#3d1a6e;
    line-height:1;
  }
  .stat-card__label {
    font-size:12px;
    color:#6b6b80;
    margin-top:6px;
    text-transform:uppercase;
    letter-spacing:.05em;
  }

  /* ── Quick links ── */
  .section-label {
    font-size:13px;
    font-weight:700;
    color:#3d1a6e;
    text-transform:uppercase;
    letter-spacing:.06em;
    margin-bottom:14px;
  }
  .links-grid {
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:16px;
    margin-bottom:36px;
  }
  .link-card {
    background:#fff;
    border:1px solid #e8e6f0;
    border-radius:12px;
    padding:18px 20px;
    text-decoration:none;
    color:#1a1a2e;
    display:flex;
    align-items:center;
    gap:14px;
    transition:all .2s;
  }
  .link-card:hover {
    border-color:#4a90d9;
    box-shadow:0 6px 20px rgba(61,26,110,.08);
    transform:translateY(-2px);
  }
  .link-card__icon {
    width:42px; height:42px;
    border-radius:10px;
    background:#f0ecfa;
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem;
    flex-shrink:0;
  }
  .link-card__text strong {
    display:block;
    font-size:14px;
    margin-bottom:2px;
  }
  .link-card__text span {
    font-size:12px;
    color:#6b6b80;
  }

  .role-badge {
    display:inline-block;
    background:#f0ecfa;
    color:#3d1a6e;
    font-size:11px;
    font-weight:700;
    padding:3px 10px;
    border-radius:20px;
    margin-top:4px;
  }
</style>
</head>
<body>

  <div class="topbar">
    <div class="topbar__brand">
      <div class="topbar__logo">IHS</div>
      <h1>Ibeku High School — Admin Panel</h1>
    </div>
    <div class="topbar__user">
      <div class="topbar__user-info">
        <strong><?php echo htmlspecialchars($admin['name']); ?></strong>
        <span><?php echo htmlspecialchars($roleDisplay); ?></span>
      </div>
      <a href="logout.php" class="logout-btn">Log Out</a>
    </div>
  </div>

  <div class="main">

    <div class="welcome">
      <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $admin['name'])[0]); ?></h2>
      <p>
        Logged in as <span class="role-badge"><?php echo htmlspecialchars($roleDisplay); ?></span>
      </p>
    </div>

    <?php if ($flashError): ?>
    <div class="flash-error"><?php echo htmlspecialchars($flashError); ?></div>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-card__num"><?php echo $studentCount; ?></div>
        <div class="stat-card__label">Active Students</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__num"><?php echo $newAdmissions; ?></div>
        <div class="stat-card__label">New Admissions</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__num"><?php echo $unreadMessages; ?></div>
        <div class="stat-card__label">Unread Messages</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__num"><?php echo $subscriberCount; ?></div>
        <div class="stat-card__label">Newsletter Subscribers</div>
      </div>
    </div>

    <div class="section-label">Quick Actions</div>
    <div class="links-grid">

      <?php if (in_array($admin['role'], ['superadmin', 'dean'], true)): ?>
      <a href="timetables-ss.php" class="link-card">
        <div class="link-card__icon">📅</div>
        <div class="link-card__text">
          <strong>Manage Timetables</strong>
          <span>Upload SS or JS timetables</span>
        </div>
      </a>
      <?php endif; ?>

      <?php if (in_array($admin['role'], ['superadmin', 'subject_teacher', 'vp_academics'], true)): ?>
      <a href="results-entry.php" class="link-card">
        <div class="link-card__icon">📊</div>
        <div class="link-card__text">
          <strong>Enter Results</strong>
          <span>Upload subject scores</span>
        </div>
      </a>
      <?php endif; ?>

      <?php if (in_array($admin['role'], ['superadmin', 'principal', 'vp_general'], true)): ?>
      <a href="news-create.php" class="link-card">
        <div class="link-card__icon">📰</div>
        <div class="link-card__text">
          <strong>Post News</strong>
          <span>Create a news article</span>
        </div>
      </a>
      <?php endif; ?>

      <?php if ($admin['role'] === 'superadmin'): ?>
      <a href="users.php" class="link-card">
        <div class="link-card__icon">👥</div>
        <div class="link-card__text">
          <strong>Manage Users</strong>
          <span>Staff accounts and roles</span>
        </div>
      </a>
      <?php endif; ?>

      <a href="admissions.php" class="link-card">
        <div class="link-card__icon">🎓</div>
        <div class="link-card__text">
          <strong>Admissions Enquiries</strong>
          <span><?php echo $newAdmissions; ?> new enquiries</span>
        </div>
      </a>

      <a href="gallery.php" class="link-card">
        <div class="link-card__icon">🖼️</div>
        <div class="link-card__text">
          <strong>Gallery</strong>
          <span>Manage school photos</span>
        </div>
      </a>

    </div>

  </div>

</body>
</html>