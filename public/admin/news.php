<?php
/* ============================================================
   IBEKU HIGH SCHOOL — NEWS LIST
   File: public/admin/news.php

   Accessible to: superadmin, principal, vp_general
   Lists all articles (draft + published), with actions to
   publish, unpublish, edit, or delete.
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

/* ── Handle publish/unpublish/delete actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_id'], $_POST['action'])) {
    $articleId = (int) $_POST['article_id'];
    $action    = $_POST['action'];

    try {
        if ($action === 'publish') {
            $stmt = $pdo->prepare('UPDATE news SET is_published = 1, published_at = NOW() WHERE id = ?');
            $stmt->execute([$articleId]);
            $message = 'Article published.';
            $messageType = 'success';

        } elseif ($action === 'unpublish') {
            $stmt = $pdo->prepare('UPDATE news SET is_published = 0 WHERE id = ?');
            $stmt->execute([$articleId]);
            $message = 'Article unpublished and returned to draft.';
            $messageType = 'success';

        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM news WHERE id = ?');
            $stmt->execute([$articleId]);
            $message = 'Article deleted.';
            $messageType = 'success';

        } elseif ($action === 'feature') {
            $stmt = $pdo->prepare('UPDATE news SET featured = 1 WHERE id = ?');
            $stmt->execute([$articleId]);
            /* Ensure only one featured article at a time */
            $stmt2 = $pdo->prepare('UPDATE news SET featured = 0 WHERE id != ?');
            $stmt2->execute([$articleId]);
            $message = 'Article marked as featured.';
            $messageType = 'success';

        } elseif ($action === 'unfeature') {
            $stmt = $pdo->prepare('UPDATE news SET featured = 0 WHERE id = ?');
            $stmt->execute([$articleId]);
            $message = 'Article removed from featured.';
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        error_log('IHS news.php action error: ' . $e->getMessage());
        $message = 'A server error occurred. Please try again.';
        $messageType = 'error';
    }
}

/* ── Filter by status ── */
$statusFilter = $_GET['status'] ?? 'all';

$sql = 'SELECT n.id, n.title, n.slug, n.category, n.featured, n.is_published,
               n.published_at, n.created_at, n.views, u.full_name AS author_name
        FROM   news n
        LEFT JOIN users u ON u.id = n.author_id';

if ($statusFilter === 'published') {
    $sql .= ' WHERE n.is_published = 1';
} elseif ($statusFilter === 'draft') {
    $sql .= ' WHERE n.is_published = 0';
}

$sql .= ' ORDER BY n.created_at DESC';

$articles = $pdo->query($sql)->fetchAll();

$totalCount     = $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
$publishedCount = $pdo->query('SELECT COUNT(*) FROM news WHERE is_published = 1')->fetchColumn();
$draftCount     = $pdo->query('SELECT COUNT(*) FROM news WHERE is_published = 0')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>All News — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 22px; flex-wrap: wrap; gap: 14px;
  }
  .btn-new {
    background: #3d1a6e; color: #fff; text-decoration: none;
    padding: 10px 20px; border-radius: 8px; font-size: 13.5px; font-weight: 700;
    white-space: nowrap;
  }
  .btn-new:hover { background: #5a2d9e; }

  .filter-tabs { display: flex; gap: 6px; margin-bottom: 20px; }
  .filter-tab {
    padding: 7px 16px; border-radius: 20px; font-size: 12.5px; font-weight: 600;
    text-decoration: none; color: #6b6b80; background: #fff; border: 1px solid #e8e6f0;
  }
  .filter-tab--active { background: #3d1a6e; color: #fff; border-color: #3d1a6e; }
  .filter-tab__count { opacity: .7; }

  .news-table-wrap { background: #fff; border: 1px solid #e8e6f0; border-radius: 14px; overflow: hidden; }
  table.news-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  table.news-table th {
    background: #3d1a6e; color: #fff; padding: 11px 14px; text-align: left;
    font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em;
  }
  table.news-table td { padding: 12px 14px; border-bottom: 1px solid #f0eef6; vertical-align: middle; }
  table.news-table tr:last-child td { border-bottom: none; }
  table.news-table tr:hover td { background: #faf9fd; }

  .article-title { font-weight: 600; color: #1a1a2e; margin-bottom: 3px; }
  .article-meta { font-size: 11.5px; color: #9b97b0; }

  .badge { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; }
  .badge--published { background: #e6f9ed; color: #1a7a3a; }
  .badge--draft { background: #fff3e6; color: #8a4a00; }
  .badge--featured { background: #fff8e1; color: #8a6000; margin-left: 5px; }

  .cat-tag {
    display: inline-block; font-size: 11px; font-weight: 600;
    padding: 2px 9px; border-radius: 20px; background: #f0ecfa; color: #3d1a6e;
  }

  .actions-cell { display: flex; gap: 6px; flex-wrap: wrap; }
  .action-btn {
    border: none; padding: 6px 12px; border-radius: 6px; font-size: 11.5px;
    font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block;
  }
  .action-btn--edit { background: #f0ecfa; color: #3d1a6e; }
  .action-btn--edit:hover { background: #e4dcf6; }
  .action-btn--publish { background: #e6f9ed; color: #1a7a3a; }
  .action-btn--publish:hover { background: #d4f2dd; }
  .action-btn--unpublish { background: #fff3e6; color: #8a4a00; }
  .action-btn--unpublish:hover { background: #ffe9d0; }
  .action-btn--feature { background: #fff8e1; color: #8a6000; }
  .action-btn--feature:hover { background: #fcefc4; }
  .action-btn--delete { background: #ffe6e6; color: #cc3333; }
  .action-btn--delete:hover { background: #ffd6d6; }

  .empty-state { padding: 50px 20px; text-align: center; color: #6b6b80; font-size: 13.5px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'news'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>All News</h2>
          <p>Manage published articles and drafts.</p>
        </div>
        <a href="news-create.php" class="btn-new">+ New Article</a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="filter-tabs">
        <a href="?status=all" class="filter-tab <?php echo $statusFilter === 'all' ? 'filter-tab--active' : ''; ?>">
          All <span class="filter-tab__count">(<?php echo $totalCount; ?>)</span>
        </a>
        <a href="?status=published" class="filter-tab <?php echo $statusFilter === 'published' ? 'filter-tab--active' : ''; ?>">
          Published <span class="filter-tab__count">(<?php echo $publishedCount; ?>)</span>
        </a>
        <a href="?status=draft" class="filter-tab <?php echo $statusFilter === 'draft' ? 'filter-tab--active' : ''; ?>">
          Drafts <span class="filter-tab__count">(<?php echo $draftCount; ?>)</span>
        </a>
      </div>

      <div class="news-table-wrap">
        <?php if (empty($articles)): ?>
        <div class="empty-state">No articles found<?php echo $statusFilter !== 'all' ? ' in this filter' : ''; ?>.</div>
        <?php else: ?>
        <table class="news-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th>Status</th>
              <th>Author</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($articles as $a): ?>
            <tr>
              <td>
                <div class="article-title"><?php echo htmlspecialchars($a['title']); ?></div>
                <div class="article-meta"><?php echo (int) $a['views']; ?> views</div>
              </td>
              <td><span class="cat-tag"><?php echo htmlspecialchars(ucfirst($a['category'])); ?></span></td>
              <td>
                <span class="badge badge--<?php echo $a['is_published'] ? 'published' : 'draft'; ?>">
                  <?php echo $a['is_published'] ? 'Published' : 'Draft'; ?>
                </span>
                <?php if ($a['featured']): ?>
                <span class="badge badge--featured">★ Featured</span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($a['author_name'] ?? 'Unknown'); ?></td>
              <td>
                <?php echo $a['is_published'] && $a['published_at']
                    ? date('M j, Y', strtotime($a['published_at']))
                    : date('M j, Y', strtotime($a['created_at'])); ?>
              </td>
              <td>
                <div class="actions-cell">
                  <a href="news-edit.php?id=<?php echo $a['id']; ?>" class="action-btn action-btn--edit">Edit</a>

                  <?php if ($a['is_published']): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="article_id" value="<?php echo $a['id']; ?>"/>
                    <input type="hidden" name="action" value="unpublish"/>
                    <button type="submit" class="action-btn action-btn--unpublish">Unpublish</button>
                  </form>
                  <?php else: ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="article_id" value="<?php echo $a['id']; ?>"/>
                    <input type="hidden" name="action" value="publish"/>
                    <button type="submit" class="action-btn action-btn--publish">Publish</button>
                  </form>
                  <?php endif; ?>

                  <?php if (!$a['featured']): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="article_id" value="<?php echo $a['id']; ?>"/>
                    <input type="hidden" name="action" value="feature"/>
                    <button type="submit" class="action-btn action-btn--feature">Feature</button>
                  </form>
                  <?php endif; ?>

                  <form method="POST" style="display:inline" onsubmit="return confirm('Delete this article permanently? This cannot be undone.');">
                    <input type="hidden" name="article_id" value="<?php echo $a['id']; ?>"/>
                    <input type="hidden" name="action" value="delete"/>
                    <button type="submit" class="action-btn action-btn--delete">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>