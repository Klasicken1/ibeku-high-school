<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS CLEARANCE LETTER
   File: public/admin/corps-letter.php
   Also accessible from portal-corps/clearance-letter.php

   Generates a printable/downloadable clearance letter.
   Layout: placeholder until official JPG design is provided.
   To update: replace the HTML inside .letter-body with the
   actual design based on the uploaded JPG template.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__, 2) . '/src/config/database.php';

/* Auth: accept both admin and corps member sessions */
$isAdmin       = false;
$isCorpsMember = false;

/* Check admin session */
if (!empty($_SESSION['admin_user'])) {
    require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
    $isAdmin = isLoggedIn();
}

/* Check corps session */
if (!$isAdmin && !empty($_SESSION['corps_member'])) {
    require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';
    $isCorpsMember = corpsLoggedIn();
}

if (!$isAdmin && !$isCorpsMember) {
    header('HTTP/1.0 403 Forbidden');
    echo '<p>Access denied. Please log in.</p>';
    exit;
}

$pdo = getDB();

/* Get params */
$memberId = (int) ($_GET['id']    ?? ($_SESSION['corps_member']['id'] ?? 0));
$year     = (int) ($_GET['year']  ?? 0);
$month    = (int) ($_GET['month'] ?? 0);
$download = !empty($_GET['download']);

/* Corps member can only access their own letter */
if ($isCorpsMember && !$isAdmin) {
    $memberId = (int) $_SESSION['corps_member']['id'];
}

if (!$memberId || !$year || !$month || $month < 1 || $month > 12) {
    echo '<p>Invalid request.</p>'; exit;
}

/* Load member */
$mStmt = $pdo->prepare('SELECT * FROM corps_members WHERE id = ? LIMIT 1');
$mStmt->execute([$memberId]);
$rows   = $mStmt->fetchAll(PDO::FETCH_ASSOC);
$member = $rows[0] ?? null;
if (!$member) { echo '<p>Corps member not found.</p>'; exit; }

/* Load clearance record */
$cStmt = $pdo->prepare(
    'SELECT c.*, u.full_name AS cleared_by_name
     FROM corps_clearance c
     LEFT JOIN users u ON u.id = c.cleared_by
     WHERE c.corps_member_id = ? AND c.month = ? AND c.year = ? AND c.is_cleared = 1
     LIMIT 1'
);
$cStmt->execute([$memberId, $month, $year]);
$clearRows  = $cStmt->fetchAll(PDO::FETCH_ASSOC);
$clearance  = $clearRows[0] ?? null;

if (!$clearance) {
    echo '<p>No clearance record found for this month, or member has not been cleared.</p>'; exit;
}

/* Load school settings */
$_site = getDB()->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$schoolName  = $_site['school_name']  ?? 'Ibeku High School';
$schoolPhone = $_site['school_phone'] ?? '';
$schoolEmail = $_site['school_email'] ?? '';

$months = ['','January','February','March','April','May','June',
           'July','August','September','October','November','December'];

$monthName  = $months[$month];
$letterDate = date('d F Y', strtotime($clearance['cleared_at'] ?? 'now'));
$refNo      = 'IHS/NYSC/CL/' . $year . '/' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '/' . $member['id'];

/* PDF download via browser print */
if ($download) {
    header('Content-Type: text/html; charset=utf-8');
    /* JS auto-triggers print dialog for save-as-PDF */
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Clearance Letter - <?php echo htmlspecialchars($member['full_name']); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'DM Sans',sans-serif;background:#e8e6f0;padding:2rem;min-height:100vh;display:flex;flex-direction:column;align-items:center}
    .no-print-bar{background:#3d1a6e;color:#fff;padding:12px 24px;border-radius:10px;margin-bottom:1.5rem;display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;max-width:780px}
    .no-print-bar span{flex:1;font-size:13.5px}
    .btn-print{background:#e8a020;color:#fff;border:none;padding:8px 20px;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif}
    .btn-back{background:rgba(255,255,255,.15);color:#fff;text-decoration:none;padding:8px 16px;border-radius:7px;font-size:13px;font-weight:600}
    @media print{.no-print-bar{display:none}body{background:#fff;padding:0}
    .letter{box-shadow:none;border-radius:0;page-break-inside:avoid}}

    /* ── LETTER ── */
    .letter{background:#fff;width:780px;max-width:100%;min-height:1050px;padding:48px 56px;box-shadow:0 8px 40px rgba(0,0,0,.15);border-radius:8px;position:relative}

    /* Header */
    .letter-header{text-align:center;border-bottom:3px double #3d1a6e;padding-bottom:20px;margin-bottom:24px}
    .letter-header__logo{font-family:'Playfair Display',serif;font-size:2rem;font-weight:900;color:#3d1a6e;letter-spacing:3px;margin-bottom:4px}
    .letter-header__name{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;color:#1a1a2e;margin-bottom:2px}
    .letter-header__address{font-size:.82rem;color:#6b6b80;margin-bottom:8px}
    .letter-header__contact{font-size:.78rem;color:#9b97b0}
    .nysc-tag{display:inline-block;background:#e8a020;color:#fff;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:3px 12px;border-radius:20px;margin-top:8px}

    /* Meta line */
    .letter-meta{display:flex;justify-content:space-between;margin-bottom:24px;font-size:.82rem;color:#6b6b80}
    .letter-meta strong{color:#3d1a6e}

    /* Title */
    .letter-title{text-align:center;margin-bottom:24px}
    .letter-title h2{font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:#3d1a6e;text-transform:uppercase;letter-spacing:.05em;text-decoration:underline;text-underline-offset:6px}
    .letter-title .month-year{font-size:1rem;color:#6b6b80;margin-top:4px}

    /* Body */
    .letter-body{font-size:.9rem;color:#1a1a2e;line-height:1.9;margin-bottom:24px}
    .letter-body p{margin-bottom:12px}
    .letter-body strong{color:#3d1a6e}

    /* Details box */
    .details-box{background:#f8f7fc;border:1px solid #e8e6f0;border-radius:8px;padding:18px 22px;margin:20px 0;display:grid;grid-template-columns:1fr 1fr;gap:10px 24px}
    .details-box__row{display:contents}
    .details-label{font-size:.75rem;font-weight:600;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em}
    .details-value{font-size:.875rem;color:#1a1a2e;font-weight:500}

    /* Bank details box */
    .bank-box{border:2px solid #3d1a6e;border-radius:8px;padding:16px 20px;margin:16px 0}
    .bank-box__title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#3d1a6e;margin-bottom:10px}
    .bank-box__grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .bank-box__label{font-size:.75rem;color:#9b97b0;font-weight:600;text-transform:uppercase}
    .bank-box__value{font-size:.875rem;color:#1a1a2e;font-weight:600}

    /* Signature */
    .signature-section{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:40px}
    .signature-block{border-top:1px solid #1a1a2e;padding-top:8px}
    .signature-block__name{font-size:.875rem;font-weight:700;color:#1a1a2e;margin-bottom:2px}
    .signature-block__title{font-size:.78rem;color:#6b6b80}

    /* Stamp area */
    .stamp-area{position:absolute;bottom:120px;right:56px;width:120px;height:120px;border:3px solid #3d1a6e;border-radius:50%;display:flex;align-items:center;justify-content:center;text-align:center;color:#3d1a6e;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;opacity:.4}

    /* Footer */
    .letter-footer{border-top:1px solid #e8e6f0;padding-top:12px;margin-top:24px;text-align:center;font-size:.72rem;color:#9b97b0}
  </style>
</head>
<body>

<!-- Print bar (hidden on print) -->
<div class="no-print-bar">
  <span>Clearance Letter — <?php echo htmlspecialchars($member['full_name']); ?> — <?php echo $monthName . ' ' . $year; ?></span>
  <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
  <a href="javascript:history.back()" class="btn-back">Back</a>
</div>

<!-- THE LETTER -->
<div class="letter">

  <!-- Header -->
  <div class="letter-header">
    <div class="letter-header__logo">IHS</div>
    <div class="letter-header__name"><?php echo htmlspecialchars($schoolName); ?></div>
    <div class="letter-header__address">Umuahia, Abia State, Nigeria</div>
    <div class="letter-header__contact">
      <?php if ($schoolPhone): ?>Tel: <?php echo htmlspecialchars($schoolPhone); ?>&nbsp;&nbsp;<?php endif; ?>
      <?php if ($schoolEmail): ?>Email: <?php echo htmlspecialchars($schoolEmail); ?><?php endif; ?>
    </div>
    <span class="nysc-tag">NYSC Host Institution</span>
  </div>

  <!-- Meta -->
  <div class="letter-meta">
    <span><strong>Ref No:</strong> <?php echo htmlspecialchars($refNo); ?></span>
    <span><strong>Date:</strong> <?php echo $letterDate; ?></span>
  </div>

  <!-- Title -->
  <div class="letter-title">
    <h2>Monthly Clearance Letter</h2>
    <div class="month-year"><?php echo $monthName . ' ' . $year; ?></div>
  </div>

  <!-- Body -->
  <div class="letter-body">
    <p>This is to certify that the following NYSC Corps Member has satisfactorily served at
    <strong><?php echo htmlspecialchars($schoolName); ?></strong>, Umuahia, Abia State, and is hereby
    cleared for the month of <strong><?php echo $monthName . ' ' . $year; ?></strong>.</p>

    <!-- Member details -->
    <div class="details-box">
      <span class="details-label">Full Name</span>
      <span class="details-value"><?php echo htmlspecialchars($member['full_name']); ?></span>

      <span class="details-label">State Code</span>
      <span class="details-value"><?php echo htmlspecialchars($member['state_code']); ?></span>

      <span class="details-label">Batch</span>
      <span class="details-value"><?php echo htmlspecialchars($member['batch']); ?></span>

      <span class="details-label">State of Origin</span>
      <span class="details-value"><?php echo htmlspecialchars($member['state_of_origin']); ?></span>

      <span class="details-label">Institution</span>
      <span class="details-value"><?php echo htmlspecialchars($member['institution']); ?></span>

      <span class="details-label">Course Studied</span>
      <span class="details-value"><?php echo htmlspecialchars($member['course_studied']); ?></span>

      <span class="details-label">Subject Taught</span>
      <span class="details-value"><?php echo htmlspecialchars($member['subject_taught'] ?? '-'); ?></span>

      <span class="details-label">Section / Class</span>
      <span class="details-value"><?php echo strtoupper($member['section']); ?> &nbsp;—&nbsp; <?php echo htmlspecialchars($member['class_arms'] ?? '-'); ?></span>

      <span class="details-label">CDS Group</span>
      <span class="details-value"><?php echo htmlspecialchars($member['cds_group'] ?? '-'); ?></span>

      <span class="details-label">CDS Day</span>
      <span class="details-value"><?php echo htmlspecialchars($member['cds_day'] ?? '-'); ?></span>
    </div>

    <!-- Bank details -->
    <div class="bank-box">
      <div class="bank-box__title">Bank Details for Allawee Payment</div>
      <div class="bank-box__grid">
        <div>
          <div class="bank-box__label">Bank Name</div>
          <div class="bank-box__value"><?php echo htmlspecialchars($member['bank_name'] ?? '-'); ?></div>
        </div>
        <div>
          <div class="bank-box__label">Account Number</div>
          <div class="bank-box__value"><?php echo htmlspecialchars($member['account_number'] ?? '-'); ?></div>
        </div>
        <div style="grid-column:span 2">
          <div class="bank-box__label">Account Name</div>
          <div class="bank-box__value"><?php echo htmlspecialchars($member['account_name'] ?? '-'); ?></div>
        </div>
      </div>
    </div>

    <?php if ($clearance['remarks']): ?>
    <p><strong>Remarks:</strong> <?php echo htmlspecialchars($clearance['remarks']); ?></p>
    <?php endif; ?>

    <p>This letter is issued in good faith and is valid only for the month stated above.
    For verification, please contact the school directly.</p>
  </div>

  <!-- Signatures -->
  <div class="signature-section">
    <div class="signature-block">
      <div style="height:50px"></div><!-- space for signature -->
      <div class="signature-block__name">Principal</div>
      <div class="signature-block__title"><?php echo htmlspecialchars($schoolName); ?></div>
    </div>
    <div class="signature-block">
      <div style="height:50px"></div>
      <div class="signature-block__name">School Supervisor</div>
      <div class="signature-block__title">NYSC Liaison</div>
    </div>
  </div>

  <!-- Stamp placeholder -->
  <div class="stamp-area">Official<br/>Stamp</div>

  <!-- Footer -->
  <div class="letter-footer">
    <?php echo htmlspecialchars($schoolName); ?> &nbsp;·&nbsp; Umuahia, Abia State, Nigeria
    &nbsp;·&nbsp; NYSC Host Institution &nbsp;·&nbsp;
    Cleared by: <?php echo htmlspecialchars($clearance['cleared_by_name'] ?? 'Admin'); ?>
    on <?php echo $letterDate; ?>
  </div>

</div>

<?php if ($download): ?>
<script>window.onload=function(){setTimeout(function(){window.print()},500)}</script>
<?php endif; ?>

</body>
</html>