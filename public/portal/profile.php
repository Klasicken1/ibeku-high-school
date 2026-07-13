<?php
/* ============================================================
   IBEKU HIGH SCHOOL â€” STUDENT PORTAL PROFILE
   File: public/portal/profile.php
   Profile is read-only for students.
   Photo updates handled in admin/students-edit.php only.
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/auth.php';

$student = requireStudentLogin();
$pdo     = getDB();

/* Load full student record from DB */
$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
$stmt->execute([$student['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$s = $rows[0] ?? null;

if (!$s) {
    logoutStudent();
    header('Location: login.php');
    exit;
}

$photoSrc = '';
if (!empty($s['photo'])) {
    $photoSrc = '../assets/images/students/' . htmlspecialchars($s['photo']);
}

$deptLabels = [
    'sciences'   => 'Sciences',
    'arts'       => 'Arts',
    'commercial' => 'Commercial',
    'general'    => 'General',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile â€” Ibeku High School Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/portal.css"/>
</head>
<body>

<?php include dirname(__DIR__, 2) . '/src/includes/portal-nav.php'; ?>

<main class="portal-main">
  <div class="portal-inner">

    <div class="page-hero">
      <h1 class="page-hero__title">My Profile</h1>
      <p class="page-hero__sub">Your personal and academic details. Contact your class teacher to update any information.</p>
    </div>

    <div class="profile-layout">

      <!-- Photo card -->
      <div class="profile-photo-card">
        <div class="profile-photo-wrap">
          <?php if ($photoSrc): ?>
          <img src="<?php echo $photoSrc; ?>"
               alt="<?php echo htmlspecialchars($s['first_name']); ?>"
               class="profile-photo"
               onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <div class="profile-avatar" style="display:none">
            <?php echo strtoupper(substr($s['first_name'], 0, 1)); ?>
          </div>
          <?php else: ?>
          <div class="profile-avatar">
            <?php echo strtoupper(substr($s['first_name'], 0, 1)); ?>
          </div>
          <?php endif; ?>
        </div>
        <h2 class="profile-photo-card__name">
          <?php echo htmlspecialchars($s['first_name'] . ' ' . ($s['other_name'] ? $s['other_name'] . ' ' : '') . $s['last_name']); ?>
        </h2>
        <p class="profile-photo-card__adm"><?php echo htmlspecialchars($s['admission_number']); ?></p>
        <div class="profile-photo-card__status <?php echo ($s['status'] ?? '') === 'active' ? 'profile-photo-card__status--active' : ''; ?>">
          <?php echo ucfirst($s['status'] ?? 'active'); ?>
        </div>
        <p class="profile-photo-card__note">
          To update your photo, speak to your class teacher or visit the school office.
        </p>
      </div>

      <!-- Details -->
      <div class="profile-details">

        <div class="detail-card">
          <h3 class="detail-card__title">Personal Information</h3>
          <div class="detail-grid">
            <div class="detail-row">
              <span class="detail-label">First Name</span>
              <span class="detail-value"><?php echo htmlspecialchars($s['first_name']); ?></span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Last Name</span>
              <span class="detail-value"><?php echo htmlspecialchars($s['last_name']); ?></span>
            </div>
            <?php if (!empty($s['other_name'])): ?>
            <div class="detail-row">
              <span class="detail-label">Other Name</span>
              <span class="detail-value"><?php echo htmlspecialchars($s['other_name']); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
              <span class="detail-label">Gender</span>
              <span class="detail-value"><?php echo ucfirst($s['gender'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Date of Birth</span>
              <span class="detail-value">
                <?php echo !empty($s['date_of_birth']) ? date('d F Y', strtotime($s['date_of_birth'])) : 'â€”'; ?>
              </span>
            </div>
            <?php if (!empty($s['address'])): ?>
            <div class="detail-row">
              <span class="detail-label">Address</span>
              <span class="detail-value"><?php echo htmlspecialchars($s['address']); ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="detail-card">
          <h3 class="detail-card__title">Academic Information</h3>
          <div class="detail-grid">
            <div class="detail-row">
              <span class="detail-label">Grade Level</span>
              <span class="detail-value"><?php echo gradeLabel($s['grade_level'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Class</span>
              <span class="detail-value"><?php echo htmlspecialchars($s['class'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Section</span>
              <span class="detail-value"><?php echo sectionLabel($s['section'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Department</span>
              <span class="detail-value"><?php echo $deptLabels[$s['department'] ?? ''] ?? ucfirst($s['department'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Date Admitted</span>
              <span class="detail-value">
                <?php echo !empty($s['date_admitted']) ? date('d F Y', strtotime($s['date_admitted'])) : 'â€”'; ?>
              </span>
            </div>
          </div>
        </div>

        <?php if (!empty($s['parent_name']) || !empty($s['parent_phone'])): ?>
        <div class="detail-card">
          <h3 class="detail-card__title">Parent / Guardian</h3>
          <div class="detail-grid">
            <?php if (!empty($s['parent_name'])): ?>
            <div class="detail-row">
              <span class="detail-label">Name</span>
              <span class="detail-value"><?php echo htmlspecialchars($s['parent_name']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($s['parent_phone'])): ?>
            <div class="detail-row">
              <span class="detail-label">Phone</span>
              <span class="detail-value">
                <a href="tel:<?php echo htmlspecialchars($s['parent_phone']); ?>"
                   style="color:#4a90d9;text-decoration:none">
                  <?php echo htmlspecialchars($s['parent_phone']); ?>
                </a>
              </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($s['parent_email'])): ?>
            <div class="detail-row">
              <span class="detail-label">Email</span>
              <span class="detail-value"><?php echo htmlspecialchars($s['parent_email']); ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</main>

</body>
</html>
