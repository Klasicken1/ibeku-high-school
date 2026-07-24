<?php
/* ============================================================
   IBEKU HIGH SCHOOL - PUBLIC CORPS MEMBER PROFILE
   File: public/corps-profile.php

   Public, read-only profile — no login required. Linked from
   public/corps.php via ?code=STATE_CODE.

   IMPORTANT: this file previously contained a copy-paste of
   portal-corps/profile.php (the authenticated portal page),
   which meant this "public" page actually required a corps
   login to view — defeating its purpose. This rebuild fixes
   that: no auth, and the DB query deliberately excludes bank
   details / phone (only ever needed by admin + the corps
   member's own portal), rather than just hiding them in the
   template.

   Clearance letter downloads stay behind corps portal login
   (portal-corps/clearance.php) — this page only shows whether
   the member is cleared for the current month, matching the
   original spec: cleared members see a confirmation note,
   uncleared members see "contact the Principal or school
   office."
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/config/database.php';

$pdo  = getDB();
$code = trim($_GET['code'] ?? '');

if ($code === '') {
    header('Location: corps.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, state_code, full_name, photo, state_of_origin, batch,
            institution, course_studied, subject_taught, section, class_arms,
            cds_group, cds_day, status
     FROM corps_members
     WHERE state_code = ? AND status IN ('active', 'passed_out')
     LIMIT 1"
);
$stmt->execute([$code]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    http_response_code(404);
    $pageTitle   = 'Corps Member Not Found — Ibeku High School';
    $pageDesc    = 'The corps member profile you are looking for could not be found.';
    $pageRobots  = 'noindex, follow';
    $currentPage = 'corps';
    require_once dirname(__DIR__) . '/src/includes/header.php';
    ?>
    <div class="wrap" style="padding:80px 20px;text-align:center">
      <div style="font-size:48px;margin-bottom:16px">🧑‍🤝‍🧑</div>
      <h1 style="color:#3d1a6e;font-family:'Playfair Display',serif;margin-bottom:12px">Corps Member Not Found</h1>
      <p style="color:#6b6b80;font-size:15px;margin-bottom:24px">This state code doesn't match any active corps member.</p>
      <a href="corps.php" class="btn btn--primary">← Back to Corps Members</a>
    </div>
    <?php
    require_once dirname(__DIR__) . '/src/includes/footer.php';
    exit;
}

/* ── Current month's clearance status (active members only —
   this concept doesn't apply once someone has passed out) ── */
$isPassedOut  = $member['status'] === 'passed_out';
$isClearedNow = false;
$currentMonthLabel = date('F Y');

if (!$isPassedOut) {
    $clearStmt = $pdo->prepare(
        'SELECT is_cleared FROM corps_clearance
         WHERE corps_member_id = ? AND month = ? AND year = ?
         LIMIT 1'
    );
    $clearStmt->execute([$member['id'], (int) date('n'), (int) date('Y')]);
    $clearRow     = $clearStmt->fetch(PDO::FETCH_ASSOC);
    $isClearedNow = $clearRow && (int) $clearRow['is_cleared'] === 1;
}

$initial = strtoupper(substr($member['full_name'], 0, 1));

$sectionLabels = ['ss' => 'Senior Secondary', 'js' => 'Junior Secondary', 'both' => 'Both Sections'];

$pageTitle   = htmlspecialchars($member['full_name']) . ' — NYSC Corps Member — Ibeku High School';
$pageDesc    = $isPassedOut
    ? htmlspecialchars($member['full_name']) . ', a former NYSC corps member who served at Ibeku High School, Umuahia.'
    : htmlspecialchars($member['full_name']) . ', NYSC corps member serving at Ibeku High School, Umuahia.';
$currentPage = 'corps';

require_once dirname(__DIR__) . '/src/includes/header.php';

/* BASE_PATH is only defined once header.php has run, so this
   must come after the require above, not before it. */
$photoSrc = !empty($member['photo'])
    ? BASE_PATH . 'assets/images/corps/' . htmlspecialchars($member['photo'])
    : '';
?>

<section class="page-hero">
  <div class="page-hero__inner">
    <div class="breadcrumb">
      <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
      <span class="breadcrumb__sep">/</span>
      <a href="<?php echo BASE_PATH; ?>corps.php">Corps Members</a>
      <span class="breadcrumb__sep">/</span>
      <span><?php echo htmlspecialchars($member['full_name']); ?></span>
    </div>
    <h1><?php echo htmlspecialchars($member['full_name']); ?></h1>
    <p>NYSC Batch <?php echo htmlspecialchars($member['batch']); ?> · State Code: <?php echo htmlspecialchars($member['state_code']); ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap" style="max-width:760px">

    <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;margin-bottom:32px">
      <?php $avatarGrad = $isPassedOut ? 'linear-gradient(135deg,#8a8a94,#b0b0ba)' : 'linear-gradient(135deg,#3d1a6e,#4a90d9)'; ?>
      <?php if ($photoSrc): ?>
      <img src="<?php echo $photoSrc; ?>" alt="<?php echo htmlspecialchars($member['full_name']); ?>"
           style="width:120px;height:120px;border-radius:16px;object-fit:cover;flex-shrink:0;<?php echo $isPassedOut ? 'filter:grayscale(55%)' : ''; ?>"
           onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
      <div style="display:none;width:120px;height:120px;border-radius:16px;background:<?php echo $avatarGrad; ?>;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;color:#fff;flex-shrink:0">
        <?php echo $initial; ?>
      </div>
      <?php else: ?>
      <div style="width:120px;height:120px;border-radius:16px;background:<?php echo $avatarGrad; ?>;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;color:#fff;flex-shrink:0">
        <?php echo $initial; ?>
      </div>
      <?php endif; ?>

      <div style="flex:1;min-width:220px">
        <?php if ($isPassedOut): ?>
        <div style="background:#eceaf0;border:1px solid #dcdae5;border-radius:10px;padding:12px 16px;font-size:13.5px;color:#5a5a68;font-weight:600">
          🎓 Passed Out — Service Completed
        </div>
        <?php elseif ($isClearedNow): ?>
        <div style="background:#e6f9ed;border:1px solid #b2dfce;border-radius:10px;padding:12px 16px;font-size:13.5px;color:#1a7a3a">
          ✓ Cleared for <?php echo htmlspecialchars($currentMonthLabel); ?>
        </div>
        <?php else: ?>
        <div style="background:#fff3e6;border:1px solid #f0d8b0;border-radius:10px;padding:12px 16px;font-size:13.5px;color:#8a4a00">
          Clearance pending for <?php echo htmlspecialchars($currentMonthLabel); ?> — contact the Principal or school office.
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card" style="padding:24px;margin-bottom:20px">
      <h3 style="font-family:'Playfair Display',serif;color:#3d1a6e;font-size:1.1rem;margin-bottom:16px">Academic Background</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">State of Origin</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($member['state_of_origin'] ?: '—'); ?></span>
        </div>
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">Institution</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($member['institution'] ?: '—'); ?></span>
        </div>
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">Course Studied</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($member['course_studied'] ?: '—'); ?></span>
        </div>
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">Batch</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($member['batch'] ?: '—'); ?></span>
        </div>
      </div>
    </div>

    <div class="card" style="padding:24px">
      <h3 style="font-family:'Playfair Display',serif;color:#3d1a6e;font-size:1.1rem;margin-bottom:16px">Posting at Ibeku High School</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">Subject Taught</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($member['subject_taught'] ?: '—'); ?></span>
        </div>
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">Section</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($sectionLabels[$member['section']] ?? $member['section']); ?></span>
        </div>
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">Class Arms</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($member['class_arms'] ?: '—'); ?></span>
        </div>
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">CDS Group</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($member['cds_group'] ?: '—'); ?></span>
        </div>
        <div>
          <span style="display:block;font-size:11px;font-weight:700;color:#9b97b0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">CDS Day</span>
          <span style="font-size:14px;color:#1a1a2e"><?php echo htmlspecialchars($member['cds_day'] ?: '—'); ?></span>
        </div>
      </div>
    </div>

    <div style="text-align:center;margin-top:32px">
      <a href="corps.php" class="btn btn--ghost">← Back to Corps Members</a>
    </div>

  </div>
</section>

<?php require_once dirname(__DIR__) . '/src/includes/footer.php'; ?>