<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT PORTAL NAVIGATION
   File: src/includes/portal-nav.php

   Included by all portal pages.
   $student must already be available (from requireStudentLogin).
   ============================================================ */

if (!isset($student)) $student = currentStudent();

/* Unread notification count for badge */
$_navUnread = 0;
if ($student && isset($pdo)) {
    try {
        $_navStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM student_notifications WHERE student_id = ? AND is_read = 0'
        );
        $_navStmt->execute([$student['id']]);
        $_navUnread = (int) $_navStmt->fetchColumn();
    } catch (PDOException $e) { /* silent */ }
}

$_navPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="portal-header">
  <div class="portal-header__inner">

    <a href="dashboard.php" class="portal-header__brand">
      <div class="portal-header__crest">IHS</div>
      <div class="portal-header__title">
        <strong>Ibeku High School</strong>
        <small>Student Portal</small>
      </div>
    </a>

    <nav class="portal-nav" id="portalNav">
      <a href="dashboard.php"
         class="portal-nav__link <?php echo $_navPage === 'dashboard' ? 'portal-nav__link--active' : ''; ?>">
        Home
      </a>
      <a href="results.php"
         class="portal-nav__link <?php echo $_navPage === 'results' ? 'portal-nav__link--active' : ''; ?>">
        Results
      </a>
      <a href="notifications.php"
         class="portal-nav__link <?php echo $_navPage === 'notifications' ? 'portal-nav__link--active' : ''; ?>">
        Notices
        <?php if ($_navUnread > 0): ?>
        <span class="portal-nav__badge"><?php echo $_navUnread; ?></span>
        <?php endif; ?>
      </a>
      <a href="profile.php"
         class="portal-nav__link <?php echo $_navPage === 'profile' ? 'portal-nav__link--active' : ''; ?>">
        Profile
      </a>
    </nav>

    <div class="portal-header__right">
      <span class="portal-header__student-name">
        <?php echo htmlspecialchars($student['first_name'] ?? ''); ?>
      </span>
      <a href="logout.php" class="portal-header__logout">Sign Out</a>
      <button class="portal-header__burger" id="portalBurger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>
</header>

<script>
  document.getElementById('portalBurger').addEventListener('click', function () {
    document.getElementById('portalNav').classList.toggle('portal-nav--open');
  });
</script>