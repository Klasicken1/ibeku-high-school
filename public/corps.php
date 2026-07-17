<?php
/* ============================================================
   IBEKU HIGH SCHOOL - PUBLIC CORPS DIRECTORY
   File: public/corps.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/config/database.php';
require_once dirname(__DIR__) . '/src/includes/header.php';

$pdo = getDB();

$currentPage = 'corps';
$pageTitle   = 'NYSC Corps Members - Ibeku High School';
$pageDesc    = 'Meet our NYSC corps members currently serving at Ibeku High School.';

$stmt = $pdo->query(
    "SELECT id, state_code, full_name, photo, state_of_origin, batch,
            institution, course_studied, subject_taught, section, class_arms,
            cds_group, cds_day
     FROM corps_members
     WHERE status = 'active'
     ORDER BY full_name ASC"
);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
/* Corps page predates the shared .page-hero class system, so the
   custom photo option is wired in directly here rather than via
   the page-hero--photo modifier used elsewhere. */
$corpsHeroImg = getInnerHeroImage('corps');
$corpsHeroBg  = $corpsHeroImg
    ? "background-image:url('" . htmlspecialchars(BASE_PATH . 'assets/images/hero/' . rawurlencode($corpsHeroImg), ENT_QUOTES) . "');background-size:cover;background-position:center"
    : 'background:linear-gradient(135deg,#3d1a6e,#2a1050)';
?>
<section class="page-hero-section" style="<?php echo $corpsHeroBg; ?>;padding:4rem 1.25rem;text-align:center;position:relative">
  <?php if ($corpsHeroImg): ?>
  <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(61,26,110,.88) 0%,rgba(90,45,158,.8) 55%,rgba(44,111,173,.82) 100%)"></div>
  <?php endif; ?>
  <div style="max-width:700px;margin:0 auto;position:relative;z-index:1">
    <span style="display:inline-block;background:#e8a020;color:#fff;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:4px 14px;border-radius:20px;margin-bottom:1rem">NYSC</span>
    <h1 style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#fff;margin-bottom:.75rem">Corps Members</h1>
    <p style="font-size:1rem;color:rgba(255,255,255,.75)">
      Meet the NYSC corps members currently serving Ibeku High School, Umuahia.
    </p>
  </div>
</section>

<section style="padding:3rem 1.25rem;max-width:1100px;margin:0 auto">
  <?php if (empty($members)): ?>
  <div style="text-align:center;padding:3rem;color:#6b6b80">
    <p style="font-size:1.1rem">No active corps members at this time.</p>
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px">
    <?php foreach ($members as $m):
      $photo = !empty($m['photo'])
        ? BASE_PATH . 'assets/images/corps/' . htmlspecialchars($m['photo'])
        : '';
      $initial = strtoupper(substr($m['full_name'], 0, 1));
    ?>
    <a href="corps-profile.php?code=<?php echo urlencode($m['state_code']); ?>"
       style="background:#fff;border:1px solid #e8e6f0;border-radius:16px;overflow:hidden;text-decoration:none;color:#1a1a2e;transition:box-shadow .2s,transform .2s;display:block"
       onmouseover="this.style.boxShadow='0 8px 32px rgba(61,26,110,.12)';this.style.transform='translateY(-2px)'"
       onmouseout="this.style.boxShadow='none';this.style.transform='none'">
      <!-- Photo -->
      <div style="height:180px;background:linear-gradient(135deg,#3d1a6e,#4a90d9);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden">
        <?php if ($photo): ?>
        <img src="<?php echo $photo; ?>" alt="<?php echo htmlspecialchars($m['full_name']); ?>"
             style="width:100%;height:100%;object-fit:cover"
             onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
        <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:3rem;font-weight:700;color:rgba(255,255,255,.8)">
          <?php echo $initial; ?>
        </div>
        <?php else: ?>
        <div style="font-size:3rem;font-weight:700;color:rgba(255,255,255,.8)"><?php echo $initial; ?></div>
        <?php endif; ?>
        <div style="position:absolute;top:10px;right:10px;background:#e8a020;color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase">
          <?php echo htmlspecialchars($m['batch']); ?>
        </div>
      </div>
      <!-- Info -->
      <div style="padding:16px">
        <h3 style="font-family:'Playfair Display',serif;font-size:1.05rem;color:#3d1a6e;margin-bottom:4px"><?php echo htmlspecialchars($m['full_name']); ?></h3>
        <p style="font-size:.78rem;color:#9b97b0;margin-bottom:10px"><?php echo htmlspecialchars($m['state_code']); ?></p>
        <?php if ($m['subject_taught']): ?>
        <div style="font-size:.82rem;color:#6b6b80;margin-bottom:4px">
          <strong style="color:#3d1a6e">Teaches:</strong> <?php echo htmlspecialchars($m['subject_taught']); ?>
        </div>
        <?php endif; ?>
        <?php if ($m['institution']): ?>
        <div style="font-size:.82rem;color:#6b6b80;margin-bottom:4px">
          <strong style="color:#3d1a6e">From:</strong> <?php echo htmlspecialchars($m['institution']); ?>
        </div>
        <?php endif; ?>
        <?php if ($m['state_of_origin']): ?>
        <div style="font-size:.82rem;color:#6b6b80">
          <strong style="color:#3d1a6e">State:</strong> <?php echo htmlspecialchars($m['state_of_origin']); ?>
        </div>
        <?php endif; ?>
        <div style="margin-top:12px;display:inline-block;background:#f0ecfa;color:#3d1a6e;font-size:.75rem;font-weight:700;padding:3px 10px;border-radius:20px">
          View Profile
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php require_once dirname(__DIR__) . '/src/includes/footer.php'; ?>