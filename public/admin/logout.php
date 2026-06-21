<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMIN LOGOUT
   File: public/admin/logout.php
   ============================================================ */

declare(strict_types=1);
session_start();

$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;