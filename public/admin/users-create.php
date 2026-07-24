<?php
/* ============================================================
   IBEKU HIGH SCHOOL — CREATE USER
   File: public/admin/users-create.php

   Accessible to: superadmin only
   Department dropdown pulls live from the subjects table to stay
   in sync with save_result_scores.php's permission check, which
   compares a subject_teacher's `department` field against the
   subject name on every score save.

   Grade Level/Class dropdowns pull live from the class_arms
   table — single source of truth shared with class-arms.php.
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
$isSectionAdmin  = $admin['role'] === 'section_admin';
$adminOwnSection = $admin['section'];
$pdo             = getDB();

/* Self-healing column adds — phone and gender weren't in the
   original schema, add them here so the page never fatals
   regardless of which admin page runs first. */
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email");
} catch (PDOException $e) { /* already exists */ }
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN gender ENUM('male','female') NULL AFTER phone");
} catch (PDOException $e) { /* already exists */ }
try {
    $pdo->exec(
        "ALTER TABLE users MODIFY COLUMN role
         ENUM('superadmin','section_admin','principal','vp_admin','vp_academics','vp_general','vp_student_affairs','dean','counselor','hod','form_teacher','subject_teacher')
         NOT NULL"
    );
} catch (PDOException $e) { /* already includes section_admin */ }
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL AFTER gender");
} catch (PDOException $e) { /* already exists */ }
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER photo");
} catch (PDOException $e) { /* already exists */ }
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN show_on_directory TINYINT(1) NOT NULL DEFAULT 1 AFTER bio");
} catch (PDOException $e) { /* already exists */ }

$message = '';
$messageType = '';

/* ── Roles that can optionally have a department/subject —
   expanded so administrators who also teach a subject (VPs, Dean,
   Principal, Counselor) can be granted subject-teacher capability
   too, not just dedicated HOD/Subject Teacher roles. Only HOD and
   Subject Teacher have this as their core function; for everyone
   else it's an optional addition. ── */
$departmentRoles = [
    'principal', 'vp_admin', 'vp_academics', 'vp_general', 'vp_student_affairs',
    'dean', 'counselor', 'hod', 'form_teacher', 'subject_teacher',
];
/* ── Roles that need a class_assigned field ── */
$classRoles = ['form_teacher'];

/* ── Pull distinct subject names for the department dropdown — single source
   of truth shared with save_result_scores.php's permission check ── */
$departments = $pdo->query('SELECT DISTINCT name FROM subjects WHERE is_active = 1 ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);

/* ── Pull active classes, grouped by grade level, for the Form Teacher
   class_assigned dropdown — single source of truth shared with
   class-arms.php and (eventually) students-create.php ── */
$allClasses = $pdo->query(
    "SELECT grade_level, class FROM class_arms WHERE is_active = 1 ORDER BY grade_level ASC, class ASC"
)->fetchAll();

$classesByGradeLevel = [];
foreach ($allClasses as $row) {
    $classesByGradeLevel[$row['grade_level']][] = $row['class'];
}

$roleLabels = [
    'principal'       => 'Principal',
    'vp_admin'        => 'Vice Principal (Administration)',
    'vp_academics'    => 'Vice Principal (Academics)',
    'vp_general'      => 'Vice Principal (General Duties)',
    'vp_student_affairs' => 'Vice Principal (Student Affairs)',
    'dean'            => 'Dean of Studies',
    'counselor'       => 'Guidance Counsellor',
    'hod'             => 'Head of Department',
    'form_teacher'    => 'Form Teacher',
    'subject_teacher' => 'Subject Teacher',
    'section_admin'   => 'Section Admin',
    'superadmin'      => 'System Administrator',
];

/* Section admins can only create "regular" staff — never another
   Section Admin or a System Administrator account. */
if ($isSectionAdmin) {
    unset($roleLabels['section_admin'], $roleLabels['superadmin']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName        = trim($_POST['full_name']       ?? '');
    $email           = trim($_POST['email']            ?? '');
    $phone           = trim($_POST['phone']            ?? '');
    $gender          = trim($_POST['gender']           ?? '');
    $bio             = trim($_POST['bio']              ?? '');
    $showOnDirectory = isset($_POST['show_on_directory']) ? 1 : 0;
    $password        = $_POST['password']              ?? '';
    $role            = trim($_POST['role']             ?? '');
    $section         = trim($_POST['section']          ?? '');
    $department      = trim($_POST['department']       ?? '');
    $gradeLevelOnly  = trim($_POST['grade_level_only'] ?? '');
    $classOnly       = trim($_POST['class_only']        ?? '');
    $classAssigned   = ($gradeLevelOnly && $classOnly) ? $gradeLevelOnly . $classOnly : '';

    $validRoles    = array_keys($roleLabels);
    $validSections = ['ss', 'js', 'both'];
    $validGenders  = ['male', 'female', ''];

    /* Section admins can only ever create accounts within their own
       section — force this server-side regardless of what was
       submitted, so tampering with the form can't bypass it. */
    if ($isSectionAdmin) {
        $section = $adminOwnSection;
    }

    if ($fullName === '') {
        $message = 'Full name is required.';
        $messageType = 'error';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'A valid email address is required.';
        $messageType = 'error';
    } elseif (!in_array($gender, $validGenders, true)) {
        $message = 'Please select a valid gender.';
        $messageType = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $messageType = 'error';
    } elseif (!in_array($role, $validRoles, true)) {
        $message = 'Please select a valid role.';
        $messageType = 'error';
    } elseif ($isSectionAdmin && in_array($role, ['superadmin', 'section_admin'], true)) {
        $message = 'You do not have permission to create that role.';
        $messageType = 'error';
    } elseif (!in_array($section, $validSections, true)) {
        $message = 'Please select a valid section.';
        $messageType = 'error';
    } elseif ($role === 'section_admin' && $section === 'both') {
        $message = 'A Section Admin must be assigned to Senior or Junior Secondary specifically, not Both.';
        $messageType = 'error';
    } elseif (in_array($role, $classRoles, true) && $classAssigned === '') {
        $message = 'Please select both a grade level and a class for the Form Teacher role.';
        $messageType = 'error';
    } else {

        /* Check email uniqueness */
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $checkStmt->execute([$email]);

        if ($checkStmt->fetch()) {
            $message = 'An account with that email address already exists.';
            $messageType = 'error';
        } else {
            $finalDepartment = in_array($role, $departmentRoles, true) ? ($department ?: null) : null;
            $finalClass      = in_array($role, $classRoles, true) ? ($classAssigned ?: null) : null;

            /* ── Photo upload — optional ── */
            $photoFilename = null;
            if (!empty($_FILES['photo']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    $message = 'Photo must be JPG, PNG, or WEBP.'; $messageType = 'error';
                } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                    $message = 'Photo must be under 2MB.'; $messageType = 'error';
                } else {
                    $photoDir = dirname(__DIR__) . '/assets/images/staff/';
                    if (!is_dir($photoDir)) mkdir($photoDir, 0755, true);
                    $photoFilename = uniqid('staff_', true) . '.' . $ext;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoDir . $photoFilename)) {
                        $photoFilename = null;
                        $message = 'Photo upload failed.'; $messageType = 'error';
                    }
                }
            }

            if ($messageType !== 'error') {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $stmt = $pdo->prepare(
                    'INSERT INTO users
                        (full_name, email, phone, gender, photo, bio, show_on_directory, password, role, section, department, class_assigned, is_active, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
                );
                $stmt->execute([
                    $fullName, $email, $phone ?: null, $gender ?: null, $photoFilename, $bio ?: null, $showOnDirectory,
                    $hash, $role, $section, $finalDepartment, $finalClass, $admin['id'],
                ]);

                $newUserId = (int) $pdo->lastInsertId();
                syncStaffDirectoryFromUser($pdo, $newUserId);

                $message = 'Account created successfully for ' . htmlspecialchars($fullName) . '.';
                $messageType = 'success';

                $fullName = $email = $phone = $gender = $bio = $role = $section = $department = $gradeLevelOnly = $classOnly = '';

            } catch (PDOException $e) {
                error_log('IHS users-create error: ' . $e->getMessage());
                $message = 'A server error occurred while creating the account.';
                $messageType = 'error';
            }
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
<title>Create User — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .form-group { margin-bottom: 18px; }
  .form-label {
    display: block; font-size: 12.5px; font-weight: 600; color: #3d1a6e;
    margin-bottom: 6px; text-transform: uppercase; letter-spacing: .03em;
  }
  .form-input, .form-select {
    width: 100%; padding: 10px 13px; border: 1.5px solid #e2e0ea; border-radius: 8px;
    font-size: 13.5px; font-family: 'DM Sans', sans-serif; color: #1a1a2e;
  }
  .form-input:focus, .form-select:focus { outline: none; border-color: #4a90d9; }
  .form-row { display: flex; gap: 16px; }
  .form-row .form-group { flex: 1; }

  .conditional-field { display: none; }
  .conditional-field--show { display: block; }

  .char-hint { font-size: 11.5px; color: #9b97b0; margin-top: 4px; }

  .btn-group { display: flex; gap: 12px; margin-top: 22px; }
  .btn-save { background: #3d1a6e; color: #fff; border: none; padding: 11px 28px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
  .btn-save:hover { background: #5a2d9e; }
  .btn-cancel { background: #f0ecfa; color: #3d1a6e; border: 1.5px solid #d8d0ee; padding: 11px 24px; border-radius: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
  .btn-cancel:hover { background: #e4dcf6; }

  .generate-pw-btn {
    background: #f0ecfa; color: #3d1a6e; border: 1px solid #d8d0ee;
    padding: 8px 14px; border-radius: 7px; font-size: 12px; font-weight: 600;
    cursor: pointer; margin-top: 8px;
  }
  .generate-pw-btn:hover { background: #e4dcf6; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'users'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Create User Account</h2>
        <p><a href="users.php" style="color:#4a90d9;text-decoration:none">← Back to All Users</a></p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="admin-card" style="max-width:620px">
        <form method="POST" id="userForm" enctype="multipart/form-data">

          <div class="form-group">
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" class="form-input" id="full_name" name="full_name" required maxlength="150"
                   value="<?php echo htmlspecialchars($fullName ?? ''); ?>" placeholder="e.g. Adaeze Okafor"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" class="form-input" id="email" name="email" required maxlength="150"
                   value="<?php echo htmlspecialchars($email ?? ''); ?>" placeholder="staff@ibekuhighschool.edu.ng"/>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="phone">Phone Number</label>
              <input type="tel" class="form-input" id="phone" name="phone" maxlength="20"
                     value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="e.g. 08012345678"/>
            </div>
            <div class="form-group">
              <label class="form-label" for="gender">Gender</label>
              <select class="form-select" id="gender" name="gender">
                <option value="">Select gender</option>
                <option value="male" <?php echo ($gender ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo ($gender ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="photo">Profile Photo</label>
            <input type="file" class="form-input" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"/>
            <p class="char-hint">Shown on the public Staff Directory (if enabled below) and used as their profile photo throughout the admin panel.</p>
          </div>

          <div class="form-group">
            <label class="form-label" for="bio">Short Bio</label>
            <textarea class="form-textarea" id="bio" name="bio" rows="3"
                      placeholder="A brief description shown on the public Staff Directory..."><?php echo htmlspecialchars($bio ?? ''); ?></textarea>
          </div>

          <div class="form-group" style="display:flex;align-items:center;gap:8px">
            <input type="checkbox" id="show_on_directory" name="show_on_directory" value="1"
                   <?php echo !isset($showOnDirectory) || $showOnDirectory ? 'checked' : ''; ?>/>
            <label class="form-label" for="show_on_directory" style="margin:0">Show on public Staff Directory (Academics page)</label>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Temporary Password</label>
            <input type="text" class="form-input" id="password" name="password" required minlength="8"
                   placeholder="Minimum 8 characters"/>
            <button type="button" class="generate-pw-btn" id="generatePwBtn">Generate Secure Password</button>
            <p class="char-hint">Share this with the staff member directly. They should change it after first login (password change feature: Phase 4).</p>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="role">Role</label>
              <select class="form-select" id="role" name="role" required>
                <option value="">Select role</option>
                <?php foreach ($roleLabels as $key => $label): ?>
                  <?php if ($key === 'superadmin') continue; /* don't let UI create more superadmins casually */ ?>
                <option value="<?php echo $key; ?>" <?php echo ($role ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="section">Section</label>
              <?php if ($isSectionAdmin): ?>
              <select class="form-select" id="section" name="section" disabled>
                <option value="<?php echo $adminOwnSection; ?>" selected>
                  <?php echo $adminOwnSection === 'ss' ? 'Senior Secondary' : 'Junior Secondary'; ?>
                </option>
              </select>
              <input type="hidden" name="section" value="<?php echo htmlspecialchars($adminOwnSection); ?>"/>
              <p class="char-hint">Locked to your own section.</p>
              <?php else: ?>
              <select class="form-select" id="section" name="section" required>
                <option value="">Select section</option>
                <option value="ss" <?php echo ($section ?? '') === 'ss' ? 'selected' : ''; ?>>Senior Secondary</option>
                <option value="js" <?php echo ($section ?? '') === 'js' ? 'selected' : ''; ?>>Junior Secondary</option>
                <option value="both" <?php echo ($section ?? '') === 'both' ? 'selected' : ''; ?>>Both Sections</option>
              </select>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-group conditional-field" id="departmentField">
            <label class="form-label" for="department">Department / Subject</label>
            <select class="form-select" id="department" name="department">
              <option value="">Select department</option>
              <?php foreach ($departments as $dept): ?>
              <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department ?? '') === $dept ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
              <?php endforeach; ?>
            </select>
            <p class="char-hint">Required for Head of Department and Subject Teacher roles; optional for other staff who also teach a subject. This must exactly match a subject name for the results entry permission system to work correctly.</p>
          </div>

          <div class="form-group conditional-field" id="classField">
            <label class="form-label">Grade Level &amp; Class Assigned</label>
            <div class="form-row">
              <div class="form-group" style="margin-bottom:0">
                <select class="form-select" id="grade_level_only" name="grade_level_only">
                  <option value="">Select grade level</option>
                </select>
              </div>
              <div class="form-group" style="margin-bottom:0">
                <select class="form-select" id="class_only" name="class_only">
                  <option value="">Select class</option>
                </select>
              </div>
            </div>
            <p class="char-hint">Required for Form Teacher role. Only active classes appear — manage these on the Manage Classes page. Select a Section above first.</p>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save">Create Account</button>
            <a href="users.php" class="btn-cancel">Cancel</a>
          </div>

        </form>
      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    var roleSelect          = document.getElementById('role');
    var sectionSelect        = document.getElementById('section');
    var departmentField      = document.getElementById('departmentField');
    var classField           = document.getElementById('classField');
    var gradeLevelOnlySelect = document.getElementById('grade_level_only');
    var classOnlySelect      = document.getElementById('class_only');

    var departmentRoles = [
      'principal', 'vp_admin', 'vp_academics', 'vp_general', 'vp_student_affairs',
      'dean', 'counselor', 'hod', 'form_teacher', 'subject_teacher'
    ];
    var classRoles      = ['form_teacher'];

    /* ── Classes data passed from PHP — single source of truth from class_arms table ── */
    var classesByGradeLevel = <?php echo json_encode($classesByGradeLevel); ?>;

    var gradeLevelLabels = {
      JSS1: 'JSS 1', JSS2: 'JSS 2', JSS3: 'JSS 3',
      SSS1: 'SSS 1', SSS2: 'SSS 2', SSS3: 'SSS 3'
    };

    /* ── Populate the Grade Level dropdown based on the selected Section ── */
    function updateGradeLevelOptions() {
      var section = sectionSelect.value;
      var prefix  = section === 'js' ? 'JSS' : (section === 'ss' ? 'SSS' : null);

      gradeLevelOnlySelect.innerHTML = '<option value="">Select grade level</option>';

      if (!prefix) return; // "Both Sections" — Form Teacher role doesn't apply, leave empty

      Object.keys(classesByGradeLevel).forEach(function (gradeLevelKey) {
        if (gradeLevelKey.indexOf(prefix) === 0) {
          var opt = document.createElement('option');
          opt.value = gradeLevelKey;
          opt.textContent = gradeLevelLabels[gradeLevelKey] || gradeLevelKey;
          gradeLevelOnlySelect.appendChild(opt);
        }
      });

      updateClassOptions();
    }

    /* ── Populate the Class dropdown based on the selected Grade Level ── */
    function updateClassOptions() {
      var gradeLevelKey = gradeLevelOnlySelect.value;
      classOnlySelect.innerHTML = '<option value="">Select class</option>';

      if (!gradeLevelKey || !classesByGradeLevel[gradeLevelKey]) return;

      classesByGradeLevel[gradeLevelKey].forEach(function (cls) {
        var opt = document.createElement('option');
        opt.value = cls;
        opt.textContent = gradeLevelLabels[gradeLevelKey] + ' ' + cls;
        classOnlySelect.appendChild(opt);
      });
    }

    function updateConditionalFields() {
      var role = roleSelect.value;

      if (departmentRoles.indexOf(role) !== -1) {
        departmentField.classList.add('conditional-field--show');
      } else {
        departmentField.classList.remove('conditional-field--show');
      }

      if (classRoles.indexOf(role) !== -1) {
        classField.classList.add('conditional-field--show');
        updateGradeLevelOptions();
      } else {
        classField.classList.remove('conditional-field--show');
      }
    }

    roleSelect.addEventListener('change', updateConditionalFields);
    sectionSelect.addEventListener('change', function () {
      if (classRoles.indexOf(roleSelect.value) !== -1) {
        updateGradeLevelOptions();
      }
    });
    gradeLevelOnlySelect.addEventListener('change', updateClassOptions);

    updateConditionalFields();

    document.getElementById('generatePwBtn').addEventListener('click', function () {
      var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
      var pw = '';
      for (var i = 0; i < 12; i++) {
        pw += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      document.getElementById('password').value = pw;
      document.getElementById('password').type = 'text';
    });
  </script>

</body>
</html>