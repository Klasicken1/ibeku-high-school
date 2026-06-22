<?php
/* ============================================================
   IBEKU HIGH SCHOOL — CHECK RESULT API
   File: src/api/check_result.php

   Accepts POST request with:
     admission_number — the student's admission number
     session           — e.g. 2025/2026
     term              — first/second/third

   Returns JSON:
     { "success": true,  "student": {...}, "subjects": [...] }
     { "success": false, "message": "..." }

   Includes grade_level AND class in the response so the public
   result checker and printable sheet display e.g. "JSS1 B",
   not just "JSS1" — matters because multiple classes can share
   a grade level.

   Includes BOTH class-level position (class_position/
   class_total_students) and grade-level-wide position
   (grade_level_position/grade_level_total_students) — the
   latter is NULL until a superadmin or VP Academics runs a
   grade_level_cumulative publish via publish_results.php.
   Frontend should display both when grade_level_position is
   not null, e.g. "3rd in JSS1 B (15th in JSS1 overall)", and
   fall back to class-only when it's null.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$pdo = getDB();

$admissionNumber = trim($_POST['admission_number'] ?? '');
$session         = trim($_POST['session']          ?? '');
$term            = trim($_POST['term']             ?? '');

$validTerms = ['first', 'second', 'third'];

if ($admissionNumber === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your admission number.']);
    exit;
}
if (!preg_match('/^\d{4}\/\d{4}$/', $session)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid academic session.']);
    exit;
}
if (!in_array($term, $validTerms, true)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid term.']);
    exit;
}

try {
    /* ── Find the student ── */
    $studentStmt = $pdo->prepare(
        'SELECT id, admission_number, first_name, last_name, other_name,
                grade_level, class, section
         FROM   students
         WHERE  admission_number = ? AND is_active = 1
         LIMIT  1'
    );
    $studentStmt->execute([$admissionNumber]);
    $student = $studentStmt->fetch();

    if (!$student) {
        echo json_encode([
            'success' => false,
            'message' => 'No student found with that admission number. Please check and try again.',
        ]);
        exit;
    }

    /* ── Find the published result for this session/term ── */
    $resultStmt = $pdo->prepare(
        'SELECT id, class_total_students, class_position,
                grade_level_position, grade_level_total_students,
                average_score, total_score,
                form_teacher_comment, principal_comment, next_term_resumption
         FROM   results
         WHERE  student_id = ? AND session = ? AND term = ? AND is_published = 1
         LIMIT  1'
    );
    $resultStmt->execute([$student['id'], $session, $term]);
    $result = $resultStmt->fetch();

    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'No published result found for that term and session yet. Please check back later or contact the school office.',
        ]);
        exit;
    }

    /* ── Load subject scores ── */
    $scoresStmt = $pdo->prepare(
        'SELECT s.name AS subject_name, rs.ca1_score, rs.ca2_score, rs.exam_score,
                rs.total_score, rs.grade, rs.remark
         FROM   result_scores rs
         JOIN   subjects s ON s.id = rs.subject_id
         WHERE  rs.result_id = ?
         ORDER  BY s.name ASC'
    );
    $scoresStmt->execute([$result['id']]);
    $subjectRows = $scoresStmt->fetchAll();

    $subjects = array_map(function ($row) {
        return [
            'name'   => $row['subject_name'],
            'ca1'    => (float) $row['ca1_score'],
            'ca2'    => (float) $row['ca2_score'],
            'exam'   => (float) $row['exam_score'],
            'score'  => (float) $row['total_score'],
            'grade'  => $row['grade'],
            'remark' => $row['remark'],
        ];
    }, $subjectRows);

    /* ── Build the class display string — "JSS1 B", not just "JSS1" ── */
    $classDisplay = trim($student['grade_level'] . ' ' . ($student['class'] ?? ''));

    $fullName = trim($student['first_name'] . ' ' .
        ($student['other_name'] ? $student['other_name'] . ' ' : '') .
        $student['last_name']);

    echo json_encode([
        'success' => true,
        'student' => [
            'name'                          => $fullName,
            'admission_number'              => $student['admission_number'],
            'class'                         => $classDisplay,
            'grade_level_only'              => $student['grade_level'],
            'class_only'                    => $student['class'],
            'session'                       => $session,
            'term'                          => ucfirst($term) . ' Term',
            'class_position'                => $result['class_position'],
            'class_total_students'          => $result['class_total_students'],
            'grade_level_position'          => $result['grade_level_position'],
            'grade_level_total_students'    => $result['grade_level_total_students'],
            'average_score'                 => $result['average_score'],
            'total_score'                   => $result['total_score'],
            'form_teacher_comment'          => $result['form_teacher_comment'],
            'principal_comment'             => $result['principal_comment'],
            'next_term_resumption'          => $result['next_term_resumption'],
        ],
        'subjects' => $subjects,
    ]);

} catch (PDOException $e) {
    error_log('IHS check_result error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
}