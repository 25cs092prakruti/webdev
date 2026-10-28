<?php
require_once __DIR__ . '/practical-10/auth.php';
appRequireLogin('practical-10/login.php', 'practical-10/timeout.php');

require_once __DIR__ . '/practical-9/db.php';

try {
    $statement = $mysqli->prepare(
        'SELECT user_id, full_name, username, email, created_at
         FROM user_accounts
         WHERE user_id = ?
         LIMIT 1'
    );
    $statement->bind_param('i', $_SESSION['user_id']);
    $statement->execute();
    $statement->bind_result($userId, $fullName, $username, $email, $createdAt);

    if (!$statement->fetch()) {
        $statement->close();
        appDestroySession();
        header('Location: practical-10/login.php?status=login_required');
        exit;
    }

    $statement->close();
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load your profile. Please try again later.');
}

function escapeHtml($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Student Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="header">
    <h1>My Student Profile</h1>
    <button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button>
</header>

<nav class="portal-nav" aria-label="Main navigation">
    <a href="practical-10/dashboard.php">Dashboard</a>
    <a href="profile.php" aria-current="page">My Profile</a>
    <a href="courses.html">Courses</a>
    <a href="result.html">Results</a>
    <a href="fees.html">Fees</a>
    <a href="certificates.html">Certificates</a>
    <a href="leave.html">Leave</a>
    <a href="notifications.html">Notifications</a>
    <a href="contactus.html">Contact</a>
    <form class="portal-logout" action="practical-10/logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
        <button type="submit">Sign out</button>
    </form>
</nav>

<main class="account-main">
    <section class="account-card" aria-labelledby="accountHeading">
        <div class="account-avatar" aria-hidden="true">
            <?= escapeHtml(mb_strtoupper(mb_substr($fullName, 0, 1, 'UTF-8'), 'UTF-8')) ?>
        </div>
        <p class="account-eyebrow">SIGNED-IN ACCOUNT</p>
        <h2 id="accountHeading"><?= escapeHtml($fullName) ?></h2>
        <p class="account-description">Your profile details associated with this Student Hub account.</p>

        <dl class="account-details">
            <div>
                <dt>Full name</dt>
                <dd><?= escapeHtml($fullName) ?></dd>
            </div>
            <div>
                <dt>Username</dt>
                <dd><?= escapeHtml($username) ?></dd>
            </div>
            <div>
                <dt>Email address</dt>
                <dd><?= escapeHtml($email) ?></dd>
            </div>
            <div>
                <dt>Role</dt>
                <dd><?= escapeHtml(ucfirst($_SESSION['role'])) ?></dd>
            </div>
            <div>
                <dt>Account ID</dt>
                <dd><?= escapeHtml($userId) ?></dd>
            </div>
            <div>
                <dt>Registered</dt>
                <dd><?= escapeHtml(date('F j, Y', strtotime($createdAt))) ?></dd>
            </div>
        </dl>

        <a class="account-dashboard-link" href="practical-10/dashboard.php">Back to dashboard</a>
    </section>
</main>

<footer><p>&copy; 2026 Student Hub Portal</p></footer>
<script src="script.js"></script>
</body>
</html>
