<?php
require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    appDestroySession();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expired | Student Hub</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="header"><h1>Student Hub Portal</h1></header>
<main class="registration-main">
    <section class="registration-card">
        <h2>Session expired</h2>
        <p>Your session ended after 15 minutes of inactivity. Sign in again to continue.</p>
        <a class="account-dashboard-link" href="login.php?status=timeout">Return to sign in</a>
    </section>
</main>
<footer><p>&copy; 2026 Student Hub Portal</p></footer>
</body>
</html>
