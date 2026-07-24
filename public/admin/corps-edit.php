<?php
/* ============================================================
   IBEKU HIGH SCHOOL - ADMIN EDIT CORPS MEMBER
   File: public/admin/corps-edit.php
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'dean', 'section_admin']);

$admin           = currentAdmin();
$pdo             = getDB();

/* ── Subjects for the dropdown — avoids free-text mismatches ── */
$allSubjects = $pdo->query('SELECT name FROM subjects WHERE is_active = 1 ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);
$isSectionAdmin  = $admin['role'] === 'section_admin';
$adminOwnSection = $admin['section'];

/* Self-healing column add — same pattern used throughout the app */
try {
    $pdo->exec("ALTER TABLE corps_members ADD COLUMN call_up_number VARCHAR(30) NULL AFTER state_code");
} catch (PDOException $e) {
    /* Column already exists — fine */
}

/* Self-healing: extend teacher_class_assignments to also support
   corps members, not just staff. teacher_id had a NOT NULL FK to
   users(id), so a corps member's id can't safely go there — a
   dedicated nullable corps_member_id column (own FK to
   corps_members) avoids any risk of misattribution. A row has
   EITHER teacher_id OR corps_member_id set, never both. */
try {
    $pdo->exec("ALTER TABLE teacher_class_assignments MODIFY COLUMN teacher_id INT UNSIGNED NULL");
} catch (PDOException $e) { /* already nullable */ }
try {
    $pdo->exec("ALTER TABLE teacher_class_assignments ADD COLUMN corps_member_id INT UNSIGNED NULL AFTER teacher_id");
} catch (PDOException $e) { /* already exists */ }
try {
    $pdo->exec(
        "ALTER TABLE teacher_class_assignments
         ADD CONSTRAINT fk_tca_corps FOREIGN KEY (corps_member_id) REFERENCES corps_members(id) ON DELETE CASCADE ON UPDATE CASCADE"
    );
} catch (PDOException $e) { /* already exists */ }

/* ── Load class arms for the assignment checkbox grid ── */
$gradeLevelLabels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3','SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];
$classArmRows = $pdo->query("SELECT grade_level, class FROM class_arms WHERE is_active = 1 ORDER BY grade_level ASC, class ASC")->fetchAll();
$classesByGradeLevel = [];
foreach ($classArmRows as $row) {
    $classesByGradeLevel[$row['grade_level']][] = $row['class'];
}

$existingAssignments = [];
$assignStmt = $pdo->prepare('SELECT grade_level, class FROM teacher_class_assignments WHERE corps_member_id = ? ORDER BY grade_level ASC, class ASC');
$assignStmt->execute([$id]);
foreach ($assignStmt->fetchAll() as $row) {
    $existingAssignments[] = $row['grade_level'] . '|' . $row['class'];
}

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: corps.php'); exit; }

$mStmt = $pdo->prepare('SELECT * FROM corps_members WHERE id = ? LIMIT 1');
$mStmt->execute([$id]);
$rows   = $mStmt->fetchAll(PDO::FETCH_ASSOC);
$member = $rows[0] ?? null;
if (!$member) { header('Location: corps.php'); exit; }

/* Section admins can only edit corps members in their exact section
   — 'both' stays out of scope for them, matching corps.php's rule */
if ($isSectionAdmin && $member['section'] !== $adminOwnSection) {
    $_SESSION['admin_error'] = 'You do not have permission to edit that corps member.';
    header('Location: corps.php'); exit;
}

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stateCode     = strtoupper(trim($_POST['state_code']     ?? ''));
    $callUpNumber  = strtoupper(trim($_POST['call_up_number']  ?? ''));
    $fullName      = trim($_POST['full_name']      ?? '');
    $stateOrigin   = trim($_POST['state_of_origin'] ?? '');
    $batch         = trim($_POST['batch']          ?? '');
    $institution   = trim($_POST['institution']    ?? '');
    $course        = trim($_POST['course_studied']  ?? '');
    $cdsGroup      = trim($_POST['cds_group']      ?? '');
    $cdsDay        = trim($_POST['cds_day']        ?? '');
    $subject       = trim($_POST['subject_taught']  ?? '');
    $section       = $_POST['section']             ?? 'both';
    if ($isSectionAdmin) $section = $adminOwnSection; /* strict — never 'both' for them */
    $classArms     = trim($_POST['class_arms']     ?? '');
    $phone         = trim($_POST['phone']          ?? '');
    $bankName      = trim($_POST['bank_name']      ?? '');
    $accountName   = trim($_POST['account_name']   ?? '');
    $accountNumber = trim($_POST['account_number']  ?? '');
    $status        = $_POST['status']              ?? 'active';

    if (!$stateCode || !$fullName) {
        $message = 'State code and full name are required.'; $messageType = 'error';
    } else {
        /* Check duplicate state code (excluding self) */
        $dup = $pdo->prepare('SELECT id FROM corps_members WHERE state_code = ? AND id != ? LIMIT 1');
        $dup->execute([$stateCode, $id]);
        if ($dup->fetchColumn()) {
            $message = 'That state code is already used by another corps member.'; $messageType = 'error';
        }
    }

    /* Photo upload */
    $photoFilename = $member['photo'];
    if (!$message && !empty($_FILES['photo']['name'])) {
        $uploadDir = dirname(__DIR__) . '/assets/images/corps/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed, true)) {
            $message = 'Photo must be JPG, PNG or WEBP.'; $messageType = 'error';
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $message = 'Photo must be under 2MB.'; $messageType = 'error';
        } else {
            $newFilename = uniqid('corps_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newFilename)) {
                /* Delete old photo */
                if ($member['photo'] && file_exists($uploadDir . $member['photo'])) {
                    unlink($uploadDir . $member['photo']);
                }
                $photoFilename = $newFilename;
            }
        }
    }

    if (!$message) {
        try {
            $pdo->prepare(
                'UPDATE corps_members SET
                    state_code=?,call_up_number=?,full_name=?,photo=?,state_of_origin=?,batch=?,
                    institution=?,course_studied=?,cds_group=?,cds_day=?,
                    subject_taught=?,section=?,class_arms=?,phone=?,
                    bank_name=?,account_name=?,account_number=?,status=?,
                    status_changed_by=?,status_changed_at=NOW()
                 WHERE id=?'
            )->execute([
                $stateCode,$callUpNumber ?: null,$fullName,$photoFilename,$stateOrigin,$batch,
                $institution,$course,$cdsGroup,$cdsDay,
                $subject,$section,$classArms,$phone,
                $bankName,$accountName,$accountNumber,$status,
                $admin['id'],$id
            ]);
            /* ── Sync class assignments ── */
            $pdo->prepare('DELETE FROM teacher_class_assignments WHERE corps_member_id = ?')->execute([$id]);
            $submittedAssignments = $_POST['class_assignments'] ?? [];
            if (!empty($submittedAssignments)) {
                $validGradeLevels = ['JSS1','JSS2','JSS3','SSS1','SSS2','SSS3'];
                $insertAssign = $pdo->prepare(
                    'INSERT IGNORE INTO teacher_class_assignments (corps_member_id, grade_level, class) VALUES (?, ?, ?)'
                );
                foreach ($submittedAssignments as $pair) {
                    $parts = explode('|', (string) $pair);
                    if (count($parts) === 2 && in_array($parts[0], $validGradeLevels, true) && $parts[1] !== '') {
                        $insertAssign->execute([$id, $parts[0], $parts[1]]);
                    }
                }
            }

            /* Reload updated member */
            $mStmt->execute([$id]);
            $rows   = $mStmt->fetchAll(PDO::FETCH_ASSOC);
            $member = $rows[0] ?? $member;

            /* Reload assignments for display */
            $existingAssignments = [];
            $assignStmt->execute([$id]);
            foreach ($assignStmt->fetchAll() as $row) {
                $existingAssignments[] = $row['grade_level'] . '|' . $row['class'];
            }

            $message = 'Changes saved.'; $messageType = 'success';
        } catch (PDOException $e) {
            error_log('IHS corps-edit: ' . $e->getMessage());
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}

$sections = ['js'=>'Junior Secondary (JS)','ss'=>'Senior Secondary (SS)','both'=>'Both Sections'];
$days     = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$statuses = ['active'=>'Active','retired'=>'Retired','passed_out'=>'Passed Out'];
$photoSrc = !empty($member['photo']) ? '../assets/images/corps/' . htmlspecialchars($member['photo']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Corps Member - Admin - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/admin-layout.css"/>
  <style>
    .form-group{margin-bottom:16px}.form-label{display:block;font-size:12px;font-weight:600;color:#3d1a6e;margin-bottom:5px;text-transform:uppercase;letter-spacing:.03em}
    .form-input,.form-select,.form-textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e0ea;border-radius:8px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#1a1a2e}
    .form-input:focus,.form-select:focus,.form-textarea:focus{outline:none;border-color:#4a90d9}
    .form-row{display:flex;gap:14px}.form-row .form-group{flex:1}
    .form-section{border-top:1px solid #f0eef6;margin:20px 0 16px;padding-top:16px}
    .form-section__label{font-size:13px;font-weight:700;color:#3d1a6e;margin-bottom:14px}
    .btn-save{background:#3d1a6e;color:#fff;border:none;padding:11px 28px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer}
    .btn-save:hover{background:#5a2d9e}
    .btn-cancel{background:#f0ecfa;color:#3d1a6e;border:1.5px solid #d8d0ee;padding:11px 22px;border-radius:8px;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-block}
    .current-photo{width:80px;height:80px;border-radius:10px;object-fit:cover;border:2px solid #e2e0ea;display:block;margin-bottom:8px}
    .current-avatar{width:80px;height:80px;border-radius:10px;background:linear-gradient(135deg,#3d1a6e,#4a90d9);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#fff;margin-bottom:8px}
    .hint{font-size:11.5px;color:#9b97b0;margin-top:3px}
  </style>
</head>
<body>
<?php renderAdminSidebar($admin, 'corps'); ?>
<div class="admin-content">
  <div class="admin-content__inner">
    <div class="page-header">
      <h2>Edit Corps Member</h2>
      <p><a href="corps.php" style="color:#4a90d9;text-decoration:none">Back to Corps Members</a></p>
    </div>
    <?php if ($message): ?>
    <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="admin-card" style="max-width:680px">
      <form method="POST" enctype="multipart/form-data">

        <div class="form-section">
          <div class="form-section__label">Identity</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">State Code *</label>
              <input type="text" class="form-input" name="state_code" required maxlength="20"
                     value="<?php echo htmlspecialchars($member['state_code']); ?>"
                     oninput="this.value=this.value.toUpperCase()"/>
            </div>
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" class="form-input" name="full_name" required maxlength="200"
                     value="<?php echo htmlspecialchars($member['full_name']); ?>"/>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Call Up Number</label>
              <input type="text" class="form-input" name="call_up_number" maxlength="30"
                     value="<?php echo htmlspecialchars($member['call_up_number'] ?? ''); ?>"
                     placeholder="e.g. NYSC/2025/1234567"
                     oninput="this.value=this.value.toUpperCase()"/>
              <p class="hint">Printed on the monthly clearance letter.</p>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">State of Origin *</label>
              <input type="text" class="form-input" name="state_of_origin" required maxlength="100"
                     value="<?php echo htmlspecialchars($member['state_of_origin']); ?>"/>
            </div>
            <div class="form-group">
              <label class="form-label">Batch *</label>
              <input type="text" class="form-input" name="batch" required maxlength="20"
                     value="<?php echo htmlspecialchars($member['batch']); ?>"/>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <?php foreach ($statuses as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo $member['status'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Profile Photo</label>
            <?php if ($photoSrc): ?>
            <img src="<?php echo $photoSrc; ?>" class="current-photo" alt="Current photo"
                 onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
            <div class="current-avatar" style="display:none"><?php echo strtoupper(substr($member['full_name'],0,1)); ?></div>
            <?php else: ?>
            <div class="current-avatar"><?php echo strtoupper(substr($member['full_name'],0,1)); ?></div>
            <?php endif; ?>
            <input type="file" class="form-input" name="photo" accept="image/jpeg,image/png,image/webp"/>
            <p class="hint">Upload a new photo to replace the current one. JPG, PNG or WEBP - max 2MB.</p>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section__label">Academic Background</div>
          <div class="form-group">
            <label class="form-label">Institution Attended *</label>
            <input type="text" class="form-input" name="institution" required maxlength="255"
                   value="<?php echo htmlspecialchars($member['institution']); ?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">Course Studied *</label>
            <input type="text" class="form-input" name="course_studied" required maxlength="255"
                   value="<?php echo htmlspecialchars($member['course_studied']); ?>"/>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section__label">Posting Details</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Subject Taught</label>
              <select class="form-select" name="subject_taught">
                <option value="">Select subject</option>
                <?php foreach ($allSubjects as $subj): ?>
                <option value="<?php echo htmlspecialchars($subj); ?>" <?php echo ($member['subject_taught'] ?? '') === $subj ? 'selected' : ''; ?>><?php echo htmlspecialchars($subj); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="char-hint">Must match an active subject exactly, or results entry won't work for this corps member.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Section</label>
              <?php if ($isSectionAdmin): ?>
              <select class="form-select" disabled>
                <option selected><?php echo $adminOwnSection === 'ss' ? 'Senior Secondary' : 'Junior Secondary'; ?></option>
              </select>
              <input type="hidden" name="section" value="<?php echo htmlspecialchars($adminOwnSection); ?>"/>
              <p class="char-hint">Locked to your own section.</p>
              <?php else: ?>
              <select class="form-select" name="section">
                <?php foreach ($sections as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo $member['section'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
            </div>
          </div>

          <div style="margin-top:16px">
            <div class="char-hint" style="font-weight:700;color:#3d1a6e;margin-bottom:6px">Class Assignments</div>
            <p class="char-hint" style="margin-bottom:8px">
              Tick the specific classes this corps member is allowed to enter results for.
              Leave all unticked to allow access to <em>all</em> classes in their section (open access).
            </p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:6px 12px;background:#f8f7fc;border-radius:10px;padding:14px">
              <?php
              $corpsCurrentSection = $member['section'] ?? 'both';
              foreach ($classesByGradeLevel as $gl => $classes):
                  $glSection = str_starts_with($gl, 'JSS') ? 'js' : 'ss';
                  if ($corpsCurrentSection !== 'both' && $glSection !== $corpsCurrentSection) continue;
              ?>
              <div style="grid-column:1/-1;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;margin-top:6px"><?php echo $gradeLevelLabels[$gl] ?? $gl; ?></div>
              <?php foreach ($classes as $cls):
                  $pair    = $gl . '|' . $cls;
                  $checked = in_array($pair, $existingAssignments, true) ? 'checked' : '';
              ?>
              <label style="display:flex;align-items:center;gap:5px;font-size:12.5px">
                <input type="checkbox" name="class_assignments[]" value="<?php echo htmlspecialchars($pair); ?>" <?php echo $checked; ?>/>
                <?php echo ($gradeLevelLabels[$gl] ?? $gl) . ' ' . htmlspecialchars($cls); ?>
              </label>
              <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
            <p class="char-hint" style="margin-top:6px">
              <?php if (!empty($existingAssignments)): ?>
              Currently assigned to <strong><?php echo count($existingAssignments); ?></strong> class(es).
              <?php else: ?>
              No specific assignments — open access to all classes in their section.
              <?php endif; ?>
            </p>
          </div>

          <div class="form-row" style="margin-top:16px">
            <div class="form-group">
              <label class="form-label">Class Arms</label>
              <input type="text" class="form-input" name="class_arms" maxlength="255"
                     value="<?php echo htmlspecialchars($member['class_arms'] ?? ''); ?>"/>
            </div>
            <div class="form-group">
              <label class="form-label">CDS Day</label>
              <select class="form-select" name="cds_day">
                <option value="">Select day</option>
                <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
                <option value="<?php echo $d; ?>" <?php echo ($member['cds_day'] ?? '') === $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">CDS Group</label>
            <input type="text" class="form-input" name="cds_group" maxlength="100"
                   value="<?php echo htmlspecialchars($member['cds_group'] ?? ''); ?>"/>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section__label">Contact & Bank Details <span style="font-weight:400;font-size:12px;color:#9b97b0">(admin only)</span></div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" class="form-input" name="phone" maxlength="20"
                   value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>"/>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Bank Name</label>
              <input type="text" class="form-input" name="bank_name" maxlength="100"
                     value="<?php echo htmlspecialchars($member['bank_name'] ?? ''); ?>"/>
            </div>
            <div class="form-group">
              <label class="form-label">Account Number</label>
              <input type="text" class="form-input" name="account_number" maxlength="20"
                     value="<?php echo htmlspecialchars($member['account_number'] ?? ''); ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Account Name</label>
            <input type="text" class="form-input" name="account_name" maxlength="200"
                   value="<?php echo htmlspecialchars($member['account_name'] ?? ''); ?>"/>
          </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px">
          <button type="submit" class="btn-save">Save Changes</button>
          <a href="corps.php" class="btn-cancel">Cancel</a>
        </div>

      </form>
    </div>
  </div>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>