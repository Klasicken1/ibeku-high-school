<?php
/* ============================================================
   IBEKU HIGH SCHOOL — EDIT USER
   File: public/admin/users-edit.php

   Accessible to: superadmin only
   Edits an existing staff account — name, role, section,
   department, class assignment. Password reset is optional:
   leave both password fields blank to keep the current password.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin']);

$admin = currentAdmin();
$pdo   = getDB();

$userId = (int) ($_GET['id'] ?? 0);

if ($userId <= 0) {
    header('Location: users.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: users.php');
    exit;
}

$message = '';
$messageType = '';

/* ── Roles that need a department field ── */
$departmentRoles = ['hod', 'subject_teacher'];
/* ── Roles that need a class_assigned field ── */
$classRoles = ['form_teacher'];

/* ── Pull subjects for department dropdown ── */
$departments = $pdo->query('SELECT DISTINCT name FROM subjects WHERE is_active = 1 ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);

/* ── Pull active classes grouped by grade level ── */
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
    'dean'            => 'Dean of Studies',
    'counselor'       => 'Guidance Counsellor',
    'hod'             => 'Head of Department',
    'form_teacher'    => 'Form Teacher',
    'subject_teacher' => 'Subject Teacher',
    'superadmin'      => 'System Administrator',
];

/* ── Handle form submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName       = trim($_POST['full_name']       ?? '');
    $email          = trim($_POST['email']            ?? '');
    $role           = trim($_POST['role']             ?? '');
    $section        = trim($_POST['section']          ?? '');
    $department     = trim($_POST['department']       ?? '');
    $gradeLevelOnly = trim($_POST['grade_level_only'] ?? '');
    $classOnly      = trim($_POST['class_only']        ?? '');
    $classAssigned  = ($gradeLevelOnly && $classOnly) ? $gradeLevelOnly . $classOnly : '';
    $isActive       = isset($_POST['is_active']) ? 1 : 0;
    $newPassword    = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $validRoles    = array_keys($roleLabels);
    $validSections = ['ss', 'js', 'both'];

    if ($fullName === '') {
        $message = 'Full name is required.';
        $messageType = 'error';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'A valid email address is required.';
        $messageType = 'error';
    } elseif (!in_array($role, $validRoles, true)) {
        $message = 'Please select a valid role.';
        $messageType = 'error';
    } elseif (!in_array($section, $validSections, true)) {
        $message = 'Please select a valid section.';
        $messageType = 'error';
    } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
        $message = 'New password must be at least 8 characters.';
        $messageType = 'error';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        /* Check email uniqueness — excluding this user */
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $checkStmt->execute([$email, $userId]);

        if ($checkStmt->fetch()) {
            $message = 'That email address is already used by another account.';
            $messageType = 'error';
        } else {
            /* Prevent superadmin from accidentally deactivating their own account */
            if ($userId === (int) $admin['id'] && !$isActive) {
                $isActive = 1;
            }

            $finalDepartment = in_array($role, $departmentRoles, true) ? ($department ?: null) : null;
            $finalClass      = in_array($role, $classRoles, true) ? ($classAssigned ?: null) : null;

            try {
                /* ── Update profile fields ── */
                $updateStmt = $pdo->prepare(
                    'UPDATE users SET
                        full_name = ?, email = ?, role = ?, section = ?,
                        department = ?, class_assigned = ?, is_active = ?,
                        updated_at = NOW()
                     WHERE id = ?'
                );
                $updateStmt->execute([
                    $fullName, $email, $role, $section,
                    $finalDepartment, $finalClass, $isActive,
                    $userId,
                ]);

                /* ── Optionally reset password ── */
                if ($newPassword !== '') {
                    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
                }

                $message = 'Account updated successfully.';
                $messageType = 'success';

                /* Reload fresh data */
                $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

            } catch (PDOException $e) {
                error_log('IHS users-edit error: ' . $e->getMessage());
                $message = 'A server error occurred while saving.';
                $messageType = 'error';
            }
        }
    }
}

/* ── Parse existing class_assigned into grade_level + class for the dropdowns ── */
$existingGradeLevel = '';
$existingClassOnly  = '';
if ($user['class_assigned'] && preg_match('/^(JSS[123]|SSS[123])([A-Z0-9]+)$/', $user['class_assigned'], $m)) {
    $existingGradeLevel = $m[1];
    $existingClassOnly  = $m[2];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Edit User — Admin — Ibeku High School</title>
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

  .section-divider {
    border-top: 1px solid #f0eef6; margin: 24px 0 20px; padding-top: 20px;
  }
  .section-divider__label {
    font-size: 13px; font-weight: 700; color: #3d1a6e; margin-bottom: 4px;
  }
  .section-divider__hint { font-size: 12px; color: #9b97b0; margin-bottom: 16px; }

  .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
  .checkbox-row input { width: 16px; height: 16px; }
  .checkbox-row label { font-size: 13.5px; color: #1a1a2e; }

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

  .self-note {
    background: #fff3e6; border: 1px solid #ffe0b2; color: #8a4a00;
    padding: 10px 14px; border-radius: 8px; font-size: 12.5px; margin-bottom: 18px;
  }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'users'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Edit User Account</h2>
        <p><a href="users.php" style="color:#4a90d9;text-decoration:none">← Back to All Users</a></p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($userId === (int) $admin['id']): ?>
      <div class="self-note">You are editing your own account. Your role and active status cannot be changed while you are logged in.</div>
      <?php endif; ?>

      <div class="admin-card" style="max-width:620px">
        <form method="POST" id="editForm">

          <div class="form-group">
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" class="form-input" id="full_name" name="full_name" required maxlength="150"
                   value="<?php echo htmlspecialchars($user['full_name']); ?>"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" class="form-input" id="email" name="email" required maxlength="150"
                   value="<?php echo htmlspecialchars($user['email']); ?>"/>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="role">Role</label>
              <select class="form-select" id="role" name="role" required
                      <?php echo $userId === (int) $admin['id'] ? 'disabled' : ''; ?>>
                <?php foreach ($roleLabels as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php echo $user['role'] === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ($userId === (int) $admin['id']): ?>
              <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>"/>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="section">Section</label>
              <select class="form-select" id="section" name="section" required>
                <option value="ss"   <?php echo $user['section'] === 'ss'   ? 'selected' : ''; ?>>Senior Secondary</option>
                <option value="js"   <?php echo $user['section'] === 'js'   ? 'selected' : ''; ?>>Junior Secondary</option>
                <option value="both" <?php echo $user['section'] === 'both' ? 'selected' : ''; ?>>Both Sections</option>
              </select>
            </div>
          </div>

          <div class="form-group conditional-field" id="departmentField">
            <label class="form-label" for="department">Department / Subject</label>
            <select class="form-select" id="department" name="department">
              <option value="">Select department</option>
              <?php foreach ($departments as $dept): ?>
              <option value="<?php echo htmlspecialchars($dept); ?>"
                      <?php echo $user['department'] === $dept ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($dept); ?>
              </option>
              <?php endforeach; ?>
            </select>
            <p class="char-hint">Must exactly match a subject name for the results entry permission system.</p>
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
            <p class="char-hint">Required for Form Teacher role.</p>
          </div>

          <div class="checkbox-row">
            <input type="checkbox" id="is_active" name="is_active"
                   <?php echo $user['is_active'] ? 'checked' : ''; ?>
                   <?php echo $userId === (int) $admin['id'] ? 'disabled' : ''; ?>/>
            <label for="is_active">Account is active (uncheck to deactivate — user cannot log in)</label>
            <?php if ($userId === (int) $admin['id']): ?>
            <input type="hidden" name="is_active" value="1"/>
            <?php endif; ?>
          </div>

          <!-- ── Password reset section ── -->
          <div class="section-divider">
            <div class="section-divider__label">Reset Password</div>
            <div class="section-divider__hint">Leave both fields blank to keep the current password unchanged.</div>

            <div class="form-group">
              <label class="form-label" for="new_password">New Password</label>
              <input type="text" class="form-input" id="new_password" name="new_password"
                     minlength="8" placeholder="Minimum 8 characters — leave blank to keep current"/>
              <button type="button" class="generate-pw-btn" id="generatePwBtn">Generate Secure Password</button>
            </div>

            <div class="form-group">
              <label class="form-label" for="confirm_password">Confirm New Password</label>
              <input type="text" class="form-input" id="confirm_password" name="confirm_password"
                     placeholder="Re-enter the new password"/>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save">Save Changes</button>
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

    var departmentRoles = ['hod', 'subject_teacher'];
    var classRoles      = ['form_teacher'];

    var classesByGradeLevel = <?php echo json_encode($classesByGradeLevel); ?>;

    var gradeLevelLabels = {
      JSS1: 'JSS 1', JSS2: 'JSS 2', JSS3: 'JSS 3',
      SSS1: 'SSS 1', SSS2: 'SSS 2', SSS3: 'SSS 3'
    };

    /* ── Pre-fill existing class assignment ── */
    var existingGradeLevel = <?php echo json_encode($existingGradeLevel); ?>;
    var existingClassOnly  = <?php echo json_encode($existingClassOnly); ?>;

    function updateGradeLevelOptions(preselectGradeLevel) {
      var section = sectionSelect.value;
      var prefix  = section === 'js' ? 'JSS' : (section === 'ss' ? 'SSS' : null);

      gradeLevelOnlySelect.innerHTML = '<option value="">Select grade level</option>';

      if (!prefix) return;

      Object.keys(classesByGradeLevel).forEach(function (gl) {
        if (gl.indexOf(prefix) === 0) {
          var opt = document.createElement('option');
          opt.value = gl;
          opt.textContent = gradeLevelLabels[gl] || gl;
          if (gl === preselectGradeLevel) opt.selected = true;
          gradeLevelOnlySelect.appendChild(opt);
        }
      });

      updateClassOptions(preselectGradeLevel === gradeLevelOnlySelect.value ? existingClassOnly : '');
    }

    function updateClassOptions(preselectClass) {
      var gradeLevelKey = gradeLevelOnlySelect.value;
      classOnlySelect.innerHTML = '<option value="">Select class</option>';

      if (!gradeLevelKey || !classesByGradeLevel[gradeLevelKey]) return;

      classesByGradeLevel[gradeLevelKey].forEach(function (cls) {
        var opt = document.createElement('option');
        opt.value = cls;
        opt.textContent = gradeLevelLabels[gradeLevelKey] + ' ' + cls;
        if (cls === preselectClass) opt.selected = true;
        classOnlySelect.appendChild(opt);
      });
    }

    function updateConditionalFields(isInitial) {
      var role = roleSelect.value;

      if (departmentRoles.indexOf(role) !== -1) {
        departmentField.classList.add('conditional-field--show');
      } else {
        departmentField.classList.remove('conditional-field--show');
      }

      if (classRoles.indexOf(role) !== -1) {
        classField.classList.add('conditional-field--show');
        updateGradeLevelOptions(isInitial ? existingGradeLevel : '');
      } else {
        classField.classList.remove('conditional-field--show');
      }
    }

    roleSelect.addEventListener('change', function () { updateConditionalFields(false); });
    sectionSelect.addEventListener('change', function () {
      if (classRoles.indexOf(roleSelect.value) !== -1) {
        updateGradeLevelOptions('');
      }
    });
    gradeLevelOnlySelect.addEventListener('change', function () { updateClassOptions(''); });

    /* Run on page load to restore existing state */
    updateConditionalFields(true);

    /* ── Generate password ── */
    document.getElementById('generatePwBtn').addEventListener('click', function () {
      var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
      var pw = '';
      for (var i = 0; i < 12; i++) {
        pw += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      document.getElementById('new_password').value    = pw;
      document.getElementById('confirm_password').value = pw;
    });
  </script>

</body>
</html>