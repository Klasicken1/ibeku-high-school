<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENTS LIST
   File: public/admin/students.php

   Accessible to: superadmin, principal, vp_admin, form_teacher
   Form teachers see only their assigned class.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin', 'form_teacher', 'section_admin']);

$admin           = currentAdmin();
$pdo             = getDB();
$isSectionAdmin  = $admin['role'] === 'section_admin';
$adminOwnSection = $admin['section'];

/* ── Form teacher: force their class ── */
$formTeacherGradeLevel = null;
$formTeacherClass      = null;
if ($admin['role'] === 'form_teacher' && !empty($admin['class_assigned'])) {
    if (preg_match('/^(JSS[123]|SSS[123])([A-Z0-9]+)$/', $admin['class_assigned'], $m)) {
        $formTeacherGradeLevel = $m[1];
        $formTeacherClass      = $m[2];
    }
}

/* ── Load active classes ── */
$allClassRows = $pdo->query(
    "SELECT grade_level, class FROM class_arms WHERE is_active = 1 ORDER BY grade_level ASC, class ASC"
)->fetchAll();
$classesByGradeLevel = [];
foreach ($allClassRows as $row) {
    $classesByGradeLevel[$row['grade_level']][] = $row['class'];
}

$allGradeLevels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3',
                    'SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];

/* ── Filters ── */
$filterGradeLevel = $formTeacherGradeLevel ?? ($_GET['grade_level'] ?? '');
$filterClass      = $formTeacherClass      ?? ($_GET['class']       ?? '');
$filterSection    = $isSectionAdmin ? $adminOwnSection : ($_GET['section'] ?? '');
$filterStatus     = $_GET['status']     ?? 'active';
$filterSearch     = trim($_GET['search'] ?? '');
$page             = max(1, (int) ($_GET['page'] ?? 1));
$perPage          = 30;
$offset           = ($page - 1) * $perPage;

/* ── Build query ── */
$where  = ['1=1'];
$params = [];

if ($filterGradeLevel) {
    $where[]  = 's.grade_level = ?';
    $params[] = $filterGradeLevel;
}
if ($filterClass) {
    $where[]  = 's.class = ?';
    $params[] = $filterClass;
}
if ($filterSection) {
    $where[]  = 's.section = ?';
    $params[] = $filterSection;
}
if ($filterStatus) {
    $where[]  = 's.status = ?';
    $params[] = $filterStatus;
}
if ($filterSearch) {
    $where[]  = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ?)';
    $like     = '%' . $filterSearch . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}

$whereSQL = implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM students s WHERE $whereSQL");
$totalStmt->execute($params);
$totalStudents = (int) $totalStmt->fetchColumn();
$totalPages    = (int) ceil($totalStudents / $perPage);

$students = $pdo->prepare(
    "SELECT s.id, s.admission_number, s.first_name, s.last_name, s.other_name,
            s.gender, s.grade_level, s.class, s.section, s.status, s.is_active,
            s.date_admitted, s.photo
     FROM   students s
     WHERE  $whereSQL
     ORDER  BY s.grade_level ASC, s.class ASC, s.last_name ASC, s.first_name ASC
     LIMIT  ? OFFSET ?"
);
$students->execute([...$params, $perPage, $offset]);
$students = $students->fetchAll();

$statusLabels = [
    'active'      => ['label' => 'Active',      'class' => 'badge--active'],
    'expelled'    => ['label' => 'Expelled',    'class' => 'badge--expelled'],
    'graduated'   => ['label' => 'Graduated',   'class' => 'badge--graduated'],
    'deceased'    => ['label' => 'Deceased',    'class' => 'badge--deceased'],
    'transferred' => ['label' => 'Transferred', 'class' => 'badge--transferred'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Students — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .filter-bar {
    background:#fff; border:1px solid #e8e6f0; border-radius:14px;
    padding:16px 20px; margin-bottom:20px;
    display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;
  }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; letter-spacing:.04em; }
  .filter-group select, .filter-group input {
    padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px;
    font-size:13px; font-family:'DM Sans',sans-serif; min-width:130px;
  }
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
  .btn-filter:hover { background:#3a7dc4; }
  .btn-reset { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:8px 14px; border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer; text-decoration:none; }

  .page-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:12px; }
  .btn-new { background:#3d1a6e; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-size:13.5px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
  .btn-new:hover { background:#5a2d9e; }

  .students-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.students-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.students-table th {
    background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left;
    font-size:11.5px; text-transform:uppercase; letter-spacing:.04em;
  }
  table.students-table td { padding:10px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.students-table tr:last-child td { border-bottom:none; }
  table.students-table tr:hover td { background:#faf9fd; }

  .student-photo {
    width:34px; height:34px; border-radius:50%; object-fit:cover;
    background:#f0ecfa; border:1px solid #e2e0ea;
  }
  .student-initials {
    width:34px; height:34px; border-radius:50%; background:#3d1a6e;
    color:#fff; font-size:12px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
  }

  .badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; }
  .badge--active      { background:#e6f9ed; color:#1a7a3a; }
  .badge--expelled    { background:#ffe6e6; color:#cc3333; }
  .badge--graduated   { background:#e6f0ff; color:#1a5a9a; }
  .badge--deceased    { background:#f4f3f9; color:#6b6b80; }
  .badge--transferred { background:#fff3e6; color:#8a4a00; }

  .action-btn { font-size:12px; font-weight:600; padding:4px 10px; border-radius:6px; text-decoration:none; border:none; cursor:pointer; }
  .action-btn--edit    { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--promote { background:#e6f9ed; color:#1a7a3a; }

  .pagination { display:flex; gap:6px; justify-content:center; margin-top:20px; flex-wrap:wrap; }
  .pagination a, .pagination span {
    padding:6px 12px; border-radius:7px; font-size:13px; font-weight:600;
    border:1px solid #e8e6f0; text-decoration:none; color:#3d1a6e; background:#fff;
  }
  .pagination a:hover { background:#f0ecfa; }
  .pagination .current { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }

  .results-count { font-size:13px; color:#6b6b80; margin-bottom:12px; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'students'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Students</h2>
          <p>Manage student records, view profiles, and track status.</p>
        </div>
        <?php if (in_array($admin['role'], ['superadmin', 'vp_admin'], true)): ?>
        <a href="students-create.php" class="btn-new">+ Add Student</a>
        <?php endif; ?>
      </div>

      <!-- Filters -->
      <form method="GET" class="filter-bar">
        <?php if (!$formTeacherGradeLevel): ?>
        <div class="filter-group">
          <label>Grade Level</label>
          <select name="grade_level" id="filter_grade_level">
            <option value="">All Grade Levels</option>
            <?php foreach ($allGradeLevels as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filterGradeLevel === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Class</label>
          <select name="class" id="filter_class">
            <option value="">All Classes</option>
            <?php if ($filterGradeLevel && !empty($classesByGradeLevel[$filterGradeLevel])): ?>
              <?php foreach ($classesByGradeLevel[$filterGradeLevel] as $cls): ?>
              <option value="<?php echo htmlspecialchars($cls); ?>" <?php echo $filterClass === $cls ? 'selected' : ''; ?>><?php echo htmlspecialchars($cls); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Section</label>
          <select name="section">
            <option value="">All Sections</option>
            <option value="ss" <?php echo $filterSection === 'ss' ? 'selected' : ''; ?>>Senior Secondary</option>
            <option value="js" <?php echo $filterSection === 'js' ? 'selected' : ''; ?>>Junior Secondary</option>
          </select>
        </div>
        <?php else: ?>
        <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($formTeacherGradeLevel); ?>"/>
        <input type="hidden" name="class" value="<?php echo htmlspecialchars($formTeacherClass); ?>"/>
        <?php endif; ?>
        <div class="filter-group">
          <label>Status</label>
          <select name="status">
            <option value="">All Statuses</option>
            <option value="active"      <?php echo $filterStatus === 'active'      ? 'selected' : ''; ?>>Active</option>
            <option value="expelled"    <?php echo $filterStatus === 'expelled'    ? 'selected' : ''; ?>>Expelled</option>
            <option value="graduated"   <?php echo $filterStatus === 'graduated'   ? 'selected' : ''; ?>>Graduated</option>
            <option value="deceased"    <?php echo $filterStatus === 'deceased'    ? 'selected' : ''; ?>>Deceased</option>
            <option value="transferred" <?php echo $filterStatus === 'transferred' ? 'selected' : ''; ?>>Transferred</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Search</label>
          <input type="text" name="search" value="<?php echo htmlspecialchars($filterSearch); ?>" placeholder="Name or admission no."/>
        </div>
        <button type="submit" class="btn-filter">Filter</button>
        <a href="students.php" class="btn-reset">Reset</a>
      </form>

      <p class="results-count">
        Showing <strong><?php echo count($students); ?></strong> of <strong><?php echo $totalStudents; ?></strong> students
        <?php if ($filterGradeLevel || $filterClass || $filterSearch): ?>
        (filtered)
        <?php endif; ?>
      </p>

      <div class="students-table-wrap">
        <?php if (empty($students)): ?>
        <div class="empty-state">No students found matching your filters.</div>
        <?php else: ?>
        <table class="students-table">
          <thead>
            <tr>
              <th style="width:44px"></th>
              <th>Admission No.</th>
              <th>Name</th>
              <th>Grade Level</th>
              <th>Class</th>
              <th>Gender</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s):
              $fullName = trim($s['first_name'] . ' ' . ($s['other_name'] ? $s['other_name'] . ' ' : '') . $s['last_name']);
              $initials = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
              $status   = $statusLabels[$s['status']] ?? $statusLabels['active'];
            ?>
            <tr>
              <td>
                <?php if ($s['photo'] && file_exists(dirname(__DIR__) . '/assets/images/students/' . $s['photo'])): ?>
                <img src="../assets/images/students/<?php echo htmlspecialchars($s['photo']); ?>"
                     alt="<?php echo htmlspecialchars($fullName); ?>" class="student-photo"/>
                <?php else: ?>
                <div class="student-initials"><?php echo htmlspecialchars($initials); ?></div>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($s['admission_number']); ?></td>
              <td><strong><?php echo htmlspecialchars($fullName); ?></strong></td>
              <td><?php echo htmlspecialchars($allGradeLevels[$s['grade_level']] ?? $s['grade_level']); ?></td>
              <td><?php echo htmlspecialchars($s['class']); ?></td>
              <td><?php echo ucfirst($s['gender']); ?></td>
              <td><span class="badge <?php echo $status['class']; ?>"><?php echo $status['label']; ?></span></td>
              <td>
                <?php if (in_array($admin['role'], ['superadmin', 'vp_admin'], true)): ?>
                <a href="students-edit.php?id=<?php echo $s['id']; ?>" class="action-btn action-btn--edit">Edit</a>
                <?php endif; ?>
                <?php if (in_array($admin['role'], ['superadmin', 'form_teacher'], true) && $s['status'] === 'active'): ?>
                <a href="students-promote.php?id=<?php echo $s['id']; ?>" class="action-btn action-btn--promote">Promote</a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
        $queryBase = http_build_query(array_filter([
            'grade_level' => $filterGradeLevel,
            'class'       => $filterClass,
            'section'     => $filterSection,
            'status'      => $filterStatus,
            'search'      => $filterSearch,
        ]));
        for ($p = 1; $p <= $totalPages; $p++):
        ?>
        <?php if ($p === $page): ?>
        <span class="current"><?php echo $p; ?></span>
        <?php else: ?>
        <a href="?<?php echo $queryBase; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
        <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    var classesByGradeLevel = <?php echo json_encode($classesByGradeLevel); ?>;
    var gradeLevelSelect = document.getElementById('filter_grade_level');
    var classSelect       = document.getElementById('filter_class');

    if (gradeLevelSelect && classSelect) {
      gradeLevelSelect.addEventListener('change', function () {
        var gl = gradeLevelSelect.value;
        classSelect.innerHTML = '<option value="">All Classes</option>';
        if (gl && classesByGradeLevel[gl]) {
          classesByGradeLevel[gl].forEach(function (cls) {
            var opt = document.createElement('option');
            opt.value = cls; opt.textContent = cls;
            classSelect.appendChild(opt);
          });
        }
      });
    }
  </script>

</body>
</html>