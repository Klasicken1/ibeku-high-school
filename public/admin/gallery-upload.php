<?php
/* ============================================================
   IBEKU HIGH SCHOOL — GALLERY UPLOAD
   File: public/admin/gallery-upload.php

   Accessible to: superadmin, vp_general, principal
   Supports uploading multiple photos at once, assigning a
   category and optional captions. Photos saved to
   public/assets/images/gallery/
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_general']);

$admin = currentAdmin();
$pdo   = getDB();

$message     = '';
$messageType = '';
$uploadResults = [];

$categories = [
    'sports'      => 'Sports',
    'events'      => 'Events',
    'classrooms'  => 'Classrooms',
    'graduation'  => 'Graduation',
    'culture'     => 'Culture',
    'assembly'    => 'Assembly',
    'ict'         => 'ICT',
    'general'     => 'General',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category   = trim($_POST['category']    ?? 'general');
    $titleBase  = trim($_POST['title_base']  ?? '');
    $caption    = trim($_POST['caption']     ?? '');
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    $validCategories = array_keys($categories);
    if (!in_array($category, $validCategories, true)) {
        $category = 'general';
    }

    $uploadDir = dirname(__DIR__) . '/assets/images/gallery/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $files = $_FILES['photos'] ?? [];

    if (empty($files['name'][0])) {
        $message = 'Please select at least one photo to upload.';
        $messageType = 'error';
    } else {
        $allowedExt  = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize     = 5 * 1024 * 1024; // 5MB per photo
        $uploaded    = 0;
        $failed      = 0;

        /* Get current max sort_order */
        $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM gallery')->fetchColumn();

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $failed++;
                $uploadResults[] = ['file' => $files['name'][$i], 'status' => 'error', 'msg' => 'Upload error'];
                continue;
            }

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                $failed++;
                $uploadResults[] = ['file' => $files['name'][$i], 'status' => 'error', 'msg' => 'Invalid file type'];
                continue;
            }

            if ($files['size'][$i] > $maxSize) {
                $failed++;
                $uploadResults[] = ['file' => $files['name'][$i], 'status' => 'error', 'msg' => 'File too large (max 5MB)'];
                continue;
            }

            $filename = 'gallery_' . uniqid('', true) . '.' . $ext;

            if (!move_uploaded_file($files['tmp_name'][$i], $uploadDir . $filename)) {
                $failed++;
                $uploadResults[] = ['file' => $files['name'][$i], 'status' => 'error', 'msg' => 'Failed to save file'];
                continue;
            }

            /* Build title: use base title or original filename */
            $title = $titleBase ?: pathinfo($files['name'][$i], PATHINFO_FILENAME);
            if (count($files['name']) > 1 && $titleBase) {
                $title = $titleBase . ' ' . ($i + 1);
            }

            $maxSort++;

            try {
                $pdo->prepare(
                    'INSERT INTO gallery
                        (title, category, filename, original_name, caption, is_published, sort_order, uploaded_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $title, $category, $filename, $files['name'][$i],
                    $caption ?: null, $isPublished, $maxSort, $admin['id'],
                ]);
                $uploaded++;
                $uploadResults[] = ['file' => $files['name'][$i], 'status' => 'success', 'msg' => 'Uploaded'];
            } catch (PDOException $e) {
                error_log('IHS gallery upload error: ' . $e->getMessage());
                /* Clean up uploaded file on DB error */
                if (file_exists($uploadDir . $filename)) unlink($uploadDir . $filename);
                $failed++;
                $uploadResults[] = ['file' => $files['name'][$i], 'status' => 'error', 'msg' => 'Database error'];
            }
        }

        if ($uploaded > 0) {
            $message = $uploaded . ' photo(s) uploaded successfully' . ($failed > 0 ? ', ' . $failed . ' failed.' : '.') ;
            $messageType = $failed > 0 ? 'warning' : 'success';
        } else {
            $message = 'No photos were uploaded. ' . $failed . ' file(s) failed.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Upload Photos — Gallery — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .form-group { margin-bottom:18px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:6px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select { width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-select:focus { outline:none; border-color:#4a90d9; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; }
  .checkbox-row input { width:16px; height:16px; }

  /* Drop zone */
  .drop-zone {
    border:2px dashed #d8d0ee; border-radius:14px; padding:40px 20px;
    text-align:center; cursor:pointer; background:#faf9fd;
    transition:border-color .2s, background .2s;
    position:relative;
  }
  .drop-zone:hover, .drop-zone--active { border-color:#3d1a6e; background:#f0ecfa; }
  .drop-zone__icon { font-size:36px; margin-bottom:10px; }
  .drop-zone__text { font-size:14px; font-weight:600; color:#3d1a6e; margin-bottom:4px; }
  .drop-zone__hint { font-size:12.5px; color:#9b97b0; }
  .drop-zone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }

  /* Preview grid */
  .preview-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:10px; margin-top:16px; }
  .preview-item { position:relative; border-radius:8px; overflow:hidden; }
  .preview-item img { width:100%; height:90px; object-fit:cover; display:block; }
  .preview-item__remove {
    position:absolute; top:4px; right:4px; background:rgba(0,0,0,.55); color:#fff;
    border:none; border-radius:50%; width:20px; height:20px; font-size:12px;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
  }
  .preview-item__name { font-size:10.5px; color:#6b6b80; padding:3px 4px; background:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

  /* Upload results */
  .upload-results { margin-top:16px; }
  .upload-result-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; margin-bottom:6px; font-size:13px; }
  .upload-result-row--success { background:#e6f9ed; color:#1a7a3a; }
  .upload-result-row--error   { background:#ffe6e6; color:#cc3333; }

  .btn-group { display:flex; gap:12px; margin-top:22px; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; display:inline-block; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'gallery'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Upload Photos</h2>
        <p><a href="gallery.php" style="color:#4a90d9;text-decoration:none">← Back to Gallery</a></p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if (!empty($uploadResults)): ?>
      <div class="upload-results">
        <?php foreach ($uploadResults as $r): ?>
        <div class="upload-result-row upload-result-row--<?php echo $r['status']; ?>">
          <?php echo $r['status'] === 'success' ? '✓' : '✗'; ?>
          <?php echo htmlspecialchars($r['file']); ?>
          — <?php echo htmlspecialchars($r['msg']); ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="admin-card" style="max-width:620px">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">

          <!-- Drop zone -->
          <div class="form-group">
            <label class="form-label">Photos *</label>
            <div class="drop-zone" id="dropZone">
              <div class="drop-zone__icon">📸</div>
              <div class="drop-zone__text">Click to select photos or drag and drop here</div>
              <div class="drop-zone__hint">JPG, PNG or WEBP — max 5MB each — multiple files supported</div>
              <input type="file" name="photos[]" id="photoInput" accept="image/jpeg,image/png,image/webp" multiple required/>
            </div>
            <div class="preview-grid" id="previewGrid"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="title_base">Title</label>
            <input type="text" class="form-input" id="title_base" name="title_base" maxlength="255"
                   placeholder="e.g. Inter-House Sports 2025"/>
            <p class="char-hint">If uploading multiple photos, titles will be numbered automatically (Title 1, Title 2…). Leave blank to use original filenames.</p>
          </div>

          <div class="form-group">
            <label class="form-label" for="category">Category *</label>
            <select class="form-select" id="category" name="category" required>
              <?php foreach ($categories as $k => $v): ?>
              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="caption">Caption</label>
            <textarea class="form-input" id="caption" name="caption" rows="2" maxlength="500"
                      placeholder="Optional caption shown below the photo on the public gallery"></textarea>
          </div>

          <div class="form-group">
            <div class="checkbox-row">
              <input type="checkbox" id="is_published" name="is_published" checked/>
              <label for="is_published" style="font-size:13.5px">Publish immediately (visible on the public gallery)</label>
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save" id="uploadBtn">Upload Photos</button>
            <a href="gallery.php" class="btn-cancel">Cancel</a>
          </div>

        </form>
      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    var dropZone    = document.getElementById('dropZone');
    var photoInput  = document.getElementById('photoInput');
    var previewGrid = document.getElementById('previewGrid');
    var selectedFiles = [];

    /* Drag and drop styling */
    dropZone.addEventListener('dragover', function (e) {
      e.preventDefault();
      dropZone.classList.add('drop-zone--active');
    });
    dropZone.addEventListener('dragleave', function () {
      dropZone.classList.remove('drop-zone--active');
    });
    dropZone.addEventListener('drop', function (e) {
      e.preventDefault();
      dropZone.classList.remove('drop-zone--active');
      handleFiles(e.dataTransfer.files);
    });

    photoInput.addEventListener('change', function () {
      handleFiles(this.files);
    });

    function handleFiles(files) {
      previewGrid.innerHTML = '';
      selectedFiles = Array.from(files);

      selectedFiles.forEach(function (file, index) {
        if (!file.type.startsWith('image/')) return;

        var reader = new FileReader();
        reader.onload = function (e) {
          var item = document.createElement('div');
          item.className = 'preview-item';
          item.id = 'preview-' + index;

          var img = document.createElement('img');
          img.src = e.target.result;
          img.alt = file.name;

          var name = document.createElement('div');
          name.className = 'preview-item__name';
          name.textContent = file.name;

          item.appendChild(img);
          item.appendChild(name);
          previewGrid.appendChild(item);
        };
        reader.readAsDataURL(file);
      });
    }

    /* Show upload button loading state */
    document.getElementById('uploadForm').addEventListener('submit', function () {
      var btn = document.getElementById('uploadBtn');
      btn.textContent = 'Uploading…';
      btn.disabled = true;
    });
  </script>

</body>
</html>