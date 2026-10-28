<?php
require_once __DIR__ . '/practical-10/auth.php';
appRequireRole('student', 'practical-10/login.php', 'practical-10/timeout.php');

header('Location: practical-10/student_dashboard.php');
exit;
