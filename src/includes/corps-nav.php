<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER PORTAL NAV
   File: src/includes/corps-nav.php
   ============================================================ */

if (!isset($corpsMember)) $corpsMember = currentCorpsMember();

$_navUnread = 0;
if ($corpsMember && isset($pdo)) {
    try {
        $_cNavStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM corps_messages
             WHERE corps_member_id = ? AND sender_type = 'admin' AND is_read = 0"
        );
        $_cNavStmt->execute([$corpsMember['id']]);
        $_navUnread = (int) $_cNavStmt->fetchColumn();
    } catch (PDOException $e) {}
}

$_navPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="corps-header">
  <div class="corps-header__inner">
    <a href="dashboard.php" class="corps-header__brand">
      <div class="corps-header__crest">IHS</div>
      <div class="corps-header__title">
        <strong>Ibeku High School</strong>
        <small>Corps Member Portal</small>
      </div>
    </a>
    <nav class="corps-nav" id="corpsNav">
      <a href="dashboard.php" class="corps-nav__link <?php echo $_navPage === 'dashboard' ? 'corps-nav__link--active' : ''; ?>">Home</a>
      <a href="clearance.php" class="corps-nav__link <?php echo $_navPage === 'clearance' ? 'corps-nav__link--active' : ''; ?>">Clearance</a>
      <a href="messages.php" class="corps-nav__link <?php echo $_navPage === 'messages' ? 'corps-nav__link--active' : ''; ?>">
        Messages
        <?php if ($_navUnread > 0): ?>
        <span class="corps-nav__badge"><?php echo $_navUnread; ?></span>
        <?php endif; ?>
      </a>
    </nav>
    <div class="corps-header__right">
      <span class="corps-header__name"><?php echo htmlspecialchars(explode(' ', $corpsMember['full_name'])[0] ?? ''); ?></span>
      <a href="logout.php" class="corps-header__logout">Sign Out</a>
      <button class="corps-header__burger" id="corpsBurger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
<script>
  document.getElementById('corpsBurger').addEventListener('click', function () {
    document.getElementById('corpsNav').classList.toggle('corps-nav--open');
  });
</script>