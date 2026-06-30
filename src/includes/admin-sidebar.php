<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMIN SIDEBAR (SHARED PARTIAL)
   File: src/includes/admin-sidebar.php
   ============================================================ */

function renderAdminSidebar(array $admin, string $currentPage = ''): void {
    $role = $admin['role'];

    $navItems = [
        ['dashboard',        'index.php',            '🏠', 'Dashboard',         null],

        ['timetables-ss',    'timetables-ss.php',    '📅', 'SS Timetables',     ['superadmin', 'dean']],
        ['timetables-js',    'timetables-js.php',    '📅', 'JS Timetables',     ['superadmin', 'dean']],

        ['results-entry',    'results-entry.php',    '📊', 'Enter Results',     ['superadmin', 'subject_teacher', 'form_teacher', 'vp_academics']],
        ['results-approve',  'results-approve.php',  '🔍', 'Approve Results',   ['superadmin', 'form_teacher']],
        ['results-publish',  'results-publish.php',  '✅', 'Publish Results',   ['superadmin', 'vp_academics']],

        ['news-create',      'news-create.php',      '📰', 'Create News',       ['superadmin', 'principal', 'vp_general']],
        ['news',             'news.php',             '📋', 'All News',          ['superadmin', 'principal', 'vp_general']],

        ['students',         'students.php',         '🎒', 'Students',          ['superadmin', 'principal', 'vp_admin', 'form_teacher']],
        ['students-promote', 'students-promote.php', '⬆️', 'Promote Students',  ['superadmin', 'principal', 'form_teacher']],

        ['admissions',       'admissions.php',       '🎓', 'Admissions',        null],
        ['gallery',          'gallery.php',          '🖼️', 'Gallery',           null],

        ['users',            'users.php',            '👥', 'Manage Users',      ['superadmin']],
        ['class-arms',       'class-arms.php',       '🏫', 'Manage Classes',    ['superadmin']],
        ['subjects',         'subjects.php',         '📚', 'Manage Subjects',   ['superadmin']],
['staff',       'staff.php',       '👨‍🏫', 'Staff Directory',   ['superadmin']],
['milestones',  'milestones.php',  '🕐',  'History Timeline',  ['superadmin']],
['clubs',       'clubs.php',       '🎭',  'Clubs & Societies', ['superadmin']],
['awards',      'awards.php',      '🏆',  'Awards',            ['superadmin']],        ['settings',         'settings.php',         '⚙️', 'Settings',          ['superadmin']],
['alumni',       'alumni.php',       '🌍', 'Alumni Directory',    ['superadmin']],
['scholarships', 'scholarships.php', '🎓', 'Scholarships',        ['superadmin']],
['prefects-admin', 'prefects-admin.php', '🎖️', 'Prefects', ['superadmin', 'principal']],
['hall-of-fame-admin', 'hall-of-fame-admin.php', '🏆', 'Hall of Fame',   ['superadmin']],
['nominations',        'nominations.php',         '📬', 'Nominations',    ['superadmin']],
['reviews', 'reviews.php', '⭐', 'Reviews', ['superadmin', 'principal', 'vp_general']],
    ];
    ?>

    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="admin-sidebar" id="adminSidebar">
      <div class="admin-sidebar__brand">
        <div class="admin-sidebar__logo">IHS</div>
        <div>
          <strong>Ibeku High School</strong>
          <span>Admin Panel</span>
        </div>
      </div>

      <nav class="admin-sidebar__nav">
        <?php foreach ($navItems as [$key, $href, $icon, $label, $allowedRoles]):
            if ($allowedRoles !== null && !in_array($role, $allowedRoles, true)) continue;
            $isActive = $currentPage === $key;
        ?>
        <a href="<?php echo $href; ?>" class="admin-sidebar__link <?php echo $isActive ? 'admin-sidebar__link--active' : ''; ?>">
          <span class="admin-sidebar__icon"><?php echo $icon; ?></span>
          <span><?php echo $label; ?></span>
        </a>
        <?php endforeach; ?>
      </nav>

      <div class="admin-sidebar__footer">
        <div class="admin-sidebar__user">
          <strong><?php echo htmlspecialchars($admin['name']); ?></strong>
          <span><?php echo htmlspecialchars(roleLabel($admin['role'], $admin['section'])); ?></span>
        </div>
        <a href="logout.php" class="admin-sidebar__logout">Log Out</a>
      </div>
    </aside>
    <?php
}