<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS CLEARANCE LETTER
   File: public/admin/corps-letter.php
   Also accessible from portal-corps/clearance-letter.php

   Renders one of TWO letter formats depending on the corps
   member's section:
     - SS (and 'both', defaulting to SS): the official Abia State
       Secondary Education Management Board letterhead format
       ("CLEARANCE FOR CORPS MEMBER").
     - JS: the "Letter of Clearance" format addressed through the
       Local Government Inspector to the State Coordinator.
   Both share the same auto-filled member/bank/principal data and
   conduct/payment checkboxes.

   Includes a faint repeating "IHS" watermark plus a Nigerian flag
   and Abia State emblem in the letterhead, as a light deterrent
   against easy forgery/photocopy reuse.

   Print/PDF: same approach as the public result slip — a
   dedicated @page rule and a hidden no-print bar, so "Print /
   Save as PDF" produces a clean page with no extra browser
   chrome. Principal's name, Sign, and Stamp are left blank for
   physical completion, matching how the paper form is used.
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';

/* ── Auth: accept both admin and corps member sessions ──
   These use two DIFFERENT named PHP sessions (admin uses the
   default session; the corps portal uses session_name('ihs_corps')
   via corps-auth.php's corpsSessionStart()). Switching between
   named sessions mid-request via session_write_close() proved
   unreliable in practice, so instead we check which session
   cookie the browser actually sent BEFORE starting either
   session, and only start the one that matches. ── */
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

$isAdmin       = false;
$isCorpsMember = false;

if (isset($_COOKIE['ihs_corps'])) {
    /* This browser has an active (or recently active) corps portal
       session — check that one first. */
    corpsSessionStart();
    $isCorpsMember = corpsLoggedIn();
}

if (!$isCorpsMember) {
    /* No valid corps session — check the admin (default) session instead. */
    require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
    $isAdmin = isLoggedIn();
}

if (!$isAdmin && !$isCorpsMember) {
    header('HTTP/1.0 403 Forbidden');
    echo '<p>Access denied. Please log in.</p>';
    exit;
}

$pdo = getDB();

/* Self-healing column adds — same columns corps-create.php,
   corps-edit.php, and corps-clearance.php already ensure, kept
   here too so this page never fatals if visited before those
   have run once. */
try {
    $pdo->exec("ALTER TABLE corps_members ADD COLUMN call_up_number VARCHAR(30) NULL AFTER state_code");
} catch (PDOException $e) { /* already exists */ }
try {
    $pdo->exec(
        "ALTER TABLE corps_clearance
         ADD COLUMN conduct_rating ENUM('diligently','well','deceitfully','grudgingly') NOT NULL DEFAULT 'diligently',
         ADD COLUMN payment_status ENUM('allowed','not_allowed') NOT NULL DEFAULT 'allowed'"
    );
} catch (PDOException $e) { /* already exists */ }

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

/* Section admins can only view letters for corps members in their
   exact section — 'both' stays out of scope for them, matching the
   same strict rule used on corps.php/corps-edit.php/corps-clearance.php */
if ($isAdmin && ($_SESSION['admin_role'] ?? null) === 'section_admin') {
    if ($member['section'] !== ($_SESSION['admin_section'] ?? null)) {
        header('HTTP/1.0 403 Forbidden');
        echo '<p>You do not have permission to view that corps member\'s letter.</p>';
        exit;
    }
}

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
$schoolName = $_site['school_name'] ?? 'Ibeku High School';

/* Principal auto-fill — chosen by the corps member's section.
   'both' defaults to the SS Principal. */
$principal = getPrincipalAssets($member['section'] ?? 'ss');

/* Which letter format to use: Junior Secondary corps members get
   the "Letter of Clearance" format; SS (and 'both', defaulting to
   SS) get the official government-letterhead format. */
$isJS = ($member['section'] ?? 'ss') === 'js';

$abiaEmblem = $_site['abia_state_emblem'] ?? '';
$schoolLogo = $_site['school_logo'] ?? '';

$months = ['','January','February','March','April','May','June',
           'July','August','September','October','November','December'];

$monthName  = $months[$month];
$letterDate = date('d F Y', strtotime($clearance['cleared_at'] ?? 'now'));
$refNo      = 'IHS/NYSC/CL/' . $year . '/' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '/' . $member['id'];

$conductRating = $clearance['conduct_rating'] ?? 'diligently';
$paymentStatus = $clearance['payment_status'] ?? 'allowed';

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

    /* ── Print: mirrors the public result-slip approach exactly —
       a dedicated @page rule and hiding everything except the
       letter itself, so Save-as-PDF produces a clean page with
       no browser header/footer/URL/date strip. ── */
    @media print {
      @page { size: A4 portrait; margin: 11mm 14mm; }
      .no-print-bar { display: none !important; }
      body { background: #fff; padding: 0; }
      .letter { box-shadow: none; border: none; border-radius: 0; width: 100%; min-height: 0; }
    }

    /* ── LETTER ── */
    .letter{background:#fff;width:780px;max-width:100%;padding:30px 44px;box-shadow:0 8px 40px rgba(0,0,0,.15);border-radius:8px;position:relative;font-size:13.5px;line-height:1.5;color:#1a1a1a;overflow:hidden}

    /* ── Security watermark — faint repeating IHS mark behind the
       content, makes the letter harder to convincingly forge or
       pass off a screenshot/photocopy as a blank template. ── */
    .watermark{position:absolute;inset:0;display:flex;flex-wrap:wrap;align-content:center;justify-content:center;overflow:hidden;pointer-events:none;z-index:0;gap:40px;transform:rotate(-28deg) scale(1.4)}
    .watermark span{font-family:'Playfair Display',serif;font-weight:900;font-size:64px;color:rgba(61,26,110,.05);white-space:nowrap;user-select:none}
    .watermark img{width:80px;height:80px;object-fit:contain;opacity:.07}
    .letter > *:not(.watermark){position:relative;z-index:1}

    /* Nigerian flag — simple, accurate tricolor */
    .ng-flag{width:36px;height:24px;display:flex;flex-shrink:0;border:1px solid rgba(0,0,0,.2)}
    .ng-flag span{flex:1}
    .ng-flag span:nth-child(1){background:#008751}
    .ng-flag span:nth-child(2){background:#fff}
    .ng-flag span:nth-child(3){background:#008751}

    /* Abia State emblem slot — falls back to a text badge if no
       image has been uploaded in Settings yet */
    .abia-emblem{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid #1a1a1a;overflow:hidden;background:#f0ecfa;font-size:.5rem;font-weight:700;color:#3d1a6e;text-align:center;line-height:1.15}
    .abia-emblem img{width:100%;height:100%;object-fit:cover}
    .letterhead__side{display:flex;flex-direction:column;align-items:center;gap:6px;flex-shrink:0}

    /* Letterhead */
    .letterhead{display:flex;align-items:center;gap:18px;border-bottom:3px double #1a1a1a;padding-bottom:10px;margin-bottom:8px}
    .letterhead__crest{width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,#3d1a6e,#4a90d9);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid #1a1a1a}
    .letterhead__crest span{font-family:'Playfair Display',serif;font-weight:900;font-size:1.05rem;color:#fff;letter-spacing:1px}
    .letterhead__text{flex:1;text-align:center}
    .letterhead__gov{font-size:.78rem;font-weight:700;letter-spacing:.04em;color:#3a3a3a;text-transform:uppercase}
    .letterhead__board{font-size:.7rem;font-weight:600;color:#5a5a5a;text-transform:uppercase;letter-spacing:.03em;margin-top:1px}
    .letterhead__school{font-family:'Playfair Display',serif;font-size:1.55rem;font-weight:900;color:#1a1a1a;margin-top:3px;letter-spacing:.5px}
    .letterhead__address{font-size:.72rem;color:#5a5a5a;margin-top:2px;letter-spacing:.03em}

    /* Ref/date line */
    .ref-line{display:flex;justify-content:space-between;border-bottom:1px solid #1a1a1a;padding-bottom:6px;margin-bottom:14px;font-size:.78rem}
    .ref-line span strong{font-weight:700}

    /* Recipient block */
    .recipient{margin-bottom:12px;font-size:.88rem}
    .recipient div{margin-bottom:1px}

    .salutation{margin-bottom:8px}

    .letter-title{font-weight:700;text-decoration:underline;text-underline-offset:4px;margin-bottom:8px;font-size:.98rem}

    .intro-text{margin-bottom:12px}

    /* Fill-in field rows: LABEL: ..........value.......... */
    .fill-row{display:flex;align-items:baseline;gap:6px;margin-bottom:10px}
    .fill-row__label{font-weight:700;white-space:nowrap;flex-shrink:0}
    .fill-row__value{flex:1;border-bottom:1px dotted #1a1a1a;padding-bottom:2px;font-weight:600;min-height:1.3em}

    /* Bank details box */
    .bank-box{border:1.5px solid #1a1a1a;border-radius:4px;padding:10px 14px;margin:12px 0 14px}
    .bank-box__title{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
    .bank-box__grid{display:grid;grid-template-columns:1fr 1fr;gap:5px 20px;font-size:.83rem}
    .bank-box__label{font-weight:600;color:#5a5a5a}

    /* Conduct/payment checkbox rows */
    .check-block{margin-bottom:10px}
    .check-block__intro{margin-bottom:6px}
    .check-row{display:flex;flex-wrap:wrap;gap:16px;margin-left:4px}
    .check-item{display:flex;align-items:center;gap:6px;font-size:.88rem}
    .checkbox{width:14px;height:14px;border:1.5px solid #1a1a1a;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-size:10px;font-weight:900;line-height:1}
    .checkbox.checked::after{content:'✓'}

    /* Signature */
    .signoff{margin-top:14px}
    .signoff__line{margin-bottom:8px}
    .signoff-row{display:flex;align-items:baseline;gap:6px;margin-bottom:10px}
    .signoff-row__label{font-weight:700;white-space:nowrap;flex-shrink:0}
    .signoff-row__blank{flex:1;border-bottom:1px dotted #1a1a1a;min-height:1.3em}

    /* ── On-screen mobile viewing only — the @media print rules
       above are untouched and still target A4 for the actual
       printed/PDF output. This just keeps the preview usable on
       a phone before printing. ── */
    @media screen and (max-width: 700px) {
      body { padding: 0.75rem; }
      .no-print-bar { flex-direction: column; align-items: stretch; text-align: center; }
      .no-print-bar span { flex: none; margin-bottom: 4px; }
      .letter { padding: 20px 18px; font-size: 12.5px; }
      .letterhead { flex-wrap: wrap; justify-content: center; text-align: center; }
      .letterhead__side { display: none; }
      .ref-line { flex-direction: column; gap: 4px; }
      .bank-box__grid { grid-template-columns: 1fr; }
      .watermark span { font-size: 40px; }
    }
  </style>
</head>
<body>

<!-- Print bar (hidden on print) -->
<div class="no-print-bar">
  <span>Clearance Letter — <?php echo htmlspecialchars($member['full_name']); ?> — <?php echo $monthName . ' ' . $year; ?></span>
  <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
  <?php if ($isAdmin): ?>
  <a href="corps-clearance.php?id=<?php echo $memberId; ?>" class="btn-back">Back</a>
  <?php else: ?>
  <a href="../portal-corps/clearance.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="btn-back">Back</a>
  <?php endif; ?>
</div>

<!-- THE LETTER -->
<div class="letter">

  <!-- Security watermark -->
  <div class="watermark" aria-hidden="true">
    <?php if ($schoolLogo): ?>
      <?php for ($i = 0; $i < 12; $i++): ?>
      <img src="../assets/images/settings/<?php echo htmlspecialchars($schoolLogo); ?>" alt=""/>
      <?php endfor; ?>
    <?php else: ?>
      <?php for ($i = 0; $i < 12; $i++): ?><span>IHS</span><?php endfor; ?>
    <?php endif; ?>
  </div>

  <!-- Letterhead -->
  <div class="letterhead">
    <div class="letterhead__side">
      <div class="abia-emblem">
        <?php if ($abiaEmblem): ?>
        <img src="../assets/images/settings/<?php echo htmlspecialchars($abiaEmblem); ?>" alt="Abia State"/>
        <?php else: ?>
        ABIA<br/>STATE
        <?php endif; ?>
      </div>
    </div>
    <div class="letterhead__text">
      <div class="letterhead__gov">Government of Abia State</div>
      <div class="letterhead__board">Secondary Education Management Board</div>
      <div class="letterhead__school"><?php echo htmlspecialchars(strtoupper($schoolName)); ?></div>
      <div class="letterhead__address">P.O. BOX 168, UMUAHIA, ABIA STATE</div>
    </div>
    <div class="letterhead__side">
      <div class="ng-flag"><span></span><span></span><span></span></div>
    </div>
  </div>

  <!-- Ref/Date line -->
  <div class="ref-line">
    <span>OUR REF: <strong><?php echo htmlspecialchars($refNo); ?></strong></span>
    <span>YOUR REF: <strong>&nbsp;</strong></span>
    <span>DATE: <strong><?php echo $letterDate; ?></strong></span>
  </div>

  <?php if ($isJS): ?>
  <!-- ═══ JUNIOR SECONDARY FORMAT — "Letter of Clearance" ═══ -->

  <!-- Recipient -->
  <div class="recipient">
    <div>THE STATE COORDINATOR</div>
    <div>NATIONAL YOUTH SERVICE CORPS SECRETARIAT</div>
    <div>UMUAHIA, ABIA STATE</div>
  </div>
  <p style="font-size:.82rem;margin-bottom:12px">
    <strong>Through:</strong> The Local Government Inspector, National Youth Service Corps Secretariat, Umuahia, Abia State.
  </p>

  <div class="salutation">Sir/Madam,</div>

  <div class="letter-title" style="text-align:center">LETTER OF CLEARANCE</div>

  <p class="intro-text">
    This is to inform you that the Corps Member <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>,
    who was deployed to <?php echo htmlspecialchars($schoolName); ?>, Umuahia North Local Government Area, Abia State,
    is hereby cleared for the month of <strong><?php echo htmlspecialchars($monthName . ' ' . $year); ?></strong>.
  </p>

  <!-- Fill-in fields -->
  <div class="fill-row">
    <span class="fill-row__label">STATE CODE:</span>
    <span class="fill-row__value"><?php echo htmlspecialchars($member['state_code']); ?></span>
  </div>
  <div class="fill-row">
    <span class="fill-row__label">CALL UP NUMBER:</span>
    <span class="fill-row__value"><?php echo htmlspecialchars($member['call_up_number'] ?? ''); ?></span>
  </div>

  <?php else: ?>
  <!-- ═══ SENIOR SECONDARY FORMAT — official letterhead ═══ -->

  <!-- Recipient -->
  <div class="recipient">
    <div>THE STATE COORDINATOR</div>
    <div>NYSC</div>
    <div>UMUAHIA</div>
  </div>

  <div class="salutation">Sir,</div>

  <div class="letter-title">CLEARANCE FOR CORPS MEMBER</div>

  <p class="intro-text">
    I hereby issue the named corps member serving in the school with his/her
    clearance for the month stated below:
  </p>

  <!-- Fill-in fields -->
  <div class="fill-row">
    <span class="fill-row__label">NAME:</span>
    <span class="fill-row__value"><?php echo htmlspecialchars($member['full_name']); ?></span>
  </div>
  <div class="fill-row">
    <span class="fill-row__label">STATE CODE:</span>
    <span class="fill-row__value"><?php echo htmlspecialchars($member['state_code']); ?></span>
  </div>
  <div class="fill-row">
    <span class="fill-row__label">CALL UP NUMBER:</span>
    <span class="fill-row__value"><?php echo htmlspecialchars($member['call_up_number'] ?? ''); ?></span>
  </div>
  <div class="fill-row">
    <span class="fill-row__label">MONTH:</span>
    <span class="fill-row__value"><?php echo htmlspecialchars($monthName . ' ' . $year); ?></span>
  </div>
  <?php endif; ?>

  <!-- Bank details -->
  <div class="bank-box">
    <div class="bank-box__title">Corps Account Details</div>
    <div class="bank-box__grid">
      <div><span class="bank-box__label">Bank Name:</span> <?php echo htmlspecialchars($member['bank_name'] ?? '—'); ?></div>
      <div><span class="bank-box__label">Account Number:</span> <?php echo htmlspecialchars($member['account_number'] ?? '—'); ?></div>
    </div>
  </div>

  <!-- Conduct -->
  <div class="check-block">
    <p class="check-block__intro">The corps member served the school</p>
    <div class="check-row">
      <?php
      $conductOptions = ['diligently' => 'Diligently', 'well' => 'Well', 'deceitfully' => 'Deceitfully', 'grudgingly' => 'Grudgingly'];
      foreach ($conductOptions as $val => $label):
      ?>
      <span class="check-item">
        <span class="checkbox <?php echo $conductRating === $val ? 'checked' : ''; ?>"></span>
        <?php echo $label; ?>
      </span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Payment -->
  <div class="check-block">
    <p class="check-block__intro">He/She should be</p>
    <div class="check-row">
      <span class="check-item">
        <span class="checkbox <?php echo $paymentStatus === 'allowed' ? 'checked' : ''; ?>"></span>
        Allowed to sign and be paid the month's allowance
      </span>
      <span class="check-item">
        <span class="checkbox <?php echo $paymentStatus === 'not_allowed' ? 'checked' : ''; ?>"></span>
        Not allowed
      </span>
    </div>
  </div>

  <?php if (!empty($clearance['remarks'])): ?>
  <p style="margin-bottom:16px"><strong>Remarks:</strong> <?php echo htmlspecialchars($clearance['remarks']); ?></p>
  <?php endif; ?>

  <?php if ($isJS): ?>
  <p style="margin-bottom:16px">
    Please accord him/her all rights and privileges he/she deserves by virtue of this clearance.
  </p>
  <?php endif; ?>

  <!-- Sign-off -->
  <div class="signoff">
    <p class="signoff__line">Yours faithfully</p>

    <div class="signoff-row">
      <span class="signoff-row__label">Principal's name:</span>
      <span class="signoff-row__blank"><?php echo htmlspecialchars($principal['name']); ?></span>
    </div>
    <div class="signoff-row">
      <span class="signoff-row__label">Sign:</span>
      <span class="signoff-row__blank">
        <?php if (!empty($principal['signature'])): ?>
        <img src="../assets/images/signatures/<?php echo htmlspecialchars($principal['signature']); ?>" style="height:44px;vertical-align:bottom"/>
        <?php endif; ?>
      </span>
    </div>
    <div class="signoff-row">
      <span class="signoff-row__label">Stamp:</span>
      <span class="signoff-row__blank">
        <?php if (!empty($principal['stamp'])): ?>
        <img src="../assets/images/signatures/<?php echo htmlspecialchars($principal['stamp']); ?>" style="height:70px;vertical-align:bottom"/>
        <?php endif; ?>
      </span>
    </div>
  </div>

</div>

<?php if ($download): ?>
<script>window.onload=function(){setTimeout(function(){window.print()},500)}</script>
<?php endif; ?>

</body>
</html>