<?php
require_once __DIR__ . '/auth.php';
appRequireRole('admin');
require_once __DIR__ . '/db.php';

try {
    $result = $mysqli->query(
        'SELECT user_id, full_name, username, email, role, last_login_at
         FROM user_accounts
         ORDER BY user_id'
    );
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load the user list. Please try again later.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Student Hub</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="header">
    <h1>Admin Dashboard</h1>
    <button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button>
</header>
<nav class="portal-nav" aria-label="Admin navigation">
    <a href="admin_dashboard.php" aria-current="page">Admin Dashboard</a>
    <a href="../profile.php">My Profile</a>
    <form class="portal-logout" action="logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
        <button type="submit">Sign out</button>
    </form>
</nav>
<main class="role-dashboard">
    <section class="role-welcome">
        <p class="registration-eyebrow">ADMINISTRATOR ACCOUNT</p>
        <h2>Welcome, <?= appEscape($_SESSION['full_name']) ?></h2>
        <p>This protected control panel is available only to administrator accounts.</p>
    </section>

    <section class="dashboard" aria-label="Administration tools">
        <div class="dashboard-box">
            <h3>Add or remove students</h3>
            <p>Create student credentials or remove a student account.</p>
            <a href="manage_students.php">Manage students</a>
        </div>
        <div class="dashboard-box">
            <h3>Practical 11 CRUD</h3>
            <p>Search, filter, edit, soft-delete, and export student records.</p>
            <a href="../practical-11/student_management.php">Open student management</a>
        </div>
        <div class="dashboard-box">
            <h3>Attendance</h3>
            <p>Record and review daily student attendance.</p>
            <a href="attendance.php">Manage attendance</a>
        </div>
        <div class="dashboard-box">
            <h3>Time table</h3>
            <p>Create and remove the student timetable.</p>
            <a href="timetable.php">Manage timetable</a>
        </div>
        <div class="dashboard-box">
            <h3>Approve leave</h3>
            <p>Review and approve or reject student leave requests.</p>
            <a href="approve_leave.php">Review leave</a>
        </div>
    </section>

    <section class="account-card admin-users" aria-labelledby="usersHeading">
        <h2 id="usersHeading">Registered accounts</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Account ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Last login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= appEscape($user['user_id']) ?></td>
                            <td><?= appEscape($user['full_name']) ?></td>
                            <td><?= appEscape($user['username']) ?></td>
                            <td><?= appEscape($user['email']) ?></td>
                            <td><?= appEscape($user['role']) ?></td>
                            <td><?= appEscape($user['last_login_at'] ?? 'Never') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<footer><p>&copy; 2026 Student Hub Portal</p></footer>
<script src="../script.js"></script>
</body>
</html>
