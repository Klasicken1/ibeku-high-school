<?php
/* ============================================================
   IBEKU HIGH SCHOOL — GALLERY MANAGEMENT
   File: public/admin/gallery.php

   Accessible to: superadmin, vp_general, principal
   Lists all gallery photos with filters, bulk publish/unpublish,
   delete, and sort order management.
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

/* ── Handle actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = trim($_POST['action']  ?? '');
    $photoId = (int) ($_POST['photo_id'] ?? 0);

    if ($action === 'toggle_publish' && $photoId > 0) {
        try {
            $pdo->prepare('UPDATE gallery SET is_published = NOT is_published WHERE id = ?')->execute([$photoId]);
            $message = 'Photo status updated.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif ($action === 'delete' && $photoId > 0) {
        try {
            $fileStmt = $pdo->prepare('SELECT filename FROM gallery WHERE id = ? LIMIT 1');
            $fileStmt->execute([$photoId]);
            $filename = $fileStmt->fetchColumn();

            if ($filename) {
                $filePath = dirname(__DIR__) . '/assets/images/gallery/' . $filename;
                if (file_exists($filePath)) unlink($filePath);
            }

            $pdo->prepare('DELETE FROM gallery WHERE id = ?')->execute([$photoId]);
            $message = 'Photo deleted.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif ($action === 'update_caption' && $photoId > 0) {
        $caption = trim($_POST['caption'] ?? '');
        try {
            $pdo->prepare('UPDATE gallery SET caption = ? WHERE id = ?')->execute([$caption ?: null, $photoId]);
            $message = 'Caption updated.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif ($action === 'bulk_publish' || $action === 'bulk_unpublish') {
        $ids = $_POST['selected_ids'] ?? [];
        if (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $val = $action === 'bulk_publish' ? 1 : 0;
            try {
                $pdo->prepare("UPDATE gallery SET is_published = $val WHERE id IN ($placeholders)")->execute($ids);
                $message = count($ids) . ' photo(s) ' . ($val ? 'published' : 'unpublished') . '.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'A server error occurred.'; $messageType = 'error';
            }
        }
    }
}

/* ── Filters ── */
$filterCategory = $_GET['category'] ?? '';
$filterStatus   = $_GET['status']   ?? '';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 24;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($filterCategory) {
    $where[]  = 'category = ?';
    $params[] = $filterCategory;
}
if ($filterStatus !== '') {
    $where[]  = 'is_published = ?';
    $params[] = (int) $filterStatus;
}

$whereSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM gallery WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

$photoStmt = $pdo->prepare(
    "SELECT g.*, u.full_name AS uploaded_by_name
     FROM   gallery g
     LEFT JOIN users u ON u.id = g.uploaded_by
     WHERE  $whereSQL
     ORDER  BY g.sort_order ASC, g.uploaded_at DESC
     LIMIT  ? OFFSET ?"
);
$photoStmt->execute([...$params, $perPage, $offset]);
$photos = $photoStmt->fetchAll();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Gallery — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
  .btn-new { background:#3d1a6e; color:#fff; text-decoration:none; padding:10px 20px; border-radius:8px; font-size:13.5px; font-weight:700; }
  .btn-new:hover { background:#5a2d9e; }

  .filter-bar { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:14px 18px; margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; letter-spacing:.04em; }
  .filter-group select { padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:140px; }
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
  .btn-filter:hover { background:#3a7dc4; }
  .btn-reset { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:8px 14px; border-radius:7px; font-size:12.5px; font-weight:600; text-decoration:none; }

  .bulk-bar { display:flex; gap:8px; margin-bottom:16px; align-items:center; flex-wrap:wrap; }
  .btn-bulk { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:7px 14px; border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer; }
  .btn-bulk:hover { background:#e4dcf6; }
  .bulk-count { font-size:12.5px; color:#9b97b0; }

  .photo-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px; margin-bottom:24px; }
  .photo-card { background:#fff; border:1px solid #e8e6f0; border-radius:12px; overflow:hidden; position:relative; }
  .photo-card--selected { border-color:#3d1a6e; box-shadow:0 0 0 2px #3d1a6e33; }

  .photo-card__checkbox {
    position:absolute; top:10px; left:10px; z-index:2;
    width:18px; height:18px; cursor:pointer;
    accent-color:#3d1a6e;
  }
  .photo-card__img {
    width:100%; height:160px; object-fit:cover; display:block;
    background:#f4f3f9;
  }
  .photo-card__body { padding:10px 12px; }
  .photo-card__title { font-size:12.5px; font-weight:600; color:#1a1a2e; margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .photo-card__meta { font-size:11.5px; color:#9b97b0; margin-bottom:8px; }

  .badge { display:inline-block; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; text-transform:uppercase; }
  .badge--published   { background:#e6f9ed; color:#1a7a3a; }
  .badge--unpublished { background:#fff3e6; color:#8a4a00; }

  .photo-card__actions { display:flex; gap:6px; flex-wrap:wrap; }
  .action-btn { font-size:11px; font-weight:600; padding:4px 9px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--publish   { background:#e6f9ed; color:#1a7a3a; }
  .action-btn--unpublish { background:#fff3e6; color:#8a4a00; }
  .action-btn--delete    { background:#ffe6e6; color:#cc3333; }

  .results-count { font-size:13px; color:#6b6b80; margin-bottom:12px; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; background:#fff; border:1px solid #e8e6f0; border-radius:14px; }

  .pagination { display:flex; gap:6px; justify-content:center; margin-top:20px; flex-wrap:wrap; }
  .pagination a, .pagination span { padding:6px 12px; border-radius:7px; font-size:13px; font-weight:600; border:1px solid #e8e6f0; text-decoration:none; color:#3d1a6e; background:#fff; }
  .pagination a:hover { background:#f0ecfa; }
  .pagination .current { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }

  .cat-tag { display:inline-block; font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; background:#f0ecfa; color:#3d1a6e; text-transform:uppercase; margin-bottom:4px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'gallery'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Gallery</h2>
          <p>Manage school photos. Upload new photos, assign categories, and control what's visible on the public gallery.</p>
        </div>
        <a href="gallery-upload.php" class="btn-new">+ Upload Photos</a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Filters -->
      <form method="GET" class="filter-bar">
        <div class="filter-group">
          <label>Category</label>
          <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filterCategory === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Status</label>
          <select name="status">
            <option value="">All</option>
            <option value="1" <?php echo $filterStatus === '1' ? 'selected' : ''; ?>>Published</option>
            <option value="0" <?php echo $filterStatus === '0' ? 'selected' : ''; ?>>Unpublished</option>
          </select>
        </div>
        <button type="submit" class="btn-filter">Filter</button>
        <a href="gallery.php" class="btn-reset">Reset</a>
      </form>

      <p class="results-count">
        Showing <strong><?php echo count($photos); ?></strong> of <strong><?php echo $total; ?></strong> photos
      </p>

      <?php if (empty($photos)): ?>
      <div class="empty-state">
        No photos found. <a href="gallery-upload.php" style="color:#4a90d9">Upload some photos</a> to get started.
      </div>
      <?php else: ?>

      <form method="POST" id="bulkForm">
        <!-- Bulk action bar -->
        <div class="bulk-bar">
          <button type="button" class="btn-bulk" onclick="selectAll(true)">Select All</button>
          <button type="button" class="btn-bulk" onclick="selectAll(false)">Deselect All</button>
          <button type="submit" name="action" value="bulk_publish"   class="btn-bulk" onclick="return confirmBulk('publish')">Publish Selected</button>
          <button type="submit" name="action" value="bulk_unpublish" class="btn-bulk" onclick="return confirmBulk('unpublish')">Unpublish Selected</button>
          <span class="bulk-count" id="bulkCount">0 selected</span>
        </div>

        <div class="photo-grid">
          <?php foreach ($photos as $photo): ?>
          <div class="photo-card" id="card-<?php echo $photo['id']; ?>">
            <input type="checkbox" name="selected_ids[]"
                   value="<?php echo $photo['id']; ?>"
                   class="photo-card__checkbox bulk-cb"
                   onchange="updateBulkCount(); toggleCardSelected(<?php echo $photo['id']; ?>, this.checked)"
                   title="Select photo"/>

            <?php
            $imgPath = '../assets/images/gallery/' . htmlspecialchars($photo['filename']);
            ?>
            <img src="<?php echo $imgPath; ?>"
                 alt="<?php echo htmlspecialchars($photo['title']); ?>"
                 class="photo-card__img"
                 onerror="this.onerror=null;this.src='../assets/images/icons/icon-192.png';this.style.objectFit='contain';this.style.padding='40px';this.style.background='#f4f3f9'"/>

            <div class="photo-card__body">
              <div class="cat-tag"><?php echo htmlspecialchars($categories[$photo['category']] ?? $photo['category']); ?></div>
              <div class="photo-card__title" title="<?php echo htmlspecialchars($photo['title']); ?>">
                <?php echo htmlspecialchars($photo['title']); ?>
              </div>
              <div class="photo-card__meta">
                <span class="badge badge--<?php echo $photo['is_published'] ? 'published' : 'unpublished'; ?>">
                  <?php echo $photo['is_published'] ? 'Published' : 'Unpublished'; ?>
                </span>
                &nbsp;·&nbsp;
                <?php echo date('d M Y', strtotime($photo['uploaded_at'])); ?>
              </div>
              <div class="photo-card__actions">
                <form method="POST" style="display:inline">
                  <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>"/>
                  <input type="hidden" name="action" value="toggle_publish"/>
                  <button type="submit" class="action-btn <?php echo $photo['is_published'] ? 'action-btn--unpublish' : 'action-btn--publish'; ?>">
                    <?php echo $photo['is_published'] ? 'Unpublish' : 'Publish'; ?>
                  </button>
                </form>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Delete this photo permanently?')">
                  <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>"/>
                  <input type="hidden" name="action" value="delete"/>
                  <button type="submit" class="action-btn action-btn--delete">Delete</button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </form>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
        $qBase = http_build_query(array_filter(['category' => $filterCategory, 'status' => $filterStatus]));
        for ($p = 1; $p <= $totalPages; $p++):
        ?>
        <?php if ($p === $page): ?>
        <span class="current"><?php echo $p; ?></span>
        <?php else: ?>
        <a href="?<?php echo $qBase; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
        <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

      <?php endif; ?>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
  <script>
    function selectAll(checked) {
      document.querySelectorAll('.bulk-cb').forEach(function (cb) {
        cb.checked = checked;
        toggleCardSelected(parseInt(cb.value), checked);
      });
      updateBulkCount();
    }

    function toggleCardSelected(id, checked) {
      var card = document.getElementById('card-' + id);
      if (card) {
        if (checked) card.classList.add('photo-card--selected');
        else         card.classList.remove('photo-card--selected');
      }
    }

    function updateBulkCount() {
      var count = document.querySelectorAll('.bulk-cb:checked').length;
      var el = document.getElementById('bulkCount');
      if (el) el.textContent = count + ' selected';
    }

    function confirmBulk(action) {
      var count = document.querySelectorAll('.bulk-cb:checked').length;
      if (count === 0) { alert('Please select at least one photo.'); return false; }
      return confirm(count + ' photo(s) will be ' + action + 'ed. Continue?');
    }
  </script>

</body>
</html>