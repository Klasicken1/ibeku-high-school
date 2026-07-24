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
       matching their users.department field. If specific
       classes are assigned via teacher_class_assignments,
       they can ONLY save for those classes.
     form_teacher — can ONLY save scores for students in their
       assigned class (users.class_assigned e.g. "SSS2A").
     section-locked roles — cannot touch the other section's
       grade levels.

   Position calculation:
     After saving, recomputes "position in class" for every
     student in this grade_level + class + subject + session +
     term group (not just the ones just submitted, since a new
     score can shift everyone else's rank). Ranking uses
     standard competition ranking on (ca1+ca2+exam): tied
     students share a position, and the next distinct total
     skips ahead accordingly (e.g. 1, 2, 2, 4).

   Returns JSON: { "success": true/false, "message": "...", "saved": N }
   ============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/corps-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

/* ── Dual auth: staff (admin) or corps member ──
   Checks which session cookie is actually present BEFORE starting
   either session, avoiding the unreliable pattern of blindly
   starting the default session first (see corps-letter.php for
   the same fix applied earlier). ── */
$admin = null;

if (isset($_COOKIE['ihs_corps'])) {
    corpsSessionStart();
    if (corpsLoggedIn()) {
        $corpsMember = currentCorpsMember();
        if (($corpsMember['status'] ?? 'active') !== 'active') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Your account is not active.']);
            exit;
        }
        /* Normalise into the same shape the rest of this file expects */
        $admin = [
            'id'             => $corpsMember['id'],
            'role'           => 'corps_member',
            'section'        => $corpsMember['section'] ?? 'both',
            'dept'           => $corpsMember['subject_taught'] ?? null,
            'class_assigned' => null,
        ];
    }
}

if ($admin === null) {
    require_once dirname(__DIR__) . '/includes/admin-auth.php';
    if (isLoggedIn()) {
        $admin = currentAdmin();
    }
}

if ($admin === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

$pdo = getDB();

/* Self-healing — same schema extension as corps-edit.php, kept here
   too so this endpoint never fatals if it's hit before that page
   has run once. */
try {
    $pdo->exec("ALTER TABLE teacher_class_assignments MODIFY COLUMN teacher_id INT UNSIGNED NULL");
} catch (PDOException $e) { /* already nullable */ }
try {
    $pdo->exec("ALTER TABLE teacher_class_assignments ADD COLUMN corps_member_id INT UNSIGNED NULL AFTER teacher_id");
} catch (PDOException $e) { /* already exists */ }
try {
    $pdo->exec(
        "ALTER TABLE teacher_class_assignments
         ADD CONSTRAINT fk_tca_corps FOREIGN KEY (corps_member_id) REFERENCES corps_members(id) ON DELETE CASCADE ON UPDATE CASCADE"
    );
} catch (PDOException $e) { /* already exists */ }

/* ── Allowed roles for this action ── */
$allowedRoles = [
    'superadmin', 'subject_teacher', 'form_teacher', 'vp_academics', 'section_admin',
    'dean', 'hod', 'vp_admin', 'vp_general', 'vp_student_affairs', 'corps_member',
];
if (!in_array($admin['role'], $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to enter results.']);
    exit;
}

/* ── Read and validate top-level inputs ── */
$gradeLevel = trim($_POST['grade_level'] ?? '');
$class      = trim($_POST['class']       ?? '');
$subjectId  = (int) ($_POST['subject_id'] ?? 0);
$session    = trim($_POST['session']     ?? '');
$term       = trim($_POST['term']        ?? '');

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

    /* ── Ensure result_scores has a position column (self-healing, same
       pattern as push_subscriptions.user_id in push-helper.php) ── */
    try {
        $pdo->exec(
            "ALTER TABLE result_scores
             ADD COLUMN position INT UNSIGNED NULL DEFAULT NULL AFTER grade"
        );
    } catch (PDOException $e) {
        /* Column already exists — fine */
    }

    /* ── Permission check: subject-restricted roles are locked to
       their own assigned subject (Subject Teacher, Dean, HOD, VPs
       — everyone entering results for a specific subject rather
       than the whole section, i.e. everyone except superadmin,
       form_teacher, vp_academics, and section_admin). ── */
    $subjectRestrictedRoles = ['subject_teacher', 'dean', 'hod', 'vp_admin', 'vp_general', 'vp_student_affairs', 'corps_member'];
    if (in_array($admin['role'], $subjectRestrictedRoles, true)) {
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

    /* ── Section check: locked roles cannot touch the other section ── */
    $gradeLevelSection = str_starts_with($gradeLevel, 'JSS') ? 'js' : 'ss';
    if ($admin['role'] !== 'superadmin' && $admin['section'] !== 'both' && $admin['section'] !== $gradeLevelSection) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot enter results for the other section.']);
        exit;
    }

    /* ── Form teacher check: locked to their assigned class only ── */
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

    /* ── Subject-restricted class check: if specific classes are assigned via
       teacher_class_assignments, verify the submitted class is one of them.
       If no assignments exist, open access to all classes in their section
       (already enforced by the section check above).
       Corps members are checked via corps_member_id (a separate nullable
       column, since teacher_id has an FK to users specifically and a
       corps member's id lives in a different table). ── */
    $classAssignmentCheckRoles = ['subject_teacher', 'dean', 'hod', 'vp_admin', 'vp_general', 'vp_student_affairs', 'corps_member'];
    if (in_array($admin['role'], $classAssignmentCheckRoles, true)) {
        $idColumn = $admin['role'] === 'corps_member' ? 'corps_member_id' : 'teacher_id';

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM teacher_class_assignments WHERE $idColumn = ?"
        );
        $countStmt->execute([$admin['id']]);
        $assignCount = (int) $countStmt->fetchColumn();

        if ($assignCount > 0) {
            $checkStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM teacher_class_assignments
                 WHERE $idColumn = ? AND grade_level = ? AND class = ?"
            );
            $checkStmt->execute([$admin['id'], $gradeLevel, $class]);

            if ((int) $checkStmt->fetchColumn() === 0) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You are not assigned to enter results for this class.',
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

        /* Server-side clamp — matches the client-side max attributes */
        $ca1  = isset($ca1Scores[$i])  ? max(0, min(15, (float) $ca1Scores[$i]))  : 0;
        $ca2  = isset($ca2Scores[$i])  ? max(0, min(15, (float) $ca2Scores[$i]))  : 0;
        $exam = isset($examScores[$i]) ? max(0, min(70, (float) $examScores[$i])) : 0;

        $total     = $ca1 + $ca2 + $exam;
        $gradeInfo = calculateGrade($total);

        /* ── Step 1: find or create the results header row ── */
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
        /* uploaded_by has a foreign key to users(id) specifically — a
           corps member's id lives in a different table entirely, so
           inserting it here would either violate the FK or, worse,
           silently misattribute the entry to an unrelated user who
           happens to share that numeric id. Pass NULL for corps
           members instead; no audit-trail regression for staff. */
        $uploaderId = $admin['role'] === 'corps_member' ? null : $admin['id'];

        $upsertScore->execute([
            $resultId, $subjectId, $ca1, $ca2, $exam,
            $gradeInfo['grade'], $gradeInfo['remark'], $uploaderId,
        ]);

        $savedCount++;
    }

    /* ── Step 3: recompute "position in class" for this subject/term group ──
       Scoped to the whole grade_level + class + subject + session + term
       group (not just the students just submitted), since new scores can
       shift everyone else's rank up or down. ── */
    $classParamForRank = $class !== '' ? $class : null;

    $rankStmt = $pdo->prepare(
        "SELECT rs.id, (rs.ca1_score + rs.ca2_score + rs.exam_score) AS total
         FROM result_scores rs
         JOIN results r ON r.id = rs.result_id
         WHERE rs.subject_id = ?
           AND r.grade_level = ?
           AND r.class <=> ?
           AND r.session = ?
           AND r.term = ?
         ORDER BY total DESC"
    );
    $rankStmt->execute([$subjectId, $gradeLevel, $classParamForRank, $session, $term]);
    $rankRows = $rankStmt->fetchAll();

    $updatePosition = $pdo->prepare('UPDATE result_scores SET position = ? WHERE id = ?');

    $rowCount      = 0;
    $currentRank   = 0;
    $previousTotal = null;

    foreach ($rankRows as $row) {
        $rowCount++;
        if ($previousTotal === null || (float) $row['total'] !== (float) $previousTotal) {
            $currentRank = $rowCount;
        }
        $updatePosition->execute([$currentRank, $row['id']]);
        $previousTotal = $row['total'];
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $savedCount . ' student score(s) saved successfully. Class positions have been recalculated. Results remain in draft until published.',
        'saved'   => $savedCount,
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('IHS save_result_scores error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred while saving.']);
}