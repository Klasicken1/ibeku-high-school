<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SETTINGS / SITE CUSTOMISER
   File: public/admin/settings.php

   Accessible to: superadmin only
   Controls school identity, principal details, academic year,
   feature toggles, and the site-wide announcement banner.
   All values read via getSettings() throughout the site.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin']);

$admin = currentAdmin();
$pdo   = getDB();

$message     = '';
$messageType = '';

/* ── Ensure settings table exists ── */
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS settings (
        `key`        VARCHAR(100) NOT NULL,
        `value`      TEXT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

/* ── Load current settings ── */
$s = getSettings();

/* ── Handle form submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = trim($_POST['section'] ?? 'school');

    $toSave = [];

    if ($section === 'school') {
        $toSave = [
            'school_name'    => trim($_POST['school_name']    ?? ''),
            'school_tagline' => trim($_POST['school_tagline'] ?? ''),
            'school_address' => trim($_POST['school_address'] ?? ''),
            'school_phone'   => trim($_POST['school_phone']   ?? ''),
            'school_email'   => trim($_POST['school_email']   ?? ''),
            'school_website' => trim($_POST['school_website'] ?? ''),
            'school_motto'   => trim($_POST['school_motto']   ?? ''),
        ];
        if ($toSave['school_name'] === '') {
            $message = 'School name is required.'; $messageType = 'error';
        }

    } elseif ($section === 'principals') {
        $toSave = [
            'principal_ss_name'    => trim($_POST['principal_ss_name']    ?? ''),
            'principal_ss_message' => trim($_POST['principal_ss_message'] ?? ''),
            'principal_js_name'    => trim($_POST['principal_js_name']    ?? ''),
            'principal_js_message' => trim($_POST['principal_js_message'] ?? ''),
        ];

    } elseif ($section === 'academic') {
        $session = trim($_POST['current_session'] ?? '');
        if ($session && !preg_match('/^\d{4}\/\d{4}$/', $session)) {
            $message = 'Session must be in format YYYY/YYYY e.g. 2025/2026.'; $messageType = 'error';
        } else {
            $toSave = [
                'current_session'      => $session,
                'current_term'         => trim($_POST['current_term']         ?? 'first'),
                'next_term_resumption' => trim($_POST['next_term_resumption'] ?? ''),
                'result_checker_open'  => isset($_POST['result_checker_open']) ? '1' : '0',
                'admissions_open'      => isset($_POST['admissions_open'])     ? '1' : '0',
            ];
        }

    } elseif ($section === 'announcement') {
        $toSave = [
            'announcement_show'      => isset($_POST['announcement_show']) ? '1' : '0',
            'announcement_text'      => trim($_POST['announcement_text']      ?? ''),
            'announcement_link'      => trim($_POST['announcement_link']      ?? ''),
            'announcement_link_text' => trim($_POST['announcement_link_text'] ?? 'Read more →'),
        ];
    }

    if ($message === '' && !empty($toSave)) {
        try {
            $upsert = $pdo->prepare(
                'INSERT INTO settings (`key`, `value`) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
            );
            foreach ($toSave as $key => $value) {
                $upsert->execute([$key, $value]);
            }
            /* Bust the cache so getSettings() re-reads on next call */
            global $_settings_cache;
            $_settings_cache = null;
            $s = getSettings();

            $message = 'Settings saved.'; $messageType = 'success';
        } catch (PDOException $e) {
            error_log('IHS settings save error: ' . $e->getMessage());
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Settings — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .settings-tabs { display:flex; gap:0; margin-bottom:24px; background:#fff; border:1px solid #e8e6f0; border-radius:12px; overflow:hidden; }
  .settings-tab { flex:1; padding:12px 16px; text-align:center; font-size:13px; font-weight:600; color:#6b6b80; text-decoration:none; border-right:1px solid #e8e6f0; transition:background .15s; cursor:pointer; }
  .settings-tab:last-child { border-right:none; }
  .settings-tab--active { background:#3d1a6e; color:#fff; }
  .settings-tab:not(.settings-tab--active):hover { background:#f4f3f9; color:#3d1a6e; }

  .settings-panel { display:none; }
  .settings-panel--active { display:block; }

  .settings-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:20px; }
  .settings-card__title { font-size:13px; font-weight:700; color:#3d1a6e; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid #f0eef6; }

  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select, .form-textarea { width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .form-row { display:flex; gap:16px; }
  .form-row .form-group { flex:1; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }

  .toggle-row { display:flex; align-items:center; justify-content:space-between; padding:13px 0; border-bottom:1px solid #f4f3f9; }
  .toggle-row:last-child { border-bottom:none; }
  .toggle-row__label { font-size:13.5px; color:#1a1a2e; font-weight:500; }
  .toggle-row__hint  { font-size:12px; color:#9b97b0; margin-top:2px; }
  .toggle { position:relative; display:inline-block; width:44px; height:24px; flex-shrink:0; }
  .toggle input { opacity:0; width:0; height:0; }
  .toggle__slider { position:absolute; cursor:pointer; inset:0; background:#e2e0ea; border-radius:24px; transition:.2s; }
  .toggle__slider::before { content:''; position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.2s; }
  .toggle input:checked + .toggle__slider { background:#3d1a6e; }
  .toggle input:checked + .toggle__slider::before { transform:translateX(20px); }

  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; margin-top:4px; }
  .btn-save:hover { background:#5a2d9e; }

  .announcement-preview {
    background:#1a0835; color:#fff; border-radius:10px; padding:12px 18px;
    font-size:13px; margin-top:14px; display:flex; align-items:center; gap:10px;
  }
  .announcement-preview .pill { background:#4a90d9; color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; flex-shrink:0; }

  .info-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; font-size:13px; padding-top:4px; }
  .info-item__label { font-size:11px; font-weight:700; color:#9b97b0; text-transform:uppercase; margin-bottom:3px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'settings'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Settings &amp; Site Customiser</h2>
        <p>Control school information, principal details, academic year, feature toggles, and announcements.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="settings-tabs">
        <div class="settings-tab settings-tab--active" onclick="switchTab('school')">🏫 School Info</div>
        <div class="settings-tab" onclick="switchTab('principals')">👤 Principals</div>
        <div class="settings-tab" onclick="switchTab('academic')">📅 Academic</div>
        <div class="settings-tab" onclick="switchTab('announcement')">📢 Announcement</div>
        <div class="settings-tab" onclick="switchTab('system')">⚙️ System</div>
      </div>

      <!-- ── School Info ── -->
      <div class="settings-panel settings-panel--active" id="panel-school">
        <form method="POST">
          <input type="hidden" name="section" value="school"/>
          <div class="settings-card">
            <div class="settings-card__title">School Identity</div>
            <div class="form-group">
              <label class="form-label">School Name *</label>
              <input type="text" class="form-input" name="school_name" required maxlength="150"
                     value="<?php echo htmlspecialchars($s['school_name']); ?>"/>
            </div>
            <div class="form-group">
              <label class="form-label">Tagline</label>
              <input type="text" class="form-input" name="school_tagline" maxlength="200"
                     value="<?php echo htmlspecialchars($s['school_tagline']); ?>"
                     placeholder="e.g. Excellence in Education"/>
            </div>
            <div class="form-group">
              <label class="form-label">School Motto</label>
              <input type="text" class="form-input" name="school_motto" maxlength="200"
                     value="<?php echo htmlspecialchars($s['school_motto']); ?>"
                     placeholder="e.g. Knowledge, Discipline, Excellence"/>
              <p class="char-hint">Printed on result slips.</p>
            </div>
          </div>

          <div class="settings-card">
            <div class="settings-card__title">Contact Information</div>
            <div class="form-group">
              <label class="form-label">Address</label>
              <input type="text" class="form-input" name="school_address" maxlength="255"
                     value="<?php echo htmlspecialchars($s['school_address']); ?>"/>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-input" name="school_phone" maxlength="30"
                       value="<?php echo htmlspecialchars($s['school_phone']); ?>"
                       placeholder="+234 000 000 0000"/>
              </div>
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-input" name="school_email" maxlength="150"
                       value="<?php echo htmlspecialchars($s['school_email']); ?>"/>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Website URL</label>
              <input type="url" class="form-input" name="school_website" maxlength="255"
                     value="<?php echo htmlspecialchars($s['school_website']); ?>"/>
            </div>
          </div>
          <button type="submit" class="btn-save">Save School Info</button>
        </form>
      </div>

      <!-- ── Principals ── -->
      <div class="settings-panel" id="panel-principals">
        <form method="POST">
          <input type="hidden" name="section" value="principals"/>
          <div class="settings-card">
            <div class="settings-card__title">Senior Secondary Principal</div>
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-input" name="principal_ss_name" maxlength="150"
                     value="<?php echo htmlspecialchars($s['principal_ss_name']); ?>"
                     placeholder="e.g. Dr. Chukwuemeka Okafor"/>
            </div>
            <div class="form-group">
              <label class="form-label">Welcome Message</label>
              <textarea class="form-textarea" name="principal_ss_message" rows="5"
                        placeholder="Message shown on the About page under SS Principal..."><?php echo htmlspecialchars($s['principal_ss_message']); ?></textarea>
              <p class="char-hint">Displayed on the About page in the Principal's Message section.</p>
            </div>
          </div>

          <div class="settings-card">
            <div class="settings-card__title">Junior Secondary Principal</div>
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-input" name="principal_js_name" maxlength="150"
                     value="<?php echo htmlspecialchars($s['principal_js_name']); ?>"
                     placeholder="e.g. Mrs. Ngozi Eze"/>
            </div>
            <div class="form-group">
              <label class="form-label">Welcome Message</label>
              <textarea class="form-textarea" name="principal_js_message" rows="5"
                        placeholder="Message shown on the About page under JS Principal..."><?php echo htmlspecialchars($s['principal_js_message']); ?></textarea>
              <p class="char-hint">Displayed on the About page in the Principal's Message section.</p>
            </div>
          </div>
          <button type="submit" class="btn-save">Save Principal Details</button>
        </form>
      </div>

      <!-- ── Academic ── -->
      <div class="settings-panel" id="panel-academic">
        <form method="POST">
          <input type="hidden" name="section" value="academic"/>
          <div class="settings-card">
            <div class="settings-card__title">Academic Year</div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Current Session</label>
                <input type="text" class="form-input" name="current_session"
                       pattern="\d{4}\/\d{4}" placeholder="2025/2026"
                       value="<?php echo htmlspecialchars($s['current_session']); ?>"/>
                <p class="char-hint">Pre-fills results entry and publish pages.</p>
              </div>
              <div class="form-group">
                <label class="form-label">Current Term</label>
                <select class="form-select" name="current_term">
                  <option value="first"  <?php echo $s['current_term'] === 'first'  ? 'selected' : ''; ?>>First Term</option>
                  <option value="second" <?php echo $s['current_term'] === 'second' ? 'selected' : ''; ?>>Second Term</option>
                  <option value="third"  <?php echo $s['current_term'] === 'third'  ? 'selected' : ''; ?>>Third Term</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Next Term Resumption Date</label>
              <input type="date" class="form-input" name="next_term_resumption"
                     value="<?php echo htmlspecialchars($s['next_term_resumption']); ?>"/>
              <p class="char-hint">Shown on printed result slips.</p>
            </div>
          </div>

          <div class="settings-card">
            <div class="settings-card__title">Feature Controls</div>
            <div class="toggle-row">
              <div>
                <div class="toggle-row__label">Result Checker</div>
                <div class="toggle-row__hint">Allow students to check results on the public website</div>
              </div>
              <label class="toggle">
                <input type="checkbox" name="result_checker_open" <?php echo $s['result_checker_open'] === '1' ? 'checked' : ''; ?>/>
                <span class="toggle__slider"></span>
              </label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-row__label">Admissions Form</div>
                <div class="toggle-row__hint">Show the online admissions enquiry form to the public</div>
              </div>
              <label class="toggle">
                <input type="checkbox" name="admissions_open" <?php echo $s['admissions_open'] === '1' ? 'checked' : ''; ?>/>
                <span class="toggle__slider"></span>
              </label>
            </div>
          </div>
          <button type="submit" class="btn-save">Save Academic Settings</button>
        </form>
      </div>

      <!-- ── Announcement ── -->
      <div class="settings-panel" id="panel-announcement">
        <form method="POST">
          <input type="hidden" name="section" value="announcement"/>
          <div class="settings-card">
            <div class="settings-card__title">Site-wide Announcement Banner</div>
            <p style="font-size:13px;color:#6b6b80;margin-bottom:16px">
              The announcement bar appears at the top of every public page. Use it for important notices — results available, school resumption dates, admissions open, etc.
            </p>

            <div class="toggle-row" style="margin-bottom:16px">
              <div>
                <div class="toggle-row__label">Show Announcement Banner</div>
                <div class="toggle-row__hint">Toggle on to display the banner on all public pages</div>
              </div>
              <label class="toggle">
                <input type="checkbox" name="announcement_show" id="annToggle"
                       <?php echo $s['announcement_show'] === '1' ? 'checked' : ''; ?>
                       onchange="updatePreview()"/>
                <span class="toggle__slider"></span>
              </label>
            </div>

            <div class="form-group">
              <label class="form-label">Announcement Text *</label>
              <input type="text" class="form-input" name="announcement_text" id="annText"
                     maxlength="300"
                     value="<?php echo htmlspecialchars($s['announcement_text']); ?>"
                     placeholder="e.g. 2024/2025 Third Term results are now available online."
                     oninput="updatePreview()"/>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Link URL (optional)</label>
                <input type="text" class="form-input" name="announcement_link" id="annLink"
                       maxlength="255"
                       value="<?php echo htmlspecialchars($s['announcement_link']); ?>"
                       placeholder="e.g. /results.php or https://..."
                       oninput="updatePreview()"/>
              </div>
              <div class="form-group">
                <label class="form-label">Link Text</label>
                <input type="text" class="form-input" name="announcement_link_text" id="annLinkText"
                       maxlength="60"
                       value="<?php echo htmlspecialchars($s['announcement_link_text']); ?>"
                       placeholder="e.g. Check results →"
                       oninput="updatePreview()"/>
              </div>
            </div>

            <!-- Live preview -->
            <div class="announcement-preview" id="annPreview"
                 style="<?php echo $s['announcement_show'] !== '1' ? 'opacity:.4' : ''; ?>">
              <span class="pill">NOTICE</span>
              <span id="annPreviewText"><?php echo htmlspecialchars($s['announcement_text'] ?: 'Your announcement text will appear here.'); ?></span>
              <?php if ($s['announcement_link']): ?>
              <a id="annPreviewLink" href="<?php echo htmlspecialchars($s['announcement_link']); ?>"
                 style="color:#4a90d9;font-size:12.5px;white-space:nowrap">
                <?php echo htmlspecialchars($s['announcement_link_text']); ?>
              </a>
              <?php else: ?>
              <span id="annPreviewLink" style="display:none"></span>
              <?php endif; ?>
            </div>
            <p class="char-hint" style="margin-top:8px">Preview of how the banner will appear on the public website.</p>
          </div>
          <button type="submit" class="btn-save">Save Announcement</button>
        </form>
      </div>

      <!-- ── System Info ── -->
      <div class="settings-panel" id="panel-system">
        <div class="settings-card">
          <div class="settings-card__title">System Information</div>
          <div class="info-row">
            <div>
              <div class="info-item__label">PHP Version</div>
              <div><?php echo PHP_VERSION; ?></div>
            </div>
            <div>
              <div class="info-item__label">Server</div>
              <div><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></div>
            </div>
            <div>
              <div class="info-item__label">Logged In As</div>
              <div><?php echo htmlspecialchars($admin['name']); ?></div>
            </div>
            <div>
              <div class="info-item__label">Server Time</div>
              <div><?php echo date('d M Y, g:ia'); ?></div>
            </div>
            <div>
              <div class="info-item__label">Current Session</div>
              <div><?php echo htmlspecialchars($s['current_session']); ?></div>
            </div>
            <div>
              <div class="info-item__label">Current Term</div>
              <div><?php echo ucfirst($s['current_term']); ?> Term</div>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="settings-card__title">Superadmin Credentials</div>
          <div style="font-size:13px;color:#6b6b80;line-height:1.8">
            <div><strong style="color:#1a1a2e">Email:</strong> <?php echo htmlspecialchars($admin['name'] ?? ''); ?> — change via Manage Users → Edit</div>
            <div><strong style="color:#1a1a2e">Password:</strong> Change via Manage Users → Edit → Reset Password</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    /* ── Tab switching ── */
    function switchTab(name) {
      document.querySelectorAll('.settings-tab').forEach(function (t) {
        t.classList.remove('settings-tab--active');
      });
      document.querySelectorAll('.settings-panel').forEach(function (p) {
        p.classList.remove('settings-panel--active');
      });
      event.currentTarget.classList.add('settings-tab--active');
      document.getElementById('panel-' + name).classList.add('settings-panel--active');
    }

    /* ── Live announcement preview ── */
    function updatePreview() {
      var toggle   = document.getElementById('annToggle');
      var textEl   = document.getElementById('annText');
      var linkEl   = document.getElementById('annLink');
      var linkTxtEl = document.getElementById('annLinkText');
      var preview  = document.getElementById('annPreview');
      var prevText = document.getElementById('annPreviewText');
      var prevLink = document.getElementById('annPreviewLink');

      preview.style.opacity = toggle.checked ? '1' : '0.4';
      prevText.textContent  = textEl.value || 'Your announcement text will appear here.';

      if (linkEl.value.trim()) {
        prevLink.style.display = '';
        prevLink.href          = linkEl.value.trim();
        prevLink.textContent   = linkTxtEl.value || 'Read more →';
      } else {
        prevLink.style.display = 'none';
      }
    }
  </script>

</body>
</html>