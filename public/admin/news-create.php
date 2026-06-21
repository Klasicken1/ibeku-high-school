<?php
/* ============================================================
   IBEKU HIGH SCHOOL — CREATE NEWS ARTICLE
   File: public/admin/news-create.php

   Accessible to: superadmin, principal, vp_general
   Uses TinyMCE Cloud for the article body editor.
   Supports featured image upload and SEO meta fields.
   TODO: replace 'no-api-key' below once the school domain is
   registered at tiny.cloud — see admin onboarding notes.
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
    $publishNow      = isset($_POST['publish_now']) ? 1 : 0;
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

        /* ── Handle featured image upload (optional) ── */
        $imageFilename = null;
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
                $imageFilename = 'news-' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetPath = $uploadDir . $imageFilename;

                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $message = 'Image upload failed. Please try again.';
                    $messageType = 'error';
                    $imageFilename = null;
                }
            }
        }

        if ($message === '') {

            $slug = strtolower(trim($title));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');

            $baseSlug = $slug;
            $suffix = 1;
            while (true) {
                $checkStmt = $pdo->prepare('SELECT id FROM news WHERE slug = ? LIMIT 1');
                $checkStmt->execute([$slug]);
                if (!$checkStmt->fetch()) break;
                $suffix++;
                $slug = $baseSlug . '-' . $suffix;
            }

            $finalMetaTitle       = $metaTitle !== ''       ? $metaTitle       : $title;
            $finalMetaDescription = $metaDescription !== '' ? $metaDescription : $excerpt;

            try {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO news
                        (title, slug, excerpt, body, category, featured, image,
                         meta_title, meta_description, is_published, published_at, author_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertStmt->execute([
                    $title,
                    $slug,
                    $excerpt ?: null,
                    $body,
                    $category,
                    $featured,
                    $imageFilename,
                    $finalMetaTitle ?: null,
                    $finalMetaDescription ?: null,
                    $publishNow,
                    $publishNow ? date('Y-m-d H:i:s') : null,
                    $admin['id'],
                ]);

                $message = $publishNow
                    ? 'Article published successfully.'
                    : 'Article saved as draft. Publish it from the News list when ready.';
                $messageType = 'success';

                $title = $excerpt = $body = $metaTitle = $metaDescription = '';
                $category = 'general';
                $featured = 0;

            } catch (PDOException $e) {
                error_log('IHS news-create error: ' . $e->getMessage());
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
<title>Create News Article — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">

<!-- TinyMCE Cloud — placeholder key, replace once domain is registered at tiny.cloud -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
  /* ── Page-specific additions only — layout/sidebar handled by admin-layout.css ── */
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

  .checkbox-row { display: flex; align-items: center; gap: 8px; margin: 4px 0 4px; }
  .checkbox-row input { width: 16px; height: 16px; }
  .checkbox-row label { font-size: 13.5px; color: #1a1a2e; }

  .btn-group { display: flex; gap: 12px; margin-top: 22px; }
  .btn-save-draft {
    background: #f0ecfa; color: #3d1a6e; border: 1.5px solid #d8d0ee;
    padding: 11px 24px; border-radius: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer;
  }
  .btn-save-draft:hover { background: #e4dcf6; }
  .btn-publish {
    background: #3d1a6e; color: #fff; border: none;
    padding: 11px 24px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer;
  }
  .btn-publish:hover { background: #5a2d9e; }

  .char-hint { font-size: 11.5px; color: #9b97b0; margin-top: 4px; }

  /* ── Featured image upload — fixed sizing, no overflow ── */
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

  .image-preview { margin-top: 12px; display: none; }
  .image-preview img {
    width: 100%; max-height: 160px; object-fit: cover;
    border-radius: 10px; border: 1px solid #e8e6f0; display: block;
  }

  .seo-preview {
    background: #f4f3f9; border-radius: 10px; padding: 14px 16px; margin-top: 14px;
  }
  .seo-preview__title { color: #1a0dab; font-size: 14px; margin-bottom: 3px; word-break: break-word; }
  .seo-preview__url { color: #1a7a3a; font-size: 11.5px; margin-bottom: 4px; }
  .seo-preview__desc { color: #4d4d4d; font-size: 12px; line-height: 1.4; }

  .sidebar-label {
    font-size: 12.5px; font-weight: 700; color: #3d1a6e;
    text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px;
  }
  .sidebar-hint { font-size: 11.5px; color: #9b97b0; margin-bottom: 14px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'news-create'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Create News Article</h2>
        <p>Write a news article or announcement. Save as draft to review later, or publish immediately.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <form method="POST" id="newsForm" enctype="multipart/form-data">

        <div class="admin-grid-2col admin-grid-2col--wide-left">

          <!-- ── LEFT COLUMN: main content ── -->
          <div class="admin-card">

            <div class="form-group">
              <label class="form-label" for="title">Article Title</label>
              <input type="text" class="form-input" id="title" name="title" required maxlength="300"
                     value="<?php echo htmlspecialchars($title ?? ''); ?>"
                     placeholder="e.g. IHS Wins Abia State Science Quiz Championship"/>
            </div>

            <div class="form-group">
              <label class="form-label" for="excerpt">Short Excerpt</label>
              <textarea class="form-textarea" id="excerpt" name="excerpt" maxlength="300"
                        placeholder="A 1-2 sentence summary shown on the news listing and homepage"><?php echo htmlspecialchars($excerpt ?? ''); ?></textarea>
              <p class="char-hint">Shown on the News page card and homepage preview. Keep it brief.</p>
            </div>

            <div class="form-group">
              <label class="form-label" for="body">Article Body</label>
              <textarea id="body" name="body"><?php echo $body ?? ''; ?></textarea>
            </div>

          </div>

          <!-- ── RIGHT COLUMN: settings sidebar ── -->
          <div>

            <div class="admin-card" style="margin-bottom:18px">
              <div class="sidebar-label">Publish</div>
              <div class="sidebar-hint">Choose to save a draft or publish immediately.</div>

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
                      $sel = ($category ?? 'general') === $key ? 'selected' : '';
                  ?>
                  <option value="<?php echo $key; ?>" <?php echo $sel; ?>><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="checkbox-row">
                <input type="checkbox" id="featured" name="featured" <?php echo !empty($featured) ? 'checked' : ''; ?>/>
                <label for="featured">Mark as featured article</label>
              </div>

              <div class="btn-group">
                <button type="submit" name="publish_now" value="0" class="btn-save-draft">Save Draft</button>
                <button type="submit" name="publish_now" value="1" class="btn-publish">Publish</button>
              </div>
            </div>

            <div class="admin-card" style="margin-bottom:18px">
              <div class="sidebar-label">Featured Image</div>
              <div class="sidebar-hint">JPG, PNG, or WEBP — max 3MB.</div>

              <label class="image-upload-box" id="uploadBox">
                <span class="image-upload-box__icon">🖼️</span>
                <span class="image-upload-box__text">Click to choose an image</span>
                <input type="file" name="featured_image" id="featuredImage" accept="image/jpeg,image/png,image/webp"/>
              </label>
              <div class="image-preview" id="imagePreview">
                <img id="imagePreviewImg" src="" alt="Preview"/>
              </div>
            </div>

            <div class="admin-card">
              <div class="sidebar-label">SEO</div>
              <div class="sidebar-hint">Optional — defaults to the title and excerpt if left blank.</div>

              <div class="form-group">
                <label class="form-label" for="meta_title">Meta Title</label>
                <input type="text" class="form-input" id="meta_title" name="meta_title" maxlength="300"
                       value="<?php echo htmlspecialchars($metaTitle ?? ''); ?>"
                       placeholder="Defaults to article title"/>
              </div>

              <div class="form-group">
                <label class="form-label" for="meta_description">Meta Description</label>
                <textarea class="form-textarea" id="meta_description" name="meta_description" maxlength="320"
                          placeholder="Defaults to excerpt"><?php echo htmlspecialchars($metaDescription ?? ''); ?></textarea>
              </div>

              <div class="seo-preview">
                <div class="seo-preview__title" id="seoPreviewTitle">Article title will appear here</div>
                <div class="seo-preview__url">ibekuhighschool.edu.ng/news/article-slug</div>
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
    var imagePreview = document.getElementById('imagePreview');
    var imagePreviewImg = document.getElementById('imagePreviewImg');
    var uploadBoxText = document.querySelector('#uploadBox .image-upload-box__text');

    featuredImageInput.addEventListener('change', function () {
      var file = this.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (e) {
        imagePreviewImg.src = e.target.result;
        imagePreview.style.display = 'block';
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