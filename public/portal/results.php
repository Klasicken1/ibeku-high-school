<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT PORTAL RESULTS
   File: public/portal/results.php

   Shows the student's own published results only.
   Respects results_blocked flag — blocked students see
   a restriction message instead of results.
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/auth.php';

$student = requireStudentLogin();
$pdo     = getDB();

/* Refresh session to catch any admin changes */
refreshStudentSession($pdo);
$student = currentStudent();

/* ── Term/session filter ── */
$filterTerm    = $_GET['term']    ?? '';
$filterSession = $_GET['session'] ?? '';

$results      = [];
$termList     = [];
$sessionList  = [];

if (!$student['results_blocked']) {
    /* Available terms for this student */
    $termStmt = $pdo->prepare(
        "SELECT DISTINCT term, session FROM results
         WHERE student_id = ? AND status = 'published'
         ORDER BY session DESC, FIELD(term,'first','second','third') DESC"
    );
    $termStmt->execute([$student['id']]);
    $termList = $termStmt->fetchAll();

    /* Auto-select latest if not specified */
    if (empty($filterTerm) && !empty($termList)) {
        $filterTerm    = $termList[0]['term'];
        $filterSession = $termList[0]['session'];
    }

    if ($filterTerm && $filterSession) {
        $resStmt = $pdo->prepare(
            "SELECT r.*, sub.name AS subject_name
             FROM results r
             JOIN subjects sub ON sub.id = r.subject_id
             WHERE r.student_id = ?
               AND r.term       = ?
               AND r.session    = ?
               AND r.status     = 'published'
             ORDER BY sub.name ASC"
        );
        $resStmt->execute([$student['id'], $filterTerm, $filterSession]);
        $results = $resStmt->fetchAll();
    }
}

/* ── Compute summary stats ── */
$totalScore = 0; $count = 0; $highest = 0; $lowest = 100;
foreach ($results as $r) {
    $total = (float) ($r['total_score'] ?? 0);
    $totalScore += $total;
    $count++;
    if ($total > $highest) $highest = $total;
    if ($total < $lowest)  $lowest  = $total;
}
$average = $count > 0 ? round($totalScore / $count, 1) : 0;

function gradeFromScore(float $score): string {
    if ($score >= 70) return 'A';
    if ($score >= 60) return 'B';
    if ($score >= 50) return 'C';
    if ($score >= 45) return 'D';
    if ($score >= 40) return 'E';
    return 'F';
}

function remarkFromScore(float $score): string {
    if ($score >= 70) return 'Excellent';
    if ($score >= 60) return 'Very Good';
    if ($score >= 50) return 'Good';
    if ($score >= 45) return 'Credit';
    if ($score >= 40) return 'Pass';
    return 'Fail';
}

function scoreColor(float $score): string {
    if ($score >= 70) return '#1a7a3a';
    if ($score >= 50) return '#4a90d9';
    if ($score >= 40) return '#e8a020';
    return '#cc3333';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Results — Ibeku High School Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/portal.css"/>
</head>
<body>

<?php include dirname(__DIR__, 2) . '/src/includes/portal-nav.php'; ?>

<main class="portal-main">
  <div class="portal-inner">

    <div class="page-hero">
      <h1 class="page-hero__title">My Results</h1>
      <p class="page-hero__sub">
        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
        &nbsp;·&nbsp; <?php echo gradeLabel($student['grade_level']); ?>
        &nbsp;·&nbsp; <?php echo htmlspecialchars($student['class']); ?>
      </p>
    </div>

    <?php if ($student['results_blocked']): ?>
    <!-- ── Results blocked ── -->
    <div class="blocked-card">
      <div class="blocked-card__icon">🔒</div>
      <h2 class="blocked-card__title">Result Access Restricted</h2>
      <p class="blocked-card__body">
        Your access to results has been restricted by the school administration.
        <?php if ($student['results_blocked_reason']): ?>
        <br/><br/>
        <strong>Reason:</strong> <?php echo htmlspecialchars($student['results_blocked_reason']); ?>
        <?php endif; ?>
      </p>
      <p class="blocked-card__contact">
        Please contact the school office or your class teacher for assistance.
      </p>
    </div>

    <?php elseif (empty($termList)): ?>
    <!-- ── No results yet ── -->
    <div class="empty-portal">
      <div class="empty-portal__icon">📋</div>
      <h2>No results available yet</h2>
      <p>Your results will appear here once they are approved and published by the school.</p>
    </div>

    <?php else: ?>

    <!-- ── Term selector ── -->
    <div class="term-selector">
      <?php foreach ($termList as $t): ?>
      <?php
        $active = $t['term'] === $filterTerm && $t['session'] === $filterSession;
        $url    = '?term=' . urlencode($t['term']) . '&session=' . urlencode($t['session']);
      ?>
      <a href="<?php echo $url; ?>"
         class="term-tab <?php echo $active ? 'term-tab--active' : ''; ?>">
        <?php echo ucfirst($t['term']); ?> Term
        <span class="term-tab__session"><?php echo htmlspecialchars($t['session']); ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($results)): ?>

    <!-- ── Summary stats ── -->
    <div class="results-stats">
      <div class="stat-pill">
        <strong><?php echo $count; ?></strong>
        <span>Subjects</span>
      </div>
      <div class="stat-pill">
        <strong><?php echo $average; ?>%</strong>
        <span>Average</span>
      </div>
      <div class="stat-pill">
        <strong><?php echo $highest; ?>%</strong>
        <span>Highest</span>
      </div>
      <div class="stat-pill">
        <strong><?php echo $count > 0 ? $lowest : '—'; ?>%</strong>
        <span>Lowest</span>
      </div>
    </div>

    <!-- ── Results table ── -->
    <div class="results-table-wrap">
      <table class="results-table">
        <thead>
          <tr>
            <th>Subject</th>
            <th class="text-center">CA Score</th>
            <th class="text-center">Exam Score</th>
            <th class="text-center">Total</th>
            <th class="text-center">Grade</th>
            <th>Remark</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r):
            $total   = (float) ($r['total_score']   ?? 0);
            $ca      = (float) ($r['ca_score']       ?? 0);
            $exam    = (float) ($r['exam_score']     ?? 0);
            $grade   = gradeFromScore($total);
            $remark  = remarkFromScore($total);
            $color   = scoreColor($total);
          ?>
          <tr>
            <td class="subject-name"><?php echo htmlspecialchars($r['subject_name']); ?></td>
            <td class="text-center"><?php echo number_format($ca, 1); ?></td>
            <td class="text-center"><?php echo number_format($exam, 1); ?></td>
            <td class="text-center">
              <strong style="color:<?php echo $color; ?>"><?php echo number_format($total, 1); ?></strong>
            </td>
            <td class="text-center">
              <span class="grade-badge" style="color:<?php echo $color; ?>;background:<?php echo $color; ?>22">
                <?php echo $grade; ?>
              </span>
            </td>
            <td><?php echo $remark; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3" style="font-weight:700;color:#3d1a6e">Average</td>
            <td class="text-center">
              <strong style="color:<?php echo scoreColor($average); ?>">
                <?php echo $average; ?>%
              </strong>
            </td>
            <td class="text-center">
              <span class="grade-badge" style="color:<?php echo scoreColor($average); ?>;background:<?php echo scoreColor($average); ?>22">
                <?php echo gradeFromScore($average); ?>
              </span>
            </td>
            <td><?php echo remarkFromScore($average); ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- ── Grade key ── -->
    <div class="grade-key">
      <strong>Grade Key:</strong>
      <span>A = 70–100 (Excellent)</span>
      <span>B = 60–69 (Very Good)</span>
      <span>C = 50–59 (Good)</span>
      <span>D = 45–49 (Credit)</span>
      <span>E = 40–44 (Pass)</span>
      <span>F = 0–39 (Fail)</span>
    </div>

    <?php endif; ?>
    <?php endif; ?>

  </div>
</main>

</body>
</html>