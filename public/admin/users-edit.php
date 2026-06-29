<?php
/* ============================================================
   IBEKU HIGH SCHOOL — EDIT USER
   File: public/admin/users-edit.php

   Accessible to: superadmin only
   Edits an existing staff account — name, role, section,
   department, class assignment, active status, optional
   password reset.

   For subject_teacher role: superadmin can assign one or more
   specific grade_level+class combinations via the
   teacher_class_assignments junction table. If no assignments
   are made, the teacher sees all classes in their section.

   Account closure: superadmin can close accounts for staff who
   have retired, transferred, deceased, or otherwise left.
   Closed accounts are deactivated and the reason is recorded.
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

$message     = '';
$messageType = '';

$departmentRoles = ['hod', 'subject_teacher'];
$classRoles      = ['form_teacher'];

/* ── Pull subjects for department dropdown ── */
$departments = $pdo->query('SELECT DISTINCT name FROM subjects WHERE is_active = 1 ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);

/* ── Pull active classes grouped by grade level ── */
$allClassRows = $pdo->query(
    "SELECT grade_level, class FROM class_arms WHERE is_active = 1 ORDER BY grade_level ASC, class ASC"
)->fetchAll();
$classesByGradeLevel = [];
foreach ($allClassRows as $row) {
    $classesByGradeLevel[$row['grade_level']][] = $row['class'];
}

/* ── Pull existing teacher class assignments for this user ── */
$existingAssignments = [];
$assignStmt = $pdo->prepare('SELECT grade_level, class FROM teacher_class_assignments WHERE teacher_id = ? ORDER BY grade_level ASC, class ASC');
$assignStmt->execute([$userId]);
foreach ($assignStmt->fetchAll() as $row) {
    $existingAssignments[] = $row['grade_level'] . '|' . $row['class'];
}

/* ── Load the name of who closed this account (if applicable) ── */
$closedByName = null;
if ($user['closed_by']) {
    $closedByStmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ? LIMIT 1');
    $closedByStmt->execute([$user['closed_by']]);
    $closedByName = $closedByStmt->fetchColumn();
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

$closureReasonLabels = [
    'retired'     => 'Retired',
    'transferred' => 'Transferred',
    'deceased'    => 'Deceased',
    'expelled'    => 'Dismissed / Expelled',
    'graduated'   => 'Contract Ended',
    'other'       => 'Other',
];

/* ── Handle form submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['form_action'] ?? 'update');

    /* ── Account closure action ── */
    if ($action === 'close_account') {
        if ($userId === (int) $admin['id']) {
            $message = 'You cannot close your own account.'; $messageType = 'error';
        } else {
            $closureReason     = trim($_POST['closure_reason']     ?? '');
            $closureReasonText = trim($_POST['closure_reason_text'] ?? '');
            $validReasons = array_keys($closureReasonLabels);

            if (!in_array($closureReason, $validReasons, true)) {
                $message = 'Please select a valid closure reason.'; $messageType = 'error';
            } else {
                try {
                    $pdo->prepare(
                        'UPDATE users SET
                            is_active = 0,
                            closed_reason = ?,
                            closed_at = NOW(),
                            closed_by = ?,
                            updated_at = NOW()
                         WHERE id = ?'
                    )->execute([$closureReason, $admin['id'], $userId]);

                    $message = 'Account closed successfully.'; $messageType = 'success';

                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    $closedByName = $admin['name'];

                } catch (PDOException $e) {
                    error_log('IHS users-edit close error: ' . $e->getMessage());
                    $message = 'A server error occurred.'; $messageType = 'error';
                }
            }
        }

    } elseif ($action === 'reopen_account') {
        /* ── Reopen a closed account ── */
        if ($userId === (int) $admin['id']) {
            $message = 'Cannot reopen your own account.'; $messageType = 'error';
        } else {
            try {
                $pdo->prepare(
                    'UPDATE users SET
                        is_active = 1,
                        closed_reason = NULL,
                        closed_at = NULL,
                        closed_by = NULL,
                        updated_at = NOW()
                     WHERE id = ?'
                )->execute([$userId]);

                $message = 'Account reopened successfully.'; $messageType = 'success';
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                $closedByName = null;

            } catch (PDOException $e) {
                error_log('IHS users-edit reopen error: ' . $e->getMessage());
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }

    } else {
        /* ── Standard profile update ── */
        $fullName        = trim($_POST['full_name']        ?? '');
        $email           = trim($_POST['email']             ?? '');
        $role            = trim($_POST['role']              ?? '');
        $section         = trim($_POST['section']           ?? '');
        $department      = trim($_POST['department']        ?? '');
        $gradeLevelOnly  = trim($_POST['grade_level_only']  ?? '');
        $classOnly       = trim($_POST['class_only']         ?? '');
        $classAssigned   = ($gradeLevelOnly && $classOnly) ? $gradeLevelOnly . $classOnly : '';
        $isActive        = isset($_POST['is_active']) ? 1 : 0;
        $newPassword     = $_POST['new_password']      ?? '';
        $confirmPassword = $_POST['confirm_password']  ?? '';
        $teacherAssignments = $_POST['teacher_assignments'] ?? [];

        $validRoles    = array_keys($roleLabels);
        $validSections = ['ss', 'js', 'both'];

        if ($fullName === '') {
            $message = 'Full name is required.'; $messageType = 'error';
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'A valid email address is required.'; $messageType = 'error';
        } elseif (!in_array($role, $validRoles, true)) {
            $message = 'Please select a valid role.'; $messageType = 'error';
        } elseif (!in_array($section, $validSections, true)) {
            $message = 'Please select a valid section.'; $messageType = 'error';
        } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
            $message = 'New password must be at least 8 characters.'; $messageType = 'error';
        } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $message = 'Passwords do not match.'; $messageType = 'error';
        } else {
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $checkStmt->execute([$email, $userId]);

            if ($checkStmt->fetch()) {
                $message = 'That email address is already used by another account.'; $messageType = 'error';
            } else {
                if ($userId === (int) $admin['id'] && !$isActive) $isActive = 1;

                $finalDepartment = in_array($role, $departmentRoles, true) ? ($department ?: null) : null;
                $finalClass      = in_array($role, $classRoles, true) ? ($classAssigned ?: null) : null;

                try {
                    $pdo->beginTransaction();

                    $pdo->prepare(
                        'UPDATE users SET full_name=?, email=?, role=?, section=?,
                         department=?, class_assigned=?, is_active=?, updated_at=NOW()
                         WHERE id=?'
                    )->execute([$fullName, $email, $role, $section, $finalDepartment, $finalClass, $isActive, $userId]);

                    if ($newPassword !== '') {
                        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                        $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $userId]);
                    }

                    /* ── Sync teacher class assignments ── */
                    $pdo->prepare('DELETE FROM teacher_class_assignments WHERE teacher_id = ?')->execute([$userId]);

                    if ($role === 'subject_teacher' && !empty($teacherAssignments)) {
                        $validGradeLevels = ['JSS1','JSS2','JSS3','SSS1','SSS2','SSS3'];
                        $insertAssign = $pdo->prepare(
                            'INSERT IGNORE INTO teacher_class_assignments (teacher_id, grade_level, class) VALUES (?, ?, ?)'
                        );
                        foreach ($teacherAssignments as $pair) {
                            $parts = explode('|', (string) $pair);
                            if (count($parts) === 2 && in_array($parts[0], $validGradeLevels, true) && $parts[1] !== '') {
                                $insertAssign->execute([$userId, $parts[0], $parts[1]]);
                            }
                        }
                    }

                    $pdo->commit();

                    $message = 'Account updated successfully.'; $messageType = 'success';

                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    $existingAssignments = [];
                    $assignStmt->execute([$userId]);
                    foreach ($assignStmt->fetchAll() as $row) {
                        $existingAssignments[] = $row['grade_level'] . '|' . $row['class'];
                    }

                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('IHS users-edit error: ' . $e->getMessage());
                    $message = 'A server error occurred while saving.'; $messageType = 'error';
                }
            }
        }
    }
}

/* ── Parse existing class_assigned for form teacher dropdown ── */
$existingGradeLevel = '';
$existingClassOnly  = '';
if ($user['class_assigned'] && preg_match('/^(JSS[123]|SSS[123])([A-Z0-9]+)$/', $user['class_assigned'], $m)) {
    $existingGradeLevel = $m[1];
    $existingClassOnly  = $m[2];
}

$gradeLevelLabels = ['JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3','SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3'];

$isClosed = !empty($user['closed_reason']);
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

  .section-divider { border-top: 1px solid #f0eef6; margin: 24px 0 20px; padding-top: 20px; }
  .section-divider__label { font-size: 13px; font-weight: 700; color: #3d1a6e; margin-bottom: 4px; }
  .section-divider__hint { font-size: 12px; color: #9b97b0; margin-bottom: 16px; }

  .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
  .checkbox-row input { width: 16px; height: 16px; }
  .checkbox-row label { font-size: 13.5px; color: #1a1a2e; }

  .btn-group { display: flex; gap: 12px; margin-top: 22px; flex-wrap:wrap; }
  .btn-save { background: #3d1a6e; color: #fff; border: none; padding: 11px 28px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
  .btn-save:hover { background: #5a2d9e; }
  .btn-cancel { background: #f0ecfa; color: #3d1a6e; border: 1.5px solid #d8d0ee; padding: 11px 24px; border-radius: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
  .btn-cancel:hover { background: #e4dcf6; }
  .btn-danger { background: #ffe6e6; color: #cc3333; border: 1.5px solid #ffcccc; padding: 11px 24px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; }
  .btn-danger:hover { background: #ffcccc; }
  .btn-reopen { background: #e6f9ed; color: #1a7a3a; border: 1.5px solid #b8eecb; padding: 11px 24px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; }
  .btn-reopen:hover { background: #b8eecb; }

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
  .closed-notice {
    background: #ffe6e6; border: 1px solid #ffcccc; color: #cc3333;
    padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 18px;
    line-height: 1.7;
  }
  .closed-notice strong { display: block; font-size: 13.5px; margin-bottom: 2px; }

  /* ── Class assignment grid ── */
  .assignment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 8px; margin-top: 10px;
  }
  .assignment-checkbox {
    display: flex; align-items: center; gap: 8px;
    background: #f4f3f9; border: 1px solid #e2e0ea; border-radius: 8px;
    padding: 8px 12px; cursor: pointer; font-size: 13px;
    transition: background .15s, border-color .15s;
  }
  .assignment-checkbox:has(input:checked) {
    background: #f0ecfa; border-color: #3d1a6e; color: #3d1a6e; font-weight: 600;
  }
  .assignment-checkbox input { width: 15px; height: 15px; cursor: pointer; }
  .assignment-section-label {
    font-size: 11px; font-weight: 700; color: #9b97b0;
    text-transform: uppercase; letter-spacing: .06em; margin: 12px 0 6px;
  }
  .assignment-hint { font-size: 11.5px; color: #9b97b0; margin-top: 8px; }
  .btn-select-all {
    background: #f0ecfa; color: #3d1a6e; border: 1px solid #d8d0ee;
    padding: 5px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 600;
    cursor: pointer; margin-right: 6px;
  }
  .btn-select-all:hover { background: #e4dcf6; }

  /* ── Closure modal ── */
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 999;
    align-items: center; justify-content: center;
  }
  .modal-overlay--show { display: flex; }
  .modal-box {
    background: #fff; border-radius: 16px; padding: 28px 28px 22px;
    max-width: 440px; width: 100%; margin: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
  }
  .modal-box h3 { font-size: 16px; color: #3d1a6e; margin-bottom: 6px; }
  .modal-box p { font-size: 13px; color: #6b6b80; margin-bottom: 18px; }
  .modal-box .form-group { margin-bottom: 14px; }
  .modal-actions { display: flex; gap: 10px; margin-top: 18px; }
  .modal-actions .btn-danger { flex: 1; text-align: center; border: none; }
  .modal-actions .btn-cancel { flex: 1; text-align: center; }
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

      <?php if ($isClosed): ?>
      <div class="closed-notice">
        <strong>⛔ This account has been closed</strong>
        Reason: <?php echo htmlspecialchars($closureReasonLabels[$user['closed_reason']] ?? $user['closed_reason']); ?>
        <?php if ($user['closed_at']): ?>
        &nbsp;·&nbsp; <?php echo date('d M Y', strtotime($user['closed_at'])); ?>
        <?php endif; ?>
        <?php if ($closedByName): ?>
        &nbsp;·&nbsp; Closed by <?php echo htmlspecialchars($closedByName); ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="admin-card" style="max-width:680px">
        <form method="POST" id="editForm">
          <input type="hidden" name="form_action" value="update"/>

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

          <!-- ── Subject Teacher Class Assignments ── -->
          <div class="conditional-field" id="teacherAssignmentsField">
            <div class="section-divider">
              <div class="section-divider__label">Class Assignments</div>
              <div class="section-divider__hint">
                Tick the classes this teacher is allowed to enter scores for.
                Leave all unticked to allow access to <em>all</em> classes in their section.
              </div>
              <div style="margin-bottom:10px">
                <button type="button" class="btn-select-all" onclick="selectAllClasses(true)">Select All</button>
                <button type="button" class="btn-select-all" onclick="selectAllClasses(false)">Clear All</button>
              </div>
              <div class="assignment-grid" id="assignmentGrid">
                <?php
                $currentSection = $user['section'];
                foreach ($classesByGradeLevel as $gl => $classes):
                    $glSection = str_starts_with($gl, 'JSS') ? 'js' : 'ss';
                    if ($currentSection !== 'both' && $glSection !== $currentSection) continue;
                ?>
                <div class="assignment-section-label" style="grid-column:1/-1"><?php echo $gradeLevelLabels[$gl] ?? $gl; ?></div>
                <?php foreach ($classes as $cls):
                    $pair    = $gl . '|' . $cls;
                    $checked = in_array($pair, $existingAssignments, true) ? 'checked' : '';
                ?>
                <label class="assignment-checkbox">
                  <input type="checkbox" name="teacher_assignments[]"
                         value="<?php echo htmlspecialchars($pair); ?>"
                         class="teacher-assign-cb"
                         <?php echo $checked; ?>/>
                  <?php echo ($gradeLevelLabels[$gl] ?? $gl) . ' ' . $cls; ?>
                </label>
                <?php endforeach; ?>
                <?php endforeach; ?>
              </div>
              <p class="assignment-hint">
                <?php if (!empty($existingAssignments)): ?>
                  Currently assigned to <strong><?php echo count($existingAssignments); ?></strong> class(es).
                <?php else: ?>
                  No specific assignments — teacher can access all classes in their section.
                <?php endif; ?>
              </p>
            </div>
          </div>

          <!-- ── Form Teacher Class Assignment ── -->
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

          <!-- ── Password reset ── -->
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
            <?php if ($userId !== (int) $admin['id']): ?>
              <?php if (!$isClosed): ?>
              <button type="button" class="btn-danger" onclick="document.getElementById('closeModal').classList.add('modal-overlay--show')">
                Close Account
              </button>
              <?php else: ?>
              <button type="button" class="btn-reopen" onclick="reopenAccount()">
                Reopen Account
              </button>
              <?php endif; ?>
            <?php endif; ?>
          </div>

        </form>
      </div>

    </div>
  </div>

  <!-- ── Account Closure Modal ── -->
  <div class="modal-overlay" id="closeModal">
    <div class="modal-box">
      <h3>Close Staff Account</h3>
      <p>This will deactivate the account and record the reason. The staff member will no longer be able to log in. You can reopen the account later if needed.</p>

      <form method="POST" id="closeForm">
        <input type="hidden" name="form_action" value="close_account"/>

        <div class="form-group">
          <label class="form-label">Reason for Closure *</label>
          <select class="form-select" name="closure_reason" required>
            <option value="">Select reason</option>
            <?php foreach ($closureReasonLabels as $val => $lbl): ?>
            <option value="<?php echo $val; ?>"><?php echo $lbl; ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="modal-actions">
          <button type="submit" class="btn-danger">Confirm — Close Account</button>
          <button type="button" class="btn-cancel"
                  onclick="document.getElementById('closeModal').classList.remove('modal-overlay--show')">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Reopen Account Form (hidden) ── -->
  <form method="POST" id="reopenForm" style="display:none">
    <input type="hidden" name="form_action" value="reopen_account"/>
  </form>

  <script src="../assets/js/admin.js"></script>
  <script>
    var roleSelect              = document.getElementById('role');
    var sectionSelect           = document.getElementById('section');
    var departmentField         = document.getElementById('departmentField');
    var teacherAssignmentsField = document.getElementById('teacherAssignmentsField');
    var classField              = document.getElementById('classField');
    var gradeLevelOnlySelect    = document.getElementById('grade_level_only');
    var classOnlySelect         = document.getElementById('class_only');

    var departmentRoles        = ['hod', 'subject_teacher'];
    var teacherAssignmentRoles = ['subject_teacher'];
    var classRoles             = ['form_teacher'];

    var classesByGradeLevel = <?php echo json_encode($classesByGradeLevel); ?>;
    var gradeLevelLabels = {
      JSS1:'JSS 1', JSS2:'JSS 2', JSS3:'JSS 3',
      SSS1:'SSS 1', SSS2:'SSS 2', SSS3:'SSS 3'
    };

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
      if (teacherAssignmentRoles.indexOf(role) !== -1) {
        teacherAssignmentsField.classList.add('conditional-field--show');
      } else {
        teacherAssignmentsField.classList.remove('conditional-field--show');
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
      if (classRoles.indexOf(roleSelect.value) !== -1) updateGradeLevelOptions('');
    });
    gradeLevelOnlySelect.addEventListener('change', function () { updateClassOptions(''); });

    updateConditionalFields(true);

    function selectAllClasses(checked) {
      document.querySelectorAll('.teacher-assign-cb').forEach(function (cb) {
        cb.checked = checked;
      });
    }

    document.getElementById('generatePwBtn').addEventListener('click', function () {
      var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
      var pw = '';
      for (var i = 0; i < 12; i++) {
        pw += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      document.getElementById('new_password').value    = pw;
      document.getElementById('confirm_password').value = pw;
    });

    function reopenAccount() {
      if (confirm('Reopen this account? The staff member will be able to log in again.')) {
        document.getElementById('reopenForm').submit();
      }
    }

    /* Close modal when clicking overlay background */
    document.getElementById('closeModal').addEventListener('click', function (e) {
      if (e.target === this) this.classList.remove('modal-overlay--show');
    });
  </script>

</body>
</html>