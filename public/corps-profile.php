<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER PORTAL PROFILE
   File: public/portal-corps/profile.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

$corpsMember = requireCorpsLogin();
$pdo         = getDB();

$stmt = $pdo->prepare('SELECT * FROM corps_members WHERE id = ? LIMIT 1');
$stmt->execute([$corpsMember['id']]);
$rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
$member = $rows[0] ?? null;

if (!$member) { logoutCorpsMember(); header('Location: login.php'); exit; }

$photoSrc = '';
if (!empty($member['photo'])) {
    $photoSrc = '../assets/images/corps/' . htmlspecialchars($member['photo']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile - Corps Portal - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/corps-portal.css"/>
  <style>
    .profile-layout{display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start}
    @media(max-width:700px){.profile-layout{grid-template-columns:1fr}}
    .profile-photo-card{background:#fff;border:1px solid var(--border);border-radius:16px;padding:1.5rem;text-align:center}
    .profile-photo{width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--border)}
    .profile-avatar{width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--blue));display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#fff;margin:0 auto}
    .profile-photo-card__name{font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:var(--purple);margin:1rem 0 4px}
    .profile-photo-card__code{font-size:.8rem;color:var(--light);margin-bottom:10px}
    .profile-photo-card__status{display:inline-block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:3px 12px;border-radius:20px;background:#e6f9ed;color:#1a7a3a;margin-bottom:12px}
    .profile-photo-card__note{font-size:.75rem;color:var(--light);line-height:1.5}
    .btn-change-pw{display:inline-block;margin-top:1rem;background:var(--purple);color:#fff;text-decoration:none;padding:9px 20px;border-radius:9px;font-size:.85rem;font-weight:700;font-family:'DM Sans',sans-serif;width:100%;text-align:center;box-sizing:border-box}
  </style>
</head>
<body>
<?php include dirname(__DIR__, 2) . '/src/includes/corps-nav.php'; ?>
<main class="corps-main">
  <div class="corps-inner">
    <div class="page-hero">
      <h1 class="page-hero__title">My Profile</h1>
      <p class="page-hero__sub">Your posting and personal details. Contact the admin to update any information.</p>
    </div>
    <div class="profile-layout">
      <div class="profile-photo-card">
        <div>
          <?php if ($photoSrc): ?>
          <img src="<?php echo $photoSrc; ?>" alt="Photo" class="profile-photo"
               onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="profile-avatar" style="display:none"><?php echo strtoupper(substr($member['full_name'],0,1)); ?></div>
          <?php else: ?>
          <div class="profile-avatar"><?php echo strtoupper(substr($member['full_name'],0,1)); ?></div>
          <?php endif; ?>
        </div>
        <h2 class="profile-photo-card__name"><?php echo htmlspecialchars($member['full_name']); ?></h2>
        <p class="profile-photo-card__code"><?php echo htmlspecialchars($member['state_code']); ?></p>
        <div class="profile-photo-card__status"><?php echo ucfirst(str_replace('_',' ',$member['status'])); ?></div>
        <p class="profile-photo-card__note">To update your photo or details, contact the school admin.</p>
        <a href="change-password.php" class="btn-change-pw">Change Password</a>
      </div>
      <div>
        <div class="detail-card">
          <h3 class="detail-card__title">Personal Information</h3>
          <div class="detail-grid">
            <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value"><?php echo htmlspecialchars($member['full_name']); ?></span></div>
            <div class="detail-row"><span class="detail-label">State Code</span><span class="detail-value"><?php echo htmlspecialchars($member['state_code']); ?></span></div>
            <div class="detail-row"><span class="detail-label">State of Origin</span><span class="detail-value"><?php echo htmlspecialchars($member['state_of_origin']); ?></span></div>
            <div class="detail-row"><span class="detail-label">Batch</span><span class="detail-value"><?php echo htmlspecialchars($member['batch']); ?></span></div>
            <div class="detail-row"><span class="detail-label">Institution</span><span class="detail-value"><?php echo htmlspecialchars($member['institution']); ?></span></div>
            <div class="detail-row"><span class="detail-label">Course Studied</span><span class="detail-value"><?php echo htmlspecialchars($member['course_studied']); ?></span></div>
          </div>
        </div>
        <div class="detail-card">
          <h3 class="detail-card__title">Posting Details</h3>
          <div class="detail-grid">
            <div class="detail-row"><span class="detail-label">Subject Taught</span><span class="detail-value"><?php echo htmlspecialchars($member['subject_taught'] ?? '-'); ?></span></div>
            <div class="detail-row"><span class="detail-label">Section</span><span class="detail-value"><?php echo sectionLabel($member['section']); ?></span></div>
            <div class="detail-row"><span class="detail-label">Class Arms</span><span class="detail-value"><?php echo htmlspecialchars($member['class_arms'] ?? '-'); ?></span></div>
            <div class="detail-row"><span class="detail-label">CDS Group</span><span class="detail-value"><?php echo htmlspecialchars($member['cds_group'] ?? '-'); ?></span></div>
            <div class="detail-row"><span class="detail-label">CDS Day</span><span class="detail-value"><?php echo htmlspecialchars($member['cds_day'] ?? '-'); ?></span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
</body>
</html>