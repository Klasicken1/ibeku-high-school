<?php
/* ============================================================
   IBEKU HIGH SCHOOL — APPROVE RESULTS API
   File: src/api/approve_results.php

   Accepts POST request with:
     action      — 'approve' | 'revoke'
     grade_level — e.g. SSS2
     class       — e.g. A
     session     — e.g. 2025/2026
     term        — first/second/third

   Accessible to: superadmin, form_teacher (own class only)

   Sets is_approved = 1/0, approved_by, approved_at on all
   results rows for the specified grade_level+class+session+term.

   Returns JSON: { "success": true/false, "message": "...", "updated": N }
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

if (!in_array($admin['role'], ['superadmin', 'form_teacher'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to approve results.']);
    exit;
}

$action     = trim($_POST['action']      ?? '');
$gradeLevel = trim($_POST['grade_level'] ?? '');
$class      = trim($_POST['class']       ?? '');
$session    = trim($_POST['session']     ?? '');
$term       = trim($_POST['term']        ?? '');

$validGradeLevels = ['JSS1','JSS2','JSS3','SSS1','SSS2','SSS3'];
$validTerms       = ['first','second','third'];
$validActions     = ['approve','revoke'];

if (!in_array($action, $validActions, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']); exit;
}
if (!in_array($gradeLevel, $validGradeLevels, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid grade level.']); exit;
}
if ($class === '') {
    echo json_encode(['success' => false, 'message' => 'Class is required.']); exit;
}
if (!preg_match('/^\d{4}\/\d{4}$/', $session)) {
    echo json_encode(['success' => false, 'message' => 'Invalid session format.']); exit;
}
if (!in_array($term, $validTerms, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid term.']); exit;
}

/* ── Form teacher can only approve their own assigned class ── */
if ($admin['role'] === 'form_teacher') {
    if (empty($admin['class_assigned'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No class assigned to your account.']);
        exit;
    }
    if (preg_match('/^(JSS[123]|SSS[123])([A-Z0-9]+)$/', $admin['class_assigned'], $m)) {
        if ($gradeLevel !== $m[1] || $class !== $m[2]) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'You can only approve results for your assigned class (' . htmlspecialchars($admin['class_assigned']) . ').',
            ]);
            exit;
        }
    }
}

try {
    $pdo = getDB();

    if ($action === 'approve') {
        /* Cannot approve if results are already published */
        $publishedStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM results r
             JOIN   students s ON s.id = r.student_id
             WHERE  s.grade_level = ? AND s.class = ? AND s.is_active = 1
             AND    r.session = ? AND r.term = ? AND r.is_published = 1"
        );
        $publishedStmt->execute([$gradeLevel, $class, $session, $term]);
        if ((int) $publishedStmt->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'These results are already published. Approval cannot be changed after publishing.',
            ]);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE results r
             JOIN   students s ON s.id = r.student_id
             SET    r.is_approved = 1,
                    r.approved_by = ?,
                    r.approved_at = NOW()
             WHERE  s.grade_level = ? AND s.class = ? AND s.is_active = 1
             AND    r.session = ? AND r.term = ?"
        );
        $stmt->execute([$admin['id'], $gradeLevel, $class, $session, $term]);
        $updated = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'message' => $updated . ' student result(s) approved for ' . htmlspecialchars($gradeLevel . ' ' . $class) . '. VP Academics can now publish.',
            'updated' => $updated,
        ]);

    } else {
        /* Revoke — cannot revoke after publishing */
        $publishedStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM results r
             JOIN   students s ON s.id = r.student_id
             WHERE  s.grade_level = ? AND s.class = ? AND s.is_active = 1
             AND    r.session = ? AND r.term = ? AND r.is_published = 1"
        );
        $publishedStmt->execute([$gradeLevel, $class, $session, $term]);
        if ((int) $publishedStmt->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'These results are already published. Approval cannot be revoked after publishing.',
            ]);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE results r
             JOIN   students s ON s.id = r.student_id
             SET    r.is_approved = 0,
                    r.approved_by = NULL,
                    r.approved_at = NULL
             WHERE  s.grade_level = ? AND s.class = ? AND s.is_active = 1
             AND    r.session = ? AND r.term = ?"
        );
        $stmt->execute([$gradeLevel, $class, $session, $term]);
        $updated = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'message' => 'Approval revoked for ' . $updated . ' student result(s) in ' . htmlspecialchars($gradeLevel . ' ' . $class) . '.',
            'updated' => $updated,
        ]);
    }

} catch (PDOException $e) {
    error_log('IHS approve_results error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}