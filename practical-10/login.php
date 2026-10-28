<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . appDashboardPath($_SESSION['role'] ?? 'student'));
    exit;
}

$status = (string)($_GET['status'] ?? '');
$errorMessage = '';
if ($status === 'invalid') {
    $errorMessage = 'Invalid email/username or password.';
} elseif ($status === 'timeout') {
    $errorMessage = 'Your session expired due to inactivity. Please sign in again.';
} elseif ($status === 'signed_out') {
    $errorMessage = 'You have been signed out successfully.';
} elseif ($status === 'login_required') {
    $errorMessage = 'Please sign in to continue.';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (appRestoreRememberedSession($mysqli)) {
            header('Location: ' . appDashboardPath($_SESSION['role']));
            exit;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log($exception->getMessage());
        http_response_code(500);
        exit('Unable to verify your remember-me token. Please try again later.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Student Hub</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="header">
    <h1>Student Hub Portal</h1>
    <button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button>
</header>
<nav class="portal-nav" aria-label="Main navigation">
    <a href="login.php" aria-current="page">Login</a>
    <a href="../practical-9/register.php">Register</a>
</nav>
<main class="registration-main">
    <section class="registration-card" aria-labelledby="loginTitle">
        <p class="registration-eyebrow">SECURE ACCOUNT ACCESS</p>
        <h2 id="loginTitle">Sign in</h2>
        <p class="registration-intro">Use your registered email address or username.</p>

        <?php if ($errorMessage !== ''): ?>
            <p class="form-message <?= $status === 'signed_out' ? 'success-message' : 'error-message' ?>"
               role="alert"><?= appEscape($errorMessage) ?></p>
        <?php endif; ?>

        <form action="authenticate.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">

            <label for="identifier">Email address or username</label>
            <input type="text" id="identifier" name="identifier"
                   autocomplete="username" required maxlength="254">

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required>

            <label class="remember-label">
                <input type="checkbox" name="remember_me" value="1">
                Keep me signed in on this device for 30 days
            </label>

            <button type="submit" class="registration-submit">Sign in</button>
        </form>

        <p class="registration-signin">
            New to Student Hub? <a href="../practical-9/register.php">Create an account</a>.
        </p>
    </section>
</main>
<footer><p>&copy; 2026 Student Hub Portal</p></footer>
<script src="../script.js"></script>
</body>
</html>
