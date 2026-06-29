<?php
/* ============================================================
   IBEKU HIGH SCHOOL — EDIT STUDENT
   File: public/admin/students-edit.php

   Accessible to: superadmin, vp_admin
   Edits all fields EXCEPT grade_level and class — those are
   changed only through the promotion system.
   Photo can be replaced or removed.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'vp_admin']);

$admin = currentAdmin();
$pdo   = getDB();

$studentId = (int) ($_GET['id'] ?? 0);
if ($studentId <= 0) { header('Location: students.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) { header('Location: students.php'); exit; }

/* ── Load student history ── */
$historyStmt = $pdo->prepare(
    "SELECT sh.*, CONCAT(u.full_name) AS recorded_by_name
     FROM   student_history sh
     JOIN   users u ON u.id = sh.recorded_by
     WHERE  sh.student_id = ?
     ORDER  BY sh.recorded_at DESC"
);
$historyStmt->execute([$studentId]);
$history = $historyStmt->fetchAll();

$allGradeLevels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3',
                    'SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];

$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName   = trim($_POST['first_name']    ?? '');
    $lastName    = trim($_POST['last_name']     ?? '');
    $otherName   = trim($_POST['other_name']    ?? '');
    $gender      = trim($_POST['gender']        ?? '');
    $dob         = trim($_POST['date_of_birth'] ?? '');
    $department  = trim($_POST['department']    ?? 'general');
    $dateAdmit   = trim($_POST['date_admitted'] ?? '');
    $parentName  = trim($_POST['parent_name']   ?? '');
    $parentPhone = trim($_POST['parent_phone']  ?? '');
    $parentEmail = trim($_POST['parent_email']  ?? '');
    $address     = trim($_POST['address']       ?? '');
    $removePhoto = isset($_POST['remove_photo']);

    if ($firstName === '' || $lastName === '' || $gender === '' || $dob === '') {
        $message = 'First name, last name, gender, and date of birth are required.';
        $messageType = 'error';
    } else {
        $photoFilename = $student['photo'];

        /* Remove photo */
        if ($removePhoto && $photoFilename) {
            $photoPath = dirname(__DIR__) . '/assets/images/students/' . $photoFilename;
            if (file_exists($photoPath)) unlink($photoPath);
            $photoFilename = null;
        }

        /* Replace photo */
        if (!empty($_FILES['photo']['name']) && !$removePhoto) {
            $uploadDir = dirname(__DIR__) . '/assets/images/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed, true)) {
                $message = 'Photo must be JPG, PNG, or WEBP.'; $messageType = 'error';
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $message = 'Photo must be under 2MB.'; $messageType = 'error';
            } else {
                /* Delete old photo */
                if ($photoFilename && file_exists($uploadDir . $photoFilename)) {
                    unlink($uploadDir . $photoFilename);
                }
                $photoFilename = uniqid('student_', true) . '.' . $ext;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoFilename)) {
                    $message = 'Photo upload failed.'; $messageType = 'error';
                    $photoFilename = $student['photo'];
                }
            }
        }

        if ($message === '') {
            try {
                $pdo->prepare(
                    'UPDATE students SET
                        first_name=?, last_name=?, other_name=?, gender=?, date_of_birth=?,
                        department=?, date_admitted=?, parent_name=?, parent_phone=?,
                        parent_email=?, address=?, photo=?, updated_at=NOW()
                     WHERE id=?'
                )->execute([
                    $firstName, $lastName, $otherName ?: null, $gender, $dob,
                    $department, $dateAdmit, $parentName ?: null, $parentPhone ?: null,
                    $parentEmail ?: null, $address ?: null, $photoFilename, $studentId,
                ]);

                $message = 'Student record updated successfully.'; $messageType = 'success';

                /* Reload */
                $stmt->execute([$studentId]);
                $student = $stmt->fetch();

            } catch (PDOException $e) {
                error_log('IHS students-edit error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

$eventLabels = [
    'promotion'      => ['label'=>'Promoted',      'color'=>'#1a7a3a'],
    'retention'      => ['label'=>'Retained',       'color'=>'#8a6a00'],
    'demotion'       => ['label'=>'Demoted',        'color'=>'#cc3333'],
    'expulsion'      => ['label'=>'Expelled',       'color'=>'#cc0000'],
    'graduation'     => ['label'=>'Graduated',      'color'=>'#1a5a9a'],
    'reinstatement'  => ['label'=>'Reinstated',     'color'=>'#3d1a6e'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Edit Student — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select { width:100%; padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-select:focus { outline:none; border-color:#4a90d9; }
  .form-row { display:flex; gap:14px; }
  .form-row .form-group { flex:1; }
  .form-section { border-top:1px solid #f0eef6; margin:20px 0 16px; padding-top:16px; }
  .form-section__label { font-size:13px; font-weight:700; color:#3d1a6e; margin-bottom:14px; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }
  .locked-field {
    background:#f4f3f9; padding:9px 12px; border-radius:8px; border:1.5px solid #e8e6f0;
    font-size:13.5px; color:#6b6b80;
  }
  .locked-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }
  .photo-current { width:70px; height:70px; border-radius:50%; object-fit:cover; border:2px solid #e2e0ea; margin-bottom:10px; }
  .photo-initials { width:70px; height:70px; border-radius:50%; background:#3d1a6e; color:#fff; font-size:22px; font-weight:700; display:flex; align-items:center; justify-content:center; margin-bottom:10px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; margin-top:8px; }
  .checkbox-row input { width:15px; height:15px; }
  .btn-group { display:flex; gap:12px; margin-top:20px; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; display:inline-block; }

  /* History timeline */
  .history-section { margin-top:28px; }
  .history-section h3 { font-size:14px; font-weight:700; color:#3d1a6e; margin-bottom:14px; }
  .history-timeline { position:relative; padding-left:24px; }
  .history-timeline::before { content:''; position:absolute; left:7px; top:0; bottom:0; width:2px; background:#e8e6f0; }
  .history-event { position:relative; margin-bottom:16px; }
  .history-event__dot { position:absolute; left:-21px; top:3px; width:12px; height:12px; border-radius:50%; border:2px solid #fff; }
  .history-event__card { background:#fff; border:1px solid #e8e6f0; border-radius:10px; padding:10px 14px; }
  .history-event__type { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
  .history-event__detail { font-size:12.5px; color:#6b6b80; margin-top:3px; }
  .history-event__meta { font-size:11.5px; color:#9b97b0; margin-top:4px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'students'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Edit Student</h2>
        <p><a href="students.php" style="color:#4a90d9;text-decoration:none">← Back to Students</a></p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start">

        <!-- Edit form -->
        <div class="admin-card" style="flex:1;min-width:320px;max-width:600px">
          <form method="POST" enctype="multipart/form-data">

            <div class="form-section">
              <div class="form-section__label">Personal Information</div>

              <div style="margin-bottom:16px">
                <?php
                $initials = strtoupper(substr($student['first_name'],0,1) . substr($student['last_name'],0,1));
                $photoPath = $student['photo'] ? dirname(__DIR__) . '/assets/images/students/' . $student['photo'] : null;
                ?>
                <?php if ($photoPath && file_exists($photoPath)): ?>
                <img src="../assets/images/students/<?php echo htmlspecialchars($student['photo']); ?>"
                     class="photo-current" alt="Current photo"/>
                <?php else: ?>
                <div class="photo-initials"><?php echo htmlspecialchars($initials); ?></div>
                <?php endif; ?>
                <label class="form-label">Replace Photo</label>
                <input type="file" class="form-input" name="photo" accept="image/jpeg,image/png,image/webp"/>
                <p class="char-hint">Leave blank to keep current photo.</p>
                <?php if ($student['photo']): ?>
                <div class="checkbox-row">
                  <input type="checkbox" name="remove_photo" id="remove_photo"/>
                  <label for="remove_photo" style="font-size:13px">Remove current photo</label>
                </div>
                <?php endif; ?>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">First Name *</label>
                  <input type="text" class="form-input" name="first_name" required maxlength="100"
                         value="<?php echo htmlspecialchars($student['first_name']); ?>"/>
                </div>
                <div class="form-group">
                  <label class="form-label">Last Name *</label>
                  <input type="text" class="form-input" name="last_name" required maxlength="100"
                         value="<?php echo htmlspecialchars($student['last_name']); ?>"/>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Other Name(s)</label>
                <input type="text" class="form-input" name="other_name" maxlength="100"
                       value="<?php echo htmlspecialchars($student['other_name'] ?? ''); ?>"/>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Gender *</label>
                  <select class="form-select" name="gender" required>
                    <option value="male"   <?php echo $student['gender']==='male'   ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo $student['gender']==='female' ? 'selected' : ''; ?>>Female</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Date of Birth *</label>
                  <input type="date" class="form-input" name="date_of_birth" required
                         value="<?php echo htmlspecialchars($student['date_of_birth']); ?>"/>
                </div>
              </div>
            </div>

            <div class="form-section">
              <div class="form-section__label">Academic Information</div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Grade Level</label>
                  <div class="locked-field"><?php echo htmlspecialchars($allGradeLevels[$student['grade_level']] ?? $student['grade_level']); ?></div>
                  <p class="locked-hint">Use the Promote page to change grade level or class.</p>
                </div>
                <div class="form-group">
                  <label class="form-label">Class</label>
                  <div class="locked-field"><?php echo htmlspecialchars($student['class']); ?></div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Department</label>
                  <select class="form-select" name="department">
                    <option value="general"    <?php echo $student['department']==='general'    ? 'selected' : ''; ?>>General</option>
                    <option value="sciences"   <?php echo $student['department']==='sciences'   ? 'selected' : ''; ?>>Sciences</option>
                    <option value="arts"       <?php echo $student['department']==='arts'       ? 'selected' : ''; ?>>Arts</option>
                    <option value="commercial" <?php echo $student['department']==='commercial' ? 'selected' : ''; ?>>Commercial</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Date Admitted</label>
                  <input type="date" class="form-input" name="date_admitted"
                         value="<?php echo htmlspecialchars($student['date_admitted']); ?>"/>
                </div>
              </div>
            </div>

            <div class="form-section">
              <div class="form-section__label">Parent / Guardian</div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Parent / Guardian Name</label>
                  <input type="text" class="form-input" name="parent_name" maxlength="150"
                         value="<?php echo htmlspecialchars($student['parent_name'] ?? ''); ?>"/>
                </div>
                <div class="form-group">
                  <label class="form-label">Phone Number</label>
                  <input type="tel" class="form-input" name="parent_phone" maxlength="20"
                         value="<?php echo htmlspecialchars($student['parent_phone'] ?? ''); ?>"/>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-input" name="parent_email" maxlength="150"
                       value="<?php echo htmlspecialchars($student['parent_email'] ?? ''); ?>"/>
              </div>

              <div class="form-group">
                <label class="form-label">Home Address</label>
                <textarea class="form-input" name="address" rows="2" maxlength="500"
                          style="resize:vertical"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
              </div>
            </div>

            <div class="btn-group">
              <button type="submit" class="btn-save">Save Changes</button>
              <a href="students.php" class="btn-cancel">Cancel</a>
            </div>

          </form>
        </div>

        <!-- History sidebar -->
        <div style="flex:0 0 300px;min-width:260px">
          <div class="admin-card">
            <div class="form-section__label" style="margin-bottom:14px">
              Student Info
            </div>
            <div style="font-size:12.5px;color:#6b6b80;line-height:2">
              <div><strong style="color:#1a1a2e">Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></div>
              <div><strong style="color:#1a1a2e">Status:</strong> <?php echo ucfirst($student['status']); ?></div>
              <div><strong style="color:#1a1a2e">Section:</strong> <?php echo strtoupper($student['section']); ?></div>
              <div><strong style="color:#1a1a2e">Current Class:</strong> <?php echo htmlspecialchars(($allGradeLevels[$student['grade_level']] ?? $student['grade_level']) . ' ' . $student['class']); ?></div>
            </div>

            <?php if (in_array($admin['role'], ['superadmin', 'form_teacher'], true) && $student['status'] === 'active'): ?>
            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
              <a href="students-promote.php?id=<?php echo $studentId; ?>"
                 style="background:#e6f9ed;color:#1a7a3a;padding:8px 14px;border-radius:7px;font-size:12.5px;font-weight:600;text-decoration:none;text-align:center">
                Promote / Retain
              </a>
              <?php if (in_array($admin['role'], ['superadmin', 'principal'], true)): ?>
              <a href="students-promote.php?id=<?php echo $studentId; ?>&action=expel"
                 style="background:#ffe6e6;color:#cc3333;padding:8px 14px;border-radius:7px;font-size:12.5px;font-weight:600;text-decoration:none;text-align:center">
                Expel Student
              </a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($history)): ?>
          <div class="admin-card" style="margin-top:16px">
            <div class="history-section">
              <h3>Student History</h3>
              <div class="history-timeline">
                <?php foreach ($history as $event):
                  $evInfo = $eventLabels[$event['event_type']] ?? ['label'=>ucfirst($event['event_type']),'color'=>'#6b6b80'];
                ?>
                <div class="history-event">
                  <div class="history-event__dot" style="background:<?php echo $evInfo['color']; ?>"></div>
                  <div class="history-event__card">
                    <div class="history-event__type" style="color:<?php echo $evInfo['color']; ?>"><?php echo $evInfo['label']; ?></div>
                    <?php if ($event['from_grade_level']): ?>
                    <div class="history-event__detail">
                      <?php echo htmlspecialchars(($allGradeLevels[$event['from_grade_level']] ?? $event['from_grade_level']) . ' ' . $event['from_class']); ?>
                      →
                      <?php echo htmlspecialchars(($allGradeLevels[$event['to_grade_level']] ?? $event['to_grade_level']) . ' ' . $event['to_class']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($event['reason']): ?>
                    <div class="history-event__detail"><?php echo htmlspecialchars($event['reason']); ?></div>
                    <?php endif; ?>
                    <div class="history-event__meta">
                      <?php echo date('d M Y', strtotime($event['recorded_at'])); ?>
                      · by <?php echo htmlspecialchars($event['recorded_by_name']); ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>

      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>

</body>
</html>