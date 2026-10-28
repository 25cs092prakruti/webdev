<?php
require_once __DIR__ . '/auth.php';
appRequireLogin();

header('Location: ' . appDashboardPath($_SESSION['role']));
exit;