<?php
/* ============================================================
   IBEKU HIGH SCHOOL - ADMIN CORPS MEMBER MANAGEMENT
   File: public/admin/corps.php
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'dean']);

$admin = currentAdmin();
$pdo   = getDB();

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['member_id'] ?? 0);

    if ($action === 'passout' && $id > 0) {
        $pdo->prepare(
            "UPDATE corps_members SET status='passed_out', status_changed_at=NOW(), status_changed_by=? WHERE id=?"
        )->execute([$admin['id'], $id]);
        $message = 'Corps member marked as passed out.'; $messageType = 'success';
    } elseif ($action === 'soft_delete' && $id > 0) {
        $pdo->prepare(
            "UPDATE corps_members SET status='deleted', status_changed_at=NOW(), status_changed_by=? WHERE id=?"
        )->execute([$admin['id'], $id]);
        $message = 'Corps member moved to deleted.'; $messageType = 'success';
    } elseif ($action === 'restore' && $id > 0) {
        $pdo->prepare(
            "UPDATE corps_members SET status='active', status_changed_at=NOW(), status_changed_by=? WHERE id=?"
        )->execute([$admin['id'], $id]);
        $message = 'Corps member restored to active.'; $messageType = 'success';
    } elseif ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM corps_members WHERE id=?')->execute([$id]);
        $message = 'Corps member permanently removed.'; $messageType = 'success';
    } elseif ($action === 'reset_password' && $id > 0) {
        $codeStmt = $pdo->prepare('SELECT state_code FROM corps_members WHERE id=? LIMIT 1');
        $codeStmt->execute([$id]);
        $code = $codeStmt->fetchColumn();
        if ($code) {
            $pdo->prepare('UPDATE corps_members SET password=? WHERE id=?')
                ->execute([password_hash($code, PASSWORD_DEFAULT), $id]);
            $message = 'Password reset to state code.'; $messageType = 'success';
        }
    }
}

$filterStatus = $_GET['status'] ?? 'active';
$search       = trim($_GET['search'] ?? '');
$where = ['1=1']; $params = [];
if ($filterStatus !== 'all') { $where[] = 'status = ?'; $params[] = $filterStatus; }
if ($search) {
    $where[] = '(full_name LIKE ? OR state_code LIKE ?)';
    $params[] = '%'.$search.'%'; $params[] = '%'.$search.'%';
}
$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM corps_members WHERE $whereSQL ORDER BY full_name ASC");
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = $pdo->query("SELECT status, COUNT(*) as n FROM corps_members GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$countMap = array_column($counts, 'n', 'status');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Corps Members - Admin - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/admin-layout.css"/>
  <style>
    .stats-row{display:flex;gap:14px;margin-bottom:20px;flex-wrap:wrap}
    .stat-pill{background:#fff;border:1px solid #e8e6f0;border-radius:10px;padding:10px 18px}
    .stat-pill strong{display:block;font-size:18px;color:#3d1a6e}
    .stat-pill span{font-size:12.5px;color:#6b6b80}
    .filter-bar{background:#fff;border:1px solid #e8e6f0;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
    .filter-tab{padding:7px 16px;border-radius:20px;font-size:12.5px;font-weight:600;text-decoration:none;color:#6b6b80;background:#fff;border:1px solid #e8e6f0}
    .filter-tab--active{background:#3d1a6e;color:#fff;border-color:#3d1a6e}
    .search-box{padding:7px 12px;border:1.5px solid #e2e0ea;border-radius:7px;font-size:13px;font-family:'DM Sans',sans-serif;min-width:200px}
    .btn-new{background:#3d1a6e;color:#fff;text-decoration:none;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:700}
    .table-wrap{background:#fff;border:1px solid #e8e6f0;border-radius:14px;overflow:hidden}
    table{width:100%;border-collapse:collapse;font-size:13px}
    th{background:#3d1a6e;color:#fff;padding:10px 14px;text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.04em}
    td{padding:10px 14px;border-bottom:1px solid #f0eef6;vertical-align:middle}
    tr:last-child td{border-bottom:none}
    tr:hover td{background:#faf9fd}
    .member-name{font-weight:600;color:#1a1a2e}
    .member-code{font-size:11.5px;color:#9b97b0}
    .photo-thumb{width:36px;height:36px;border-radius:50%;object-fit:cover;background:linear-gradient(135deg,#3d1a6e,#4a90d9);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}
    .status-badge{display:inline-block;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase}
    .badge--active{background:#e6f9ed;color:#1a7a3a}
    .badge--passed_out{background:#f0eef6;color:#6b6b80}
    .badge--deleted{background:#ffe6e6;color:#cc3333}
    .action-btn{font-size:11.5px;font-weight:600;padding:4px 10px;border-radius:6px;border:none;cursor:pointer;margin-right:4px}
    .btn-edit{background:#f0ecfa;color:#3d1a6e;text-decoration:none;display:inline-block}
    .btn-passout{background:#f0eef6;color:#6b6b80}
    .btn-restore{background:#e6f9ed;color:#1a7a3a}
    .btn-softdelete{background:#fff3e6;color:#8a4a00}
    .btn-delete{background:#ffe6e6;color:#cc3333}
    .btn-pw{background:#f0ecfa;color:#3d1a6e}
    .empty-state{padding:50px 20px;text-align:center;color:#6b6b80}
  </style>
</head>
<body>
<?php renderAdminSidebar($admin, 'corps'); ?>
<div class="admin-content">
  <div class="admin-content__inner">

    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px">
      <div>
        <h2>Corps Members</h2>
        <p>Manage NYSC corps members serving at Ibeku High School.</p>
      </div>
      <a href="corps-create.php" class="btn-new">+ Add Corps Member</a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-pill"><strong><?php echo $countMap['active'] ?? 0; ?></strong><span>Active</span></div>
      <div class="stat-pill"><strong><?php echo $countMap['passed_out'] ?? 0; ?></strong><span>Passed Out</span></div>
      <div class="stat-pill"><strong><?php echo $countMap['deleted'] ?? 0; ?></strong><span>Deleted</span></div>
    </div>

    <!-- Filters -->
    <form method="GET" class="filter-bar">
      <a href="?status=active"     class="filter-tab <?php echo $filterStatus === 'active'     ? 'filter-tab--active' : ''; ?>">Active</a>
      <a href="?status=passed_out" class="filter-tab <?php echo $filterStatus === 'passed_out' ? 'filter-tab--active' : ''; ?>">Passed Out</a>
      <a href="?status=deleted"    class="filter-tab <?php echo $filterStatus === 'deleted'    ? 'filter-tab--active' : ''; ?>">Deleted</a>
      <a href="?status=all"        class="filter-tab <?php echo $filterStatus === 'all'        ? 'filter-tab--active' : ''; ?>">All</a>
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>"/>
      <input type="text" name="search" class="search-box" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name or state code..."/>
      <button type="submit" style="background:#4a90d9;color:#fff;border:none;padding:8px 16px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer">Search</button>
    </form>

    <!-- Table -->
    <div class="table-wrap">
      <?php if (empty($members)): ?>
      <div class="empty-state">No corps members found. <a href="corps-create.php" style="color:#4a90d9">Add the first one</a>.</div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Member</th>
            <th>Batch</th>
            <th>Subject</th>
            <th>Section</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($members as $m): ?>
          <?php
            $photoSrc = !empty($m['photo']) ? '../assets/images/corps/' . htmlspecialchars($m['photo']) : '';
            $initial  = strtoupper(substr($m['full_name'], 0, 1));
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <?php if ($photoSrc): ?>
                <img src="<?php echo $photoSrc; ?>" class="photo-thumb" alt=""
                     onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
                <div class="photo-thumb" style="display:none"><?php echo $initial; ?></div>
                <?php else: ?>
                <div class="photo-thumb"><?php echo $initial; ?></div>
                <?php endif; ?>
                <div>
                  <div class="member-name"><?php echo htmlspecialchars($m['full_name']); ?></div>
                  <div class="member-code"><?php echo htmlspecialchars($m['state_code']); ?></div>
                </div>
              </div>
            </td>
            <td><?php echo htmlspecialchars($m['batch']); ?></td>
            <td><?php echo htmlspecialchars($m['subject_taught'] ?? '-'); ?></td>
            <td><?php echo strtoupper($m['section']); ?></td>
            <td>
              <span class="status-badge badge--<?php echo $m['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $m['status'])); ?>
              </span>
            </td>
            <td>
              <a href="corps-edit.php?id=<?php echo $m['id']; ?>" class="action-btn btn-edit">Edit</a>
              <a href="corps-clearance.php?id=<?php echo $m['id']; ?>" class="action-btn btn-edit">Clearance</a>
              <a href="corps-messages.php?id=<?php echo $m['id']; ?>" class="action-btn btn-edit">Message</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>"/>
                <button type="submit" name="action" value="reset_password" class="action-btn btn-pw"
                        onclick="return confirm('Reset password to state code?')">Reset PW</button>
                <?php if ($m['status'] === 'active'): ?>
                <button type="submit" name="action" value="passout" class="action-btn btn-passout"
                        onclick="return confirm('Mark this member as passed out?')">Passed Out</button>
                <button type="submit" name="action" value="soft_delete" class="action-btn btn-softdelete"
                        onclick="return confirm('Move this member to Deleted?')">Delete</button>
                <?php elseif ($m['status'] === 'passed_out'): ?>
                <button type="submit" name="action" value="restore" class="action-btn btn-restore"
                        onclick="return confirm('Restore this member to active?')">Restore</button>
                <button type="submit" name="action" value="soft_delete" class="action-btn btn-softdelete"
                        onclick="return confirm('Move this member to Deleted?')">Delete</button>
                <?php elseif ($m['status'] === 'deleted'): ?>
                <button type="submit" name="action" value="restore" class="action-btn btn-restore"
                        onclick="return confirm('Restore this member to active?')">Restore</button>
                <button type="submit" name="action" value="delete" class="action-btn btn-delete"
                        onclick="return confirm('Permanently remove this corps member? This cannot be undone.')">Remove Permanently</button>
                <?php endif; ?>
              </form>
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