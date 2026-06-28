<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SAVE RESULT SCORES API
   File: src/api/save_result_scores.php

   Accepts POST request with:
     student_id[]   — array of student IDs
     score_ca1[]    — array of 1st test scores (max 15)
     score_ca2[]    — array of 2nd test scores (max 15)
     score_exam[]   — array of exam scores (max 70)
     grade_level    — e.g. SSS2
     class          — e.g. A
     subject_id     — which subject these scores belong to
     session        — e.g. 2025/2026
     term           — first/second/third

   Permission rules enforced server-side:
     subject_teacher — can ONLY save scores for the subject
       matching their users.department field.
     form_teacher — can ONLY save scores for students in their
       assigned class (users.class_assigned e.g. "SSS2A").
     section-locked roles — cannot touch the other section's
       grade levels.

   Returns JSON: { "success": true/false, "message": "...", "saved": N }
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

/* ── Allowed roles for this action ── */
$allowedRoles = ['superadmin', 'subject_teacher', 'form_teacher', 'vp_academics'];
if (!in_array($admin['role'], $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to enter results.']);
    exit;
}

/* ── Read and validate top-level inputs ── */
$gradeLevel = trim($_POST['grade_level'] ?? '');
$class      = trim($_POST['class']       ?? '');
$subjectId  = (int) ($_POST['subject_id'] ?? 0);
$session    = trim($_POST['session']    ?? '');
$term       = trim($_POST['term']       ?? '');

$studentIds = $_POST['student_id']  ?? [];
$ca1Scores  = $_POST['score_ca1']   ?? [];
$ca2Scores  = $_POST['score_ca2']   ?? [];
$examScores = $_POST['score_exam']  ?? [];

$validGradeLevels = ['JSS1','JSS2','JSS3','SSS1','SSS2','SSS3'];
$validTerms       = ['first','second','third'];

if (!in_array($gradeLevel, $validGradeLevels, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid grade level.']);
    exit;
}
if ($subjectId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid subject.']);
    exit;
}
if (!preg_match('/^\d{4}\/\d{4}$/', $session)) {
    echo json_encode(['success' => false, 'message' => 'Invalid session format.']);
    exit;
}
if (!in_array($term, $validTerms, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid term.']);
    exit;
}
if (empty($studentIds) || !is_array($studentIds)) {
    echo json_encode(['success' => false, 'message' => 'No student scores submitted.']);
    exit;
}

try {
    $pdo = getDB();

    /* ── Permission check: subject_teacher restricted to their own subject ── */
    if ($admin['role'] === 'subject_teacher') {
        $subjStmt = $pdo->prepare('SELECT name FROM subjects WHERE id = ?');
        $subjStmt->execute([$subjectId]);
        $subjectName = $subjStmt->fetchColumn();

        if (!$subjectName || strcasecmp((string) $subjectName, (string) $admin['dept']) !== 0) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'You can only enter scores for your assigned subject (' . htmlspecialchars((string) $admin['dept']) . ').',
            ]);
            exit;
        }
    }

    /* ── Section check: a Dean/teacher assigned to JS can't touch SS grade levels etc. ── */
    $gradeLevelSection = str_starts_with($gradeLevel, 'JSS') ? 'js' : 'ss';
    if ($admin['role'] !== 'superadmin' && $admin['section'] !== 'both' && $admin['section'] !== $gradeLevelSection) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot enter results for the other section.']);
        exit;
    }

    /* ── Form teacher check: locked to their own assigned class only ──
       class_assigned is stored as e.g. "SSS2A" — parse into grade_level + class
       and verify both match what's being submitted. ── */
    if ($admin['role'] === 'form_teacher' && !empty($admin['class_assigned'])) {
        if (preg_match('/^(JSS[123]|SSS[123])([A-Z0-9]+)$/', $admin['class_assigned'], $m)) {
            $assignedGradeLevel = $m[1];
            $assignedClass      = $m[2];

            if ($gradeLevel !== $assignedGradeLevel || $class !== $assignedClass) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You can only enter results for your assigned class (' .
                                 htmlspecialchars($admin['class_assigned']) . ').',
                ]);
                exit;
            }
        }
    }

    $pdo->beginTransaction();

    $savedCount = 0;

    foreach ($studentIds as $i => $studentId) {
        $studentId = (int) $studentId;
        if ($studentId <= 0) continue;

        $ca1  = isset($ca1Scores[$i])  ? max(0, min(15, (float) $ca1Scores[$i]))  : 0;
        $ca2  = isset($ca2Scores[$i])  ? max(0, min(15, (float) $ca2Scores[$i]))  : 0;
        $exam = isset($examScores[$i]) ? max(0, min(70, (float) $examScores[$i])) : 0;

        $total = $ca1 + $ca2 + $exam;
        $gradeInfo = calculateGrade($total);

        /* ── Step 1: find or create the results header row for this student/term ── */
        $resultStmt = $pdo->prepare(
            'SELECT id FROM results WHERE student_id = ? AND session = ? AND term = ? LIMIT 1'
        );
        $resultStmt->execute([$studentId, $session, $term]);
        $resultId = $resultStmt->fetchColumn();

        if (!$resultId) {
            $insertResult = $pdo->prepare(
                'INSERT INTO results (student_id, session, term, grade_level, class, is_published)
                 VALUES (?, ?, ?, ?, ?, 0)'
            );
            $insertResult->execute([$studentId, $session, $term, $gradeLevel, $class ?: null]);
            $resultId = (int) $pdo->lastInsertId();
        }

        /* ── Step 2: upsert the subject score row ── */
        $upsertScore = $pdo->prepare(
            'INSERT INTO result_scores
                (result_id, subject_id, ca1_score, ca2_score, exam_score, grade, remark, uploaded_by, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                ca1_score   = VALUES(ca1_score),
                ca2_score   = VALUES(ca2_score),
                exam_score  = VALUES(exam_score),
                grade       = VALUES(grade),
                remark      = VALUES(remark),
                uploaded_by = VALUES(uploaded_by),
                uploaded_at = NOW()'
        );
        $upsertScore->execute([
            $resultId, $subjectId, $ca1, $ca2, $exam,
            $gradeInfo['grade'], $gradeInfo['remark'], $admin['id'],
        ]);

        $savedCount++;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $savedCount . ' student score(s) saved successfully. Results remain in draft until published.',
        'saved'   => $savedCount,
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('IHS save_result_scores error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred while saving.']);
}