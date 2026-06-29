<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PUBLISH RESULTS API
   File: src/api/publish_results.php

   Accepts POST request with:
     mode         — 'class' | 'grade_level_cumulative' | 'bulk'
     grade_level  — e.g. JSS1
     class        — required for mode=class
     section      — required for mode=bulk ('js' or 'ss')
     session      — e.g. 2025/2026
     term         — first/second/third

   Approval gate: mode=class requires ALL results for the
   requested grade_level+class to have is_approved=1 before
   publishing is allowed. VP Academics cannot bypass this.

   MODE: class
     Publishes one grade_level+class. Computes totals, ranks
     within that classroom, sets class_position +
     class_total_students.

   MODE: grade_level_cumulative
     Re-ranks ALL published classes of one grade level together.
     Sets grade_level_position + grade_level_total_students.
     Requires all classes to be class-published first.

   MODE: bulk
     Loops every active class in a grade level (or section) and
     runs MODE: class on each one. Skips unapproved classes.

   Accessible to: superadmin, vp_academics
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

if (!in_array($admin['role'], ['superadmin', 'vp_academics'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to publish results.']);
    exit;
}

$mode       = trim($_POST['mode']        ?? 'class');
$gradeLevel = trim($_POST['grade_level'] ?? '');
$class      = trim($_POST['class']       ?? '');
$section    = trim($_POST['section']     ?? '');
$session    = trim($_POST['session']     ?? '');
$term       = trim($_POST['term']        ?? '');

$validGradeLevels = ['JSS1','JSS2','JSS3','SSS1','SSS2','SSS3'];
$validTerms       = ['first','second','third'];
$validModes       = ['class', 'grade_level_cumulative', 'bulk'];

if (!in_array($mode, $validModes, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid publish mode.']);
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

function checkSectionPermission(array $admin, string $gradeLevelSection): bool {
    if ($admin['role'] === 'superadmin') return true;
    if ($admin['section'] === 'both') return true;
    return $admin['section'] === $gradeLevelSection;
}

/* ── Check approval status for a grade_level+class ──
   Returns ['approved' => N, 'total' => N, 'unapproved_names' => [...]] */
function getApprovalStatus(PDO $pdo, string $gradeLevel, string $class, string $session, string $term): array {
    $stmt = $pdo->prepare(
        "SELECT r.is_approved,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name
         FROM   results r
         JOIN   students s ON s.id = r.student_id
         WHERE  s.grade_level = ? AND s.class = ? AND s.is_active = 1
         AND    r.session = ? AND r.term = ?"
    );
    $stmt->execute([$gradeLevel, $class, $session, $term]);
    $rows = $stmt->fetchAll();

    $total      = count($rows);
    $approved   = 0;
    $unapproved = [];

    foreach ($rows as $row) {
        if ((int) $row['is_approved'] === 1) {
            $approved++;
        } else {
            $unapproved[] = $row['student_name'];
        }
    }

    return ['approved' => $approved, 'total' => $total, 'unapproved' => $unapproved];
}

$pdo = getDB();

/* ============================================================
   Shared helper — publish a single grade_level+class
   ============================================================ */
function publishSingleClass(PDO $pdo, int $adminId, string $gradeLevel, string $class, string $session, string $term): int {
    $stmt = $pdo->prepare(
        "SELECT r.id AS result_id, r.student_id
         FROM   results r
         JOIN   students s ON s.id = r.student_id
         WHERE  s.grade_level = ? AND s.class = ? AND s.is_active = 1
         AND    r.session = ? AND r.term = ?"
    );
    $stmt->execute([$gradeLevel, $class, $session, $term]);
    $resultRows = $stmt->fetchAll();

    if (empty($resultRows)) return 0;

    $totals = [];
    foreach ($resultRows as $row) {
        $sumStmt = $pdo->prepare(
            'SELECT COUNT(*) AS subject_count, COALESCE(SUM(total_score), 0) AS total_sum
             FROM   result_scores WHERE result_id = ?'
        );
        $sumStmt->execute([$row['result_id']]);
        $sum = $sumStmt->fetch();

        $subjectCount = (int) $sum['subject_count'];
        $totalSum     = (float) $sum['total_sum'];
        $average      = $subjectCount > 0 ? round($totalSum / $subjectCount, 2) : 0;

        $totals[$row['result_id']] = ['total' => $totalSum, 'average' => $average];
    }

    uasort($totals, fn($a, $b) => $b['total'] <=> $a['total']);

    $position      = 0;
    $lastTotal     = null;
    $totalStudents = count($totals);

    $updateStmt = $pdo->prepare(
        'UPDATE results SET
            total_score = ?, average_score = ?, class_position = ?, class_total_students = ?,
            is_published = 1, published_at = NOW(), published_by = ?
         WHERE id = ?'
    );

    $i = 0;
    foreach ($totals as $resultId => $data) {
        if ($lastTotal === null || $data['total'] < $lastTotal) {
            $position = $i + 1;
        }
        $updateStmt->execute([$data['total'], $data['average'], $position, $totalStudents, $adminId, $resultId]);
        $lastTotal = $data['total'];
        $i++;
    }

    return $totalStudents;
}

try {
    $pdo->beginTransaction();

    if ($mode === 'class') {

        if (!in_array($gradeLevel, $validGradeLevels, true) || $class === '') {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Invalid grade level or class.']);
            exit;
        }

        $gradeLevelSection = str_starts_with($gradeLevel, 'JSS') ? 'js' : 'ss';
        if (!checkSectionPermission($admin, $gradeLevelSection)) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You cannot publish results for the other section.']);
            exit;
        }

        /* ── Approval gate ── */
        $approval = getApprovalStatus($pdo, $gradeLevel, $class, $session, $term);

        if ($approval['total'] === 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'No results found for ' . htmlspecialchars($gradeLevel . ' ' . $class) . '. Enter scores first.']);
            exit;
        }

        if ($approval['approved'] < $approval['total']) {
            $pdo->rollBack();
            $unapprovedCount = $approval['total'] - $approval['approved'];
            echo json_encode([
                'success' => false,
                'message' => 'Cannot publish — ' . $unapprovedCount . ' student result(s) have not been approved by the Form Teacher yet. The Form Teacher must approve all results before publishing.',
            ]);
            exit;
        }

        $count = publishSingleClass($pdo, (int) $admin['id'], $gradeLevel, $class, $session, $term);

        $pdo->commit();
        echo json_encode([
            'success'   => true,
            'message'   => $count . ' student result(s) published for ' . htmlspecialchars($gradeLevel . ' ' . $class) . '.',
            'published' => $count,
        ]);

    } elseif ($mode === 'grade_level_cumulative') {

        if (!in_array($gradeLevel, $validGradeLevels, true)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Invalid grade level.']);
            exit;
        }

        $gradeLevelSection = str_starts_with($gradeLevel, 'JSS') ? 'js' : 'ss';
        if (!checkSectionPermission($admin, $gradeLevelSection)) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You cannot publish results for the other section.']);
            exit;
        }

        /* Check all classes in this grade level are published first */
        $classStmt = $pdo->prepare(
            "SELECT ca.class,
                    COUNT(r.id) AS total_results,
                    SUM(CASE WHEN r.is_published = 1 THEN 1 ELSE 0 END) AS published_results
             FROM   class_arms ca
             LEFT JOIN students s ON s.grade_level = ca.grade_level AND s.class = ca.class AND s.is_active = 1
             LEFT JOIN results r ON r.student_id = s.id AND r.session = ? AND r.term = ?
             WHERE  ca.grade_level = ? AND ca.is_active = 1
             GROUP  BY ca.class"
        );
        $classStmt->execute([$session, $term, $gradeLevel]);
        $classStatuses = $classStmt->fetchAll();

        $unpublishedClasses = [];
        foreach ($classStatuses as $cs) {
            if ((int) $cs['total_results'] > 0 && (int) $cs['published_results'] < (int) $cs['total_results']) {
                $unpublishedClasses[] = $gradeLevel . ' ' . $cs['class'];
            }
        }

        if (!empty($unpublishedClasses)) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Cannot calculate grade level rankings — the following classes have unpublished results: ' . implode(', ', $unpublishedClasses) . '. Publish all classes first.',
            ]);
            exit;
        }

        /* Rank all published students across the grade level */
        $stmt = $pdo->prepare(
            "SELECT r.id AS result_id, r.total_score
             FROM   results r
             JOIN   students s ON s.id = r.student_id
             WHERE  s.grade_level = ? AND s.is_active = 1
             AND    r.session = ? AND r.term = ? AND r.is_published = 1"
        );
        $stmt->execute([$gradeLevel, $session, $term]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'No published results found for ' . htmlspecialchars($gradeLevel) . '. Publish each class individually first.',
            ]);
            exit;
        }

        usort($rows, fn($a, $b) => (float) $b['total_score'] <=> (float) $a['total_score']);

        $position      = 0;
        $lastTotal     = null;
        $totalStudents = count($rows);

        $updateStmt = $pdo->prepare(
            'UPDATE results SET grade_level_position = ?, grade_level_total_students = ? WHERE id = ?'
        );

        foreach ($rows as $i => $row) {
            $current = (float) $row['total_score'];
            if ($lastTotal === null || $current < $lastTotal) {
                $position = $i + 1;
            }
            $updateStmt->execute([$position, $totalStudents, $row['result_id']]);
            $lastTotal = $current;
        }

        $pdo->commit();
        echo json_encode([
            'success'   => true,
            'message'   => 'Grade level rankings calculated for ' . $totalStudents . ' students across all classes of ' . htmlspecialchars($gradeLevel) . '.',
            'published' => $totalStudents,
        ]);

    } elseif ($mode === 'bulk') {

        $gradeLevelsToProcess = [];

        if ($gradeLevel !== '' && in_array($gradeLevel, $validGradeLevels, true)) {
            $gradeLevelsToProcess = [$gradeLevel];
        } elseif ($section === 'js') {
            $gradeLevelsToProcess = ['JSS1', 'JSS2', 'JSS3'];
        } elseif ($section === 'ss') {
            $gradeLevelsToProcess = ['SSS1', 'SSS2', 'SSS3'];
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Specify a grade level or a valid section for bulk publish.']);
            exit;
        }

        $totalPublished   = 0;
        $classesProcessed = 0;
        $skipped          = [];
        $unapprovedSkipped = [];

        foreach ($gradeLevelsToProcess as $gl) {
            $glSection = str_starts_with($gl, 'JSS') ? 'js' : 'ss';
            if (!checkSectionPermission($admin, $glSection)) continue;

            $classStmt = $pdo->prepare(
                "SELECT class FROM class_arms WHERE grade_level = ? AND is_active = 1 ORDER BY class ASC"
            );
            $classStmt->execute([$gl]);
            $classes = $classStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($classes as $className) {
                /* Check approval before bulk-publishing each class */
                $approval = getApprovalStatus($pdo, $gl, $className, $session, $term);

                if ($approval['total'] === 0) {
                    $skipped[] = $gl . ' ' . $className . ' (no scores)';
                    continue;
                }

                if ($approval['approved'] < $approval['total']) {
                    $unapprovedSkipped[] = $gl . ' ' . $className . ' (not approved)';
                    continue;
                }

                $count = publishSingleClass($pdo, (int) $admin['id'], $gl, $className, $session, $term);
                if ($count > 0) {
                    $totalPublished += $count;
                    $classesProcessed++;
                } else {
                    $skipped[] = $gl . ' ' . $className . ' (no scores)';
                }
            }
        }

        $pdo->commit();

        $message = $totalPublished . ' student result(s) published across ' . $classesProcessed . ' class(es).';
        if (!empty($unapprovedSkipped)) {
            $message .= ' Skipped — awaiting form teacher approval: ' . implode(', ', $unapprovedSkipped) . '.';
        }
        if (!empty($skipped)) {
            $message .= ' Skipped — no scores entered: ' . implode(', ', $skipped) . '.';
        }

        echo json_encode([
            'success'   => true,
            'message'   => $message,
            'published' => $totalPublished,
        ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('IHS publish_results error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred while publishing.']);
}