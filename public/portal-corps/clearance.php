<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER CLEARANCE PAGE
   File: public/portal-corps/clearance.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

$corpsMember = requireCorpsLogin();
$pdo         = getDB();

/* Load full member record */
$stmt = $pdo->prepare('SELECT * FROM corps_members WHERE id = ? LIMIT 1');
$stmt->execute([$corpsMember['id']]);
$rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
$member = $rows[0] ?? null;

/* Load all clearance records */
$clStmt = $pdo->prepare(
    'SELECT * FROM corps_clearance
     WHERE corps_member_id = ?
     ORDER BY year DESC, month DESC'
);
$clStmt->execute([$corpsMember['id']]);
$clearances = $clStmt->fetchAll(PDO::FETCH_ASSOC);

$months = ['','January','February','March','April','May','June',
           'July','August','September','October','November','December'];

/* Build clearance map for quick lookup */
$clearMap = [];
foreach ($clearances as $c) {
    $clearMap[$c['year'] . '-' . $c['month']] = $c;
}

/* Selected month for letter download */
$selectedYear  = (int) ($_GET['year']  ?? 0);
$selectedMonth = (int) ($_GET['month'] ?? 0);
$selectedClear = null;
if ($selectedYear && $selectedMonth) {
    $key = $selectedYear . '-' . $selectedMonth;
    $selectedClear = $clearMap[$key] ?? null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Clearance - Corps Portal - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/corps-portal.css"/>
</head>
<body>
<?php include dirname(__DIR__, 2) . '/src/includes/corps-nav.php'; ?>
<main class="corps-main">
  <div class="corps-inner">

    <div class="page-hero">
      <h1 class="page-hero__title">Monthly Clearance</h1>
      <p class="page-hero__sub">View your clearance status and download your clearance letter for any cleared month.</p>
    </div>

    <?php if (empty($clearances)): ?>
    <div class="empty-portal">
      <div class="empty-portal__icon">&#128203;</div>
      <h2>No clearance records yet</h2>
      <p>Your monthly clearance records will appear here once they are added by school administration.</p>
    </div>
    <?php else: ?>

    <!-- Clearance grid -->
    <div class="clearance-grid">
      <?php foreach ($clearances as $c): ?>
      <div class="month-card <?php echo $c['is_cleared'] ? 'month-card--cleared' : 'month-card--pending'; ?>">
        <div class="month-card__name"><?php echo $months[$c['month']] . ' ' . $c['year']; ?></div>
        <span class="month-card__status <?php echo $c['is_cleared'] ? 'month-card__status--cleared' : 'month-card__status--pending'; ?>">
          <?php echo $c['is_cleared'] ? 'Cleared' : 'Pending'; ?>
        </span>
        <?php if ($c['is_cleared'] && $c['cleared_at']): ?>
        <div class="month-card__date"><?php echo date('d M Y', strtotime($c['cleared_at'])); ?></div>
        <?php endif; ?>
        <?php if ($c['is_cleared']): ?>
        <div style="margin-top:10px">
          <a href="?year=<?php echo $c['year']; ?>&month=<?php echo $c['month']; ?>"
             class="btn-download" style="padding:7px 16px;font-size:.75rem;width:100%;justify-content:center">
            View Letter
          </a>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($selectedClear && $selectedClear['is_cleared']): ?>
    <!-- Letter preview and download -->
    <div class="detail-card" style="margin-top:1.5rem">
      <h3 class="detail-card__title">
        Clearance Letter — <?php echo $months[$selectedMonth] . ' ' . $selectedYear; ?>
      </h3>
      <div style="padding:20px;display:flex;gap:12px;flex-wrap:wrap">
        <a href="clearance-letter.php?year=<?php echo $selectedYear; ?>&month=<?php echo $selectedMonth; ?>"
           target="_blank" class="btn-download">
          Preview Letter
        </a>
        <a href="clearance-letter.php?year=<?php echo $selectedYear; ?>&month=<?php echo $selectedMonth; ?>&download=1"
           class="btn-download" style="background:#1a7a3a">
          Download PDF
        </a>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

  </div>
</main>
</body>
</html>