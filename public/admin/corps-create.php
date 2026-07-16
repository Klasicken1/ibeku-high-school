<?php
/* ============================================================
   IBEKU HIGH SCHOOL - ADMIN ADD CORPS MEMBER
   File: public/admin/corps-create.php
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'dean']);

$admin = currentAdmin();
$pdo   = getDB();

$message = ''; $messageType = ''; $formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stateCode    = strtoupper(trim($_POST['state_code']    ?? ''));
    $fullName     = trim($_POST['full_name']     ?? '');
    $stateOrigin  = trim($_POST['state_of_origin'] ?? '');
    $batch        = trim($_POST['batch']         ?? '');
    $institution  = trim($_POST['institution']   ?? '');
    $course       = trim($_POST['course_studied'] ?? '');
    $cdsGroup     = trim($_POST['cds_group']     ?? '');
    $cdsDay       = trim($_POST['cds_day']       ?? '');
    $subject      = trim($_POST['subject_taught'] ?? '');
    $section      = $_POST['section']            ?? 'both';
    $classArms    = trim($_POST['class_arms']    ?? '');
    $phone        = trim($_POST['phone']         ?? '');
    $bankName     = trim($_POST['bank_name']     ?? '');
    $accountName  = trim($_POST['account_name']  ?? '');
    $accountNumber= trim($_POST['account_number'] ?? '');

    $formData = compact('stateCode','fullName','stateOrigin','batch','institution','course',
        'cdsGroup','cdsDay','subject','section','classArms','phone','bankName','accountName','accountNumber');

    if (!$stateCode || !$fullName || !$stateOrigin || !$batch || !$institution || !$course) {
        $message = 'Please fill in all required fields.'; $messageType = 'error';
    } else {
        /* Check duplicate */
        $dup = $pdo->prepare('SELECT id FROM corps_members WHERE state_code = ? LIMIT 1');
        $dup->execute([$stateCode]);
        if ($dup->fetchColumn()) {
            $message = 'State code ' . htmlspecialchars($stateCode) . ' already exists.'; $messageType = 'error';
        }
    }

    /* Photo upload */
    $photoFilename = null;
    if (!$message && !empty($_FILES['photo']['name'])) {
        $uploadDir = dirname(__DIR__) . '/assets/images/corps/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed, true)) {
            $message = 'Photo must be JPG, PNG or WEBP.'; $messageType = 'error';
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $message = 'Photo must be under 2MB.'; $messageType = 'error';
        } else {
            $photoFilename = uniqid('corps_', true) . '.' . $ext;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoFilename)) {
                $message = 'Photo upload failed.'; $messageType = 'error'; $photoFilename = null;
            }
        }
    }

    if (!$message) {
        try {
            $defaultPw = password_hash($stateCode, PASSWORD_DEFAULT);
            $pdo->prepare(
                'INSERT INTO corps_members
                    (state_code,full_name,photo,state_of_origin,batch,institution,
                     course_studied,cds_group,cds_day,subject_taught,section,class_arms,
                     phone,bank_name,account_name,account_number,password,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $stateCode,$fullName,$photoFilename,$stateOrigin,$batch,$institution,
                $course,$cdsGroup,$cdsDay,$subject,$section,$classArms,
                $phone,$bankName,$accountName,$accountNumber,$defaultPw,$admin['id']
            ]);
            $message = htmlspecialchars($fullName) . ' added successfully. Default password is their state code.';
            $messageType = 'success';
            $formData = [];
        } catch (PDOException $e) {
            error_log('IHS corps-create: ' . $e->getMessage());
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}

$sections = ['js'=>'Junior Secondary (JS)','ss'=>'Senior Secondary (SS)','both'=>'Both Sections'];
$days     = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add Corps Member - Admin - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/admin-layout.css"/>
  <style>
    .form-group{margin-bottom:16px}
    .form-label{display:block;font-size:12px;font-weight:600;color:#3d1a6e;margin-bottom:5px;text-transform:uppercase;letter-spacing:.03em}
    .form-input,.form-select,.form-textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e0ea;border-radius:8px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#1a1a2e}
    .form-input:focus,.form-select:focus,.form-textarea:focus{outline:none;border-color:#4a90d9}
    .form-row{display:flex;gap:14px}
    .form-row .form-group{flex:1}
    .form-section{border-top:1px solid #f0eef6;margin:20px 0 16px;padding-top:16px}
    .form-section__label{font-size:13px;font-weight:700;color:#3d1a6e;margin-bottom:14px}
    .btn-save{background:#3d1a6e;color:#fff;border:none;padding:11px 28px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer}
    .btn-save:hover{background:#5a2d9e}
    .btn-cancel{background:#f0ecfa;color:#3d1a6e;border:1.5px solid #d8d0ee;padding:11px 22px;border-radius:8px;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-block}
    .hint{font-size:11.5px;color:#9b97b0;margin-top:3px}
    .photo-preview{width:80px;height:80px;border-radius:10px;object-fit:cover;border:2px solid #e2e0ea;margin-top:8px;display:none}
  </style>
</head>
<body>
<?php renderAdminSidebar($admin, 'corps'); ?>
<div class="admin-content">
  <div class="admin-content__inner">
    <div class="page-header">
      <h2>Add Corps Member</h2>
      <p><a href="corps.php" style="color:#4a90d9;text-decoration:none">Back to Corps Members</a></p>
    </div>
    <?php if ($message): ?>
    <div class="alert alert--<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
    <div class="admin-card" style="max-width:680px">
      <form method="POST" enctype="multipart/form-data">

        <!-- Identity -->
        <div class="form-section">
          <div class="form-section__label">Identity</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">State Code *</label>
              <input type="text" class="form-input" name="state_code" required maxlength="20"
                     value="<?php echo htmlspecialchars(strtoupper($formData['stateCode'] ?? '')); ?>"
                     placeholder="e.g. AB/25C/0245"
                     oninput="this.value=this.value.toUpperCase()"/>
              <p class="hint">Default portal password will be the state code.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" class="form-input" name="full_name" required maxlength="200"
                     value="<?php echo htmlspecialchars($formData['fullName'] ?? ''); ?>"
                     placeholder="e.g. Nweke Kenneth Nnaemeka"/>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">State of Origin *</label>
              <input type="text" class="form-input" name="state_of_origin" required maxlength="100"
                     value="<?php echo htmlspecialchars($formData['stateOrigin'] ?? ''); ?>"
                     placeholder="e.g. Abia State"/>
            </div>
            <div class="form-group">
              <label class="form-label">Batch *</label>
              <input type="text" class="form-input" name="batch" required maxlength="20"
                     value="<?php echo htmlspecialchars($formData['batch'] ?? ''); ?>"
                     placeholder="e.g. 2025 Batch C"/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Profile Photo</label>
            <input type="file" class="form-input" name="photo" accept="image/jpeg,image/png,image/webp"
                   onchange="previewPhoto(this)"/>
            <p class="hint">JPG, PNG or WEBP - max 2MB.</p>
            <img id="photoPreview" class="photo-preview" src="" alt="Preview"/>
          </div>
        </div>

        <!-- Academic -->
        <div class="form-section">
          <div class="form-section__label">Academic Background</div>
          <div class="form-group">
            <label class="form-label">Institution Attended *</label>
            <input type="text" class="form-input" name="institution" required maxlength="255"
                   value="<?php echo htmlspecialchars($formData['institution'] ?? ''); ?>"
                   placeholder="e.g. Michael Okpara University of Agriculture, Umudike"/>
          </div>
          <div class="form-group">
            <label class="form-label">Course Studied *</label>
            <input type="text" class="form-input" name="course_studied" required maxlength="255"
                   value="<?php echo htmlspecialchars($formData['course'] ?? ''); ?>"
                   placeholder="e.g. Computer Engineering"/>
          </div>
        </div>

        <!-- Posting -->
        <div class="form-section">
          <div class="form-section__label">Posting at Ibeku High School</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Subject Taught</label>
              <input type="text" class="form-input" name="subject_taught" maxlength="150"
                     value="<?php echo htmlspecialchars($formData['subject'] ?? ''); ?>"
                     placeholder="e.g. Digital Technology"/>
            </div>
            <div class="form-group">
              <label class="form-label">Section</label>
              <select class="form-select" name="section">
                <?php foreach ($sections as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo ($formData['section'] ?? 'both') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Class Arms</label>
              <input type="text" class="form-input" name="class_arms" maxlength="255"
                     value="<?php echo htmlspecialchars($formData['classArms'] ?? ''); ?>"
                     placeholder="e.g. JSS 1A, JSS 1B, JSS 1C, JSS 1D"/>
            </div>
            <div class="form-group">
              <label class="form-label">CDS Day</label>
              <select class="form-select" name="cds_day">
                <option value="">Select day</option>
                <?php foreach ($days as $d): ?>
                <option value="<?php echo $d; ?>" <?php echo ($formData['cdsDay'] ?? '') === $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">CDS Group</label>
            <input type="text" class="form-input" name="cds_group" maxlength="100"
                   value="<?php echo htmlspecialchars($formData['cdsGroup'] ?? ''); ?>"
                   placeholder="e.g. FIDAPS"/>
          </div>
        </div>

        <!-- Contact & Bank (admin only) -->
        <div class="form-section">
          <div class="form-section__label">Contact & Bank Details <span style="font-weight:400;font-size:12px;color:#9b97b0">(admin only — not shown publicly)</span></div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" class="form-input" name="phone" maxlength="20"
                   value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>"/>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Bank Name</label>
              <input type="text" class="form-input" name="bank_name" maxlength="100"
                     value="<?php echo htmlspecialchars($formData['bankName'] ?? ''); ?>"/>
            </div>
            <div class="form-group">
              <label class="form-label">Account Number</label>
              <input type="text" class="form-input" name="account_number" maxlength="20"
                     value="<?php echo htmlspecialchars($formData['accountNumber'] ?? ''); ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Account Name</label>
            <input type="text" class="form-input" name="account_name" maxlength="200"
                   value="<?php echo htmlspecialchars($formData['accountName'] ?? ''); ?>"/>
          </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px">
          <button type="submit" class="btn-save">Add Corps Member</button>
          <a href="corps.php" class="btn-cancel">Cancel</a>
        </div>

      </form>
    </div>
  </div>
</div>
<script>
function previewPhoto(input){
  var preview=document.getElementById('photoPreview');
  if(input.files&&input.files[0]){
    var reader=new FileReader();
    reader.onload=function(e){preview.src=e.target.result;preview.style.display='block'};
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
<script src="../assets/js/admin.js"></script>
</body>
</html>