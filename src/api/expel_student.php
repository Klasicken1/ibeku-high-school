<?php
/* ============================================================
   IBEKU HIGH SCHOOL — EXPEL STUDENT API
   File: src/api/expel_student.php

   Standalone expulsion endpoint — used from students-edit.php
   expel button when a more direct single-student expulsion
   is needed without going through the promote page.

   Accessible to: superadmin, principal
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$admin = currentAdmin();

if (!in_array($admin['role'], ['superadmin', 'principal'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only Principals and the System Administrator can expel students.']);
    exit;
}

$studentId = (int) ($_POST['student_id'] ?? 0);
$reason    = trim($_POST['reason'] ?? '');

if ($studentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID.']); exit;
}
if ($reason === '') {
    echo json_encode(['success' => false, 'message' => 'A reason for expulsion is required.']); exit;
}

try {
    $pdo = getDB();

    $stStmt = $pdo->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
    $stStmt->execute([$studentId]);
    $student = $stStmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']); exit;
    }
    if ($student['status'] === 'expelled') {
        echo json_encode(['success' => false, 'message' => 'This student is already expelled.']); exit;
    }

    $pdo->beginTransaction();

    $pdo->prepare(
        'UPDATE students SET
            status = \'expelled\', is_active = 0,
            status_reason = ?, status_changed_at = NOW(), status_changed_by = ?,
            updated_at = NOW()
         WHERE id = ?'
    )->execute([$reason, $admin['id'], $studentId]);

    $pdo->prepare(
        'INSERT INTO student_history
            (student_id, event_type, from_grade_level, from_class, to_grade_level, to_class, reason, recorded_by)
         VALUES (?, \'expulsion\', ?, ?, ?, ?, ?, ?)'
    )->execute([
        $studentId,
        $student['grade_level'], $student['class'],
        $student['grade_level'], $student['class'],
        $reason, $admin['id'],
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Student expelled and account deactivated. The expulsion has been recorded in the student history.',
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('IHS expel_student error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}