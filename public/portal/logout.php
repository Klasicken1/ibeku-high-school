<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT PORTAL LOGOUT
   File: public/portal/logout.php
   ============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/includes/auth.php';

portalSessionStart();
logoutStudent();

header('Location: login.php');
exit;