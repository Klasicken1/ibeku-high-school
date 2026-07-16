<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS PORTAL CLEARANCE LETTER
   File: public/portal-corps/clearance-letter.php
   Proxies to the admin letter generator with corps auth check
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config/database.php';
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';

$corpsMember = requireCorpsLogin();

$year     = (int) ($_GET['year']  ?? 0);
$month    = (int) ($_GET['month'] ?? 0);
$download = !empty($_GET['download']);

if (!$year || !$month) {
    header('Location: clearance.php');
    exit;
}

/* Verify this member IS cleared for this month */
$pdo   = getDB();
$check = $pdo->prepare(
    'SELECT id FROM corps_clearance
     WHERE corps_member_id = ? AND month = ? AND year = ? AND is_cleared = 1 LIMIT 1'
);
$check->execute([$corpsMember['id'], $month, $year]);
if (!$check->fetchColumn()) {
    header('Location: clearance.php');
    exit;
}

/* Redirect to the letter generator with the corps member's own ID */
$url = '../admin/corps-letter.php'
    . '?id=' . $corpsMember['id']
    . '&year=' . $year
    . '&month=' . $month
    . ($download ? '&download=1' : '');

header('Location: ' . $url);
exit;