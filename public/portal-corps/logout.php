<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/includes/corps-auth.php';
corpsSessionStart();
logoutCorpsMember();
header('Location: login.php');
exit;