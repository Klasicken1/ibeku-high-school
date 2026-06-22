<?php
/* ============================================================
   IBEKU HIGH SCHOOL — PUBLISH RESULTS API
   File: src/api/publish_results.php

   Accepts POST request with:
     mode         — 'class' | 'grade_level_cumulative' | 'bulk'
     grade_level  — e.g. JSS1
     class        — required for mode=class, ignored otherwise
     section      — required for mode=bulk ('js' or 'ss'), ignored otherwise
     session      — e.g. 2025/2026
     term         — first/second/third

   MODE: class
     Publishes one grade_level+class (classroom). Computes
     total_score/average_score per student, ranks within that
     classroom only, sets class_position + class_total_students.
     Does NOT touch grade_level_position.

   MODE: grade_level_cumulative
     Re-ranks ALL classes of one grade level together. Computes
     grade_level_position + grade_level_total_students across
     every active class of that grade level. Does NOT touch the
     class-level position — a grade level must already be
     class-published before cumulative ranking makes sense,
     since totals must exist first.

   MODE: bulk
     Loops every active class in a grade level (or every grade
     level in a section) and runs MODE: class on each one
     independently. Each classroom still ranks only against itself.

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

/* ── Section permission check helper ── */
function checkSectionPermission(array $admin, string $gradeLevelSection): bool {
    if ($admin['role'] === 'superadmin') return true;
    if ($admin['section'] === 'both') return true;
    return $admin['section'] === $gradeLevelSection;
}

$pdo = getDB();

/* ============================================================
   Shared helper — publish a single grade_level+class (classroom)
   Returns count of students published.
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

    if (empty($resultRows)) {
        return 0;
    }

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

        $totals[$row['result_id']] = [
            'total'   => $totalSum,
            'average' => $average,
        ];
    }

    uasort($totals, fn($a, $b) => $b['total'] <=> $a['total']);

    $position = 0;
    $lastTotal = null;
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
        /* ── Single classroom publish ── */
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

        $count = publishSingleClass($pdo, (int) $admin['id'], $gradeLevel, $class, $session, $term);

        if ($count === 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'No results found for this grade level, class, session, and term. Enter scores first.']);
            exit;
        }

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => $count . ' student result(s) published for ' . htmlspecialchars($gradeLevel) . ' ' . htmlspecialchars($class) . '.',
            'published' => $count,
        ]);

    } elseif ($mode === 'grade_level_cumulative') {
        /* ── Cumulative ranking across all classes of one grade level ──
           Requires results already class-published (total_score must exist) */
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
                'message' => 'No published class results found for ' . htmlspecialchars($gradeLevel) . '. Publish each class individually first, then run cumulative ranking.',
            ]);
            exit;
        }

        /* Rank by total_score across the whole grade level */
        usort($rows, fn($a, $b) => (float) $b['total_score'] <=> (float) $a['total_score']);

        $position = 0;
        $lastTotal = null;
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
            'success' => true,
            'message' => 'Cumulative grade level ranking calculated for ' . $totalStudents . ' students across all classes of ' . htmlspecialchars($gradeLevel) . '.',
            'published' => $totalStudents,
        ]);

    } elseif ($mode === 'bulk') {
        /* ── Bulk: publish every active class in a grade level, or every grade level+class in a section ── */
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

        $totalPublished = 0;
        $classesProcessed = 0;
        $skipped = [];

        foreach ($gradeLevelsToProcess as $gl) {
            $glSection = str_starts_with($gl, 'JSS') ? 'js' : 'ss';
            if (!checkSectionPermission($admin, $glSection)) {
                continue; // silently skip sections this admin can't touch
            }

            $classStmt = $pdo->prepare("SELECT class FROM class_arms WHERE grade_level = ? AND is_active = 1 ORDER BY class ASC");
            $classStmt->execute([$gl]);
            $classes = $classStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($classes as $className) {
                $count = publishSingleClass($pdo, (int) $admin['id'], $gl, $className, $session, $term);
                if ($count > 0) {
                    $totalPublished += $count;
                    $classesProcessed++;
                } else {
                    $skipped[] = $gl . ' ' . $className;
                }
            }
        }

        $pdo->commit();

        $message = $totalPublished . ' student result(s) published across ' . $classesProcessed . ' class(es).';
        if (!empty($skipped)) {
            $message .= ' Skipped (no scores entered): ' . implode(', ', $skipped) . '.';
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