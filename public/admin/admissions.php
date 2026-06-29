<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMISSIONS ENQUIRIES
   File: public/admin/admissions.php

   Accessible to: superadmin, principal, vp_admin
   View all admission enquiries submitted via the public form.
   Update status (new → contacted → assessed → admitted/declined).
   Add internal notes. Convert admitted enquiries directly into
   student records.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin']);

$admin = currentAdmin();
$pdo   = getDB();

$message     = '';
$messageType = '';

/* ── Handle actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = trim($_POST['action']        ?? '');
    $enquiryId    = (int) ($_POST['enquiry_id']  ?? 0);

    if ($action === 'update_status' && $enquiryId > 0) {
        $newStatus = trim($_POST['new_status'] ?? '');
        $notes     = trim($_POST['notes']      ?? '');
        $validStatuses = ['new', 'contacted', 'assessed', 'admitted', 'declined'];

        if (!in_array($newStatus, $validStatuses, true)) {
            $message = 'Invalid status.'; $messageType = 'error';
        } else {
            try {
                $pdo->prepare(
                    'UPDATE admissions SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?'
                )->execute([$newStatus, $notes ?: null, $enquiryId]);
                $message = 'Enquiry updated.'; $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }

    } elseif ($action === 'delete' && $enquiryId > 0) {
        try {
            $pdo->prepare('DELETE FROM admissions WHERE id = ?')->execute([$enquiryId]);
            $message = 'Enquiry deleted.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif ($action === 'convert_to_student' && $enquiryId > 0) {
        /* ── Convert an admitted enquiry into a student record ── */
        $enqStmt = $pdo->prepare('SELECT * FROM admissions WHERE id = ? LIMIT 1');
        $enqStmt->execute([$enquiryId]);
        $enq = $enqStmt->fetch();

        if (!$enq) {
            $message = 'Enquiry not found.'; $messageType = 'error';
        } elseif ($enq['status'] !== 'admitted') {
            $message = 'Only admitted enquiries can be converted to student records. Update the status to Admitted first.';
            $messageType = 'error';
        } else {
            try {
                /* Auto-generate admission number */
                $year   = date('Y');
                $prefix = 'IHS/' . $year . '/';
                $lastStmt = $pdo->prepare(
                    "SELECT admission_number FROM students WHERE admission_number LIKE ? ORDER BY admission_number DESC LIMIT 1"
                );
                $lastStmt->execute([$prefix . '%']);
                $last = $lastStmt->fetchColumn();
                $next = $last ? ((int) substr($last, strrpos($last, '/') + 1) + 1) : 1;
                $admNo = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

                $section = str_starts_with($enq['entry_class'], 'JSS') ? 'js' : 'ss';

                $pdo->beginTransaction();

                $pdo->prepare(
                    'INSERT INTO students
                        (admission_number, first_name, last_name, gender, date_of_birth,
                         section, grade_level, class, department, date_admitted,
                         is_active, status, parent_name, parent_phone, parent_email)
                     VALUES (?,?,?,?,?,?,?,\'A\',\'general\',CURDATE(),1,\'active\',?,?,?)'
                )->execute([
                    $admNo,
                    $enq['student_first'],
                    $enq['student_last'],
                    $enq['gender'] ?: 'male',
                    $enq['date_of_birth'],
                    $section,
                    $enq['entry_class'],
                    trim($enq['parent_first'] . ' ' . $enq['parent_last']),
                    $enq['parent_phone'],
                    $enq['parent_email'],
                ]);

                $newStudentId = (int) $pdo->lastInsertId();

                /* Record in student_history */
                $pdo->prepare(
                    'INSERT INTO student_history (student_id, event_type, to_grade_level, to_class, reason, recorded_by)
                     VALUES (?, \'promotion\', ?, \'A\', \'Admitted via online enquiry\', ?)'
                )->execute([$newStudentId, $enq['entry_class'], $admin['id']]);

                /* Mark enquiry as converted */
                $pdo->prepare(
                    'UPDATE admissions SET status = \'admitted\',
                     notes = CONCAT(COALESCE(notes, \'\'), \'\nConverted to student record. Admission No: \', ?),
                     updated_at = NOW()
                     WHERE id = ?'
                )->execute([$admNo, $enquiryId]);

                $pdo->commit();

                $message = 'Student record created successfully. Admission number: ' . $admNo;
                $messageType = 'success';

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('IHS admissions convert error: ' . $e->getMessage());
                $message = 'A server error occurred while creating the student record.';
                $messageType = 'error';
            }
        }
    }
}

/* ── Status config ── */
$statuses = [
    'new'       => ['label' => 'New',       'bg' => '#e6f0ff', 'color' => '#1a5a9a'],
    'contacted' => ['label' => 'Contacted', 'bg' => '#fff3e6', 'color' => '#8a4a00'],
    'assessed'  => ['label' => 'Assessed',  'bg' => '#f0ecfa', 'color' => '#3d1a6e'],
    'admitted'  => ['label' => 'Admitted',  'bg' => '#e6f9ed', 'color' => '#1a7a3a'],
    'declined'  => ['label' => 'Declined',  'bg' => '#ffe6e6', 'color' => '#cc3333'],
];

$entryClassLabels = ['JSS1' => 'JSS 1 (Entry)', 'SSS1' => 'SSS 1 (Entry)'];

/* ── Filters ── */
$filterStatus  = $_GET['status']  ?? '';
$filterSession = $_GET['session'] ?? '';
$filterSearch  = trim($_GET['search'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($filterStatus) {
    $where[]  = 'a.status = ?';
    $params[] = $filterStatus;
}
if ($filterSession) {
    $where[]  = 'a.session = ?';
    $params[] = $filterSession;
}
if ($filterSearch) {
    $where[]  = '(a.student_first LIKE ? OR a.student_last LIKE ? OR a.parent_email LIKE ? OR a.parent_phone LIKE ?)';
    $like     = '%' . $filterSearch . '%';
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$whereSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM admissions a WHERE $whereSQL");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

$enqStmt = $pdo->prepare(
    "SELECT * FROM admissions a WHERE $whereSQL
     ORDER BY a.created_at DESC LIMIT ? OFFSET ?"
);
$enqStmt->execute([...$params, $perPage, $offset]);
$enquiries = $enqStmt->fetchAll();

/* ── Stats ── */
$statCounts = [];
foreach (array_keys($statuses) as $st) {
    $statCounts[$st] = (int) $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE status = ?")
        ->execute([$st]) ? 0 : 0;
}
$scStmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM admissions GROUP BY status");
foreach ($scStmt->fetchAll() as $row) {
    $statCounts[$row['status']] = (int) $row['cnt'];
}

/* ── Available sessions for filter ── */
$sessions = $pdo->query("SELECT DISTINCT session FROM admissions ORDER BY session DESC")->fetchAll(PDO::FETCH_COLUMN);

/* ── Detail view ── */
$viewId  = (int) ($_GET['view'] ?? 0);
$viewEnq = null;
if ($viewId > 0) {
    $viewStmt = $pdo->prepare('SELECT * FROM admissions WHERE id = ? LIMIT 1');
    $viewStmt->execute([$viewId]);
    $viewEnq = $viewStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admissions — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .stats-row { display:flex; gap:12px; margin-bottom:22px; flex-wrap:wrap; }
  .stat-pill {
    background:#fff; border:1px solid #e8e6f0; border-radius:10px;
    padding:10px 16px; font-size:12.5px; cursor:pointer; text-decoration:none;
    display:block; transition:border-color .15s;
  }
  .stat-pill:hover { border-color:#3d1a6e; }
  .stat-pill--active { border-color:#3d1a6e; background:#f0ecfa; }
  .stat-pill strong { display:block; font-size:17px; }

  .filter-bar { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:14px 18px; margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; }
  .filter-group select, .filter-group input { padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:130px; }
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
  .btn-filter:hover { background:#3a7dc4; }
  .btn-reset { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:8px 14px; border-radius:7px; font-size:12.5px; font-weight:600; text-decoration:none; }

  .enq-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.enq-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.enq-table th { background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
  table.enq-table td { padding:11px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.enq-table tr:last-child td { border-bottom:none; }
  table.enq-table tr:hover td { background:#faf9fd; }

  .student-name { font-weight:600; color:#1a1a2e; }
  .parent-info  { font-size:12px; color:#9b97b0; margin-top:2px; }

  .status-badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; }

  .action-btn { font-size:11.5px; font-weight:600; padding:5px 10px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; display:inline-block; }
  .action-btn--view    { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--delete  { background:#ffe6e6; color:#cc3333; }
  .action-btn--convert { background:#e6f9ed; color:#1a7a3a; }

  .results-count { font-size:13px; color:#6b6b80; margin-bottom:12px; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }

  .pagination { display:flex; gap:6px; justify-content:center; margin-top:20px; flex-wrap:wrap; }
  .pagination a, .pagination span { padding:6px 12px; border-radius:7px; font-size:13px; font-weight:600; border:1px solid #e8e6f0; text-decoration:none; color:#3d1a6e; background:#fff; }
  .pagination a:hover { background:#f0ecfa; }
  .pagination .current { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }

  /* Detail panel */
  .detail-panel { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:24px; }
  .detail-panel h3 { font-size:15px; color:#3d1a6e; margin-bottom:16px; }
  .detail-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:14px; margin-bottom:20px; }
  .detail-item__label { font-size:11px; font-weight:700; color:#9b97b0; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
  .detail-item__value { font-size:13.5px; color:#1a1a2e; }

  .update-form { border-top:1px solid #f0eef6; padding-top:18px; margin-top:4px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
  .update-form .form-group { margin-bottom:0; }
  .update-form label { display:block; font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; margin-bottom:5px; }
  .update-form select, .update-form textarea { padding:8px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; }
  .update-form textarea { min-width:260px; resize:vertical; }
  .btn-update { background:#3d1a6e; color:#fff; border:none; padding:9px 22px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
  .btn-update:hover { background:#5a2d9e; }
  .btn-convert { background:#1a7a3a; color:#fff; border:none; padding:9px 22px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
  .btn-convert:hover { background:#155e2d; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'admissions'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Admissions Enquiries</h2>
        <p>Review and manage admission applications submitted via the school website.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Status stats -->
      <div class="stats-row">
        <?php foreach ($statuses as $key => $cfg): ?>
        <a href="?status=<?php echo $key; ?>"
           class="stat-pill <?php echo $filterStatus === $key ? 'stat-pill--active' : ''; ?>"
           style="border-left:3px solid <?php echo $cfg['color']; ?>">
          <strong style="color:<?php echo $cfg['color']; ?>"><?php echo $statCounts[$key] ?? 0; ?></strong>
          <?php echo $cfg['label']; ?>
        </a>
        <?php endforeach; ?>
        <a href="admissions.php" class="stat-pill <?php echo !$filterStatus ? 'stat-pill--active' : ''; ?>">
          <strong><?php echo $total; ?></strong>All
        </a>
      </div>

      <!-- Detail view for selected enquiry -->
      <?php if ($viewEnq): ?>
      <?php
      $st = $statuses[$viewEnq['status']] ?? $statuses['new'];
      $parentName = trim($viewEnq['parent_first'] . ' ' . $viewEnq['parent_last']);
      $studentName = trim($viewEnq['student_first'] . ' ' . $viewEnq['student_last']);
      ?>
      <div class="detail-panel">
        <h3>
          Enquiry — <?php echo htmlspecialchars($studentName); ?>
          <span class="status-badge" style="background:<?php echo $st['bg']; ?>;color:<?php echo $st['color']; ?>;margin-left:8px">
            <?php echo $st['label']; ?>
          </span>
          <a href="admissions.php" style="font-size:12.5px;color:#9b97b0;text-decoration:none;margin-left:12px;font-weight:400">✕ Close</a>
        </h3>

        <div class="detail-grid">
          <div>
            <div class="detail-item__label">Student Name</div>
            <div class="detail-item__value"><?php echo htmlspecialchars($studentName); ?></div>
          </div>
          <div>
            <div class="detail-item__label">Date of Birth</div>
            <div class="detail-item__value"><?php echo $viewEnq['date_of_birth'] ? date('d M Y', strtotime($viewEnq['date_of_birth'])) : '—'; ?></div>
          </div>
          <div>
            <div class="detail-item__label">Gender</div>
            <div class="detail-item__value"><?php echo $viewEnq['gender'] ? ucfirst($viewEnq['gender']) : '—'; ?></div>
          </div>
          <div>
            <div class="detail-item__label">Entry Class</div>
            <div class="detail-item__value"><?php echo htmlspecialchars($entryClassLabels[$viewEnq['entry_class']] ?? $viewEnq['entry_class']); ?></div>
          </div>
          <div>
            <div class="detail-item__label">Session</div>
            <div class="detail-item__value"><?php echo htmlspecialchars($viewEnq['session']); ?></div>
          </div>
          <div>
            <div class="detail-item__label">Previous School</div>
            <div class="detail-item__value"><?php echo htmlspecialchars($viewEnq['previous_school'] ?: '—'); ?></div>
          </div>
          <div>
            <div class="detail-item__label">Parent / Guardian</div>
            <div class="detail-item__value"><?php echo htmlspecialchars($parentName); ?></div>
          </div>
          <div>
            <div class="detail-item__label">Parent Phone</div>
            <div class="detail-item__value"><?php echo htmlspecialchars($viewEnq['parent_phone']); ?></div>
          </div>
          <div>
            <div class="detail-item__label">Parent Email</div>
            <div class="detail-item__value"><?php echo htmlspecialchars($viewEnq['parent_email']); ?></div>
          </div>
          <div>
            <div class="detail-item__label">Submitted</div>
            <div class="detail-item__value"><?php echo date('d M Y, g:ia', strtotime($viewEnq['created_at'])); ?></div>
          </div>
        </div>

        <?php if ($viewEnq['message']): ?>
        <div style="margin-bottom:16px">
          <div class="detail-item__label" style="margin-bottom:5px">Message from Parent</div>
          <div style="background:#faf9fd;border:1px solid #f0eef6;border-radius:8px;padding:12px 14px;font-size:13px;color:#1a1a2e;line-height:1.6">
            <?php echo nl2br(htmlspecialchars($viewEnq['message'])); ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($viewEnq['notes']): ?>
        <div style="margin-bottom:16px">
          <div class="detail-item__label" style="margin-bottom:5px">Internal Notes</div>
          <div style="background:#fffbe6;border:1px solid #fff0a0;border-radius:8px;padding:12px 14px;font-size:13px;color:#1a1a2e;line-height:1.6">
            <?php echo nl2br(htmlspecialchars($viewEnq['notes'])); ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Update status form -->
        <form method="POST" class="update-form">
          <input type="hidden" name="action"     value="update_status"/>
          <input type="hidden" name="enquiry_id" value="<?php echo $viewEnq['id']; ?>"/>
          <input type="hidden" name="redirect_view" value="<?php echo $viewEnq['id']; ?>"/>

          <div class="form-group">
            <label>Update Status</label>
            <select name="new_status">
              <?php foreach ($statuses as $key => $cfg): ?>
              <option value="<?php echo $key; ?>" <?php echo $viewEnq['status'] === $key ? 'selected' : ''; ?>><?php echo $cfg['label']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Notes (internal only)</label>
            <textarea name="notes" rows="2" placeholder="e.g. Called parent, meeting scheduled for..."><?php echo htmlspecialchars($viewEnq['notes'] ?? ''); ?></textarea>
          </div>

          <button type="submit" class="btn-update">Save</button>
        </form>

        <?php if ($viewEnq['status'] === 'admitted'): ?>
        <form method="POST" style="margin-top:10px"
              onsubmit="return confirm('Create a full student record for <?php echo htmlspecialchars($studentName); ?>? This will admit them into class <?php echo htmlspecialchars($viewEnq['entry_class']); ?> A and generate an admission number.')">
          <input type="hidden" name="action"     value="convert_to_student"/>
          <input type="hidden" name="enquiry_id" value="<?php echo $viewEnq['id']; ?>"/>
          <button type="submit" class="btn-convert">
            🎓 Create Student Record
          </button>
          <span style="font-size:12px;color:#9b97b0;margin-left:8px">Auto-assigns admission number and places in <?php echo htmlspecialchars($viewEnq['entry_class']); ?> A</span>
        </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Filters -->
      <form method="GET" class="filter-bar">
        <div class="filter-group">
          <label>Session</label>
          <select name="session">
            <option value="">All Sessions</option>
            <?php foreach ($sessions as $s): ?>
            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $filterSession === $s ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Status</label>
          <select name="status">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $key => $cfg): ?>
            <option value="<?php echo $key; ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>><?php echo $cfg['label']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Search</label>
          <input type="text" name="search" value="<?php echo htmlspecialchars($filterSearch); ?>" placeholder="Name, email or phone"/>
        </div>
        <button type="submit" class="btn-filter">Filter</button>
        <a href="admissions.php" class="btn-reset">Reset</a>
      </form>

      <p class="results-count">
        Showing <strong><?php echo count($enquiries); ?></strong> of <strong><?php echo $total; ?></strong> enquiries
      </p>

      <div class="enq-table-wrap">
        <?php if (empty($enquiries)): ?>
        <div class="empty-state">No admissions enquiries found.</div>
        <?php else: ?>
        <table class="enq-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Entry Class</th>
              <th>Session</th>
              <th>Status</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($enquiries as $enq):
              $st = $statuses[$enq['status']] ?? $statuses['new'];
              $studentName = trim($enq['student_first'] . ' ' . $enq['student_last']);
              $parentName  = trim($enq['parent_first']  . ' ' . $enq['parent_last']);
            ?>
            <tr>
              <td>
                <div class="student-name"><?php echo htmlspecialchars($studentName); ?></div>
                <div class="parent-info">Parent: <?php echo htmlspecialchars($parentName); ?> · <?php echo htmlspecialchars($enq['parent_phone']); ?></div>
              </td>
              <td><?php echo htmlspecialchars($entryClassLabels[$enq['entry_class']] ?? $enq['entry_class']); ?></td>
              <td><?php echo htmlspecialchars($enq['session']); ?></td>
              <td>
                <span class="status-badge" style="background:<?php echo $st['bg']; ?>;color:<?php echo $st['color']; ?>">
                  <?php echo $st['label']; ?>
                </span>
              </td>
              <td style="font-size:12px;color:#9b97b0"><?php echo date('d M Y', strtotime($enq['created_at'])); ?></td>
              <td>
                <a href="?view=<?php echo $enq['id']; ?><?php echo $filterStatus ? '&status=' . urlencode($filterStatus) : ''; ?>"
                   class="action-btn action-btn--view">View</a>
                <?php if ($enq['status'] === 'admitted'): ?>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Create student record for <?php echo htmlspecialchars($studentName); ?>?')">
                  <input type="hidden" name="action"     value="convert_to_student"/>
                  <input type="hidden" name="enquiry_id" value="<?php echo $enq['id']; ?>"/>
                  <button type="submit" class="action-btn action-btn--convert">Admit</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Delete this enquiry permanently?')">
                  <input type="hidden" name="action"     value="delete"/>
                  <input type="hidden" name="enquiry_id" value="<?php echo $enq['id']; ?>"/>
                  <button type="submit" class="action-btn action-btn--delete">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
        $qBase = http_build_query(array_filter(['status' => $filterStatus, 'session' => $filterSession, 'search' => $filterSearch]));
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

</body>
</html>