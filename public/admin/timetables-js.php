<?php
/* ============================================================
   IBEKU HIGH SCHOOL — TIMETABLE UPLOAD (JUNIOR SECONDARY)
   File: public/admin/timetables-js.php

   Accessible to: superadmin, dean (section = js)
   Mirror of timetables-ss.php for JSS1–JSS3. The academic
   session label is shared with the SS upload page via meta.json.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';

requireRole(['superadmin', 'dean'], 'js');

$admin = currentAdmin();

$classes = [
    'jss1' => 'JSS 1',
    'jss2' => 'JSS 2',
    'jss3' => 'JSS 3',
];

$uploadDir = dirname(__DIR__) . '/assets/timetables/';
$metaPath  = $uploadDir . 'meta.json';
$message   = '';
$messageType = '';

/* ── Load or initialise metadata ── */
function loadTimetableMeta(string $metaPath): array {
    if (!file_exists($metaPath)) {
        return ['session' => '2024/2025', 'files' => []];
    }
    $data = json_decode(file_get_contents($metaPath), true);
    return is_array($data) ? $data : ['session' => '2024/2025', 'files' => []];
}

function saveTimetableMeta(string $metaPath, array $meta): bool {
    return file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT)) !== false;
}

$meta = loadTimetableMeta($metaPath);

/* ── Handle session label update ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_session'])) {
    $newSession = trim($_POST['session_label'] ?? '');
    if (preg_match('/^\d{4}\/\d{4}$/', $newSession)) {
        $meta['session'] = $newSession;
        saveTimetableMeta($metaPath, $meta);
        $message = 'Academic session updated to ' . htmlspecialchars($newSession) . '.';
        $messageType = 'success';
    } else {
        $message = 'Session must be in the format 2025/2026.';
        $messageType = 'error';
    }
}

/* ── Handle PDF upload ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_key'])) {
    $classKey = $_POST['class_key'];

    if (!array_key_exists($classKey, $classes)) {
        $message = 'Invalid class selected.';
        $messageType = 'error';
    } elseif (!isset($_FILES['timetable_pdf']) || $_FILES['timetable_pdf']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please choose a PDF file to upload.';
        $messageType = 'error';
    } else {
        $file     = $_FILES['timetable_pdf'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($mimeType !== 'application/pdf' || $extension !== 'pdf') {
            $message = 'Only PDF files are allowed.';
            $messageType = 'error';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $message = 'File is too large. Maximum size is 5MB.';
            $messageType = 'error';
        } else {
            $targetPath = $uploadDir . 'timetable-' . $classKey . '.pdf';

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                /* Record the real upload timestamp in metadata */
                $meta['files'][$classKey] = date('c'); // ISO 8601
                saveTimetableMeta($metaPath, $meta);

                $message = strtoupper($classKey) . ' timetable uploaded successfully. The public Academics page now shows this version.';
                $messageType = 'success';
            } else {
                $message = 'Upload failed. Please check folder permissions and try again.';
                $messageType = 'error';
            }
        }
    }
}

/* ── Build current file status for display ── */
$fileStatus = [];
foreach ($classes as $key => $label) {
    $path = $uploadDir . 'timetable-' . $key . '.pdf';
    $lastUpdated = $meta['files'][$key] ?? null;
    $fileStatus[$key] = [
        'label'    => $label,
        'exists'   => file_exists($path),
        'modified' => $lastUpdated ? date('F j, Y, g:i A', strtotime($lastUpdated)) : null,
        'size'     => file_exists($path) ? round(filesize($path) / 1024, 1) . ' KB' : null,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage JS Timetables — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/../assets/css/admin-timetables.css">
</head>
<body>

  <div class="topbar">
    <div class="topbar__brand">
      <div class="topbar__logo">IHS</div>
      <h1>Ibeku High School — Admin Panel</h1>
    </div>
    <a href="index.php" class="back-link">← Back to Dashboard</a>
  </div>

  <div class="main">

    <div class="page-header">
      <h2>Junior Secondary Timetables</h2>
      <p>Upload a new PDF to replace the current timetable for each class. The public Academics page updates immediately.</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="timetable-card">
      <div class="timetable-card__top">
        <h3>Academic Session Label</h3>
      </div>
      <div class="file-meta">This text appears on the public Academics page above all 6 timetable downloads (shared with the SS Dean).</div>
      <form class="upload-form" method="POST">
        <input type="hidden" name="update_session" value="1"/>
        <div class="file-input-wrap" style="flex:0 0 160px">
          <input type="text" name="session_label" value="<?php echo htmlspecialchars($meta['session']); ?>" placeholder="2025/2026" pattern="\d{4}/\d{4}" required class="text-field"/>
        </div>
        <button type="submit" class="btn-upload">Save Session</button>
      </form>
    </div>

    <?php foreach ($fileStatus as $key => $info): ?>
    <div class="timetable-card">
      <div class="timetable-card__top">
        <div>
          <h3><?php echo htmlspecialchars($info['label']); ?> Timetable</h3>
        </div>
        <span class="status-badge status-badge--<?php echo $info['exists'] ? 'available' : 'missing'; ?>">
          <?php echo $info['exists'] ? 'Available' : 'Not Uploaded'; ?>
        </span>
      </div>

      <div class="file-meta">
        <?php if ($info['exists']): ?>
          Last updated <?php echo htmlspecialchars($info['modified'] ?? 'unknown'); ?> &middot; <?php echo htmlspecialchars($info['size']); ?>
          <a href="<?php echo dirname($_SERVER['SCRIPT_NAME'], 2); ?>/assets/timetables/timetable-<?php echo $key; ?>.pdf" target="_blank" class="view-link">View current PDF →</a>
        <?php else: ?>
          No timetable uploaded yet for this class.
        <?php endif; ?>
      </div>

      <form class="upload-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="class_key" value="<?php echo htmlspecialchars($key); ?>"/>
        <div class="file-input-wrap">
          <input type="file" name="timetable_pdf" accept="application/pdf" required/>
        </div>
        <button type="submit" class="btn-upload">Upload &amp; Replace</button>
      </form>
    </div>
    <?php endforeach; ?>

  </div>

</body>
</html>