<?php
/* ============================================================
   IBEKU HIGH SCHOOL — EVENTS MANAGEMENT (LIST)
   File: public/admin/events.php

   Accessible to: superadmin, principal, vp_general, dean
   Lists all school events with filters by category, status,
   and date range. Supports publish/unpublish, feature/unfeature,
   and delete.
   ============================================================ */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-auth.php';
require_once dirname(__DIR__, 2) . '/src/includes/admin-sidebar.php';

requireRole(['superadmin', 'principal', 'vp_general', 'dean']);

$admin = currentAdmin();
$pdo   = getDB();

$message     = '';
$messageType = '';

/* ── Handle actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = trim($_POST['action']  ?? '');
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($action === 'toggle_publish' && $eventId > 0) {
        try {
            $pdo->prepare('UPDATE events SET is_published = NOT is_published WHERE id = ?')->execute([$eventId]);
            $message = 'Event status updated.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif ($action === 'toggle_featured' && $eventId > 0) {
        try {
            $pdo->prepare('UPDATE events SET is_featured = NOT is_featured WHERE id = ?')->execute([$eventId]);
            $message = 'Event feature status updated.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }

    } elseif ($action === 'delete' && $eventId > 0) {
        try {
            $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$eventId]);
            $message = 'Event deleted.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}

$categories = [
    'academic'    => 'Academic',
    'sports'      => 'Sports',
    'culture'     => 'Culture',
    'examination' => 'Examination',
    'meeting'     => 'Meeting',
    'holiday'     => 'Holiday',
    'general'     => 'General',
];

$categoryColors = [
    'academic'    => ['bg' => '#e6f0ff', 'color' => '#1a5a9a'],
    'sports'      => ['bg' => '#e6f9ed', 'color' => '#1a7a3a'],
    'culture'     => ['bg' => '#f0ecfa', 'color' => '#3d1a6e'],
    'examination' => ['bg' => '#ffe6e6', 'color' => '#cc3333'],
    'meeting'     => ['bg' => '#fff3e6', 'color' => '#8a4a00'],
    'holiday'     => ['bg' => '#fffbe6', 'color' => '#8a6a00'],
    'general'     => ['bg' => '#f4f3f9', 'color' => '#6b6b80'],
];

/* ── Filters ── */
$filterCategory = $_GET['category'] ?? '';
$filterStatus   = $_GET['status']   ?? '';
$filterWhen     = $_GET['when']     ?? 'all';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($filterCategory) {
    $where[]  = 'e.category = ?';
    $params[] = $filterCategory;
}
if ($filterStatus !== '') {
    $where[]  = 'e.is_published = ?';
    $params[] = (int) $filterStatus;
}
if ($filterWhen === 'upcoming') {
    $where[]  = 'e.event_date >= CURDATE()';
} elseif ($filterWhen === 'past') {
    $where[]  = 'e.event_date < CURDATE()';
} elseif ($filterWhen === 'today') {
    $where[]  = 'e.event_date = CURDATE()';
}

$whereSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM events e WHERE $whereSQL");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

$eventStmt = $pdo->prepare(
    "SELECT e.*, u.full_name AS created_by_name
     FROM   events e
     LEFT JOIN users u ON u.id = e.created_by
     WHERE  $whereSQL
     ORDER  BY e.event_date ASC, e.start_time ASC
     LIMIT  ? OFFSET ?"
);
$eventStmt->execute([...$params, $perPage, $offset]);
$events = $eventStmt->fetchAll();

/* ── Stats ── */
$upcoming = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND is_published = 1")->fetchColumn();
$total_events = (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Events — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .page-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
  .btn-new { background:#3d1a6e; color:#fff; text-decoration:none; padding:10px 20px; border-radius:8px; font-size:13.5px; font-weight:700; }
  .btn-new:hover { background:#5a2d9e; }

  .stats-row { display:flex; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
  .stat-pill { background:#fff; border:1px solid #e8e6f0; border-radius:10px; padding:10px 18px; font-size:12.5px; color:#6b6b80; }
  .stat-pill strong { color:#3d1a6e; font-size:15px; }

  .filter-bar { background:#fff; border:1px solid #e8e6f0; border-radius:14px; padding:14px 18px; margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#3d1a6e; text-transform:uppercase; letter-spacing:.04em; }
  .filter-group select { padding:7px 10px; border:1.5px solid #e2e0ea; border-radius:7px; font-size:13px; font-family:'DM Sans',sans-serif; min-width:130px; }
  .btn-filter { background:#4a90d9; color:#fff; border:none; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
  .btn-filter:hover { background:#3a7dc4; }
  .btn-reset { background:#f0ecfa; color:#3d1a6e; border:1px solid #d8d0ee; padding:8px 14px; border-radius:7px; font-size:12.5px; font-weight:600; text-decoration:none; }

  .events-table-wrap { background:#fff; border:1px solid #e8e6f0; border-radius:14px; overflow:hidden; }
  table.events-table { width:100%; border-collapse:collapse; font-size:13px; }
  table.events-table th {
    background:#3d1a6e; color:#fff; padding:11px 14px; text-align:left;
    font-size:11.5px; text-transform:uppercase; letter-spacing:.04em;
  }
  table.events-table td { padding:12px 14px; border-bottom:1px solid #f0eef6; vertical-align:middle; }
  table.events-table tr:last-child td { border-bottom:none; }
  table.events-table tr:hover td { background:#faf9fd; }

  .event-date-block { text-align:center; min-width:50px; }
  .event-date-block__day { font-size:20px; font-weight:700; color:#3d1a6e; line-height:1; }
  .event-date-block__month { font-size:11px; font-weight:600; color:#9b97b0; text-transform:uppercase; }
  .event-date-block__past { opacity:.45; }

  .event-title { font-weight:600; color:#1a1a2e; margin-bottom:3px; }
  .event-venue { font-size:12px; color:#9b97b0; }
  .event-time  { font-size:12px; color:#6b6b80; }

  .cat-badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; }

  .badge { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; }
  .badge--published   { background:#e6f9ed; color:#1a7a3a; }
  .badge--unpublished { background:#fff3e6; color:#8a4a00; }
  .badge--featured    { background:#f0ecfa; color:#3d1a6e; }

  .actions-cell { display:flex; gap:6px; flex-wrap:wrap; }
  .action-btn { font-size:11.5px; font-weight:600; padding:5px 11px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
  .action-btn--edit      { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--edit:hover { background:#e4dcf6; }
  .action-btn--publish   { background:#e6f9ed; color:#1a7a3a; }
  .action-btn--unpublish { background:#fff3e6; color:#8a4a00; }
  .action-btn--feature   { background:#f0ecfa; color:#3d1a6e; }
  .action-btn--delete    { background:#ffe6e6; color:#cc3333; }

  .results-count { font-size:13px; color:#6b6b80; margin-bottom:12px; }
  .empty-state { padding:50px 20px; text-align:center; color:#6b6b80; font-size:13.5px; }

  .pagination { display:flex; gap:6px; justify-content:center; margin-top:20px; flex-wrap:wrap; }
  .pagination a, .pagination span { padding:6px 12px; border-radius:7px; font-size:13px; font-weight:600; border:1px solid #e8e6f0; text-decoration:none; color:#3d1a6e; background:#fff; }
  .pagination a:hover { background:#f0ecfa; }
  .pagination .current { background:#3d1a6e; color:#fff; border-color:#3d1a6e; }

  .today-badge { background:#4a90d9; color:#fff; font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; text-transform:uppercase; margin-left:6px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'events'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
          <h2>Events</h2>
          <p>Manage the school calendar — create, publish, and feature upcoming events.</p>
        </div>
        <a href="events-create.php" class="btn-new">+ New Event</a>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="stats-row">
        <div class="stat-pill"><strong><?php echo $total_events; ?></strong> Total Events</div>
        <div class="stat-pill"><strong><?php echo $upcoming; ?></strong> Upcoming (Published)</div>
      </div>

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
          <label>When</label>
          <select name="when">
            <option value="all"      <?php echo $filterWhen === 'all'      ? 'selected' : ''; ?>>All</option>
            <option value="today"    <?php echo $filterWhen === 'today'    ? 'selected' : ''; ?>>Today</option>
            <option value="upcoming" <?php echo $filterWhen === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
            <option value="past"     <?php echo $filterWhen === 'past'     ? 'selected' : ''; ?>>Past</option>
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
        <a href="events.php" class="btn-reset">Reset</a>
      </form>

      <p class="results-count">
        Showing <strong><?php echo count($events); ?></strong> of <strong><?php echo $total; ?></strong> events
      </p>

      <div class="events-table-wrap">
        <?php if (empty($events)): ?>
        <div class="empty-state">No events found. <a href="events-create.php" style="color:#4a90d9">Create one</a>.</div>
        <?php else: ?>
        <table class="events-table">
          <thead>
            <tr>
              <th style="width:60px">Date</th>
              <th>Event</th>
              <th>Category</th>
              <th>Time</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $event):
              $isPast  = strtotime($event['event_date']) < strtotime('today');
              $isToday = $event['event_date'] === date('Y-m-d');
              $catColor = $categoryColors[$event['category']] ?? $categoryColors['general'];
            ?>
            <tr>
              <td>
                <div class="event-date-block <?php echo $isPast ? 'event-date-block__past' : ''; ?>">
                  <div class="event-date-block__day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                  <div class="event-date-block__month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                </div>
              </td>
              <td>
                <div class="event-title">
                  <?php echo htmlspecialchars($event['title']); ?>
                  <?php if ($isToday): ?><span class="today-badge">Today</span><?php endif; ?>
                  <?php if ($event['is_featured']): ?><span class="badge badge--featured" style="margin-left:4px">Featured</span><?php endif; ?>
                </div>
                <?php if ($event['venue']): ?>
                <div class="event-venue">📍 <?php echo htmlspecialchars($event['venue']); ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="cat-badge" style="background:<?php echo $catColor['bg']; ?>;color:<?php echo $catColor['color']; ?>">
                  <?php echo htmlspecialchars($categories[$event['category']] ?? $event['category']); ?>
                </span>
              </td>
              <td class="event-time">
                <?php if ($event['start_time']): ?>
                  <?php echo date('g:ia', strtotime($event['start_time'])); ?>
                  <?php if ($event['end_time']): ?> – <?php echo date('g:ia', strtotime($event['end_time'])); ?><?php endif; ?>
                <?php else: ?>
                  <span style="color:#c8c4dc">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge--<?php echo $event['is_published'] ? 'published' : 'unpublished'; ?>">
                  <?php echo $event['is_published'] ? 'Published' : 'Draft'; ?>
                </span>
              </td>
              <td>
                <div class="actions-cell">
                  <a href="events-create.php?edit=<?php echo $event['id']; ?>" class="action-btn action-btn--edit">Edit</a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"/>
                    <input type="hidden" name="action" value="toggle_publish"/>
                    <button type="submit" class="action-btn <?php echo $event['is_published'] ? 'action-btn--unpublish' : 'action-btn--publish'; ?>">
                      <?php echo $event['is_published'] ? 'Unpublish' : 'Publish'; ?>
                    </button>
                  </form>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"/>
                    <input type="hidden" name="action" value="toggle_featured"/>
                    <button type="submit" class="action-btn action-btn--feature">
                      <?php echo $event['is_featured'] ? '★ Unfeature' : '☆ Feature'; ?>
                    </button>
                  </form>
                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('Delete this event permanently?')">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"/>
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

      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
        $qBase = http_build_query(array_filter(['category' => $filterCategory, 'status' => $filterStatus, 'when' => $filterWhen]));
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

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>

</body>
</html>