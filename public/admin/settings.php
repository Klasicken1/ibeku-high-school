<?php
/* ============================================================
   IBEKU HIGH SCHOOL — SETTINGS / SITE CUSTOMISER
   File: public/admin/settings.php

   Accessible to: superadmin only
   Controls school identity, principal details, academic year,
   feature toggles, announcement banner, popup notification,
   YouTube embed, and school operating hours.
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
    $toSave  = [];

    if ($section === 'school') {
        $toSave = [
            'school_name'    => trim($_POST['school_name']    ?? ''),
            'school_tagline' => trim($_POST['school_tagline'] ?? ''),
            'school_address' => trim($_POST['school_address'] ?? ''),
            'school_phone'   => trim($_POST['school_phone']   ?? ''),
            'school_email'   => trim($_POST['school_email']   ?? ''),
            'school_website' => trim($_POST['school_website'] ?? ''),
            'school_motto'   => trim($_POST['school_motto']   ?? ''),
            'school_hours'   => trim($_POST['school_hours']   ?? ''),
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

        /* ── Signature/stamp uploads — one optional file per field.
           Keeps the existing saved filename if no new file is chosen. ── */
        $sigUploadDir = dirname(__DIR__) . '/assets/images/signatures/';
        if (!is_dir($sigUploadDir)) mkdir($sigUploadDir, 0755, true);

        foreach (['principal_ss_signature', 'principal_ss_stamp', 'principal_js_signature', 'principal_js_stamp'] as $fieldKey) {
            if (!empty($_FILES[$fieldKey]['name'])) {
                $ext     = strtolower(pathinfo($_FILES[$fieldKey]['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    $message = ucwords(str_replace('_', ' ', $fieldKey)) . ' must be JPG, PNG, or WEBP.';
                    $messageType = 'error';
                } elseif ($_FILES[$fieldKey]['size'] > 1 * 1024 * 1024) {
                    $message = ucwords(str_replace('_', ' ', $fieldKey)) . ' must be under 1MB.';
                    $messageType = 'error';
                } else {
                    $newFilename = uniqid('sig_', true) . '.' . $ext;
                    if (move_uploaded_file($_FILES[$fieldKey]['tmp_name'], $sigUploadDir . $newFilename)) {
                        $toSave[$fieldKey] = $newFilename;
                    }
                }
            }
        }

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

    } elseif ($section === 'popup') {
        $toSave = [
            'popup_show'            => isset($_POST['popup_show']) ? '1' : '0',
            'popup_title'           => trim($_POST['popup_title']           ?? ''),
            'popup_text'            => trim($_POST['popup_text']            ?? ''),
            'popup_link'            => trim($_POST['popup_link']            ?? ''),
            'popup_link_text'       => trim($_POST['popup_link_text']       ?? 'Learn more →'),
            'popup_trigger_scroll'  => (string) max(0, min(100, (int) ($_POST['popup_trigger_scroll']  ?? 20))),
            'popup_trigger_seconds' => (string) max(0, min(120, (int) ($_POST['popup_trigger_seconds'] ?? 5))),
        ];

    } elseif ($section === 'media') {
        $ytId = trim($_POST['youtube_video_id'] ?? '');
        /* Accept full YouTube URLs or bare video IDs */
        if ($ytId && preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $ytId, $m)) {
            $ytId = $m[1];
        }
        $toSave = [
            'youtube_video_id'    => $ytId,
            'youtube_video_title' => trim($_POST['youtube_video_title'] ?? ''),
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
  .settings-tabs {
    display:flex; gap:0; margin-bottom:24px;
    background:#fff; border:1px solid #e8e6f0; border-radius:12px;
    overflow-x:auto; -webkit-overflow-scrolling:touch;
  }
  .settings-tab {
    flex:1; min-width:100px; padding:12px 14px; text-align:center;
    font-size:12.5px; font-weight:600; color:#6b6b80;
    border-right:1px solid #e8e6f0; transition:background .15s; cursor:pointer;
    white-space:nowrap;
  }
  .settings-tab:last-child { border-right:none; }
  .settings-tab--active { background:#3d1a6e; color:#fff; }
  .settings-tab:not(.settings-tab--active):hover { background:#f4f3f9; color:#3d1a6e; }

  .settings-panel { display:none; }
  .settings-panel--active { display:block; }

  .settings-card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:20px; }
  .settings-card__title { font-size:13px; font-weight:700; color:#3d1a6e; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid #f0eef6; }

  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select, .form-textarea {
    width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px;
    font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e;
  }
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

  /* Announcement preview */
  .ann-preview {
    background:#1a0835; color:#fff; border-radius:10px; padding:12px 18px;
    font-size:13px; margin-top:14px; display:flex; align-items:center; gap:10px;
  }
  .ann-preview .pill { background:#4a90d9; color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; flex-shrink:0; }

  /* Popup preview */
  .popup-preview {
    background:#fff; border:1px solid #e8e6f0; border-radius:16px;
    padding:22px 20px 20px; max-width:340px; position:relative;
    box-shadow:0 8px 28px rgba(26,8,53,.14); margin-top:16px;
  }
  .popup-preview__close {
    position:absolute; top:10px; right:10px; width:24px; height:24px;
    border-radius:50%; background:#f0ecfa; color:#3d1a6e;
    display:flex; align-items:center; justify-content:center; font-size:11px;
    border:none; cursor:default;
  }
  .popup-preview__title { font-size:16px; font-weight:700; color:#3d1a6e; margin:0 24px 8px 0; font-family:'Playfair Display',serif; }
  .popup-preview__text  { font-size:13px; color:#6b6b80; line-height:1.6; margin-bottom:12px; }
  .popup-preview__cta   { display:inline-block; background:#3d1a6e; color:#fff; font-size:12.5px; font-weight:600; padding:8px 16px; border-radius:7px; }

  /* YouTube preview */
  .yt-preview {
    margin-top:14px; border-radius:12px; overflow:hidden;
    background:#000; aspect-ratio:16/9; max-width:480px;
  }
  .yt-preview iframe { width:100%; height:100%; border:0; }
  .yt-placeholder {
    display:flex; align-items:center; justify-content:center;
    height:100%; min-height:180px; background:#1a1a2e;
    color:rgba(255,255,255,.4); font-size:13px; border-radius:12px;
    flex-direction:column; gap:8px;
  }
  .yt-placeholder span { font-size:32px; }

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
        <p>Control school information, principal details, academic year, feature toggles, announcements, popups, and media.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="settings-tabs">
        <div class="settings-tab settings-tab--active" onclick="switchTab('school', this)">🏫 School Info</div>
        <div class="settings-tab" onclick="switchTab('principals', this)">👤 Principals</div>
        <div class="settings-tab" onclick="switchTab('academic', this)">📅 Academic</div>
        <div class="settings-tab" onclick="switchTab('announcement', this)">📢 Announcement</div>
        <div class="settings-tab" onclick="switchTab('popup', this)">💬 Popup</div>
        <div class="settings-tab" onclick="switchTab('media', this)">🎬 Media</div>
        <div class="settings-tab" onclick="switchTab('system', this)">⚙️ System</div>
      </div>


      <!-- ════════════════════════════════════════
           SCHOOL INFO
           ════════════════════════════════════════ -->
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
            <div class="settings-card__title">Contact Information &amp; Hours</div>
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
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Website URL</label>
                <input type="url" class="form-input" name="school_website" maxlength="255"
                       value="<?php echo htmlspecialchars($s['school_website']); ?>"/>
              </div>
              <div class="form-group">
                <label class="form-label">Days &amp; Hours of Operation</label>
                <input type="text" class="form-input" name="school_hours" maxlength="100"
                       value="<?php echo htmlspecialchars($s['school_hours']); ?>"
                       placeholder="e.g. Mon – Fri: 8:00 AM – 3:00 PM"/>
                <p class="char-hint">Shown in the footer contact strip.</p>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-save">Save School Info</button>
        </form>
      </div>


      <!-- ════════════════════════════════════════
           PRINCIPALS
           ════════════════════════════════════════ -->
      <div class="settings-panel" id="panel-principals">
        <form method="POST" enctype="multipart/form-data">
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
                        placeholder="Message shown on the About page and Homepage..."><?php echo htmlspecialchars($s['principal_ss_message']); ?></textarea>
              <p class="char-hint">Shown on the About page (Principal's Message) and the Homepage principal section.</p>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Signature Image</label>
                <?php if (!empty($s['principal_ss_signature'])): ?>
                <img src="../assets/images/signatures/<?php echo htmlspecialchars($s['principal_ss_signature']); ?>"
                     style="height:50px;display:block;margin-bottom:8px;background:#f8f7fc;border-radius:6px;padding:4px"/>
                <?php endif; ?>
                <input type="file" class="form-input" name="principal_ss_signature" accept="image/png,image/jpeg,image/webp"/>
                <p class="char-hint">PNG with transparent background works best. Used on clearance letters and result sheets.</p>
              </div>
              <div class="form-group">
                <label class="form-label">Stamp Image</label>
                <?php if (!empty($s['principal_ss_stamp'])): ?>
                <img src="../assets/images/signatures/<?php echo htmlspecialchars($s['principal_ss_stamp']); ?>"
                     style="height:50px;display:block;margin-bottom:8px;background:#f8f7fc;border-radius:6px;padding:4px"/>
                <?php endif; ?>
                <input type="file" class="form-input" name="principal_ss_stamp" accept="image/png,image/jpeg,image/webp"/>
              </div>
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
                        placeholder="Message shown on the About page..."><?php echo htmlspecialchars($s['principal_js_message']); ?></textarea>
              <p class="char-hint">Shown on the About page under the JS Principal's Message section.</p>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Signature Image</label>
                <?php if (!empty($s['principal_js_signature'])): ?>
                <img src="../assets/images/signatures/<?php echo htmlspecialchars($s['principal_js_signature']); ?>"
                     style="height:50px;display:block;margin-bottom:8px;background:#f8f7fc;border-radius:6px;padding:4px"/>
                <?php endif; ?>
                <input type="file" class="form-input" name="principal_js_signature" accept="image/png,image/jpeg,image/webp"/>
                <p class="char-hint">PNG with transparent background works best. Used on clearance letters and result sheets.</p>
              </div>
              <div class="form-group">
                <label class="form-label">Stamp Image</label>
                <?php if (!empty($s['principal_js_stamp'])): ?>
                <img src="../assets/images/signatures/<?php echo htmlspecialchars($s['principal_js_stamp']); ?>"
                     style="height:50px;display:block;margin-bottom:8px;background:#f8f7fc;border-radius:6px;padding:4px"/>
                <?php endif; ?>
                <input type="file" class="form-input" name="principal_js_stamp" accept="image/png,image/jpeg,image/webp"/>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-save">Save Principal Details</button>
        </form>
      </div>


      <!-- ════════════════════════════════════════
           ACADEMIC
           ════════════════════════════════════════ -->
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
                <p class="char-hint">Format: YYYY/YYYY. Pre-fills results entry and publish pages.</p>
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
                <input type="checkbox" name="result_checker_open"
                       <?php echo $s['result_checker_open'] === '1' ? 'checked' : ''; ?>/>
                <span class="toggle__slider"></span>
              </label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-row__label">Admissions Form</div>
                <div class="toggle-row__hint">Show the online admissions enquiry form to the public</div>
              </div>
              <label class="toggle">
                <input type="checkbox" name="admissions_open"
                       <?php echo $s['admissions_open'] === '1' ? 'checked' : ''; ?>/>
                <span class="toggle__slider"></span>
              </label>
            </div>
          </div>

          <button type="submit" class="btn-save">Save Academic Settings</button>
        </form>
      </div>


      <!-- ════════════════════════════════════════
           ANNOUNCEMENT BAR
           ════════════════════════════════════════ -->
      <div class="settings-panel" id="panel-announcement">
        <form method="POST">
          <input type="hidden" name="section" value="announcement"/>

          <div class="settings-card">
            <div class="settings-card__title">Site-wide Announcement Banner</div>
            <p style="font-size:13px;color:#6b6b80;margin-bottom:16px">
              A slim banner that appears just below the navigation on every public page. Use it for important notices — results available, school resumption dates, admissions open, etc. Visitors can dismiss it for the session.
            </p>

            <div class="toggle-row" style="margin-bottom:16px">
              <div>
                <div class="toggle-row__label">Show Announcement Banner</div>
                <div class="toggle-row__hint">Toggle on to display the banner on all public pages</div>
              </div>
              <label class="toggle">
                <input type="checkbox" name="announcement_show" id="annToggle"
                       <?php echo $s['announcement_show'] === '1' ? 'checked' : ''; ?>
                       onchange="updateAnnPreview()"/>
                <span class="toggle__slider"></span>
              </label>
            </div>

            <div class="form-group">
              <label class="form-label">Announcement Text *</label>
              <input type="text" class="form-input" name="announcement_text" id="annText"
                     maxlength="300"
                     value="<?php echo htmlspecialchars($s['announcement_text']); ?>"
                     placeholder="e.g. 2024/2025 Third Term results are now available online."
                     oninput="updateAnnPreview()"/>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Link URL (optional)</label>
                <input type="text" class="form-input" name="announcement_link" id="annLink"
                       maxlength="255"
                       value="<?php echo htmlspecialchars($s['announcement_link']); ?>"
                       placeholder="e.g. /results.php"
                       oninput="updateAnnPreview()"/>
              </div>
              <div class="form-group">
                <label class="form-label">Link Text</label>
                <input type="text" class="form-input" name="announcement_link_text" id="annLinkText"
                       maxlength="60"
                       value="<?php echo htmlspecialchars($s['announcement_link_text']); ?>"
                       placeholder="e.g. Check results →"
                       oninput="updateAnnPreview()"/>
              </div>
            </div>

            <div class="ann-preview" id="annPreview"
                 style="<?php echo $s['announcement_show'] !== '1' ? 'opacity:.4' : ''; ?>">
              <span class="pill">NOTICE</span>
              <span id="annPreviewText"><?php echo htmlspecialchars($s['announcement_text'] ?: 'Your announcement text will appear here.'); ?></span>
              <span id="annPreviewLink" style="color:#4a90d9;font-size:12.5px;<?php echo empty($s['announcement_link']) ? 'display:none' : ''; ?>">
                <?php echo htmlspecialchars($s['announcement_link_text'] ?: 'Read more →'); ?>
              </span>
            </div>
            <p class="char-hint" style="margin-top:8px">Live preview of the announcement bar.</p>
          </div>

          <button type="submit" class="btn-save">Save Announcement</button>
        </form>
      </div>


      <!-- ════════════════════════════════════════
           POPUP NOTIFICATION
           ════════════════════════════════════════ -->
      <div class="settings-panel" id="panel-popup">
        <form method="POST">
          <input type="hidden" name="section" value="popup"/>

          <div class="settings-card">
            <div class="settings-card__title">Intrusive Popup Notification</div>
            <p style="font-size:13px;color:#6b6b80;margin-bottom:16px">
              A separate popup card that appears in the bottom-right corner — independent of the announcement bar. Triggers after the visitor scrolls a set percentage of the page OR stays for a set number of seconds, whichever happens first. Visitors can dismiss it for the session. Use sparingly for high-priority notices.
            </p>

            <div class="toggle-row" style="margin-bottom:16px">
              <div>
                <div class="toggle-row__label">Show Popup Notification</div>
                <div class="toggle-row__hint">Toggle on to enable the popup across all public pages</div>
              </div>
              <label class="toggle">
                <input type="checkbox" name="popup_show" id="popupToggle"
                       <?php echo $s['popup_show'] === '1' ? 'checked' : ''; ?>
                       onchange="updatePopupPreview()"/>
                <span class="toggle__slider"></span>
              </label>
            </div>

            <div class="form-group">
              <label class="form-label">Popup Title (optional)</label>
              <input type="text" class="form-input" name="popup_title" id="popupTitle"
                     maxlength="100"
                     value="<?php echo htmlspecialchars($s['popup_title']); ?>"
                     placeholder="e.g. Admissions Now Open!"
                     oninput="updatePopupPreview()"/>
            </div>

            <div class="form-group">
              <label class="form-label">Popup Message *</label>
              <textarea class="form-textarea" name="popup_text" id="popupText" rows="3" maxlength="300"
                        oninput="updatePopupPreview()"
                        placeholder="e.g. Applications for the 2025/2026 session are now open. Limited spaces available."><?php echo htmlspecialchars($s['popup_text']); ?></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Link URL (optional)</label>
                <input type="text" class="form-input" name="popup_link" id="popupLink"
                       maxlength="255"
                       value="<?php echo htmlspecialchars($s['popup_link']); ?>"
                       placeholder="e.g. /admissions.php"
                       oninput="updatePopupPreview()"/>
              </div>
              <div class="form-group">
                <label class="form-label">Button Text</label>
                <input type="text" class="form-input" name="popup_link_text" id="popupLinkText"
                       maxlength="60"
                       value="<?php echo htmlspecialchars($s['popup_link_text']); ?>"
                       oninput="updatePopupPreview()"/>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Trigger — Scroll %</label>
                <input type="number" class="form-input" name="popup_trigger_scroll"
                       min="0" max="100"
                       value="<?php echo htmlspecialchars($s['popup_trigger_scroll']); ?>"/>
                <p class="char-hint">Popup shows once visitor scrolls this % of the page (0–100).</p>
              </div>
              <div class="form-group">
                <label class="form-label">Trigger — Seconds on Page</label>
                <input type="number" class="form-input" name="popup_trigger_seconds"
                       min="0" max="120"
                       value="<?php echo htmlspecialchars($s['popup_trigger_seconds']); ?>"/>
                <p class="char-hint">Popup shows after this many seconds, whichever trigger fires first.</p>
              </div>
            </div>

            <!-- Live preview -->
            <p style="font-size:12px;font-weight:600;color:#3d1a6e;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em">Live Preview</p>
            <div class="popup-preview" id="popupPreview"
                 style="<?php echo $s['popup_show'] !== '1' ? 'opacity:.4' : ''; ?>">
              <div class="popup-preview__close">✕</div>
              <div class="popup-preview__title" id="popupPreviewTitle">
                <?php echo htmlspecialchars($s['popup_title'] ?: 'Popup Title'); ?>
              </div>
              <div class="popup-preview__text" id="popupPreviewText">
                <?php echo htmlspecialchars($s['popup_text'] ?: 'Your popup message will appear here.'); ?>
              </div>
              <span class="popup-preview__cta" id="popupPreviewCta"
                    style="<?php echo empty($s['popup_link']) ? 'display:none' : ''; ?>">
                <?php echo htmlspecialchars($s['popup_link_text'] ?: 'Learn more →'); ?>
              </span>
            </div>
            <p class="char-hint" style="margin-top:8px">Live preview of the popup card.</p>
          </div>

          <button type="submit" class="btn-save">Save Popup Settings</button>
        </form>
      </div>


      <!-- ════════════════════════════════════════
           MEDIA — YouTube embed
           ════════════════════════════════════════ -->
      <div class="settings-panel" id="panel-media">
        <form method="POST">
          <input type="hidden" name="section" value="media"/>

          <div class="settings-card">
            <div class="settings-card__title">Homepage YouTube Video</div>
            <p style="font-size:13px;color:#6b6b80;margin-bottom:16px">
              Embed a YouTube video on the homepage — for a school tour, speech day highlights, prize-giving ceremony, etc. Paste the full YouTube URL or just the video ID.
            </p>

            <div class="form-group">
              <label class="form-label">YouTube Video URL or ID</label>
              <input type="text" class="form-input" name="youtube_video_id" id="ytInput"
                     maxlength="255"
                     value="<?php echo htmlspecialchars($s['youtube_video_id']); ?>"
                     placeholder="e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ or just dQw4w9WgXcQ"
                     oninput="updateYtPreview()"/>
              <p class="char-hint">Leave blank to hide the video section from the homepage.</p>
            </div>

            <div class="form-group">
              <label class="form-label">Video Title / Caption</label>
              <input type="text" class="form-input" name="youtube_video_title"
                     maxlength="200"
                     value="<?php echo htmlspecialchars($s['youtube_video_title']); ?>"
                     placeholder="e.g. A Look Inside Ibeku High School"/>
            </div>

            <!-- Live preview -->
            <div class="yt-preview" id="ytPreview">
              <?php
              $ytId = $s['youtube_video_id'];
              if ($ytId):
              ?>
              <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($ytId); ?>"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                      allowfullscreen loading="lazy"></iframe>
              <?php else: ?>
              <div class="yt-placeholder" id="ytPlaceholder">
                <span>▶</span>
                <p>Enter a YouTube URL above to preview</p>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <button type="submit" class="btn-save">Save Media Settings</button>
        </form>
      </div>


      <!-- ════════════════════════════════════════
           SYSTEM INFO
           ════════════════════════════════════════ -->
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
            <div>
              <div class="info-item__label">School Hours</div>
              <div><?php echo htmlspecialchars($s['school_hours']); ?></div>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="settings-card__title">Superadmin Credentials</div>
          <div style="font-size:13px;color:#6b6b80;line-height:1.8">
            <div><strong style="color:#1a1a2e">Email:</strong> <?php echo htmlspecialchars($admin['email'] ?? ''); ?> — change via Manage Users → Edit</div>
            <div><strong style="color:#1a1a2e">Password:</strong> Change via Manage Users → Edit → Reset Password</div>
          </div>
        </div>
      </div>

    </div><!-- /.admin-content__inner -->
  </div><!-- /.admin-content -->

  <script src="../assets/js/admin.js"></script>
  <script>
    /* ── Tab switching ── */
    function switchTab(name, el) {
      document.querySelectorAll('.settings-tab').forEach(function (t) {
        t.classList.remove('settings-tab--active');
      });
      document.querySelectorAll('.settings-panel').forEach(function (p) {
        p.classList.remove('settings-panel--active');
      });
      el.classList.add('settings-tab--active');
      document.getElementById('panel-' + name).classList.add('settings-panel--active');
    }

    /* ── Announcement bar live preview ── */
    function updateAnnPreview() {
      var toggle    = document.getElementById('annToggle');
      var textEl    = document.getElementById('annText');
      var linkEl    = document.getElementById('annLink');
      var linkTxtEl = document.getElementById('annLinkText');
      var preview   = document.getElementById('annPreview');
      var prevText  = document.getElementById('annPreviewText');
      var prevLink  = document.getElementById('annPreviewLink');

      preview.style.opacity = toggle.checked ? '1' : '0.4';
      prevText.textContent  = textEl.value || 'Your announcement text will appear here.';

      if (linkEl.value.trim()) {
        prevLink.style.display = '';
        prevLink.textContent   = linkTxtEl.value || 'Read more →';
      } else {
        prevLink.style.display = 'none';
      }
    }

    /* ── Popup live preview ── */
    function updatePopupPreview() {
      var toggle    = document.getElementById('popupToggle');
      var titleEl   = document.getElementById('popupTitle');
      var textEl    = document.getElementById('popupText');
      var linkEl    = document.getElementById('popupLink');
      var linkTxtEl = document.getElementById('popupLinkText');
      var preview   = document.getElementById('popupPreview');
      var prevTitle = document.getElementById('popupPreviewTitle');
      var prevText  = document.getElementById('popupPreviewText');
      var prevCta   = document.getElementById('popupPreviewCta');

      preview.style.opacity = toggle.checked ? '1' : '0.4';
      prevTitle.textContent = titleEl.value || 'Popup Title';
      prevText.textContent  = textEl.value  || 'Your popup message will appear here.';

      if (linkEl.value.trim()) {
        prevCta.style.display = '';
        prevCta.textContent   = linkTxtEl.value || 'Learn more →';
      } else {
        prevCta.style.display = 'none';
      }
    }

    /* ── YouTube live preview ── */
    function updateYtPreview() {
      var input       = document.getElementById('ytInput');
      var previewBox  = document.getElementById('ytPreview');
      var raw         = input.value.trim();
      var videoId     = '';

      /* Extract video ID from full URL or bare ID */
      var match = raw.match(/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
      if (match) {
        videoId = match[1];
      } else if (/^[a-zA-Z0-9_-]{11}$/.test(raw)) {
        videoId = raw;
      }

      if (videoId) {
        previewBox.innerHTML =
          '<iframe src="https://www.youtube.com/embed/' + videoId + '"' +
          ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"' +
          ' allowfullscreen loading="lazy" style="width:100%;height:100%;border:0"></iframe>';
      } else {
        previewBox.innerHTML =
          '<div class="yt-placeholder"><span>▶</span><p>Enter a YouTube URL above to preview</p></div>';
      }
    }
  </script>

</body>
</html>