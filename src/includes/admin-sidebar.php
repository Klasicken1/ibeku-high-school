<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMIN SIDEBAR (SHARED PARTIAL)
   File: src/includes/admin-sidebar.php
   ============================================================ */

function renderAdminSidebar(array $admin, string $currentPage = ''): void {
    $role = $admin['role'];
    $pdo  = getDB();

    /* ── Unread message count for bell badge ── */
    $unreadCount = 0;
    try {
        $unreadStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM staff_messages
             WHERE recipient_id = ? AND is_read = 0"
        );
        $unreadStmt->execute([$admin['id']]);
        $unreadCount = (int) $unreadStmt->fetchColumn();
    } catch (PDOException $e) {
        /* Table may not exist yet on older installs — fail silently */
        $unreadCount = 0;
    }

    $navItems = [
        ['dashboard',          'index.php',              '🏠', 'Dashboard',         null],

        ['timetables-ss',      'timetables-ss.php',      '📅', 'SS Timetables',     ['superadmin', 'dean']],
        ['timetables-js',      'timetables-js.php',      '📅', 'JS Timetables',     ['superadmin', 'dean']],

        ['results-entry',      'results-entry.php',      '📊', 'Enter Results',     ['superadmin', 'subject_teacher', 'form_teacher', 'vp_academics']],
        ['results-approve',    'results-approve.php',    '🔍', 'Approve Results',   ['superadmin', 'form_teacher']],
        ['results-publish',    'results-publish.php',    '✅', 'Publish Results',   ['superadmin', 'vp_academics']],

        ['news-create',        'news-create.php',        '📰', 'Create News',       ['superadmin', 'principal', 'vp_general']],
        ['news',               'news.php',               '📋', 'All News',          ['superadmin', 'principal', 'vp_general']],

        ['events',             'events.php',             '📆', 'All Events',        ['superadmin', 'principal', 'vp_general', 'dean']],
        ['events-create',      'events-create.php',      '➕', 'Create Event',      ['superadmin', 'principal', 'vp_general', 'dean']],

        ['gallery',            'gallery.php',            '🖼️', 'Gallery',           null],
        ['gallery-upload',     'gallery-upload.php',     '📷', 'Upload Photos',     ['superadmin', 'principal', 'vp_general']],

        ['students',           'students.php',           '🎒', 'Students',          ['superadmin', 'principal', 'vp_admin', 'form_teacher']],
        ['students-promote',   'students-promote.php',   '⬆️', 'Promote Students',  ['superadmin', 'principal', 'form_teacher']],

        ['admissions',         'admissions.php',         '🎓', 'Admissions',        null],

        ['staff',              'staff.php',              '👨‍🏫', 'Staff Directory',   ['superadmin']],
        ['milestones',         'milestones.php',         '🕐', 'History Timeline',  ['superadmin']],
        ['clubs',              'clubs.php',              '🎭', 'Clubs & Societies', ['superadmin']],
        ['awards',             'awards.php',             '🏆', 'Awards',            ['superadmin']],
        ['alumni',             'alumni.php',             '🌍', 'Alumni Directory',  ['superadmin']],
        ['scholarships',       'scholarships.php',       '🎓', 'Scholarships',      ['superadmin']],
        ['prefects-admin',     'prefects-admin.php',     '🎖️', 'Prefects',          ['superadmin', 'principal']],
        ['hall-of-fame-admin', 'hall-of-fame-admin.php', '🏆', 'Hall of Fame',      ['superadmin']],
        ['nominations',        'nominations.php',        '📬', 'Nominations',       ['superadmin']],
        ['reviews',            'reviews.php',            '⭐', 'Reviews',           ['superadmin', 'principal', 'vp_general']],

        ['messages',           'messages.php',           '💬', 'Messages',          null],
        ['push-notifications', 'push-notifications.php', '🔔', 'Push Notifications', ['superadmin', 'principal']],

        ['users',              'users.php',              '👥', 'Manage Users',      ['superadmin']],
        ['class-arms',         'class-arms.php',         '🏫', 'Manage Classes',    ['superadmin']],
        ['subjects',           'subjects.php',           '📚', 'Manage Subjects',   ['superadmin']],
        ['settings',           'settings.php',           '⚙️', 'Settings',          ['superadmin']],
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

      <!-- ── Bell icon for messages ── -->
      <div class="admin-sidebar__bell">
        <a href="messages.php" class="admin-bell" title="Messages">
          <span class="admin-bell__icon">🔔</span>
          <?php if ($unreadCount > 0): ?>
          <span class="admin-bell__badge"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
          <?php endif; ?>
          <span class="admin-bell__label">
            Messages<?php echo $unreadCount > 0 ? ' (' . $unreadCount . ' unread)' : ''; ?>
          </span>
        </a>
      </div>

      <nav class="admin-sidebar__nav">
        <?php foreach ($navItems as [$key, $href, $icon, $label, $allowedRoles]):
            if ($allowedRoles !== null && !in_array($role, $allowedRoles, true)) continue;
            $isActive = $currentPage === $key;
            /* Append unread badge inline for the Messages link */
            $badgeHtml = ($key === 'messages' && $unreadCount > 0)
                ? ' <span style="background:#cc3333;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px;margin-left:auto">'
                  . ($unreadCount > 99 ? '99+' : $unreadCount)
                  . '</span>'
                : '';
        ?>
        <a href="<?php echo $href; ?>"
           class="admin-sidebar__link <?php echo $isActive ? 'admin-sidebar__link--active' : ''; ?>"
           style="<?php echo $key === 'messages' ? 'display:flex;align-items:center' : ''; ?>">
          <span class="admin-sidebar__icon"><?php echo $icon; ?></span>
          <span><?php echo $label; ?></span>
          <?php echo $badgeHtml; ?>
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

    <style>
      .admin-sidebar__bell {
        margin: 0 12px 8px;
        border-bottom: 1px solid rgba(255,255,255,.08);
        padding-bottom: 10px;
      }
      .admin-bell {
        display: flex; align-items: center; gap: 8px;
        background: rgba(255,255,255,.06); border-radius: 8px;
        padding: 8px 12px; text-decoration: none; position: relative;
        transition: background .15s;
      }
      .admin-bell:hover { background: rgba(255,255,255,.12); }
      .admin-bell__icon { font-size: 16px; }
      .admin-bell__label { font-size: 13px; color: rgba(255,255,255,.85); font-weight: 500; }
      .admin-bell__badge {
        position: absolute; top: 4px; left: 26px;
        background: #cc3333; color: #fff;
        font-size: 9px; font-weight: 700;
        padding: 1px 5px; border-radius: 20px;
        border: 1.5px solid #2a0a4e;
        min-width: 16px; text-align: center;
        line-height: 1.4;
      }
    </style>
    <?php
}