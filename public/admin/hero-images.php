<?php
/* ============================================================
   IBEKU HIGH SCHOOL — HERO IMAGES MANAGEMENT
   File: public/admin/hero-images.php

   Two independent sections:
   1. Homepage Slider — hero_slides table. Add/edit/reorder
      (up/down)/activate-deactivate/remove full slides (photo +
      badge + heading + description + up to 2 CTA buttons).
      index.php loops over active slides in sort_order; falls
      back to its 3 hardcoded default slides if none exist yet.
   2. Inner Page Images — one optional background photo per
      public page, stored as JSON under the settings key
      'hero_images_inner'. Each inner page opts in with a single
      line using getInnerHeroImage()/renderInnerHeroStyle() from
      database.php.

   Accessible to: superadmin, principal, vp_general — same
   access level as other site content pages (news, gallery,
   reviews).
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_general']);

$admin = currentAdmin();
$pdo   = getDB();

/* ── Ensure hero_slides table exists ── */
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS hero_slides (
        id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        image        VARCHAR(255) NOT NULL,
        badge_text   VARCHAR(150) NULL,
        heading      VARCHAR(255) NOT NULL,
        description  TEXT NULL,
        cta1_text    VARCHAR(60)  NULL,
        cta1_url     VARCHAR(255) NULL,
        cta2_text    VARCHAR(60)  NULL,
        cta2_url     VARCHAR(255) NULL,
        sort_order   SMALLINT NOT NULL DEFAULT 0,
        is_active    TINYINT(1) NOT NULL DEFAULT 1,
        created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

/* Self-healing column add — same pattern as push_subscriptions.user_id */
try {
    $pdo->exec(
        "ALTER TABLE hero_slides ADD COLUMN focal_position VARCHAR(20) NOT NULL DEFAULT 'center center' AFTER image"
    );
} catch (PDOException $e) {
    /* Column already exists — fine */
}

/* Valid focal point values — shared by both the homepage slide form
   and each inner-page card's picker */
$focalPositions = [
    'top left', 'top center', 'top right',
    'center left', 'center center', 'center right',
    'bottom left', 'bottom center', 'bottom right',
];

$message     = '';
$messageType = '';

$uploadDir = dirname(__DIR__) . '/assets/images/hero/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/* ── Inner page keys — must match what each page's
   getInnerHeroImage('key') call uses ── */
$innerPages = [
    'about'        => 'About',
    'academics'    => 'Academics',
    'students'     => 'Students',
    'admissions'   => 'Admissions',
    'contact'      => 'Contact',
    'hall_of_fame' => 'Hall of Fame',
    'news'         => 'News',
    'events'       => 'Events',
    'gallery'      => 'Gallery',
    'results'      => 'Results',
    'corps'        => 'Corps Members',
];

/* ── Shared image upload handler ── */
function handleHeroImageUpload(array $file, string $uploadDir): array {
    // Returns [filename_or_null, error_or_null]
    if (empty($file['name'])) return [null, null];

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        return [null, 'Photo must be JPG, PNG, or WEBP.'];
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        return [null, 'Photo must be under 3MB.'];
    }

    $filename = uniqid('hero_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return [null, 'Photo upload failed.'];
    }
    return [$filename, null];
}

/* ════════════════════════════════════════════════════════════
   POST HANDLERS
   ════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── Save (add or update) a homepage slide ── */
    if ($action === 'save_slide') {
        $id            = (int) ($_POST['id'] ?? 0);
        $badgeText     = trim($_POST['badge_text']  ?? '');
        $heading       = trim($_POST['heading']     ?? '');
        $description   = trim($_POST['description'] ?? '');
        $cta1Text      = trim($_POST['cta1_text']   ?? '');
        $cta1Url       = trim($_POST['cta1_url']    ?? '');
        $cta2Text      = trim($_POST['cta2_text']   ?? '');
        $cta2Url       = trim($_POST['cta2_url']    ?? '');
        $isActive      = isset($_POST['is_active']) ? 1 : 0;
        $focalPosition = trim($_POST['focal_position'] ?? 'center center');
        if (!in_array($focalPosition, $focalPositions, true)) {
            $focalPosition = 'center center';
        }

        if ($heading === '') {
            $message = 'Heading is required.'; $messageType = 'error';
        } else {
            [$uploadedImage, $uploadError] = handleHeroImageUpload($_FILES['image'] ?? [], $uploadDir);

            if ($uploadError) {
                $message = $uploadError; $messageType = 'error';
            } elseif (!$uploadedImage && $id === 0) {
                $message = 'A photo is required for a new slide.'; $messageType = 'error';
            } else {
                try {
                    if ($id > 0) {
                        if ($uploadedImage) {
                            $pdo->prepare(
                                'UPDATE hero_slides SET
                                    image = ?, focal_position = ?, badge_text = ?, heading = ?, description = ?,
                                    cta1_text = ?, cta1_url = ?, cta2_text = ?, cta2_url = ?, is_active = ?
                                 WHERE id = ?'
                            )->execute([
                                $uploadedImage, $focalPosition, $badgeText ?: null, $heading, $description ?: null,
                                $cta1Text ?: null, $cta1Url ?: null, $cta2Text ?: null, $cta2Url ?: null,
                                $isActive, $id,
                            ]);
                        } else {
                            $pdo->prepare(
                                'UPDATE hero_slides SET
                                    focal_position = ?, badge_text = ?, heading = ?, description = ?,
                                    cta1_text = ?, cta1_url = ?, cta2_text = ?, cta2_url = ?, is_active = ?
                                 WHERE id = ?'
                            )->execute([
                                $focalPosition, $badgeText ?: null, $heading, $description ?: null,
                                $cta1Text ?: null, $cta1Url ?: null, $cta2Text ?: null, $cta2Url ?: null,
                                $isActive, $id,
                            ]);
                        }
                        $message = 'Slide updated.'; $messageType = 'success';
                    } else {
                        $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM hero_slides')->fetchColumn();
                        $pdo->prepare(
                            'INSERT INTO hero_slides
                                (image, focal_position, badge_text, heading, description, cta1_text, cta1_url, cta2_text, cta2_url, sort_order, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                        )->execute([
                            $uploadedImage, $focalPosition, $badgeText ?: null, $heading, $description ?: null,
                            $cta1Text ?: null, $cta1Url ?: null, $cta2Text ?: null, $cta2Url ?: null,
                            $maxOrder + 1, $isActive,
                        ]);
                        $message = 'Slide added.'; $messageType = 'success';
                    }
                } catch (PDOException $e) {
                    error_log('IHS hero-images save_slide: ' . $e->getMessage());
                    $message = 'A server error occurred.'; $messageType = 'error';
                }
            }
        }

    /* ── Delete a slide ── */
    } elseif ($action === 'delete_slide') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM hero_slides WHERE id = ?')->execute([$id]);
            $message = 'Slide removed.'; $messageType = 'success';
        }

    /* ── Toggle active/inactive ── */
    } elseif ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('UPDATE hero_slides SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            $message = 'Slide visibility updated.'; $messageType = 'success';
        }

    /* ── Reorder: swap sort_order with the adjacent slide ── */
    } elseif ($action === 'move_up' || $action === 'move_down') {
        $id = (int) ($_POST['id'] ?? 0);
        $slideStmt = $pdo->prepare('SELECT id, sort_order FROM hero_slides ORDER BY sort_order ASC');
        $slideStmt->execute();
        $all = $slideStmt->fetchAll(PDO::FETCH_ASSOC);

        $posIndex = null;
        foreach ($all as $i => $row) {
            if ((int) $row['id'] === $id) { $posIndex = $i; break; }
        }

        if ($posIndex !== null) {
            $swapWith = $action === 'move_up' ? $posIndex - 1 : $posIndex + 1;
            if ($swapWith >= 0 && $swapWith < count($all)) {
                $a = $all[$posIndex];
                $b = $all[$swapWith];
                $swapStmt = $pdo->prepare('UPDATE hero_slides SET sort_order = ? WHERE id = ?');
                $swapStmt->execute([$b['sort_order'], $a['id']]);
                $swapStmt->execute([$a['sort_order'], $b['id']]);
            }
        }

    /* ── Save inner-page images (one upload slot per page) ── */
    } elseif ($action === 'save_inner_images') {
        $current = getInnerHeroImages();

        foreach (array_keys($innerPages) as $pageKey) {
            $fileKey     = 'inner_' . $pageKey;
            $posKey      = 'position_' . $pageKey;
            $submittedPos = trim($_POST[$posKey] ?? 'center center');
            if (!in_array($submittedPos, $focalPositions, true)) {
                $submittedPos = 'center center';
            }

            if (!empty($_FILES[$fileKey]['name'])) {
                /* New photo uploaded — replace image, use the submitted position */
                [$uploadedImage, $uploadError] = handleHeroImageUpload($_FILES[$fileKey], $uploadDir);
                if ($uploadError) {
                    $message = htmlspecialchars($innerPages[$pageKey]) . ': ' . $uploadError;
                    $messageType = 'error';
                } elseif ($uploadedImage) {
                    $current[$pageKey] = ['image' => $uploadedImage, 'position' => $submittedPos];
                }
            } elseif (isset($current[$pageKey])) {
                /* No new upload, but the focal point may have changed for the
                   existing photo — normalise legacy string entries too */
                $existingImage = is_string($current[$pageKey])
                    ? $current[$pageKey]
                    : ($current[$pageKey]['image'] ?? null);
                if ($existingImage) {
                    $current[$pageKey] = ['image' => $existingImage, 'position' => $submittedPos];
                }
            }
        }

        if ($messageType !== 'error') {
            $pdo->prepare(
                "INSERT INTO settings (`key`, `value`) VALUES ('hero_images_inner', ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
            )->execute([json_encode($current)]);
            global $_settings_cache;
            $_settings_cache = null;
            $message = 'Inner page images saved.'; $messageType = 'success';
        }

    /* ── Remove a single inner-page image ── */
    } elseif ($action === 'remove_inner_image') {
        $pageKey = trim($_POST['page_key'] ?? '');
        $current = getInnerHeroImages();
        if (isset($current[$pageKey])) {
            unset($current[$pageKey]);
            $pdo->prepare(
                "INSERT INTO settings (`key`, `value`) VALUES ('hero_images_inner', ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
            )->execute([json_encode($current)]);
            global $_settings_cache;
            $_settings_cache = null;
            $message = 'Image removed.'; $messageType = 'success';
        }
    }
}

/* ── Load data for rendering ── */
$slides = $pdo->query('SELECT * FROM hero_slides ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC);

$editId   = (int) ($_GET['edit'] ?? 0);
$editSlide = null;
if ($editId > 0) {
    foreach ($slides as $s) {
        if ((int) $s['id'] === $editId) { $editSlide = $s; break; }
    }
}

$innerImages = getInnerHeroImages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Hero Images — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .section-block { margin-bottom: 40px; }
  .section-block__title { font-size: 16px; font-weight: 700; color: #3d1a6e; margin-bottom: 6px; font-family: 'Playfair Display', serif; }
  .section-block__sub { font-size: 13px; color: #6b6b80; margin-bottom: 18px; }

  .card { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:24px; margin-bottom:20px; }

  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-textarea { width:100%; padding:9px 12px; border:1.5px solid #e2e0ea; border-radius:8px; font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e; }
  .form-input:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-row { display:flex; gap:14px; }
  .form-row .form-group { flex:1; }
  .hint { font-size:11.5px; color:#9b97b0; margin-top:3px; }

  .toggle-inline { display:flex; align-items:center; gap:8px; font-size:13.5px; color:#1a1a2e; margin-bottom:16px; }

  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:10px 24px; border-radius:8px; font-size:13.5px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:10px 20px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; display:inline-block; }

  /* Slide list */
  .slide-row { display:flex; gap:16px; align-items:center; background:#fff; border:1px solid #e8e6f0; border-radius:12px; padding:14px; margin-bottom:10px; }
  .slide-row__thumb { width:100px; height:60px; border-radius:8px; object-fit:cover; flex-shrink:0; background:#f4f3f9; }
  .slide-row__body { flex:1; min-width:0; }
  .slide-row__heading { font-weight:700; color:#1a1a2e; font-size:14px; margin-bottom:2px; }
  .slide-row__meta { font-size:12px; color:#9b97b0; }
  .slide-row__actions { display:flex; gap:6px; align-items:center; flex-shrink:0; }
  .icon-btn { background:#f4f3f9; border:none; width:32px; height:32px; border-radius:7px; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; }
  .icon-btn:hover { background:#e8e6f0; }
  .icon-btn--danger:hover { background:#ffe6e6; }
  .status-badge { font-size:10.5px; font-weight:700; padding:2px 9px; border-radius:20px; text-transform:uppercase; flex-shrink:0; }
  .badge--active { background:#e6f9ed; color:#1a7a3a; }
  .badge--inactive { background:#f0eef6; color:#9b97b0; }

  /* Inner pages grid */
  .inner-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
  .inner-item { background:#fff; border:1px solid #e8e6f0; border-radius:12px; padding:14px; }
  .inner-item__label { font-size:12.5px; font-weight:700; color:#3d1a6e; margin-bottom:8px; }
  .inner-item__thumb { width:100%; height:100px; border-radius:8px; object-fit:cover; background:#f4f3f9; margin-bottom:8px; display:block; }
  .inner-item__empty { width:100%; height:100px; border-radius:8px; background:#f4f3f9; margin-bottom:8px; display:flex; align-items:center; justify-content:center; color:#c8c4dc; font-size:11px; }
  .inner-item input[type="file"] { font-size:11.5px; width:100%; }
  .inner-item__remove { font-size:11px; color:#cc3333; background:none; border:none; cursor:pointer; margin-top:6px; padding:0; }

  .empty-state { padding:30px 20px; text-align:center; color:#9b97b0; font-size:13px; }

  /* Focal point picker — 3x3 grid */
  .focal-picker { display:inline-grid; grid-template-columns:repeat(3,28px); grid-template-rows:repeat(3,28px); gap:3px; margin-top:8px; }
  .focal-picker__cell { width:28px; height:28px; border:1.5px solid #e2e0ea; border-radius:5px; background:#fff; cursor:pointer; padding:0; display:flex; align-items:center; justify-content:center; }
  .focal-picker__cell:hover { border-color:#4a90d9; }
  .focal-picker__cell.selected { background:#3d1a6e; border-color:#3d1a6e; }
  .focal-picker__cell.selected::after { content:''; width:8px; height:8px; border-radius:50%; background:#fff; }
  .focal-picker__wrap { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
  .focal-picker__hint { font-size:11.5px; color:#9b97b0; max-width:220px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'hero-images'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2>Hero Images</h2>
        <p>Manage the homepage slider and background photos for individual page headers.</p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- ════════════════════════════════════════
           SECTION 1 — HOMEPAGE SLIDER
           ════════════════════════════════════════ -->
      <div class="section-block">
        <div class="section-block__title">🖼️ Homepage Slider</div>
        <p class="section-block__sub">
          Each slide is a full photo with its own heading, description, and up to two buttons. Auto-rotates on the homepage with dot navigation. If no slides exist, the homepage falls back to its default content.
        </p>

        <div class="card">
          <form method="POST" enctype="multipart/form-data" id="slideForm">
            <input type="hidden" name="action" value="save_slide"/>
            <input type="hidden" name="id" value="<?php echo $editSlide ? (int) $editSlide['id'] : ''; ?>"/>

            <div class="form-group">
              <label class="form-label">Photo <?php echo $editSlide ? '(leave blank to keep current)' : '*'; ?></label>
              <input type="file" class="form-input" name="image" accept="image/jpeg,image/png,image/webp"/>
              <p class="hint">JPG, PNG, or WEBP — max 3MB. Recommended: wide landscape photo, at least 1600px.</p>
              <?php if ($editSlide): ?>
              <img src="../assets/images/hero/<?php echo htmlspecialchars($editSlide['image']); ?>" style="width:160px;height:90px;object-fit:cover;border-radius:8px;margin-top:8px"/>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label class="form-label">Focal Point</label>
              <div class="focal-picker__wrap">
                <div class="focal-picker" data-target="slideFocalPosition">
                  <?php
                  $slideCurrentPos = $editSlide['focal_position'] ?? 'center center';
                  foreach ($focalPositions as $fp):
                  ?>
                  <button type="button" class="focal-picker__cell <?php echo $fp === $slideCurrentPos ? 'selected' : ''; ?>"
                          data-position="<?php echo $fp; ?>" title="<?php echo ucwords($fp); ?>"></button>
                  <?php endforeach; ?>
                </div>
                <p class="focal-picker__hint">Choose which part of the photo stays visible when it's cropped to fit the wide banner shape.</p>
              </div>
              <input type="hidden" name="focal_position" id="slideFocalPosition" value="<?php echo htmlspecialchars($slideCurrentPos); ?>"/>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Badge Text</label>
                <input type="text" class="form-input" name="badge_text" maxlength="150"
                       value="<?php echo htmlspecialchars($editSlide['badge_text'] ?? ''); ?>"
                       placeholder="e.g. ⭐ Est. 1954 · Government Secondary School"/>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Heading *</label>
              <input type="text" class="form-input" name="heading" required maxlength="255"
                     value="<?php echo htmlspecialchars($editSlide['heading'] ?? ''); ?>"
                     placeholder="e.g. Shaping Minds. Building Character."/>
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-textarea" name="description" rows="3" maxlength="400"
                        placeholder="Short supporting text under the heading"><?php echo htmlspecialchars($editSlide['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Button 1 Text</label>
                <input type="text" class="form-input" name="cta1_text" maxlength="60"
                       value="<?php echo htmlspecialchars($editSlide['cta1_text'] ?? ''); ?>" placeholder="e.g. Apply for Admission"/>
              </div>
              <div class="form-group">
                <label class="form-label">Button 1 Link</label>
                <input type="text" class="form-input" name="cta1_url" maxlength="255"
                       value="<?php echo htmlspecialchars($editSlide['cta1_url'] ?? ''); ?>" placeholder="e.g. admissions.php"/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Button 2 Text</label>
                <input type="text" class="form-input" name="cta2_text" maxlength="60"
                       value="<?php echo htmlspecialchars($editSlide['cta2_text'] ?? ''); ?>" placeholder="e.g. Check Results Online"/>
              </div>
              <div class="form-group">
                <label class="form-label">Button 2 Link</label>
                <input type="text" class="form-input" name="cta2_url" maxlength="255"
                       value="<?php echo htmlspecialchars($editSlide['cta2_url'] ?? ''); ?>" placeholder="e.g. results.php"/>
              </div>
            </div>

            <label class="toggle-inline">
              <input type="checkbox" name="is_active" <?php echo (!$editSlide || $editSlide['is_active']) ? 'checked' : ''; ?>/>
              Active (visible on homepage)
            </label>

            <div style="display:flex;gap:10px">
              <button type="submit" class="btn-save"><?php echo $editSlide ? 'Update Slide' : 'Add Slide'; ?></button>
              <?php if ($editSlide): ?>
              <a href="hero-images.php" class="btn-cancel">Cancel Edit</a>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <?php if (empty($slides)): ?>
        <div class="card"><div class="empty-state">No slides yet. The homepage is showing its default 3 built-in slides. Add one above to start replacing them.</div></div>
        <?php else: ?>
        <?php foreach ($slides as $i => $slide): ?>
        <div class="slide-row">
          <img src="../assets/images/hero/<?php echo htmlspecialchars($slide['image']); ?>" class="slide-row__thumb" alt=""/>
          <div class="slide-row__body">
            <div class="slide-row__heading"><?php echo htmlspecialchars($slide['heading']); ?></div>
            <div class="slide-row__meta">
              <?php echo htmlspecialchars($slide['badge_text'] ?: 'No badge text'); ?>
            </div>
          </div>
          <span class="status-badge <?php echo $slide['is_active'] ? 'badge--active' : 'badge--inactive'; ?>">
            <?php echo $slide['is_active'] ? 'Active' : 'Hidden'; ?>
          </span>
          <div class="slide-row__actions">
            <form method="POST" style="display:contents">
              <input type="hidden" name="id" value="<?php echo $slide['id']; ?>"/>
              <button type="submit" name="action" value="move_up" class="icon-btn" title="Move up" <?php echo $i === 0 ? 'disabled style="opacity:.3"' : ''; ?>>↑</button>
              <button type="submit" name="action" value="move_down" class="icon-btn" title="Move down" <?php echo $i === count($slides) - 1 ? 'disabled style="opacity:.3"' : ''; ?>>↓</button>
              <button type="submit" name="action" value="toggle_active" class="icon-btn" title="Toggle visibility"><?php echo $slide['is_active'] ? '🙈' : '👁️'; ?></button>
            </form>
            <a href="hero-images.php?edit=<?php echo $slide['id']; ?>#slideForm" class="icon-btn" title="Edit">✏️</a>
            <form method="POST" style="display:contents" onsubmit="return confirm('Remove this slide permanently?')">
              <input type="hidden" name="id" value="<?php echo $slide['id']; ?>"/>
              <button type="submit" name="action" value="delete_slide" class="icon-btn icon-btn--danger" title="Delete">🗑️</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>


      <!-- ════════════════════════════════════════
           SECTION 2 — INNER PAGE IMAGES
           ════════════════════════════════════════ -->
      <div class="section-block">
        <div class="section-block__title">📄 Inner Page Header Images</div>
        <p class="section-block__sub">
          One optional background photo per page, shown behind the purple gradient header. Leave blank to keep the default gradient look.
        </p>

        <div class="card">
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_inner_images"/>

            <div class="inner-grid">
              <?php foreach ($innerPages as $key => $label):
                $entry = getInnerHeroEntry($key);
                $entryImage = $entry['image']    ?? null;
                $entryPos   = $entry['position'] ?? 'center center';
              ?>
              <div class="inner-item">
                <div class="inner-item__label"><?php echo htmlspecialchars($label); ?></div>
                <?php if ($entryImage): ?>
                <img src="../assets/images/hero/<?php echo htmlspecialchars($entryImage); ?>" class="inner-item__thumb" alt=""/>
                <?php else: ?>
                <div class="inner-item__empty">No image set</div>
                <?php endif; ?>
                <input type="file" name="inner_<?php echo $key; ?>" accept="image/jpeg,image/png,image/webp"/>
                <?php if ($entryImage): ?>
                <button type="button" class="inner-item__remove" onclick="removeInnerImage('<?php echo $key; ?>')">Remove image</button>

                <div style="margin-top:10px">
                  <div style="font-size:11px;font-weight:600;color:#3d1a6e;text-transform:uppercase;letter-spacing:.03em;margin-bottom:4px">Focal Point</div>
                  <div class="focal-picker" data-target="pos_<?php echo $key; ?>">
                    <?php foreach ($focalPositions as $fp): ?>
                    <button type="button" class="focal-picker__cell <?php echo $fp === $entryPos ? 'selected' : ''; ?>"
                            data-position="<?php echo $fp; ?>" title="<?php echo ucwords($fp); ?>"></button>
                    <?php endforeach; ?>
                  </div>
                  <input type="hidden" name="position_<?php echo $key; ?>" id="pos_<?php echo $key; ?>" value="<?php echo htmlspecialchars($entryPos); ?>"/>
                </div>
                <?php else: ?>
                <input type="hidden" name="position_<?php echo $key; ?>" value="center center"/>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>

            <button type="submit" class="btn-save" style="margin-top:18px">Save Page Images</button>
          </form>
        </div>
      </div>

    </div>
  </div>

  <!-- Hidden form for removing a single inner image -->
  <form method="POST" id="removeInnerForm" style="display:none">
    <input type="hidden" name="action" value="remove_inner_image"/>
    <input type="hidden" name="page_key" id="removeInnerKey"/>
  </form>

  <script src="../assets/js/admin.js"></script>
  <script>
    function removeInnerImage(key) {
      if (!confirm('Remove this page\'s header image?')) return;
      document.getElementById('removeInnerKey').value = key;
      document.getElementById('removeInnerForm').submit();
    }

    /* ── Focal point pickers — works for both the slide form's single
       picker and every inner-page card's picker, keyed by data-target ── */
    document.querySelectorAll('.focal-picker').forEach(function (picker) {
      var hiddenInput = document.getElementById(picker.dataset.target);
      if (!hiddenInput) return;

      picker.querySelectorAll('.focal-picker__cell').forEach(function (cell) {
        cell.addEventListener('click', function () {
          picker.querySelectorAll('.focal-picker__cell').forEach(function (c) {
            c.classList.remove('selected');
          });
          cell.classList.add('selected');
          hiddenInput.value = cell.dataset.position;
        });
      });
    });
  </script>

</body>
</html>