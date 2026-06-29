<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PROMOTE STUDENTS API
   File: src/api/promote_students.php

   Accepts POST:
     student_ids[]     — array of student IDs
     actions[id]       — per-student action: promote|retain|demote|expel|graduate
     bulk_action       — optional: single action for all (used from single-student form)
     target_class      — optional target class letter for promoted/demoted students
     reason            — optional reason text
     redirect          — where to redirect after success
     bulk_grade_level  — for bulk operations, the source grade level
     bulk_class        — for bulk operations, the source class

   Accessible to: superadmin, principal (expel only), form_teacher
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

requireLogin();

$admin = currentAdmin();

if (!in_array($admin['role'], ['superadmin', 'principal', 'form_teacher'], true)) {
    $_SESSION['admin_error'] = 'You do not have permission to perform this action.';
    header('Location: ../admin/students.php');
    exit;
}

$pdo = getDB();

$studentIds  = $_POST['student_ids']    ?? [];
$actions     = $_POST['actions']        ?? [];
$bulkAction  = trim($_POST['bulk_action']  ?? '');
$targetClass = trim($_POST['target_class'] ?? '');
$reason      = trim($_POST['reason']       ?? '');
$redirect    = trim($_POST['redirect']     ?? 'students.php');

/* Whitelist redirect */
$allowedRedirects = ['students.php','students-edit.php'];
$redirectBase = explode('?', $redirect)[0];
$redirectBase = basename($redirectBase);
if (!in_array($redirectBase, $allowedRedirects, true)) {
    $redirect = 'students.php';
}

$promotionMap = [
    'JSS1' => 'JSS2', 'JSS2' => 'JSS3', 'JSS3' => 'SSS1',
    'SSS1' => 'SSS2', 'SSS2' => 'SSS3', 'SSS3' => null,
];
$demotionMap = [
    'JSS2' => 'JSS1', 'JSS3' => 'JSS2', 'SSS1' => 'JSS3',
    'SSS2' => 'SSS1', 'SSS3' => 'SSS2',
];

$validActions = ['promote','retain','demote','expel','graduate'];

if (empty($studentIds) || !is_array($studentIds)) {
    $_SESSION['admin_error'] = 'No students selected.';
    header('Location: ../admin/' . $redirect);
    exit;
}

try {
    $pdo->beginTransaction();

    $processed = 0;
    $skipped   = 0;

    foreach ($studentIds as $sid) {
        $sid = (int) $sid;
        if ($sid <= 0) continue;

        /* Determine action for this student */
        $action = $bulkAction ?: ($actions[$sid] ?? '');
        if (!in_array($action, $validActions, true)) { $skipped++; continue; }

        /* Load student */
        $stStmt = $pdo->prepare('SELECT * FROM students WHERE id = ? AND is_active = 1 LIMIT 1');
        $stStmt->execute([$sid]);
        $s = $stStmt->fetch();
        if (!$s) { $skipped++; continue; }

        /* Form teacher restriction */
        if ($admin['role'] === 'form_teacher' && !empty($admin['class_assigned'])) {
            if (preg_match('/^(JSS[123]|SSS[123])([A-Z0-9]+)$/', $admin['class_assigned'], $m)) {
                if ($s['grade_level'] !== $m[1] || $s['class'] !== $m[2]) {
                    $skipped++; continue;
                }
            }
        }

        /* Principal can only expel — not promote/retain/demote */
        if ($admin['role'] === 'principal' && $action !== 'expel') {
            $skipped++; continue;
        }

        $fromGL    = $s['grade_level'];
        $fromClass = $s['class'];
        $toGL      = $fromGL;
        $toClass   = $fromClass;
        $eventType = $action;
        $newStatus = $s['status'];
        $isActive  = 1;

        if ($action === 'promote') {
            $nextGL = $promotionMap[$fromGL] ?? null;
            if (!$nextGL) { /* SSS3 → graduate instead */ $action = 'graduate'; }
            else {
                $toGL    = $nextGL;
                $toClass = $targetClass ?: $fromClass;
                $eventType = 'promotion';
            }
        }

        if ($action === 'graduate') {
            $toGL      = $fromGL; $toClass = $fromClass;
            $eventType = 'graduation';
            $newStatus = 'graduated';
        }

        if ($action === 'retain') {
            $eventType = 'retention';
            /* grade level and class unchanged */
        }

        if ($action === 'demote') {
            if ($admin['role'] !== 'superadmin') { $skipped++; continue; }
            $prevGL = $demotionMap[$fromGL] ?? null;
            if (!$prevGL) { $skipped++; continue; }
            $toGL    = $prevGL;
            $toClass = $targetClass ?: $fromClass;
            $eventType = 'demotion';
        }

        if ($action === 'expel') {
            if (!in_array($admin['role'], ['superadmin','principal'], true)) {
                $skipped++; continue;
            }
            $eventType = 'expulsion';
            $newStatus = 'expelled';
            $isActive  = 0;
        }

        /* Update student */
        $updateStmt = $pdo->prepare(
            'UPDATE students SET
                grade_level = ?, class = ?, section = ?, status = ?,
                is_active = ?,
                status_reason = CASE WHEN ? != \'\' THEN ? ELSE status_reason END,
                status_changed_at = CASE WHEN ? IN (\'expulsion\',\'graduation\') THEN NOW() ELSE status_changed_at END,
                status_changed_by = CASE WHEN ? IN (\'expulsion\',\'graduation\') THEN ? ELSE status_changed_by END,
                updated_at = NOW()
             WHERE id = ?'
        );
        $section = str_starts_with($toGL, 'JSS') ? 'js' : 'ss';
        $updateStmt->execute([
            $toGL, $toClass, $section, $newStatus, $isActive,
            $reason, $reason,
            $eventType, $eventType, $admin['id'],
            $sid,
        ]);

        /* Record in student_history */
        $histStmt = $pdo->prepare(
            'INSERT INTO student_history
                (student_id, event_type, from_grade_level, from_class, to_grade_level, to_class, reason, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $histStmt->execute([
            $sid, $eventType, $fromGL, $fromClass,
            $toGL, $toClass,
            $reason ?: null, $admin['id'],
        ]);

        $processed++;
    }

    $pdo->commit();

    $_SESSION['admin_success'] = $processed . ' student(s) updated successfully.' .
        ($skipped > 0 ? ' ' . $skipped . ' skipped.' : '');
    header('Location: ../admin/' . $redirect);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('IHS promote_students error: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'A server error occurred. Please try again.';
    header('Location: ../admin/students.php');
    exit;
}