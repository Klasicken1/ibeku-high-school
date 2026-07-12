<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT PORTAL ACCESS CONTROL
   File: public/admin/student-portal.php

   Roles:
   - Lock/unlock portal login  → superadmin, principal, vp_admin
   - Block/unblock results     → superadmin, principal, vp_admin, vp_academics
   - Reset password            → superadmin, vp_admin
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin', 'vp_academics']);

$admin    = currentAdmin();
$pdo      = getDB();
$role     = $admin['role'];

$canLockPortal   = in_array($role, ['superadmin', 'principal', 'vp_admin'], true);
$canBlockResults = in_array($role, ['superadmin', 'principal', 'vp_admin', 'vp_academics'], true);
$canResetPassword = in_array($role, ['superadmin', 'vp_admin'], true);

$message     = '';
$messageType = '';

/* ════════════════════════════════════════════════════════════
   HANDLE ACTIONS
════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    /* ── Single student actions ── */
    if (in_array($action, ['lock_portal', 'unlock_portal'], true) && $canLockPortal) {
        $sid    = (int) ($_POST['student_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $lock   = $action === 'lock_portal' ? 1 : 0;
        if ($sid > 0) {
            try {
                if ($lock) {
                    $pdo->prepare(
                        'UPDATE students SET
                            portal_blocked        = 1,
                            portal_blocked_reason = ?,
                            portal_blocked_by     = ?,
                            portal_blocked_at     = NOW()
                         WHERE id = ?'
                    )->execute([$reason ?: null, $admin['id'], $sid]);
                    $message = 'Portal access locked.';
                } else {
                    $pdo->prepare(
                        'UPDATE students SET
                            portal_blocked        = 0,
                            portal_blocked_reason = NULL,
                            portal_blocked_by     = NULL,
                            portal_blocked_at     = NULL
                         WHERE id = ?'
                    )->execute([$sid]);
                    $message = 'Portal access restored.';
                }
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }

    } elseif (in_array($action, ['block_results', 'unblock_results'], true) && $canBlockResults) {
        $sid    = (int) ($_POST['student_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $block  = $action === 'block_results' ? 1 : 0;
        if ($sid > 0) {
            try {
                if ($block) {
                    $pdo->prepare(
                        'UPDATE students SET
                            results_blocked        = 1,
                            results_blocked_reason = ?,
                            results_blocked_by     = ?,
                            results_blocked_at     = NOW()
                         WHERE id = ?'
                    )->execute([$reason ?: null, $admin['id'], $sid]);
                    $message = 'Results access blocked.';
                } else {
                    $pdo->prepare(
                        'UPDATE students SET
                            results_blocked        = 0,
                            results_blocked_reason = NULL,
                            results_blocked_by     = NULL,
                            results_blocked_at     = NULL
                         WHERE id = ?'
                    )->execute([$sid]);
                    $message = 'Results access restored.';
                }
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }

    } elseif ($action === 'reset_password' && $canResetPassword) {
        $sid = (int) ($_POST['student_id'] ?? 0);
        if ($sid > 0) {
            try {
                $admStmt = $pdo->prepare(
                    'SELECT admission_number FROM students WHERE id = ? LIMIT 1'
                );
                $admStmt->execute([$sid]);
                $admNo = $admStmt->fetchColumn();
                if ($admNo) {
                    $hash = password_hash($admNo, PASSWORD_DEFAULT);
                    $pdo->prepare(
                        'UPDATE students SET password = ? WHERE id = ?'
                    )->execute([$hash, $sid]);
                    $message = 'Password reset to admission number: ' . htmlspecialchars($admNo);
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }

    /* ── Bulk actions ── */
    } elseif ($action === 'bulk_lock_portal' && $canLockPortal) {
        $ids    = array_map('intval', $_POST['selected_ids'] ?? []);
        $reason = trim($_POST['bulk_reason'] ?? '');
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$reason ?: null, $admin['id']], $ids);
            $pdo->prepare(
                "UPDATE students SET
                    portal_blocked = 1, portal_blocked_reason = ?,
                    portal_blocked_by = ?, portal_blocked_at = NOW()
                 WHERE id IN ($ph)"
            )->execute($params);
            $message = count($ids) . ' student(s) portal locked.';
            $messageType = 'success';
        }

    } elseif ($action === 'bulk_unlock_portal' && $canLockPortal) {
        $ids = array_map('intval', $_POST['selected_ids'] ?? []);
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare(
                "UPDATE students SET
                    portal_blocked = 0, portal_blocked_reason = NULL,
                    portal_blocked_by = NULL, portal_blocked_at = NULL
                 WHERE id IN ($ph)"
            )->execute($ids);
            $message = count($ids) . ' student(s) portal unlocked.';
            $messageType = 'success';
        }

    } elseif ($action === 'bulk_block_results' && $canBlockResults) {
        $ids    = array_map('intval', $_POST['selected_ids'] ?? []);
        $reason = trim($_POST['bulk_reason'] ?? '');
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$reason ?: null, $admin['id']], $ids);
            $pdo->prepare(
                "UPDATE students SET
                    results_blocked = 1, results_blocked_reason = ?,
                    results_blocked_by = ?, results_blocked_at = NOW()
                 WHERE id IN ($ph)"
            )->execute($params);
            $message = count($ids) . ' student(s) results blocked.';
            $messageType = 'success';
        }

    } elseif ($action === 'bulk_unblock_results' && $canBlockResults) {
        $ids = array_map('intval', $_POST['selected_ids'] ?? []);
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare(
                "UPDATE students SET
                    results_blocked = 0, results_blocked_reason = NULL,
                    results_blocked_by = NULL, results_blocked_at = NULL
                 WHERE id IN ($ph)"
            )->execute($ids);
            $message = count($ids) . ' student(s) results unblocked.';
            $messageType = 'success';
        }
    }
}

/* ── Filters ── */
$filterGrade   = $_GET['grade']   ?? '';
$filterClass   = $_GET['class']   ?? '';
$filterPortal  = $_GET['portal']  ?? '';
$filterResults = $_GET['results'] ?? '';
$search        = trim($_GET['search'] ?? '');
$page          = max(1, (int) ($_GET['page'] ?? 1));
$perPage       = 30;
$offset        = ($page - 1) * $perPage;

$where  = ['s.is_active = 1'];
$params = [];

if ($filterGrade) {
    $where[] = 's.grade_level = ?'; $params[] = $filterGrade;
}
if ($filterClass) {
    $where[] = 's.class = ?'; $params[] = $filterClass;
}
if ($filterPortal !== '') {
    $where[] = 's.portal_blocked = ?'; $params[] = (int) $filterPortal;
}
if ($filterResults !== '') {
    $where[] = 's.results_blocked = ?'; $params[] = (int) $filterResults;
}
if ($search) {
    $where[]  = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students s WHERE $whereSQL");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

$studentStmt = $pdo->prepare(
    "SELECT s.id, s.admission_number, s.first_name, s.last_name,
            s.grade_level, s.class, s.portal_blocked, s.results_blocked,
            s.portal_blocked_reason, s.results_blocked_reason,
            s.portal_blocked_at, s.results_blocked_at,
            u1.full_name AS portal_blocked_by_name,
            u2.full_name AS results_blocked_by_name
     FROM students s
     LEFT JOIN users u1 ON u1.id = s.portal_blocked_by
     LEFT JOIN users u2 ON u2.id = s.results_blocked_by
     WHERE $whereSQL
     ORDER BY s.last_name ASC, s.first_name ASC
     LIMIT ? OFFSET ?"
);
$studentStmt->execute([...$params, $perPage, $offset]);
$students = $studentStmt->fetchAll();

/* ── Stats ── */
$portalBlockedCount  = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE portal_blocked = 1 AND is_active = 1")->fetchColumn();
$resultsBlockedCount = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE results_blocked = 1 AND is_active = 1")->fetchColumn();
$totalActive         = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();

$gradeLevels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3',
                'SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Student Portal Control — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .stats-row { display:flex; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
  .stat-pill { background:#fff; border:1px solid #e8e6f0; border-radius:10px; padding:10px 18px; }
  .stat-pill strong { display:block; font-size:18px; color:#3d1a6e; }
  .stat-pill span { font-size:12.5px; color:#6b6b80; }
  .stat-pill--warn strong { color:#cc3333; }

  .filter-bar { background:#fff; border:1px solid #e8e6f0; border-radius:12px; padding:14px 18px; margin-bottom:16px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; letter-spacing:.04em; }
  .filter-group select, .filter-group input { padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:130px; }
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:8px 16px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
  .btn-reset  { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:8px 14px; border-radius:7px; font-size:12.5px; font-weight:600; text-decoration:none; }

  .bulk-bar { display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap; align-items:center; }
  .bulk-reason { padding:7px 12px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:200px; }
  .btn-bulk { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:7px 14px; border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer; }
  .btn-bulk:hover { background:#e4dcf6; }
  .btn-bulk--danger { background:#ffe6e6; color:#cc3333; border-color:#ffcccc; }
  .btn-bulk--danger:hover { background:#ffd0d0; }

  .table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  th { background:#3d1a6e; color:#fff; padding:10px 14px; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
  td { padding:10px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:#faf9fd; }
  input[type=checkbox] { accent-color:#3d1a6e; width:15px; height:15px; }

  .student-name { font-weight:600; color:#1a1a2e; }
  .adm-no { font-size:11.5px; color:#9b97b0; }

  .status-badge { display:inline-block; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; }
  .badge--ok      { background:#e6f9ed; color:#1a7a3a; }
  .badge--blocked { background:#ffe6e6; color:#cc3333; }

  .action-cell { display:flex; gap:6px; flex-wrap:wrap; }
  .btn-action { font-size:11.5px; font-weight:600; padding:4px 10px; border-radius:6px; border:none; cursor:pointer; }
  .btn-lock    { background:#ffe6e6; color:#cc3333; }
  .btn-unlock  { background:#e6f9ed; color:#1a7a3a; }
  .btn-reset-pw { background:#f0ecfa; color:#3d1a6e; }

  .reason-tip { font-size:11px; color:#9b97b0; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; cursor:help; }

  .pagination { display:flex; gap:6px; justify-content:center; margin-top:16px; flex-wrap:wrap; }
  .pagination a, .pagination span { padding:6px 12px; border-radius:7px; font-size:13px; font-weight:600; border:1px solid #e8e6f0; text-decoration:none; color:#3d1a6e; background:#fff; }
  .pagination a:hover { background:#f0ecfa; }
  .pagination .current { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }

  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }

  /* Reason modal */
  .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; display:none; align-items:center; justify-content:center; }
  .modal-overlay.open { display:flex; }
  .modal { background:#fff; border-radius:16px; padding:24px; width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,.2); }
  .modal h3 { font-size:16px; font-weight:700; color:#3d1a6e; margin-bottom:8px; }
  .modal p  { font-size:13px; color:#6b6b80; margin-bottom:14px; }
  .modal textarea { width:100%; padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif; resize:vertical; }
  .modal-btns { display:flex; gap:10px; margin-top:14px; }
  .modal-confirm { background:#3d1a6e; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-size:13.5px; font-weight:700; cursor:pointer; }
  .modal-confirm--danger { background:#cc3333; }
  .modal-cancel  { background:#f0ecfa; color:#3d1a6e; border:none; padding:10px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'student-portal'); ?>

  <!-- Reason modal -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal">
      <h3 id="modalTitle">Confirm Action</h3>
      <p id="modalDesc">Provide a reason (optional but recommended):</p>
      <textarea id="modalReason" rows="3" placeholder="Enter reason…"></textarea>
      <div class="modal-btns">
        <button class="modal-confirm" id="modalConfirmBtn" onclick="submitModal()">Confirm</button>
        <button class="modal-cancel" onclick="closeModal()">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Hidden form for modal submissions -->
  <form method="POST" id="modalForm">
    <input type="hidden" name="action"     id="modalAction"/>
    <input type="hidden" name="student_id" id="modalStudentId"/>
    <input type="hidden" name="reason"     id="modalReasonHidden"/>
  </form>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Student Portal Control</h2>
        <p>Manage student login access, results visibility, and portal passwords.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo $message; ?></div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-pill">
          <strong><?php echo $totalActive; ?></strong>
          <span>Active Students</span>
        </div>
        <div class="stat-pill <?php echo $portalBlockedCount > 0 ? 'stat-pill--warn' : ''; ?>">
          <strong><?php echo $portalBlockedCount; ?></strong>
          <span>Portal Locked</span>
        </div>
        <div class="stat-pill <?php echo $resultsBlockedCount > 0 ? 'stat-pill--warn' : ''; ?>">
          <strong><?php echo $resultsBlockedCount; ?></strong>
          <span>Results Blocked</span>
        </div>
      </div>

      <!-- Filters -->
      <form method="GET" class="filter-bar">
        <div class="filter-group">
          <label>Grade</label>
          <select name="grade">
            <option value="">All Grades</option>
            <?php foreach ($gradeLevels as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filterGrade === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Portal</label>
          <select name="portal">
            <option value="">All</option>
            <option value="0" <?php echo $filterPortal === '0' ? 'selected' : ''; ?>>Active</option>
            <option value="1" <?php echo $filterPortal === '1' ? 'selected' : ''; ?>>Locked</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Results</label>
          <select name="results">
            <option value="">All</option>
            <option value="0" <?php echo $filterResults === '0' ? 'selected' : ''; ?>>Accessible</option>
            <option value="1" <?php echo $filterResults === '1' ? 'selected' : ''; ?>>Blocked</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Search</label>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or admission no."/>
        </div>
        <button type="submit" class="btn-filter">Filter</button>
        <a href="student-portal.php" class="btn-reset">Reset</a>
      </form>

      <!-- Bulk actions form -->
      <form method="POST" id="bulkForm">
        <div class="bulk-bar">
          <button type="button" class="btn-bulk" onclick="selectAll(true)">Select All</button>
          <button type="button" class="btn-bulk" onclick="selectAll(false)">Deselect All</button>
          <input type="text" name="bulk_reason" class="bulk-reason" placeholder="Reason for bulk action (optional)"/>
          <?php if ($canLockPortal): ?>
          <button type="submit" name="action" value="bulk_lock_portal"
                  class="btn-bulk btn-bulk--danger"
                  onclick="return confirmBulk('lock portal access for')">
            🔒 Lock Portal
          </button>
          <button type="submit" name="action" value="bulk_unlock_portal"
                  class="btn-bulk"
                  onclick="return confirmBulk('unlock portal access for')">
            🔓 Unlock Portal
          </button>
          <?php endif; ?>
          <?php if ($canBlockResults): ?>
          <button type="submit" name="action" value="bulk_block_results"
                  class="btn-bulk btn-bulk--danger"
                  onclick="return confirmBulk('block results for')">
            📊 Block Results
          </button>
          <button type="submit" name="action" value="bulk_unblock_results"
                  class="btn-bulk"
                  onclick="return confirmBulk('unblock results for')">
            📊 Unblock Results
          </button>
          <?php endif; ?>
          <span id="selCount" style="font-size:12px;color:#9b97b0">0 selected</span>
        </div>

        <!-- Table -->
        <div class="table-wrap">
          <?php if (empty($students)): ?>
          <div class="empty-state">No students found matching your filters.</div>
          <?php else: ?>
          <table>
            <thead>
              <tr>
                <th style="width:36px"></th>
                <th>Student</th>
                <th>Class</th>
                <th>Portal</th>
                <th>Results</th>
                <?php if ($canLockPortal || $canBlockResults || $canResetPassword): ?>
                <th>Actions</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $s): ?>
              <tr>
                <td>
                  <input type="checkbox" name="selected_ids[]"
                         value="<?php echo $s['id']; ?>"
                         class="row-cb"
                         onchange="updateSelCount()"/>
                </td>
                <td>
                  <div class="student-name">
                    <?php echo htmlspecialchars($s['last_name'] . ', ' . $s['first_name']); ?>
                  </div>
                  <div class="adm-no"><?php echo htmlspecialchars($s['admission_number']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($s['grade_level'] . ' · ' . $s['class']); ?></td>
                <td>
                  <span class="status-badge <?php echo $s['portal_blocked'] ? 'badge--blocked' : 'badge--ok'; ?>">
                    <?php echo $s['portal_blocked'] ? 'Locked' : 'Active'; ?>
                  </span>
                  <?php if ($s['portal_blocked'] && $s['portal_blocked_reason']): ?>
                  <div class="reason-tip" title="<?php echo htmlspecialchars($s['portal_blocked_reason']); ?>">
                    <?php echo htmlspecialchars($s['portal_blocked_reason']); ?>
                  </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-badge <?php echo $s['results_blocked'] ? 'badge--blocked' : 'badge--ok'; ?>">
                    <?php echo $s['results_blocked'] ? 'Blocked' : 'Accessible'; ?>
                  </span>
                  <?php if ($s['results_blocked'] && $s['results_blocked_reason']): ?>
                  <div class="reason-tip" title="<?php echo htmlspecialchars($s['results_blocked_reason']); ?>">
                    <?php echo htmlspecialchars($s['results_blocked_reason']); ?>
                  </div>
                  <?php endif; ?>
                </td>
                <?php if ($canLockPortal || $canBlockResults || $canResetPassword): ?>
                <td>
                  <div class="action-cell">
                    <?php if ($canLockPortal): ?>
                      <?php if ($s['portal_blocked']): ?>
                      <button type="button" class="btn-action btn-unlock"
                              onclick="openModal('unlock_portal', <?php echo $s['id']; ?>, 'Unlock portal for <?php echo htmlspecialchars(addslashes($s['first_name'])); ?>?', false)">
                        🔓 Unlock
                      </button>
                      <?php else: ?>
                      <button type="button" class="btn-action btn-lock"
                              onclick="openModal('lock_portal', <?php echo $s['id']; ?>, 'Lock portal for <?php echo htmlspecialchars(addslashes($s['first_name'])); ?>?', true)">
                        🔒 Lock
                      </button>
                      <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($canBlockResults): ?>
                      <?php if ($s['results_blocked']): ?>
                      <button type="button" class="btn-action btn-unlock"
                              onclick="openModal('unblock_results', <?php echo $s['id']; ?>, 'Unblock results for <?php echo htmlspecialchars(addslashes($s['first_name'])); ?>?', false)">
                        📊 Unblock
                      </button>
                      <?php else: ?>
                      <button type="button" class="btn-action btn-lock"
                              onclick="openModal('block_results', <?php echo $s['id']; ?>, 'Block results for <?php echo htmlspecialchars(addslashes($s['first_name'])); ?>?', true)">
                        📊 Block
                      </button>
                      <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($canResetPassword): ?>
                    <button type="button" class="btn-action btn-reset-pw"
                            onclick="openModal('reset_password', <?php echo $s['id']; ?>, 'Reset password for <?php echo htmlspecialchars(addslashes($s['first_name'])); ?> to their admission number?', false)">
                      🔑 Reset PW
                    </button>
                    <?php endif; ?>
                  </div>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </form>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
        $qBase = http_build_query(array_filter([
            'grade' => $filterGrade, 'portal' => $filterPortal,
            'results' => $filterResults, 'search' => $search,
        ]));
        for ($p = 1; $p <= $totalPages; $p++):
        ?>
        <?php if ($p === $page): ?>
        <span class="current"><?php echo $p; ?></span>
        <?php else: ?>
        <a href="?<?php echo $qBase; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
        <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    function selectAll(checked) {
      document.querySelectorAll('.row-cb').forEach(function (cb) { cb.checked = checked; });
      updateSelCount();
    }

    function updateSelCount() {
      var n = document.querySelectorAll('.row-cb:checked').length;
      document.getElementById('selCount').textContent = n + ' selected';
    }

    function confirmBulk(action) {
      var n = document.querySelectorAll('.row-cb:checked').length;
      if (n === 0) { alert('Please select at least one student.'); return false; }
      return confirm(n + ' student(s) will have their ' + action + ' applied. Continue?');
    }

    /* ── Modal ── */
    var _modalAction    = '';
    var _modalStudentId = '';
    var _needsReason    = true;

    function openModal(action, studentId, title, needsReason) {
      _modalAction    = action;
      _modalStudentId = studentId;
      _needsReason    = needsReason;
      document.getElementById('modalTitle').textContent   = title;
      document.getElementById('modalDesc').textContent    = needsReason
        ? 'Provide a reason (recommended — shown to student):'
        : 'This action can be reversed at any time.';
      document.getElementById('modalReason').value        = '';
      document.getElementById('modalConfirmBtn').className =
        needsReason ? 'modal-confirm modal-confirm--danger' : 'modal-confirm';
      document.getElementById('modalOverlay').classList.add('open');
    }

    function closeModal() {
      document.getElementById('modalOverlay').classList.remove('open');
    }

    function submitModal() {
      document.getElementById('modalAction').value      = _modalAction;
      document.getElementById('modalStudentId').value  = _modalStudentId;
      document.getElementById('modalReasonHidden').value = document.getElementById('modalReason').value;
      document.getElementById('modalForm').submit();
    }

    document.getElementById('modalOverlay').addEventListener('click', function (e) {
      if (e.target === this) closeModal();
    });
  </script>

</body>
</html>