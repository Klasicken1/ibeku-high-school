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
        $unreadCount = 0;
    }

    $navItems = [
        ['dashboard',          'index.php',               '🏠', 'Dashboard',            null],

        ['timetables-ss',      'timetables-ss.php',       '📅', 'SS Timetables',        ['superadmin', 'dean', 'section_admin']],
        ['timetables-js',      'timetables-js.php',       '📅', 'JS Timetables',        ['superadmin', 'dean', 'section_admin']],

        ['results-entry',      'results-entry.php',       '📊', 'Enter Results',        ['superadmin', 'subject_teacher', 'form_teacher', 'vp_academics', 'section_admin']],
        ['results-approve',    'results-approve.php',     '🔍', 'Approve Results',      ['superadmin', 'form_teacher', 'section_admin']],
        ['results-publish',    'results-publish.php',     '✅', 'Publish Results',      ['superadmin', 'vp_academics', 'section_admin']],

        ['news-create',        'news-create.php',         '📰', 'Create News',          ['superadmin', 'principal', 'vp_general', 'vp_student_affairs']],
        ['news',               'news.php',                '📋', 'All News',             ['superadmin', 'principal', 'vp_general', 'vp_student_affairs']],

        ['events',             'events.php',              '📆', 'All Events',           ['superadmin', 'principal', 'vp_general', 'vp_student_affairs', 'dean']],
        ['events-create',      'events-create.php',       '➕', 'Create Event',         ['superadmin', 'principal', 'vp_general', 'vp_student_affairs', 'dean']],

        ['gallery',            'gallery.php',             '🖼️', 'Gallery',              ['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'vp_student_affairs', 'dean', 'counselor', 'hod', 'form_teacher', 'subject_teacher']],
        ['hero-images',        'hero-images.php',         '🌅', 'Hero Images',          ['superadmin', 'principal', 'vp_general', 'vp_student_affairs']],
        ['gallery-upload',     'gallery-upload.php',      '📷', 'Upload Photos',        ['superadmin', 'principal', 'vp_general', 'vp_student_affairs']],

        ['students',           'students.php',            '🎒', 'Students',             ['superadmin', 'principal', 'vp_admin', 'form_teacher', 'section_admin']],
        ['students-promote',   'students-promote.php',    '⬆️', 'Promote Students',     ['superadmin', 'principal', 'form_teacher', 'section_admin']],

        /* ── Student Portal Controls ── */
        ['student-portal',     'student-portal.php',      '🔐', 'Portal Access',        ['superadmin', 'principal', 'vp_admin', 'vp_academics', 'section_admin']],
        ['student-notices',    'student-notices.php',     '📢', 'Student Notices',      ['superadmin', 'principal', 'vp_admin', 'vp_academics', 'dean', 'form_teacher', 'section_admin']],

        /* ── Corps Members Module ── */
        ['corps',              'corps.php',                '🧑‍🤝‍🧑', 'Corps Members',       ['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'vp_student_affairs', 'dean', 'section_admin']],
        ['corps-messages',     'corps-messages.php',       '✉️', 'Corps Messages',       ['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'vp_student_affairs', 'dean', 'section_admin']],

        ['admissions',         'admissions.php',          '🎓', 'Admissions',           ['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'vp_student_affairs', 'dean', 'counselor', 'hod', 'form_teacher', 'subject_teacher']],

        ['staff',              'staff.php',               '👨‍🏫', 'Staff Directory',    ['superadmin']],
        ['milestones',         'milestones.php',          '🕐', 'History Timeline',     ['superadmin']],
        ['clubs',              'clubs.php',               '🎭', 'Clubs & Societies',    ['superadmin']],
        ['awards',             'awards.php',              '🏆', 'Awards',               ['superadmin']],
        ['alumni',             'alumni.php',              '🌍', 'Alumni Directory',     ['superadmin']],
        ['scholarships',       'scholarships.php',        '🎓', 'Scholarships',         ['superadmin']],
        ['prefects-admin',     'prefects-admin.php',      '🎖️', 'Prefects',             ['superadmin', 'principal']],
        ['hall-of-fame-admin', 'hall-of-fame-admin.php',  '🏆', 'Hall of Fame',         ['superadmin']],
        ['nominations',        'nominations.php',         '📬', 'Nominations',          ['superadmin']],
        ['reviews',            'reviews.php',              '⭐', 'Reviews',              ['superadmin', 'principal', 'vp_general', 'vp_student_affairs']],

        ['messages',           'messages.php',            '💬', 'Messages',             null],
        ['change-password',    'change-password.php',     '🔑', 'Change Password',      null],
        ['push-notifications', 'push-notifications.php',  '🔔', 'Push Notifications',  ['superadmin', 'principal']],
        ['newsletter-admin',   'newsletter-admin.php',    '📧', 'Newsletter',           ['superadmin', 'principal', 'vp_general', 'vp_student_affairs']],

        ['users',              'users.php',               '👥', 'Manage Users',         ['superadmin', 'section_admin']],
        ['class-arms',         'class-arms.php',          '🏫', 'Manage Classes',       ['superadmin', 'section_admin']],
        ['subjects',           'subjects.php',            '📚', 'Manage Subjects',      ['superadmin']],
        ['settings',           'settings.php',            '⚙️', 'Settings',             ['superadmin']],
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