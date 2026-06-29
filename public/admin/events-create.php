<?php
/* ============================================================
   IBEKU HIGH SCHOOL — CREATE / EDIT EVENT
   File: public/admin/events-create.php

   Accessible to: superadmin, principal, vp_general, dean
   Handles both create (no ?edit param) and edit (?edit=ID).
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

$editId  = (int) ($_GET['edit'] ?? 0);
$isEdit  = $editId > 0;
$event   = null;

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $event = $stmt->fetch();
    if (!$event) { header('Location: events.php'); exit; }
}

$message     = '';
$messageType = '';

$categories = [
    'academic'    => 'Academic',
    'sports'      => 'Sports',
    'culture'     => 'Culture',
    'examination' => 'Examination',
    'meeting'     => 'Meeting',
    'holiday'     => 'Holiday',
    'general'     => 'General',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category']    ?? 'general');
    $eventDate   = trim($_POST['event_date']  ?? '');
    $startTime   = trim($_POST['start_time']  ?? '');
    $endTime     = trim($_POST['end_time']    ?? '');
    $venue       = trim($_POST['venue']       ?? '');
    $isFeatured  = isset($_POST['is_featured'])  ? 1 : 0;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    $validCategories = array_keys($categories);

    if ($title === '') {
        $message = 'Event title is required.'; $messageType = 'error';
    } elseif ($eventDate === '') {
        $message = 'Event date is required.'; $messageType = 'error';
    } elseif (!in_array($category, $validCategories, true)) {
        $message = 'Please select a valid category.'; $messageType = 'error';
    } elseif ($endTime && $startTime && $endTime <= $startTime) {
        $message = 'End time must be after start time.'; $messageType = 'error';
    } else {
        try {
            if ($isEdit) {
                $pdo->prepare(
                    'UPDATE events SET
                        title=?, description=?, category=?, event_date=?,
                        start_time=?, end_time=?, venue=?,
                        is_featured=?, is_published=?, updated_at=NOW()
                     WHERE id=?'
                )->execute([
                    $title, $description ?: null, $category, $eventDate,
                    $startTime ?: null, $endTime ?: null, $venue ?: null,
                    $isFeatured, $isPublished, $editId,
                ]);
                $message = 'Event updated successfully.'; $messageType = 'success';

                $stmt->execute([$editId]);
                $event = $stmt->fetch();

            } else {
                $pdo->prepare(
                    'INSERT INTO events
                        (title, description, category, event_date, start_time, end_time,
                         venue, is_featured, is_published, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $title, $description ?: null, $category, $eventDate,
                    $startTime ?: null, $endTime ?: null, $venue ?: null,
                    $isFeatured, $isPublished, $admin['id'],
                ]);

                $message = 'Event created successfully.'; $messageType = 'success';
                /* Clear form */
                $title = $description = $venue = $eventDate = $startTime = $endTime = '';
                $isFeatured = 0; $isPublished = 1;
            }
        } catch (PDOException $e) {
            error_log('IHS events-create error: ' . $e->getMessage());
            $message = 'A server error occurred.'; $messageType = 'error';
        }
    }
}

/* Pre-fill values for edit mode or after failed create */
$v = [
    'title'        => $event['title']        ?? ($title        ?? ''),
    'description'  => $event['description']  ?? ($description  ?? ''),
    'category'     => $event['category']     ?? ($category     ?? 'general'),
    'event_date'   => $event['event_date']   ?? ($eventDate    ?? ''),
    'start_time'   => $event['start_time']   ?? ($startTime    ?? ''),
    'end_time'     => $event['end_time']     ?? ($endTime      ?? ''),
    'venue'        => $event['venue']        ?? ($venue        ?? ''),
    'is_featured'  => $event['is_featured']  ?? ($isFeatured   ?? 0),
    'is_published' => $event['is_published'] ?? ($isPublished  ?? 1),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo $isEdit ? 'Edit Event' : 'New Event'; ?> — Admin — Ibeku High School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-layout.css">
<style>
  .form-group { margin-bottom:18px; }
  .form-label { display:block; font-size:12px; font-weight:600; color:#3d1a6e; margin-bottom:6px; text-transform:uppercase; letter-spacing:.03em; }
  .form-input, .form-select, .form-textarea {
    width:100%; padding:10px 13px; border:1.5px solid #e2e0ea; border-radius:8px;
    font-size:13.5px; font-family:'DM Sans',sans-serif; color:#1a1a2e;
  }
  .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color:#4a90d9; }
  .form-textarea { resize:vertical; }
  .form-row { display:flex; gap:16px; }
  .form-row .form-group { flex:1; }
  .char-hint { font-size:11.5px; color:#9b97b0; margin-top:4px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; margin-bottom:14px; }
  .checkbox-row input { width:16px; height:16px; }
  .checkbox-row label { font-size:13.5px; color:#1a1a2e; }
  .btn-group { display:flex; gap:12px; margin-top:22px; flex-wrap:wrap; }
  .btn-save { background:#3d1a6e; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; }
  .btn-save:hover { background:#5a2d9e; }
  .btn-cancel { background:#f0ecfa; color:#3d1a6e; border:1.5px solid #d8d0ee; padding:11px 22px; border-radius:8px; font-size:13.5px; font-weight:600; text-decoration:none; display:inline-block; }
  .section-label { font-size:13px; font-weight:700; color:#3d1a6e; margin-bottom:14px; border-top:1px solid #f0eef6; padding-top:16px; margin-top:4px; }
</style>
</head>
<body>

  <?php renderAdminSidebar($admin, 'events'); ?>

  <div class="admin-content">
    <div class="admin-content__inner">

      <div class="page-header">
        <h2><?php echo $isEdit ? 'Edit Event' : 'New Event'; ?></h2>
        <p><a href="events.php" style="color:#4a90d9;text-decoration:none">← Back to Events</a></p>
      </div>

      <?php if ($message): ?>
      <div class="alert alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="admin-card" style="max-width:620px">
        <form method="POST">

          <div class="form-group">
            <label class="form-label" for="title">Event Title *</label>
            <input type="text" class="form-input" id="title" name="title" required maxlength="255"
                   value="<?php echo htmlspecialchars($v['title']); ?>"
                   placeholder="e.g. Inter-House Sports Day 2025"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-textarea" id="description" name="description" rows="4"
                      placeholder="Details about the event — what's happening, who should attend, what to bring, etc."><?php echo htmlspecialchars($v['description'] ?? ''); ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="category">Category *</label>
              <select class="form-select" id="category" name="category" required>
                <?php foreach ($categories as $k => $lbl): ?>
                <option value="<?php echo $k; ?>" <?php echo $v['category'] === $k ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="event_date">Date *</label>
              <input type="date" class="form-input" id="event_date" name="event_date" required
                     value="<?php echo htmlspecialchars($v['event_date']); ?>"/>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="start_time">Start Time</label>
              <input type="time" class="form-input" id="start_time" name="start_time"
                     value="<?php echo htmlspecialchars($v['start_time'] ?? ''); ?>"/>
            </div>
            <div class="form-group">
              <label class="form-label" for="end_time">End Time</label>
              <input type="time" class="form-input" id="end_time" name="end_time"
                     value="<?php echo htmlspecialchars($v['end_time'] ?? ''); ?>"/>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="venue">Venue</label>
            <input type="text" class="form-input" id="venue" name="venue" maxlength="255"
                   value="<?php echo htmlspecialchars($v['venue'] ?? ''); ?>"
                   placeholder="e.g. School Auditorium, Sports Field, Assembly Hall"/>
          </div>

          <div class="section-label">Visibility</div>

          <div class="checkbox-row">
            <input type="checkbox" id="is_published" name="is_published"
                   <?php echo $v['is_published'] ? 'checked' : ''; ?>/>
            <label for="is_published">Published — visible on the public events page</label>
          </div>

          <div class="checkbox-row">
            <input type="checkbox" id="is_featured" name="is_featured"
                   <?php echo $v['is_featured'] ? 'checked' : ''; ?>/>
            <label for="is_featured">Featured — highlighted on the homepage and events page</label>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn-save"><?php echo $isEdit ? 'Save Changes' : 'Create Event'; ?></button>
            <a href="events.php" class="btn-cancel">Cancel</a>
            <?php if ($isEdit): ?>
            <form method="POST" action="events.php" style="display:inline"
                  onsubmit="return confirm('Delete this event permanently?')">
              <input type="hidden" name="event_id" value="<?php echo $editId; ?>"/>
              <input type="hidden" name="action" value="delete"/>
              <button type="submit" style="background:#ffe6e6;color:#cc3333;border:1.5px solid #ffcccc;padding:11px 22px;border-radius:8px;font-size:13.5px;font-weight:700;cursor:pointer;">
                Delete Event
              </button>
            </form>
            <?php endif; ?>
          </div>

        </form>
      </div>

    </div>
  </div>

  <script src="../assets/js/admin.js"></script>

</body>
</html>