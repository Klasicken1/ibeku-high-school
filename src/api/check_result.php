<?php
/* ============================================================
   IBEKU HIGH SCHOOL — CHECK RESULT API
   File: src/api/check_result.php

   Accepts POST request with:
     admission_number — the student's admission number
     session           — e.g. 2025/2026
     term              — first/second/third

   Returns JSON:
     { "success": true,  "student": {...}, "subjects": [...], "history": [...] }
     { "success": false, "message": "..." }

   Expelled students can still view past results but receive
   an expulsion notice in the student object (status field).
   History events (promotions, retentions, expulsions etc.)
   are returned so the frontend can display a student timeline.

   Includes BOTH class-level position (class_position/
   class_total_students) and grade-level-wide position
   (grade_level_position/grade_level_total_students) — the
   latter is NULL until VP Academics runs grade_level_cumulative.
   Frontend displays both when grade_level_position is not null,
   e.g. "3rd in JSS1 B (15th in JSS1 overall)", and falls back
   to class-only when null.
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
    /* ── Find the student — include expelled/graduated students ──
       We do NOT filter by is_active=1 here because expelled/graduated
       students can still view past results. ── */
    $studentStmt = $pdo->prepare(
        'SELECT id, admission_number, first_name, last_name, other_name,
                grade_level, class, section, status, status_reason, status_changed_at
         FROM   students
         WHERE  admission_number = ?
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

    /* ── Load student history events ── */
    $historyStmt = $pdo->prepare(
        "SELECT sh.event_type, sh.from_grade_level, sh.from_class,
                sh.to_grade_level, sh.to_class, sh.reason, sh.recorded_at
         FROM   student_history sh
         WHERE  sh.student_id = ?
         ORDER  BY sh.recorded_at ASC"
    );
    $historyStmt->execute([$student['id']]);
    $historyRows = $historyStmt->fetchAll();

    $gradeLevelLabels = [
        'JSS1'=>'JSS 1','JSS2'=>'JSS 2','JSS3'=>'JSS 3',
        'SSS1'=>'SSS 1','SSS2'=>'SSS 2','SSS3'=>'SSS 3',
    ];

    $history = array_map(function ($row) use ($gradeLevelLabels) {
        $fromLabel = $row['from_grade_level']
            ? (($gradeLevelLabels[$row['from_grade_level']] ?? $row['from_grade_level']) . ' ' . $row['from_class'])
            : null;
        $toLabel = $row['to_grade_level']
            ? (($gradeLevelLabels[$row['to_grade_level']] ?? $row['to_grade_level']) . ' ' . $row['to_class'])
            : null;

        return [
            'event_type'  => $row['event_type'],
            'from'        => $fromLabel,
            'to'          => $toLabel,
            'reason'      => $row['reason'],
            'date'        => date('d M Y', strtotime($row['recorded_at'])),
        ];
    }, $historyRows);

    /* ── Build response ── */
    $classDisplay = trim($student['grade_level'] . ' ' . ($student['class'] ?? ''));

    $fullName = trim($student['first_name'] . ' ' .
        ($student['other_name'] ? $student['other_name'] . ' ' : '') .
        $student['last_name']);

    /* Principal auto-fill — chosen by the student's section (js/ss) */
    $principal = getPrincipalAssets($student['section'] ?? 'ss');

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
            'principal_name'                => $principal['name'],
            'principal_signature'           => $principal['signature'] ?: null,
            'principal_stamp'               => $principal['stamp']     ?: null,
            'next_term_resumption'          => $result['next_term_resumption'],
            /* Student status — frontend shows notice for non-active statuses */
            'status'                        => $student['status'],
            'status_reason'                 => $student['status_reason'],
            'status_changed_at'             => $student['status_changed_at']
                ? date('d M Y', strtotime($student['status_changed_at']))
                : null,
        ],
        'subjects' => $subjects,
        'history'  => $history,
    ]);

} catch (PDOException $e) {
    error_log('IHS check_result error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
}