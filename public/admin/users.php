<?php
/* ============================================================
   IBEKU HIGH SCHOOL — USER MANAGEMENT (LIST)
   File: public/admin/users.php

   Accessible to: superadmin only
   Lists all staff accounts with role, section, status.
   Allows activate/deactivate and delete.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'section_admin']);

$admin           = currentAdmin();
$pdo             = getDB();
$isSectionAdmin  = $admin['role'] === 'section_admin';
$adminOwnSection = $admin['section']; /* 'ss' or 'js' for a section_admin */

try {
    $pdo->exec(
        "ALTER TABLE users MODIFY COLUMN role
         ENUM('superadmin','section_admin','principal','vp_admin','vp_academics','vp_general','vp_student_affairs','dean','counselor','hod','form_teacher','subject_teacher')
         NOT NULL"
    );
} catch (PDOException $e) { /* already includes section_admin */ }

$message = '';
$messageType = '';

/* ── Handle activate/deactivate/delete actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $userId = (int) $_POST['user_id'];
    $action = $_POST['action'];

    /* Prevent superadmin from disabling/deleting their own account */
    if ($userId === (int) $admin['id']) {
        $message = 'You cannot deactivate or delete your own account while logged in.';
        $messageType = 'error';
    } elseif ($isSectionAdmin) {
        /* Section admins can only act on regular staff within their
           own section — never on superadmin or other section_admin
           accounts, and never outside their section. */
        $targetStmt = $pdo->prepare('SELECT role, section FROM users WHERE id = ? LIMIT 1');
        $targetStmt->execute([$userId]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            $message = 'User not found.'; $messageType = 'error';
        } elseif (in_array($target['role'], ['superadmin', 'section_admin'], true)) {
            $message = 'You do not have permission to manage that account.'; $messageType = 'error';
        } elseif ($target['section'] !== $adminOwnSection && $target['section'] !== 'both') {
            $message = 'You can only manage staff in your own section.'; $messageType = 'error';
        }
    }

    if ($message === '') {
        try {
            if ($action === 'activate') {
                $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?')->execute([$userId]);
                $message = 'Account activated.';
                $messageType = 'success';

            } elseif ($action === 'deactivate') {
                $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$userId]);
                $message = 'Account deactivated. This user can no longer log in.';
                $messageType = 'success';

            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
                $message = 'Account deleted.';
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            error_log('IHS users.php action error: ' . $e->getMessage());
            $message = 'A server error occurred. Please try again.';
            $messageType = 'error';
        }
    }
}

/* ── Role display labels ── */
$roleLabels = [
    'superadmin'      => 'System Administrator',
    'section_admin'   => 'Section Admin',
    'principal'       => 'Principal',
    'vp_admin'        => 'VP (Administration)',
    'vp_academics'    => 'VP (Academics)',
    'vp_general'      => 'VP (General Duties)',
    'vp_student_affairs' => 'VP (Student Affairs)',
    'dean'            => 'Dean of Studies',
    'counselor'       => 'Guidance Counsellor',
    'hod'             => 'Head of Department',
    'form_teacher'    => 'Form Teacher',
    'subject_teacher' => 'Subject Teacher',
];

/* ── Filter by role/section ──
   Section admins are locked to their own section regardless of
   any ?section= in the URL — they never get a choice here. ── */
$sectionFilter = $isSectionAdmin ? $adminOwnSection : ($_GET['section'] ?? 'all');

$sql = 'SELECT * FROM users';
$params = [];

if ($sectionFilter !== 'all') {
    $sql .= ' WHERE section = ? OR section = "both"';
    $params[] = $sectionFilter;
}

/* Section admins never see superadmin or other section_admin accounts */
if ($isSectionAdmin) {
    $sql .= ($params ? ' AND' : ' WHERE') . ' role NOT IN ("superadmin", "section_admin")';
}

$sql .= ' ORDER BY FIELD(role, "superadmin","section_admin","principal","vp_admin","vp_academics","vp_general","vp_student_affairs","dean","counselor","hod","form_teacher","subject_teacher"), full_name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

/* ── Stats — scoped to the section admin's own section ── */
if ($isSectionAdmin) {
    $totalCount    = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (section = ? OR section = "both") AND role NOT IN ("superadmin","section_admin")');
    $totalCount->execute([$adminOwnSection]);
    $totalCount = $totalCount->fetchColumn();

    $activeCount   = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (section = ? OR section = "both") AND role NOT IN ("superadmin","section_admin") AND is_active = 1');
    $activeCount->execute([$adminOwnSection]);
    $activeCount = $activeCount->fetchColumn();

    $inactiveCount = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (section = ? OR section = "both") AND role NOT IN ("superadmin","section_admin") AND is_active = 0');
    $inactiveCount->execute([$adminOwnSection]);
    $inactiveCount = $inactiveCount->fetchColumn();
} else {
    $totalCount    = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $activeCount   = $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
    $inactiveCount = $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 0')->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Users — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 22px; flex-wrap: wrap; gap: 14px;
  }
  .btn-new {
    background: #3d1a6e; color: #fff; text-decoration: none;
    padding: 10px 20px; border-radius: 8px; font-size: 13.5px; font-weight: 700;
    white-space: nowrap;
  }
  .btn-new:hover { background: #5a2d9e; }

  .filter-tabs { display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; }
  .filter-tab {
    padding: 7px 16px; border-radius: 20px; font-size: 12.5px; font-weight: 600;
    text-decoration: none; color: #6b6b80; background: #fff; border: 1px solid #e8e6f0;
  }
  .filter-tab--active { background: #3d1a6e; color: #fff; border-color: #3d1a6e; }

  .stats-row { display: flex; gap: 14px; margin-bottom: 22px; flex-wrap: wrap; }
  .stat-pill {
    background: #fff; border: 1px solid #e8e6f0; border-radius: 10px;
    padding: 10px 18px; font-size: 12.5px; color: #6b6b80;
  }
  .stat-pill strong { color: #3d1a6e; font-size: 15px; }

  .users-table-wrap { background: #fff; border: 1px solid #e8e6f0; border-radius: 14px; overflow: hidden; }
  table.users-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  table.users-table th {
    background: #3d1a6e; color: #fff; padding: 11px 14px; text-align: left;
    font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em;
  }
  table.users-table td { padding: 12px 14px; border-bottom: 1px solid #f0eef6; vertical-align: middle; }
  table.users-table tr:last-child td { border-bottom: none; }
  table.users-table tr:hover td { background: #faf9fd; }

  .user-name { font-weight: 600; color: #1a1a2e; margin-bottom: 2px; }
  .user-email { font-size: 11.5px; color: #9b97b0; }

  .section-tag {
    display: inline-block; font-size: 10.5px; font-weight: 700;
    padding: 2px 9px; border-radius: 20px; text-transform: uppercase;
  }
  .section-tag--ss { background: #f0ecfa; color: #3d1a6e; }
  .section-tag--js { background: #e6f0ff; color: #1a5a9a; }
  .section-tag--both { background: #fff3e6; color: #8a4a00; }

  .badge { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; }
  .badge--active { background: #e6f9ed; color: #1a7a3a; }
  .badge--inactive { background: #ffe6e6; color: #cc3333; }

  .actions-cell { display: flex; gap: 6px; flex-wrap: wrap; }
  .action-btn {
    border: none; padding: 6px 12px; border-radius: 6px; font-size: 11.5px;
    font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block;
  }
  .action-btn--edit { background: #f0ecfa; color: #3d1a6e; }
  .action-btn--edit:hover { background: #e4dcf6; }
  .action-btn--activate { background: #e6f9ed; color: #1a7a3a; }
  .action-btn--activate:hover { background: #d4f2dd; }
  .action-btn--deactivate { background: #fff3e6; color: #8a4a00; }
  .action-btn--deactivate:hover { background: #ffe9d0; }
  .action-btn--delete { background: #ffe6e6; color: #cc3333; }
  .action-btn--delete:hover { background: #ffd6d6; }

  .self-note { font-size: 11px; color: #9b97b0; font-style: italic; }
  .empty-state { padding: 50px 20px; text-align: center; color: #6b6b80; font-size: 13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'users'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Manage Users</h2>
          <p><?php echo $isSectionAdmin ? 'Staff accounts in your section.' : 'Staff accounts across both Senior and Junior Secondary sections.'; ?></p>
        </div>
        <a href="users-create.php" class="btn-new">+ New User</a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="stats-row">
        <div class="stat-pill"><strong><?php echo $totalCount; ?></strong> Total Accounts</div>
        <div class="stat-pill"><strong><?php echo $activeCount; ?></strong> Active</div>
        <div class="stat-pill"><strong><?php echo $inactiveCount; ?></strong> Inactive</div>
      </div>

      <?php if (!$isSectionAdmin): ?>
      <div class="filter-tabs">
        <a href="?section=all" class="filter-tab <?php echo $sectionFilter === 'all' ? 'filter-tab--active' : ''; ?>">All</a>
        <a href="?section=ss"  class="filter-tab <?php echo $sectionFilter === 'ss'  ? 'filter-tab--active' : ''; ?>">Senior Secondary</a>
        <a href="?section=js"  class="filter-tab <?php echo $sectionFilter === 'js'  ? 'filter-tab--active' : ''; ?>">Junior Secondary</a>
      </div>
      <?php endif; ?>

      <div class="users-table-wrap">
        <?php if (empty($users)): ?>
        <div class="empty-state">No users found.</div>
        <?php else: ?>
        <table class="users-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Role</th>
              <th>Section</th>
              <th>Dept / Class</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <div class="user-name"><?php echo htmlspecialchars($u['full_name']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($u['email']); ?></div>
              </td>
              <td><?php echo htmlspecialchars($roleLabels[$u['role']] ?? $u['role']); ?></td>
              <td>
                <span class="section-tag section-tag--<?php echo $u['section']; ?>">
                  <?php echo $u['section'] === 'both' ? 'Both' : strtoupper($u['section']); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($u['department'] ?: ($u['class_assigned'] ?: '—')); ?></td>
              <td>
                <span class="badge badge--<?php echo $u['is_active'] ? 'active' : 'inactive'; ?>">
                  <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
              </td>
              <td>
                <?php if ((int) $u['id'] === (int) $admin['id']): ?>
                <span class="self-note">This is you</span>
                <?php else: ?>
                <div class="actions-cell">
                  <a href="users-edit.php?id=<?php echo $u['id']; ?>" class="action-btn action-btn--edit">Edit</a>

                  <?php if ($u['is_active']): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"/>
                    <input type="hidden" name="action" value="deactivate"/>
                    <button type="submit" class="action-btn action-btn--deactivate">Deactivate</button>
                  </form>
                  <?php else: ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"/>
                    <input type="hidden" name="action" value="activate"/>
                    <button type="submit" class="action-btn action-btn--activate">Activate</button>
                  </form>
                  <?php endif; ?>

                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('Delete this account permanently? This cannot be undone.');">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"/>
                    <input type="hidden" name="action" value="delete"/>
                    <button type="submit" class="action-btn action-btn--delete">Delete</button>
                  </form>
                </div>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>