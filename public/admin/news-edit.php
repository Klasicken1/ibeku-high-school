<?php
/* ============================================================
   IBEKU HIGH SCHOOL — EDIT NEWS ARTICLE
   File: public/admin/news-edit.php

   Accessible to: superadmin, principal, vp_general
   Loads an existing article by ?id= and lets the admin update
   any field, including replacing the featured image.
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

$articleId = (int) ($_GET['id'] ?? 0);

if ($articleId <= 0) {
    header('Location: news.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM news WHERE id = ? LIMIT 1');
$stmt->execute([$articleId]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: news.php');
    exit;
}

$message = '';
$messageType = '';

$uploadDir = dirname(__DIR__) . '/assets/images/news/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/* ── Handle form submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title           = trim($_POST['title']            ?? '');
    $excerpt         = trim($_POST['excerpt']           ?? '');
    $body            = $_POST['body']                   ?? '';
    $category        = trim($_POST['category']          ?? 'general');
    $featured        = isset($_POST['featured']) ? 1 : 0;
    $metaTitle       = trim($_POST['meta_title']        ?? '');
    $metaDescription = trim($_POST['meta_description']  ?? '');

    $validCategories = ['achievement', 'academic', 'ict', 'sports', 'announcement', 'culture', 'general'];

    if ($title === '') {
        $message = 'Title is required.';
        $messageType = 'error';
    } elseif (strip_tags($body) === '') {
        $message = 'Article body cannot be empty.';
        $messageType = 'error';
    } elseif (!in_array($category, $validCategories, true)) {
        $message = 'Invalid category selected.';
        $messageType = 'error';
    } else {

        $imageFilename = $article['image']; // keep existing unless a new one is uploaded

        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['featured_image'];

            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if (!array_key_exists($mimeType, $allowedMimes)) {
                $message = 'Featured image must be a JPG, PNG, or WEBP file.';
                $messageType = 'error';
            } elseif ($file['size'] > 3 * 1024 * 1024) {
                $message = 'Featured image is too large. Maximum size is 3MB.';
                $messageType = 'error';
            } else {
                $ext = $allowedMimes[$mimeType];
                $newImageFilename = 'news-' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetPath = $uploadDir . $newImageFilename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    /* Delete the old image file if one existed */
                    if ($imageFilename && file_exists($uploadDir . $imageFilename)) {
                        @unlink($uploadDir . $imageFilename);
                    }
                    $imageFilename = $newImageFilename;
                } else {
                    $message = 'Image upload failed. Please try again.';
                    $messageType = 'error';
                }
            }
        }

        if ($message === '') {
            $finalMetaTitle       = $metaTitle !== ''       ? $metaTitle       : $title;
            $finalMetaDescription = $metaDescription !== '' ? $metaDescription : $excerpt;

            try {
                $updateStmt = $pdo->prepare(
                    'UPDATE news SET
                        title = ?, excerpt = ?, body = ?, category = ?, featured = ?,
                        image = ?, meta_title = ?, meta_description = ?, updated_at = NOW()
                     WHERE id = ?'
                );
                $updateStmt->execute([
                    $title,
                    $excerpt ?: null,
                    $body,
                    $category,
                    $featured,
                    $imageFilename,
                    $finalMetaTitle ?: null,
                    $finalMetaDescription ?: null,
                    $articleId,
                ]);

                $message = 'Article updated successfully.';
                $messageType = 'success';

                /* Reload fresh data after save */
                $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ? LIMIT 1');
                $stmt->execute([$articleId]);
                $article = $stmt->fetch();

            } catch (PDOException $e) {
                error_log('IHS news-edit error: ' . $e->getMessage());
                $message = 'A server error occurred while saving. Please try again.';
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Edit Article — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
  .form-group { margin-bottom: 18px; }
  .form-label {
    display: block; font-size: 12.5px; font-weight: 600; color: #3d1a6e;
    margin-bottom: 6px; text-transform: uppercase; letter-spacing: .03em;
  }
  .form-input, .form-textarea, .form-select {
    width: 100%; padding: 10px 13px; border: 1.5px solid #e2e0ea; border-radius: 8px;
    font-size: 13.5px; font-family: 'DM Sans', sans-serif; color: #1a1a2e;
  }
  .form-input:focus, .form-textarea:focus, .form-select:focus { outline: none; border-color: #4a90d9; }
  .form-textarea { resize: vertical; min-height: 70px; }

  .checkbox-row { display: flex; align-items: center; gap: 8px; margin: 4px 0; }
  .checkbox-row input { width: 16px; height: 16px; }
  .checkbox-row label { font-size: 13.5px; color: #1a1a2e; }

  .btn-group { display: flex; gap: 12px; margin-top: 22px; }
  .btn-save { background: #3d1a6e; color: #fff; border: none; padding: 11px 24px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; }
  .btn-save:hover { background: #5a2d9e; }
  .btn-cancel { background: #f0ecfa; color: #3d1a6e; border: 1.5px solid #d8d0ee; padding: 11px 24px; border-radius: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
  .btn-cancel:hover { background: #e4dcf6; }

  .char-hint { font-size: 11.5px; color: #9b97b0; margin-top: 4px; }

  .image-upload-box {
    border: 1.5px dashed #c8c4dc; border-radius: 10px; padding: 18px;
    text-align: center; cursor: pointer; transition: border-color .2s;
    background: #faf9fd;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    min-height: 110px;
  }
  .image-upload-box:hover { border-color: #4a90d9; }
  .image-upload-box input[type="file"] { display: none; }
  .image-upload-box__text { font-size: 12.5px; color: #6b6b80; word-break: break-word; }
  .image-upload-box__icon { font-size: 22px; display: block; margin-bottom: 6px; }

  .image-preview { margin-top: 12px; }
  .image-preview img { width: 100%; max-height: 160px; object-fit: cover; border-radius: 10px; border: 1px solid #e8e6f0; display: block; }
  .image-preview__label { font-size: 11px; color: #9b97b0; margin-top: 6px; }

  .seo-preview { background: #f4f3f9; border-radius: 10px; padding: 14px 16px; margin-top: 14px; }
  .seo-preview__title { color: #1a0dab; font-size: 14px; margin-bottom: 3px; word-break: break-word; }
  .seo-preview__url { color: #1a7a3a; font-size: 11.5px; margin-bottom: 4px; }
  .seo-preview__desc { color: #4d4d4d; font-size: 12px; line-height: 1.4; }

  .sidebar-label { font-size: 12.5px; font-weight: 700; color: #3d1a6e; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
  .sidebar-hint { font-size: 11.5px; color: #9b97b0; margin-bottom: 14px; }

  .status-line { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
  .badge { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; }
  .badge--published { background: #e6f9ed; color: #1a7a3a; }
  .badge--draft { background: #fff3e6; color: #8a4a00; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'news'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Edit Article</h2>
        <p><a href="news.php" style="color:#4a90d9;text-decoration:none">← Back to All News</a></p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <form method="POST" id="newsForm" enctype="multipart/form-data">

        <div class="admin-grid-2col admin-grid-2col--wide-left">

          <div class="admin-card">

            <div class="form-group">
              <label class="form-label" for="title">Article Title</label>
              <input type="text" class="form-input" id="title" name="title" required maxlength="300"
                     value="<?php echo htmlspecialchars($article['title']); ?>"/>
            </div>

            <div class="form-group">
              <label class="form-label" for="excerpt">Short Excerpt</label>
              <textarea class="form-textarea" id="excerpt" name="excerpt" maxlength="300"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
              <p class="char-hint">Shown on the News page card and homepage preview.</p>
            </div>

            <div class="form-group">
              <label class="form-label" for="body">Article Body</label>
              <textarea id="body" name="body"><?php echo $article['body']; ?></textarea>
            </div>

          </div>

          <div>

            <div class="admin-card" style="margin-bottom:18px">
              <div class="sidebar-label">Status</div>
              <div class="status-line">
                <span class="badge badge--<?php echo $article['is_published'] ? 'published' : 'draft'; ?>">
                  <?php echo $article['is_published'] ? 'Published' : 'Draft'; ?>
                </span>
              </div>
              <div class="sidebar-hint">Use the All News list to publish/unpublish — this page only saves edits to the content below.</div>

              <div class="form-group">
                <label class="form-label" for="category">Category</label>
                <select class="form-select" id="category" name="category">
                  <?php
                  $categories = [
                      'achievement'   => 'Achievement',
                      'academic'      => 'Academic',
                      'ict'           => 'ICT',
                      'sports'        => 'Sports',
                      'announcement'  => 'Announcement',
                      'culture'       => 'Culture',
                      'general'       => 'General',
                  ];
                  foreach ($categories as $key => $label):
                      $sel = $article['category'] === $key ? 'selected' : '';
                  ?>
                  <option value="<?php echo $key; ?>" <?php echo $sel; ?>><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="checkbox-row">
                <input type="checkbox" id="featured" name="featured" <?php echo $article['featured'] ? 'checked' : ''; ?>/>
                <label for="featured">Featured article</label>
              </div>

              <div class="btn-group">
                <button type="submit" class="btn-save">Save Changes</button>
                <a href="news.php" class="btn-cancel">Cancel</a>
              </div>
            </div>

            <div class="admin-card" style="margin-bottom:18px">
              <div class="sidebar-label">Featured Image</div>
              <div class="sidebar-hint">Upload a new image to replace the current one.</div>

              <?php if ($article['image']): ?>
              <div class="image-preview">
                <img src="../assets/images/news/<?php echo htmlspecialchars($article['image']); ?>" alt="Current featured image"/>
                <div class="image-preview__label">Current image</div>
              </div>
              <?php endif; ?>

              <label class="image-upload-box" id="uploadBox" style="margin-top:12px">
                <span class="image-upload-box__icon">🖼️</span>
                <span class="image-upload-box__text">Click to replace image</span>
                <input type="file" name="featured_image" id="featuredImage" accept="image/jpeg,image/png,image/webp"/>
              </label>
              <div class="image-preview" id="newImagePreview" style="display:none">
                <img id="imagePreviewImg" src="" alt="New image preview"/>
                <div class="image-preview__label">New image (not saved yet)</div>
              </div>
            </div>

            <div class="admin-card">
              <div class="sidebar-label">SEO</div>
              <div class="sidebar-hint">Optional — defaults to the title and excerpt if left blank.</div>

              <div class="form-group">
                <label class="form-label" for="meta_title">Meta Title</label>
                <input type="text" class="form-input" id="meta_title" name="meta_title" maxlength="300"
                       value="<?php echo htmlspecialchars($article['meta_title'] ?? ''); ?>"/>
              </div>

              <div class="form-group">
                <label class="form-label" for="meta_description">Meta Description</label>
                <textarea class="form-textarea" id="meta_description" name="meta_description" maxlength="320"><?php echo htmlspecialchars($article['meta_description'] ?? ''); ?></textarea>
              </div>

              <div class="seo-preview">
                <div class="seo-preview__title" id="seoPreviewTitle">Article title will appear here</div>
                <div class="seo-preview__url">ibekuhighschool.edu.ng/news/<?php echo htmlspecialchars($article['slug']); ?></div>
                <div class="seo-preview__desc" id="seoPreviewDesc">Article description will appear here</div>
              </div>
            </div>

          </div>

        </div>
      </form>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    tinymce.init({
      selector: '#body',
      height: 380,
      menubar: false,
      plugins: 'lists link image table code wordcount autolink',
      toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image table | code',
      content_style: "body { font-family:'DM Sans', sans-serif; font-size:14px; }",
      branding: false,
    });

    var featuredImageInput = document.getElementById('featuredImage');
    var newImagePreview = document.getElementById('newImagePreview');
    var imagePreviewImg = document.getElementById('imagePreviewImg');
    var uploadBoxText = document.querySelector('#uploadBox .image-upload-box__text');

    featuredImageInput.addEventListener('change', function () {
      var file = this.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (e) {
        imagePreviewImg.src = e.target.result;
        newImagePreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
      uploadBoxText.textContent = file.name;
    });

    var titleInput   = document.getElementById('title');
    var excerptInput = document.getElementById('excerpt');
    var metaTitleInput = document.getElementById('meta_title');
    var metaDescInput  = document.getElementById('meta_description');
    var seoPreviewTitle = document.getElementById('seoPreviewTitle');
    var seoPreviewDesc  = document.getElementById('seoPreviewDesc');

    function updateSeoPreview() {
      seoPreviewTitle.textContent = metaTitleInput.value.trim() || titleInput.value.trim() || 'Article title will appear here';
      seoPreviewDesc.textContent  = metaDescInput.value.trim()  || excerptInput.value.trim() || 'Article description will appear here';
    }
    [titleInput, excerptInput, metaTitleInput, metaDescInput].forEach(function (el) {
      el.addEventListener('input', updateSeoPreview);
    });
    updateSeoPreview();
  </script>

</body>
</html>