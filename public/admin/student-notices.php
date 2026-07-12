<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT NOTICES
   File: public/admin/student-notices.php

   Send official notices to one or multiple students.
   Notice types and role restrictions:
   - suspension        → superadmin, principal, vp_admin, vp_academics
   - expulsion         → superadmin, principal
   - promotion         → superadmin, principal, vp_admin, vp_academics
   - demotion          → superadmin, principal, vp_admin, vp_academics
   - retention         → superadmin, principal, vp_admin, vp_academics
   - behavioural_remark → all above + dean + form_teacher
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole([
    'superadmin', 'principal', 'vp_admin', 'vp_academics',
    'dean', 'form_teacher',
]);

$admin = currentAdmin();
$pdo   = getDB();
$role  = $admin['role'];

/* ── Which notice types this role can send ── */
$allowedTypes = [];

if (in_array($role, ['superadmin', 'principal'], true)) {
    $allowedTypes = ['suspension', 'expulsion', 'promotion', 'demotion', 'retention', 'behavioural_remark'];
} elseif (in_array($role, ['vp_admin', 'vp_academics'], true)) {
    $allowedTypes = ['suspension', 'promotion', 'demotion', 'retention', 'behavioural_remark'];
} elseif (in_array($role, ['dean', 'form_teacher'], true)) {
    $allowedTypes = ['behavioural_remark'];
}

$typeLabels = [
    'suspension'         => 'Suspension Notice',
    'expulsion'          => 'Expulsion Notice',
    'promotion'          => 'Promotion Notice',
    'demotion'           => 'Demotion Notice',
    'retention'          => 'Retention Notice',
    'behavioural_remark' => 'Behavioural Remark',
];

$typeIcons = [
    'suspension'         => '⛔',
    'expulsion'          => '🚫',
    'promotion'          => '🎉',
    'demotion'           => '⚠️',
    'retention'          => '📋',
    'behavioural_remark' => '📝',
];

$typeColors = [
    'suspension'         => '#cc3333',
    'expulsion'          => '#8a0000',
    'promotion'          => '#1a7a3a',
    'demotion'           => '#e8a020',
    'retention'          => '#e8a020',
    'behavioural_remark' => '#3d1a6e',
];

$message     = '';
$messageType = '';

/* ── Handle send ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notice') {

    $type    = trim($_POST['type']   ?? '');
    $title   = trim($_POST['title']  ?? '');
    $body    = trim($_POST['body']   ?? '');
    $ids     = array_map('intval', $_POST['student_ids'] ?? []);

    if (!in_array($type, $allowedTypes, true)) {
        $message = 'You are not authorised to send this notice type.'; $messageType = 'error';
    } elseif ($title === '') {
        $message = 'Notice title is required.'; $messageType = 'error';
    } elseif ($body === '') {
        $message = 'Notice body is required.'; $messageType = 'error';
    } elseif (empty($ids)) {
        $message = 'Please select at least one student.'; $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO student_notifications
                    (student_id, type, title, body, issued_by)
                 VALUES (?,?,?,?,?)'
            );
            $sent = 0;
            foreach ($ids as $sid) {
                $stmt->execute([$sid, $type, $title, $body, $admin['id']]);
                $sent++;
            }
            $message = $typeIcons[$type] . ' ' . $typeLabels[$type] . ' sent to ' . $sent . ' student(s).';
            $messageType = 'success';
        } catch (PDOException $e) {
            error_log('IHS student-notices error: ' . $e->getMessage());
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}

/* ── Load recent notices (last 50) ── */
$recentStmt = $pdo->prepare(
    'SELECT n.*,
            CONCAT(s.first_name, \' \', s.last_name) AS student_name,
            s.admission_number, s.grade_level, s.class,
            u.full_name AS issued_by_name
     FROM student_notifications n
     JOIN students s ON s.id = n.student_id
     JOIN users u    ON u.id = n.issued_by
     ORDER BY n.created_at DESC
     LIMIT 50'
);
$recentStmt->execute();
$recentNotices = $recentStmt->fetchAll();

/* ── Student search for recipient picker ── */
$searchQuery = trim($_GET['q'] ?? '');
$students    = [];
if ($searchQuery !== '' || isset($_GET['grade'])) {
    $filterGrade = $_GET['grade'] ?? '';
    $filterClass = $_GET['class'] ?? '';
    $sw = ['s.is_active = 1'];
    $sp = [];
    if ($filterGrade) { $sw[] = 's.grade_level = ?'; $sp[] = $filterGrade; }
    if ($filterClass) { $sw[] = 's.class = ?';       $sp[] = $filterClass; }
    if ($searchQuery) {
        $sw[]  = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ?)';
        $sp[]  = '%' . $searchQuery . '%';
        $sp[]  = '%' . $searchQuery . '%';
        $sp[]  = '%' . $searchQuery . '%';
    }
    $studentStmt = $pdo->prepare(
        'SELECT id, admission_number, first_name, last_name, grade_level, class
         FROM students s WHERE ' . implode(' AND ', $sw) .
        ' ORDER BY last_name ASC, first_name ASC LIMIT 100'
    );
    $studentStmt->execute($sp);
    $students = $studentStmt->fetchAll();
}

$gradeLevels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3',
                'SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Student Notices — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .notices-grid { display:grid; grid-template-columns:1fr 420px; gap:24px; align-items:start; }
  @media (max-width:900px) { .notices-grid { grid-template-columns:1fr; } }

  /* Compose card */
  .compose-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:22px; }
  .compose-card__title { font-size:14px; font-weight:700; color:#3d1a6e; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid #f0eef6; }

  .form-group  { margin-bottom:14px; }
  .form-label  { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select, .form-textarea {
    width:100%; padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px;
    font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e;
  }
  .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }

  /* Type selector */
  .type-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-bottom:14px; }
  .type-btn {
    border:2px solid #e8e6f0; border-radius:10px; padding:10px 8px;
    text-align:center; cursor:pointer; background:#fff; transition:.15s;
    font-family:'DM Sans',sans-serif;
  }
  .type-btn:hover { border-color:#3d1a6e; }
  .type-btn.selected { border-color:#3d1a6e; background:#f0ecfa; }
  .type-btn input { display:none; }
  .type-btn__icon { font-size:1.3rem; display:block; margin-bottom:4px; }
  .type-btn__label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; letter-spacing:.03em; }

  /* Student picker */
  .picker-bar { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
  .picker-bar input, .picker-bar select { padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; }
  .picker-bar input { flex:1; min-width:160px; }
  .btn-search { background:#4a90d9; color:#fff; border:none; padding:8px 16px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }

  .student-results { max-height:260px; overflow-y:auto; border:1px solid #e8e6f0; border-radius:8px; margin-bottom:12px; }
  .student-row {
    display:flex; align-items:center; gap:10px; padding:9px 12px;
    border-bottom:1px solid #f0eef6; cursor:pointer;
    transition:background .1s; font-size:13px;
  }
  .student-row:last-child { border-bottom:none; }
  .student-row:hover { background:#f8f7fc; }
  .student-row input { accent-color:#3d1a6e; flex-shrink:0; }
  .student-row__name { font-weight:600; color:#1a1a2e; flex:1; }
  .student-row__meta { font-size:11.5px; color:#9b97b0; }
  .student-row__badge { font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; background:#f0ecfa; color:#3d1a6e; }

  .selected-tags { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; min-height:24px; }
  .sel-tag { background:#3d1a6e; color:#fff; font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:20px; display:flex; align-items:center; gap:5px; }
  .sel-tag__remove { cursor:pointer; opacity:.7; font-size:13px; line-height:1; }
  .sel-tag__remove:hover { opacity:1; }

  .btn-send { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; width:100%; margin-top:6px; }
  .btn-send:hover { background:#5a2d9e; }

  .sel-all-btn { font-size:12px; font-weight:600; color:#4a90d9; background:none; border:none; cursor:pointer; padding:0; }
  .sel-all-btn:hover { text-decoration:underline; }

  /* Recent notices log */
  .log-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  .log-card__title { padding:14px 18px; font-size:14px; font-weight:700; color:#3d1a6e; border-bottom:1px solid #f0eef6; }

  .log-table { width:100%; border-collapse:collapse; font-size:12.5px; }
  .log-table th { background:#f8f7fc; color:#6b6b80; padding:9px 14px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #f0eef6; }
  .log-table td { padding:9px 14px; border-bottom:1px solid #f8f7fc; vertical-align:middle; }
  .log-table tr:last-child td { border-bottom:none; }

  .type-pill { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; }
  .unread-dot { width:7px; height:7px; border-radius:50%; background:#3d1a6e; display:inline-block; }
  .empty-log { padding:30px 20px; text-align:center; color:#9b97b0; font-size:13px; }

  .template-btns { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
  .tpl-btn { font-size:11.5px; font-weight:600; padding:4px 10px; border-radius:6px; background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; cursor:pointer; }
  .tpl-btn:hover { background:#e4dcf6; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'student-notices'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Student Notices</h2>
        <p>Send official notices to students. All notices appear in the student's portal inbox.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="notices-grid">

        <!-- ── Compose ── -->
        <div class="compose-card">
          <div class="compose-card__title">Compose Notice</div>

          <form method="POST" id="noticeForm">
            <input type="hidden" name="action" value="send_notice"/>

            <!-- Notice type -->
            <div class="form-group">
              <label class="form-label">Notice Type *</label>
              <div class="type-grid">
                <?php foreach ($allowedTypes as $t): ?>
                <label class="type-btn" id="typebtn-<?php echo $t; ?>">
                  <input type="radio" name="type" value="<?php echo $t; ?>"
                         onchange="selectType('<?php echo $t; ?>')"/>
                  <span class="type-btn__icon"><?php echo $typeIcons[$t]; ?></span>
                  <span class="type-btn__label"><?php echo $typeLabels[$t]; ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Title -->
            <div class="form-group">
              <label class="form-label" for="noticeTitle">Title *</label>

              <!-- Quick templates -->
              <div class="template-btns" id="templateBtns"></div>

              <input type="text" class="form-input" id="noticeTitle" name="title"
                     maxlength="255" required
                     placeholder="e.g. Three-Day Suspension — Misconduct"/>
            </div>

            <!-- Body -->
            <div class="form-group">
              <label class="form-label" for="noticeBody">Message *</label>
              <textarea class="form-textarea" id="noticeBody" name="body"
                        rows="6" required
                        placeholder="Write the full notice here. This will appear in the student's portal inbox exactly as typed."></textarea>
            </div>

            <!-- Student picker -->
            <div class="form-group">
              <label class="form-label">Recipients *</label>

              <!-- Search bar (GET to same page) -->
              <form method="GET" class="picker-bar" id="searchForm">
                <input type="text" name="q" id="searchInput"
                       value="<?php echo htmlspecialchars($searchQuery); ?>"
                       placeholder="Search by name or admission no."/>
                <select name="grade">
                  <option value="">All Grades</option>
                  <?php foreach ($gradeLevels as $k => $v): ?>
                  <option value="<?php echo $k; ?>" <?php echo ($_GET['grade'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-search">Search</button>
              </form>

              <!-- Selected tags -->
              <div class="selected-tags" id="selectedTags">
                <span style="font-size:12px;color:#9b97b0" id="noSelMsg">No students selected yet.</span>
              </div>

              <!-- Results list -->
              <?php if (!empty($students)): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <span style="font-size:12px;color:#6b6b80"><?php echo count($students); ?> student(s) found</span>
                <button type="button" class="sel-all-btn" onclick="selectAllStudents()">Select All</button>
              </div>
              <div class="student-results">
                <?php foreach ($students as $s): ?>
                <div class="student-row" onclick="toggleStudent(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars(addslashes($s['first_name'] . ' ' . $s['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($s['admission_number'])); ?>')">
                  <input type="checkbox" id="sch_<?php echo $s['id']; ?>"
                         data-id="<?php echo $s['id']; ?>"
                         data-name="<?php echo htmlspecialchars(addslashes($s['first_name'] . ' ' . $s['last_name'])); ?>"
                         data-adm="<?php echo htmlspecialchars(addslashes($s['admission_number'])); ?>"
                         class="student-cb"/>
                  <span class="student-row__name">
                    <?php echo htmlspecialchars($s['last_name'] . ', ' . $s['first_name']); ?>
                  </span>
                  <span class="student-row__meta"><?php echo htmlspecialchars($s['admission_number']); ?></span>
                  <span class="student-row__badge"><?php echo htmlspecialchars($s['grade_level'] . ' ' . $s['class']); ?></span>
                </div>
                <?php endforeach; ?>
              </div>
              <?php elseif ($searchQuery || isset($_GET['grade'])): ?>
              <div style="padding:14px;font-size:13px;color:#9b97b0;border:1px solid #e8e6f0;border-radius:8px;text-align:center">
                No students found. Try a different search.
              </div>
              <?php else: ?>
              <div style="padding:14px;font-size:13px;color:#9b97b0;border:1px solid #e8e6f0;border-radius:8px;text-align:center">
                Search for students above to add recipients.
              </div>
              <?php endif; ?>

              <!-- Hidden inputs for selected student IDs (populated by JS) -->
              <div id="hiddenInputs"></div>
            </div>

            <button type="submit" class="btn-send"
                    onclick="return validateNoticeForm()">
              Send Notice →
            </button>
          </form>
        </div>

        <!-- ── Recent notices log ── -->
        <div class="log-card">
          <div class="log-card__title">Recent Notices Sent</div>
          <?php if (empty($recentNotices)): ?>
          <div class="empty-log">No notices sent yet.</div>
          <?php else: ?>
          <table class="log-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Type</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentNotices as $n):
                $color = $typeColors[$n['type']] ?? '#3d1a6e';
              ?>
              <tr>
                <td>
                  <div style="font-weight:600;font-size:12.5px;color:#1a1a2e">
                    <?php echo htmlspecialchars($n['student_name']); ?>
                  </div>
                  <div style="font-size:11px;color:#9b97b0">
                    <?php echo htmlspecialchars($n['admission_number']); ?>
                    &nbsp;·&nbsp;
                    <?php echo htmlspecialchars($n['grade_level'] . ' ' . $n['class']); ?>
                  </div>
                  <div style="font-size:11.5px;color:#3d1a6e;margin-top:2px">
                    <?php echo htmlspecialchars(mb_substr($n['title'], 0, 40)) . (mb_strlen($n['title']) > 40 ? '…' : ''); ?>
                  </div>
                </td>
                <td>
                  <div class="type-pill" style="color:<?php echo $color; ?>;background:<?php echo $color; ?>18">
                    <?php echo $typeIcons[$n['type']] ?? ''; ?>
                    <?php echo $typeLabels[$n['type']] ?? $n['type']; ?>
                  </div>
                  <?php if (!$n['is_read']): ?>
                  <div style="margin-top:4px;font-size:11px;color:#9b97b0">
                    <span class="unread-dot"></span> Unread
                  </div>
                  <?php else: ?>
                  <div style="margin-top:4px;font-size:11px;color:#1a7a3a">✓ Read</div>
                  <?php endif; ?>
                </td>
                <td style="font-size:11.5px;color:#9b97b0;white-space:nowrap">
                  <?php echo date('d M Y', strtotime($n['created_at'])); ?><br/>
                  <?php echo date('g:ia', strtotime($n['created_at'])); ?><br/>
                  <span style="color:#3d1a6e"><?php echo htmlspecialchars($n['issued_by_name']); ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    /* ── Notice type selection ── */
    var templates = {
      suspension: [
        'Three-Day Suspension — Misconduct',
        'One-Week Suspension — Fighting',
        'Suspension Pending Investigation',
      ],
      expulsion: [
        'Expulsion Notice — Gross Misconduct',
        'Permanent Expulsion — Examination Malpractice',
      ],
      promotion: [
        'Promotion to Next Class — End of Session',
        'Congratulations — Promoted to Senior Secondary',
      ],
      demotion: [
        'Demotion Notice — Academic Performance',
        'Class Reassignment — End of Term Review',
      ],
      retention: [
        'Retention in Current Class — Academic Review',
        'Retention Notice — Attendance Issues',
      ],
      behavioural_remark: [
        'Behavioural Concern — Class Disruption',
        'Positive Conduct Commendation',
        'Warning — Repeated Lateness',
        'Counselling Referral',
      ],
    };

    function selectType(type) {
      document.querySelectorAll('.type-btn').forEach(function (b) {
        b.classList.remove('selected');
      });
      document.getElementById('typebtn-' + type).classList.add('selected');

      /* Show templates */
      var tplEl = document.getElementById('templateBtns');
      tplEl.innerHTML = '';
      if (templates[type]) {
        templates[type].forEach(function (tpl) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'tpl-btn';
          btn.textContent = tpl;
          btn.onclick = function () {
            document.getElementById('noticeTitle').value = tpl;
          };
          tplEl.appendChild(btn);
        });
      }
    }

    /* ── Student selection ── */
    var selectedStudents = {};

    function toggleStudent(id, name, adm) {
      var cb = document.getElementById('sch_' + id);
      if (!cb) return;
      if (selectedStudents[id]) {
        delete selectedStudents[id];
        cb.checked = false;
      } else {
        selectedStudents[id] = { name: name, adm: adm };
        cb.checked = true;
      }
      renderTags();
      renderHidden();
    }

    function selectAllStudents() {
      document.querySelectorAll('.student-cb').forEach(function (cb) {
        var id   = cb.dataset.id;
        var name = cb.dataset.name;
        var adm  = cb.dataset.adm;
        selectedStudents[id] = { name: name, adm: adm };
        cb.checked = true;
      });
      renderTags();
      renderHidden();
    }

    function removeStudent(id) {
      delete selectedStudents[id];
      var cb = document.getElementById('sch_' + id);
      if (cb) cb.checked = false;
      renderTags();
      renderHidden();
    }

    function renderTags() {
      var container = document.getElementById('selectedTags');
      var noMsg     = document.getElementById('noSelMsg');
      var ids       = Object.keys(selectedStudents);

      if (ids.length === 0) {
        container.innerHTML = '';
        container.appendChild(noMsg || document.createTextNode('No students selected.'));
        return;
      }

      container.innerHTML = '';
      ids.forEach(function (id) {
        var s    = selectedStudents[id];
        var tag  = document.createElement('div');
        tag.className = 'sel-tag';
        tag.innerHTML =
          '<span>' + s.name + '</span>' +
          '<span class="sel-tag__remove" onclick="removeStudent(' + id + ')">✕</span>';
        container.appendChild(tag);
      });
    }

    function renderHidden() {
      var container = document.getElementById('hiddenInputs');
      container.innerHTML = '';
      Object.keys(selectedStudents).forEach(function (id) {
        var inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'student_ids[]';
        inp.value = id;
        container.appendChild(inp);
      });
    }

    function validateNoticeForm() {
      var type  = document.querySelector('input[name="type"]:checked');
      var title = document.getElementById('noticeTitle').value.trim();
      var body  = document.getElementById('noticeBody').value.trim();
      var ids   = Object.keys(selectedStudents);

      if (!type) { alert('Please select a notice type.'); return false; }
      if (!title) { alert('Please enter a title for the notice.'); return false; }
      if (!body)  { alert('Please enter the notice message.'); return false; }
      if (ids.length === 0) { alert('Please select at least one student.'); return false; }

      return confirm(
        'Send ' + ids.length + ' notice(s)?\n\nType: ' + type.value +
        '\nTitle: ' + title +
        '\nRecipients: ' + ids.length + ' student(s)'
      );
    }

    /* Re-check already-selected students after search page reload */
    document.querySelectorAll('.student-cb').forEach(function (cb) {
      if (selectedStudents[cb.dataset.id]) cb.checked = true;
    });
  </script>

</body>
</html>