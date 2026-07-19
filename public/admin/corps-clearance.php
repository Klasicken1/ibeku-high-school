<?php
/* ============================================================
   IBEKU HIGH SCHOOL - ADMIN CORPS CLEARANCE MANAGEMENT
   File: public/admin/corps-clearance.php
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_admin', 'vp_academics', 'vp_general', 'dean']);

$admin = currentAdmin();
$pdo   = getDB();

/* Self-healing column adds — same pattern used throughout the app */
try {
    $pdo->exec(
        "ALTER TABLE corps_clearance
         ADD COLUMN conduct_rating ENUM('diligently','well','deceitfully','grudgingly') NOT NULL DEFAULT 'diligently',
         ADD COLUMN payment_status ENUM('allowed','not_allowed') NOT NULL DEFAULT 'allowed'"
    );
} catch (PDOException $e) { /* Columns already exist — fine */ }

$memberId = (int) ($_GET['id'] ?? 0);
if (!$memberId) { header('Location: corps.php'); exit; }

$mStmt = $pdo->prepare('SELECT * FROM corps_members WHERE id = ? LIMIT 1');
$mStmt->execute([$memberId]);
$rows   = $mStmt->fetchAll(PDO::FETCH_ASSOC);
$member = $rows[0] ?? null;
if (!$member) { header('Location: corps.php'); exit; }

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month    = (int) ($_POST['month']     ?? 0);
    $year     = (int) ($_POST['year']      ?? 0);
    $cleared  = (int) ($_POST['is_cleared'] ?? 0);
    $remarks  = trim($_POST['remarks']     ?? '');
    $conduct  = $_POST['conduct_rating'] ?? 'diligently';
    $payment  = $_POST['payment_status'] ?? 'allowed';

    $validConduct = ['diligently', 'well', 'deceitfully', 'grudgingly'];
    $validPayment = ['allowed', 'not_allowed'];
    if (!in_array($conduct, $validConduct, true)) $conduct = 'diligently';
    if (!in_array($payment, $validPayment, true)) $payment = 'allowed';

    if ($month < 1 || $month > 12 || $year < 2020) {
        $message = 'Invalid month or year.'; $messageType = 'error';
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO corps_clearance
                    (corps_member_id, month, year, is_cleared, cleared_by, cleared_at, remarks, conduct_rating, payment_status)
                 VALUES (?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                    is_cleared     = VALUES(is_cleared),
                    cleared_by     = VALUES(cleared_by),
                    cleared_at     = VALUES(cleared_at),
                    remarks        = VALUES(remarks),
                    conduct_rating = VALUES(conduct_rating),
                    payment_status = VALUES(payment_status)"
            )->execute([
                $memberId, $month, $year, $cleared,
                $cleared ? $admin['id'] : null,
                $cleared ? date('Y-m-d H:i:s') : null,
                $remarks ?: null,
                $conduct, $payment,
            ]);
            $message = 'Clearance record saved.'; $messageType = 'success';
        } catch (PDOException $e) {
            error_log('IHS corps-clearance: ' . $e->getMessage());
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}

/* Load all clearance records for this member */
$clStmt = $pdo->prepare(
    'SELECT c.*, u.full_name AS cleared_by_name
     FROM corps_clearance c
     LEFT JOIN users u ON u.id = c.cleared_by
     WHERE c.corps_member_id = ?
     ORDER BY c.year DESC, c.month DESC'
);
$clStmt->execute([$memberId]);
$clearances = $clStmt->fetchAll(PDO::FETCH_ASSOC);

$months = ['','January','February','March','April','May','June',
           'July','August','September','October','November','December'];
$currentYear = (int) date('Y');
$years = range($currentYear, $currentYear - 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Corps Clearance - Admin - Ibeku High School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/admin-layout.css"/>
  <style>
    .member-banner{background:linear-gradient(135deg,#3d1a6e,#5a2d9e);border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:14px;margin-bottom:20px}
    .member-banner__avatar{width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.3);flex-shrink:0;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:#fff}
    .member-banner__name{font-family:'Playfair Display',serif;font-size:1.1rem;color:#fff;font-weight:700}
    .member-banner__code{font-size:.8rem;color:rgba(255,255,255,.7)}
    .grid{display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start}
    @media(max-width:800px){.grid{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #e8e6f0;border-radius:14px;overflow:hidden}
    .card__header{padding:14px 18px;font-size:14px;font-weight:700;color:#3d1a6e;border-bottom:1px solid #f0eef6}
    .card__body{padding:18px}
    .form-group{margin-bottom:14px}
    .form-label{display:block;font-size:12px;font-weight:600;color:#3d1a6e;margin-bottom:5px;text-transform:uppercase;letter-spacing:.03em}
    .form-select,.form-textarea,.form-input{width:100%;padding:9px 12px;border:1.5px solid #e2e0ea;border-radius:8px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#1a1a2e}
    .form-select:focus,.form-textarea:focus,.form-input:focus{outline:none;border-color:#4a90d9}
    .btn-save{background:#3d1a6e;color:#fff;border:none;padding:10px 24px;border-radius:8px;font-size:13.5px;font-weight:700;cursor:pointer;width:100%}
    .btn-save:hover{background:#5a2d9e}
    .toggle-btns{display:flex;gap:8px;margin-bottom:6px}
    .radio-row{display:flex;flex-wrap:wrap;gap:12px;font-size:13px;color:#1a1a2e}
    .radio-row label{display:flex;align-items:center;gap:5px;cursor:pointer}
    .toggle-btn{flex:1;padding:9px;border-radius:8px;border:2px solid #e2e0ea;background:#fff;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:.15s}
    .toggle-btn.cleared{border-color:#1a7a3a;background:#e6f9ed;color:#1a7a3a}
    .toggle-btn.pending{border-color:#cc3333;background:#fff0f0;color:#cc3333}
    table.cl-table{width:100%;border-collapse:collapse;font-size:13px}
    .cl-table th{background:#3d1a6e;color:#fff;padding:9px 14px;text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.04em}
    .cl-table td{padding:9px 14px;border-bottom:1px solid #f0eef6}
    .cl-table tr:last-child td{border-bottom:none}
    .badge-cleared{background:#e6f9ed;color:#1a7a3a;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase}
    .badge-pending{background:#f0eef6;color:#9b97b0;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase}
    .empty-state{padding:30px 20px;text-align:center;color:#9b97b0;font-size:13px}
  </style>
</head>
<body>
<?php renderAdminSidebar($admin, 'corps'); ?>
<div class="admin-content">
  <div class="admin-content__inner">

    <div class="page-header">
      <h2>Corps Clearance</h2>
      <p><a href="corps.php" style="color:#4a90d9;text-decoration:none">Back to Corps Members</a></p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Member banner -->
    <div class="member-banner">
      <?php
        $photoSrc = !empty($member['photo']) ? '../assets/images/corps/' . htmlspecialchars($member['photo']) : '';
        $initial  = strtoupper(substr($member['full_name'], 0, 1));
      ?>
      <?php if ($photoSrc): ?>
      <img src="<?php echo $photoSrc; ?>" class="member-banner__avatar" alt=""
           onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"/>
      <div class="member-banner__avatar" style="display:none"><?php echo $initial; ?></div>
      <?php else: ?>
      <div class="member-banner__avatar"><?php echo $initial; ?></div>
      <?php endif; ?>
      <div>
        <div class="member-banner__name"><?php echo htmlspecialchars($member['full_name']); ?></div>
        <div class="member-banner__code"><?php echo htmlspecialchars($member['state_code']); ?> &nbsp;·&nbsp; Batch <?php echo htmlspecialchars($member['batch']); ?></div>
      </div>
    </div>

    <div class="grid">
      <!-- Add/update clearance form -->
      <div class="card">
        <div class="card__header">Add / Update Clearance</div>
        <div class="card__body">
          <form method="POST" id="clearForm">
            <div class="form-group">
              <label class="form-label">Month *</label>
              <select class="form-select" name="month" required>
                <option value="">Select month</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $months[$i]; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Year *</label>
              <select class="form-select" name="year" required>
                <?php foreach ($years as $y): ?>
                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Clearance Status *</label>
              <div class="toggle-btns">
                <button type="button" id="btnCleared" class="toggle-btn"
                        onclick="setCleared(1)">Cleared</button>
                <button type="button" id="btnPending" class="toggle-btn"
                        onclick="setCleared(0)">Pending</button>
              </div>
              <input type="hidden" name="is_cleared" id="isClearedInput" value=""/>
            </div>
            <div class="form-group">
              <label class="form-label">Conduct — "The corps member served the school..."</label>
              <div class="radio-row">
                <label><input type="radio" name="conduct_rating" value="diligently" checked/> Diligently</label>
                <label><input type="radio" name="conduct_rating" value="well"/> Well</label>
                <label><input type="radio" name="conduct_rating" value="deceitfully"/> Deceitfully</label>
                <label><input type="radio" name="conduct_rating" value="grudgingly"/> Grudgingly</label>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Payment — "He/She should be..."</label>
              <div class="radio-row">
                <label><input type="radio" name="payment_status" value="allowed" checked/> Allowed to sign &amp; be paid</label>
                <label><input type="radio" name="payment_status" value="not_allowed"/> Not allowed</label>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Remarks</label>
              <textarea class="form-textarea" name="remarks" rows="3"
                        placeholder="Optional remarks..."></textarea>
            </div>
            <button type="submit" class="btn-save"
                    onclick="if(!document.getElementById('isClearedInput').value){alert('Please select a clearance status.');return false}">
              Save Clearance Record
            </button>
          </form>
        </div>
      </div>

      <!-- Clearance history -->
      <div class="card">
        <div class="card__header">Clearance History</div>
        <?php if (empty($clearances)): ?>
        <div class="empty-state">No clearance records yet. Add one using the form.</div>
        <?php else: ?>
        <table class="cl-table">
          <thead>
            <tr>
              <th>Month</th>
              <th>Status</th>
              <th>Conduct / Payment</th>
              <th>Cleared By</th>
              <th>Date</th>
              <th>Letter</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $conductLabels = ['diligently' => 'Diligently', 'well' => 'Well', 'deceitfully' => 'Deceitfully', 'grudgingly' => 'Grudgingly'];
            $paymentLabels = ['allowed' => 'Allowed', 'not_allowed' => 'Not Allowed'];
            foreach ($clearances as $c):
            ?>
            <tr>
              <td><?php echo $months[$c['month']] . ' ' . $c['year']; ?></td>
              <td>
                <?php if ($c['is_cleared']): ?>
                <span class="badge-cleared">Cleared</span>
                <?php else: ?>
                <span class="badge-pending">Pending</span>
                <?php endif; ?>
              </td>
              <td style="font-size:11.5px;color:#6b6b80">
                <?php echo htmlspecialchars($conductLabels[$c['conduct_rating'] ?? 'diligently'] ?? 'Diligently'); ?>
                /
                <?php echo htmlspecialchars($paymentLabels[$c['payment_status'] ?? 'allowed'] ?? 'Allowed'); ?>
              </td>
              <td style="font-size:12px;color:#6b6b80"><?php echo htmlspecialchars($c['cleared_by_name'] ?? '-'); ?></td>
              <td style="font-size:12px;color:#9b97b0">
                <?php echo $c['cleared_at'] ? date('d M Y', strtotime($c['cleared_at'])) : '-'; ?>
              </td>
              <td>
                <?php if ($c['is_cleared']): ?>
                <a href="corps-letter.php?id=<?php echo $memberId; ?>&year=<?php echo $c['year']; ?>&month=<?php echo $c['month']; ?>"
                   target="_blank"
                   style="font-size:11.5px;font-weight:700;color:#3d1a6e;text-decoration:none">
                  Preview
                </a>
                &nbsp;
                <a href="corps-letter.php?id=<?php echo $memberId; ?>&year=<?php echo $c['year']; ?>&month=<?php echo $c['month']; ?>&download=1"
                   style="font-size:11.5px;font-weight:700;color:#1a7a3a;text-decoration:none">
                  Download
                </a>
                <?php else: ?>
                <span style="font-size:11.5px;color:#c8c4dc">N/A</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
<script>
function setCleared(val){
  document.getElementById('isClearedInput').value=val;
  document.getElementById('btnCleared').className='toggle-btn'+(val==1?' cleared':'');
  document.getElementById('btnPending').className='toggle-btn'+(val==0?' pending':'');
}
</script>
<script src="../assets/js/admin.js"></script>
</body>
</html>