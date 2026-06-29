<?php
/* ============================================================
   IBEKU HIGH SCHOOL — CREATE STUDENT
   File: public/admin/students-create.php

   Accessible to: superadmin, vp_admin
   Admission number auto-generated in format IHS/YEAR/XXXX.
   Photo upload optional — saved to public/assets/images/students/
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

/* ── Auto-generate next admission number ── */
function generateAdmissionNumber(PDO $pdo): string {
    $year = date('Y');
    $prefix = 'IHS/' . $year . '/';
    $stmt = $pdo->prepare(
        "SELECT admission_number FROM students
         WHERE admission_number LIKE ? ORDER BY admission_number DESC LIMIT 1"
    );
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();

    if ($last) {
        $parts  = explode('/', $last);
        $lastNo = (int) end($parts);
        $next   = $lastNo + 1;
    } else {
        $next = 1;
    }

    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

$nextAdmissionNumber = generateAdmissionNumber($pdo);

$message     = '';
$messageType = '';
$formData    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName    = trim($_POST['first_name']   ?? '');
    $lastName     = trim($_POST['last_name']    ?? '');
    $otherName    = trim($_POST['other_name']   ?? '');
    $gender       = trim($_POST['gender']       ?? '');
    $dob          = trim($_POST['date_of_birth'] ?? '');
    $gradeLevel   = trim($_POST['grade_level']  ?? '');
    $class        = trim($_POST['class']         ?? '');
    $section      = str_starts_with($gradeLevel, 'JSS') ? 'js' : 'ss';
    $department   = trim($_POST['department']   ?? 'general');
    $dateAdmitted = trim($_POST['date_admitted'] ?? date('Y-m-d'));
    $parentName   = trim($_POST['parent_name']  ?? '');
    $parentPhone  = trim($_POST['parent_phone'] ?? '');
    $parentEmail  = trim($_POST['parent_email'] ?? '');
    $address      = trim($_POST['address']      ?? '');

    $formData = compact('firstName','lastName','otherName','gender','dob',
                        'gradeLevel','class','department','dateAdmitted',
                        'parentName','parentPhone','parentEmail','address');

    $validGradeLevels = array_keys($allGradeLevels);
    $validGenders     = ['male','female'];
    $validDepts       = ['sciences','arts','commercial','general'];

    if ($firstName === '') {
        $message = 'First name is required.'; $messageType = 'error';
    } elseif ($lastName === '') {
        $message = 'Last name is required.'; $messageType = 'error';
    } elseif (!in_array($gender, $validGenders, true)) {
        $message = 'Please select a gender.'; $messageType = 'error';
    } elseif ($dob === '') {
        $message = 'Date of birth is required.'; $messageType = 'error';
    } elseif (!in_array($gradeLevel, $validGradeLevels, true)) {
        $message = 'Please select a grade level.'; $messageType = 'error';
    } elseif ($class === '') {
        $message = 'Please select a class.'; $messageType = 'error';
    } else {
        /* Handle photo upload */
        $photoFilename = null;
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = dirname(__DIR__) . '/assets/images/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext      = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp'];
            $maxSize  = 2 * 1024 * 1024; // 2MB

            if (!in_array($ext, $allowed, true)) {
                $message = 'Photo must be JPG, PNG, or WEBP.'; $messageType = 'error';
            } elseif ($_FILES['photo']['size'] > $maxSize) {
                $message = 'Photo must be under 2MB.'; $messageType = 'error';
            } else {
                $photoFilename = uniqid('student_', true) . '.' . $ext;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoFilename)) {
                    $message = 'Photo upload failed. Please try again.'; $messageType = 'error';
                    $photoFilename = null;
                }
            }
        }

        if ($message === '') {
            try {
                /* Re-generate in case of concurrent inserts */
                $admNo = generateAdmissionNumber($pdo);

                $stmt = $pdo->prepare(
                    'INSERT INTO students
                        (admission_number, first_name, last_name, other_name, gender,
                         date_of_birth, section, grade_level, class, department,
                         date_admitted, is_active, status, parent_name, parent_phone,
                         parent_email, address, photo)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,1,\'active\',?,?,?,?,?)'
                );
                $stmt->execute([
                    $admNo, $firstName, $lastName, $otherName ?: null, $gender,
                    $dob, $section, $gradeLevel, $class, $department,
                    $dateAdmitted, $parentName ?: null, $parentPhone ?: null,
                    $parentEmail ?: null, $address ?: null, $photoFilename,
                ]);

                $newId = (int) $pdo->lastInsertId();

                /* Record in student_history */
                $pdo->prepare(
                    'INSERT INTO student_history (student_id, event_type, to_grade_level, to_class, reason, recorded_by)
                     VALUES (?, \'promotion\', ?, ?, \'Initial admission\', ?)'
                )->execute([$newId, $gradeLevel, $class, $admin['id']]);

                $message = 'Student ' . htmlspecialchars($firstName . ' ' . $lastName) .
                           ' admitted successfully. Admission number: ' . $admNo;
                $messageType = 'success';
                $nextAdmissionNumber = generateAdmissionNumber($pdo);
                $formData = [];

            } catch (PDOException $e) {
                error_log('IHS students-create error: ' . $e->getMessage());
                $message = 'A server error occurred. Please try again.'; $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Add Student — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select {
    width:100%; padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px;
    font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e;
  }
  .form-input:focus, .form-select:focus { outline:none; border-color:#4a90d9; }
  .form-row { display:flex; gap:14px; }
  .form-row .form-group { flex:1; }
  .form-section { border-top:1px solid #f0eef6; margin:20px 0 16px; padding-top:16px; }
  .form-section__label { font-size:13px; font-weight:700; color:#3d1a6e; margin-bottom:14px; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }
  .admission-preview {
    background:#f0ecfa; border:1px solid #d8d0ee; border-radius:8px;
    padding:10px 14px; font-size:13px; color:#3d1a6e; font-weight:600; margin-bottom:16px;
  }
  .photo-preview { width:80px; height:80px; border-radius:50%; object-fit:cover; border:2px solid #e2e0ea; margin-top:8px; display:none; }
  .btn-group { display:flex; gap:12px; margin-top:20px; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; display:inline-block; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'students'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Add New Student</h2>
        <p><a href="students.php" style="color:#4a90d9;text-decoration:none">← Back to Students</a></p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo $message; ?></div>
      <?php endif; ?>

      <div class="admin-card" style="max-width:660px">

        <div class="admission-preview">
          Next Admission Number: <span id="admPreview"><?php echo htmlspecialchars($nextAdmissionNumber); ?></span>
          <span style="font-size:11px;color:#9b97b0;font-weight:400;margin-left:8px">(auto-assigned on save)</span>
        </div>

        <form method="POST" enctype="multipart/form-data" id="createForm">

          <div class="form-section">
            <div class="form-section__label">Personal Information</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="first_name">First Name *</label>
                <input type="text" class="form-input" id="first_name" name="first_name" required maxlength="100"
                       value="<?php echo htmlspecialchars($formData['firstName'] ?? ''); ?>"/>
              </div>
              <div class="form-group">
                <label class="form-label" for="last_name">Last Name *</label>
                <input type="text" class="form-input" id="last_name" name="last_name" required maxlength="100"
                       value="<?php echo htmlspecialchars($formData['lastName'] ?? ''); ?>"/>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="other_name">Other Name(s)</label>
              <input type="text" class="form-input" id="other_name" name="other_name" maxlength="100"
                     value="<?php echo htmlspecialchars($formData['otherName'] ?? ''); ?>"/>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="gender">Gender *</label>
                <select class="form-select" id="gender" name="gender" required>
                  <option value="">Select gender</option>
                  <option value="male"   <?php echo ($formData['gender'] ?? '') === 'male'   ? 'selected' : ''; ?>>Male</option>
                  <option value="female" <?php echo ($formData['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="date_of_birth">Date of Birth *</label>
                <input type="date" class="form-input" id="date_of_birth" name="date_of_birth" required
                       value="<?php echo htmlspecialchars($formData['dob'] ?? ''); ?>"/>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="photo">Photo</label>
              <input type="file" class="form-input" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"
                     onchange="previewPhoto(this)"/>
              <p class="char-hint">JPG, PNG or WEBP — max 2MB. Leave blank to skip.</p>
              <img id="photoPreview" class="photo-preview" src="" alt="Photo preview"/>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section__label">Academic Information</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="grade_level">Grade Level *</label>
                <select class="form-select" id="grade_level" name="grade_level" required>
                  <option value="">Select grade level</option>
                  <?php foreach ($allGradeLevels as $k => $v): ?>
                  <option value="<?php echo $k; ?>" <?php echo ($formData['gradeLevel'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="class">Class *</label>
                <select class="form-select" id="class" name="class" required>
                  <option value="">Select class</option>
                  <?php if (!empty($formData['gradeLevel']) && !empty($classesByGradeLevel[$formData['gradeLevel']])): ?>
                    <?php foreach ($classesByGradeLevel[$formData['gradeLevel']] as $cls): ?>
                    <option value="<?php echo htmlspecialchars($cls); ?>" <?php echo ($formData['class'] ?? '') === $cls ? 'selected' : ''; ?>><?php echo htmlspecialchars($cls); ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="department">Department</label>
                <select class="form-select" id="department" name="department">
                  <option value="general"    <?php echo ($formData['department'] ?? 'general') === 'general'    ? 'selected' : ''; ?>>General</option>
                  <option value="sciences"   <?php echo ($formData['department'] ?? '') === 'sciences'   ? 'selected' : ''; ?>>Sciences</option>
                  <option value="arts"       <?php echo ($formData['department'] ?? '') === 'arts'       ? 'selected' : ''; ?>>Arts</option>
                  <option value="commercial" <?php echo ($formData['department'] ?? '') === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                </select>
                <p class="char-hint">Department applies mainly to SSS students.</p>
              </div>
              <div class="form-group">
                <label class="form-label" for="date_admitted">Date Admitted *</label>
                <input type="date" class="form-input" id="date_admitted" name="date_admitted" required
                       value="<?php echo htmlspecialchars($formData['dateAdmitted'] ?? date('Y-m-d')); ?>"/>
              </div>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section__label">Parent / Guardian Information</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="parent_name">Parent / Guardian Name</label>
                <input type="text" class="form-input" id="parent_name" name="parent_name" maxlength="150"
                       value="<?php echo htmlspecialchars($formData['parentName'] ?? ''); ?>"/>
              </div>
              <div class="form-group">
                <label class="form-label" for="parent_phone">Phone Number</label>
                <input type="tel" class="form-input" id="parent_phone" name="parent_phone" maxlength="20"
                       value="<?php echo htmlspecialchars($formData['parentPhone'] ?? ''); ?>"/>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="parent_email">Email Address</label>
              <input type="email" class="form-input" id="parent_email" name="parent_email" maxlength="150"
                     value="<?php echo htmlspecialchars($formData['parentEmail'] ?? ''); ?>"/>
            </div>

            <div class="form-group">
              <label class="form-label" for="address">Home Address</label>
              <textarea class="form-input" id="address" name="address" rows="2" maxlength="500"
                        style="resize:vertical"><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save">Admit Student</button>
            <a href="students.php" class="btn-cancel">Cancel</a>
          </div>

        </form>
      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    var classesByGradeLevel = <?php echo json_encode($classesByGradeLevel); ?>;
    var gradeLevelSelect = document.getElementById('grade_level');
    var classSelect       = document.getElementById('class');

    gradeLevelSelect.addEventListener('change', function () {
      var gl = gradeLevelSelect.value;
      classSelect.innerHTML = '<option value="">Select class</option>';
      if (gl && classesByGradeLevel[gl]) {
        classesByGradeLevel[gl].forEach(function (cls) {
          var opt = document.createElement('option');
          opt.value = cls; opt.textContent = cls;
          classSelect.appendChild(opt);
        });
      }
    });

    function previewPhoto(input) {
      var preview = document.getElementById('photoPreview');
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
      }
    }
  </script>

</body>
</html>