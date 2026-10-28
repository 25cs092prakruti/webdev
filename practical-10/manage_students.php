<?php
require_once __DIR__ . '/auth.php';
appRequireRole('admin');
require_once __DIR__ . '/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('The request token is invalid. Refresh the page and try again.');
    }

    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'add') {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $username = strtolower(trim((string)($_POST['username'] ?? '')));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');

            if ($fullName === '' || !preg_match('/\A[a-z0-9_]{3,30}\z/', $username) ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                $error = 'Enter a name, a username with only letters, numbers, or underscores, a valid email, and a password of at least 8 characters.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $statement = $mysqli->prepare(
                    'INSERT INTO user_accounts (full_name, username, email, password_hash, role)
                     VALUES (?, ?, ?, ?, "student")'
                );
                $statement->bind_param('ssss', $fullName, $username, $email, $passwordHash);
                $statement->execute();
                $statement->close();
                $message = 'Student account added successfully.';
            }
        } elseif ($action === 'remove') {
            $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$studentId) {
                $error = 'Select a valid student account.';
            } else {
                $statement = $mysqli->prepare(
                    'DELETE FROM user_accounts WHERE user_id = ? AND role = "student"'
                );
                $statement->bind_param('i', $studentId);
                $statement->execute();
                $message = $statement->affected_rows === 1
                    ? 'Student account removed.'
                    : 'Student account was not found.';
                $statement->close();
            }
        }
    } catch (mysqli_sql_exception $exception) {
        error_log($exception->getMessage());
        $error = $exception->getCode() === 1062
            ? 'That username or email is already in use.'
            : 'The student account could not be changed.';
    }
}

try {
    $result = $mysqli->query(
        'SELECT user_id, full_name, username, email, created_at
         FROM user_accounts WHERE role = "student" ORDER BY full_name'
    );
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load student accounts.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | Student Hub</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="header"><h1>Manage Students</h1><button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button></header>
<nav class="portal-nav" aria-label="Admin navigation">
    <a href="admin_dashboard.php">Admin Dashboard</a><a href="../profile.php">My Profile</a>
    <a href="attendance.php">Attendance</a><a href="timetable.php">Time table</a>
    <a href="approve_leave.php">Approve leave</a>
    <form class="portal-logout" action="logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><button type="submit">Sign out</button>
    </form>
</nav>
<main class="role-dashboard">
    <?php if ($message !== ''): ?><p class="form-message success-message"><?= appEscape($message) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="form-message error-message"><?= appEscape($error) ?></p><?php endif; ?>
    <section class="account-card">
        <h2>Add student</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="add">
            <label for="full_name">Full name</label><input id="full_name" name="full_name" required maxlength="100">
            <label for="username">Username</label>
            <input id="username" name="username" required pattern="[a-zA-Z0-9_]{3,30}" maxlength="30"
                   autocomplete="username" autocapitalize="none"
                   aria-describedby="usernameHint"
                   title="Use 3–30 letters, numbers, or underscores only."
                   placeholder="for example, devangi_bharaya"
                   oninvalid="this.setCustomValidity('Use 3–30 letters, numbers, or underscores only. Do not enter an email address.')"
                   oninput="this.setCustomValidity('')">
            <small id="usernameHint">Use 3–30 letters, numbers, or underscores only. Do not enter an email address.</small>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required maxlength="254"
                   autocomplete="email" placeholder="student@example.com">
            <label for="password">Temporary password</label><input id="password" name="password" type="password" required minlength="8" maxlength="72">
            <button type="submit" class="registration-submit">Add student</button>
        </form>
    </section>
    <section class="account-card admin-users">
        <h2>Remove student</h2>
        <div class="table-responsive"><table>
            <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Registered</th><th>Action</th></tr></thead>
            <tbody><?php foreach ($students as $student): ?><tr>
                <td><?= appEscape($student['full_name']) ?></td><td><?= appEscape($student['username']) ?></td>
                <td><?= appEscape($student['email']) ?></td><td><?= appEscape($student['created_at']) ?></td>
                <td><form method="post">
                    <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="remove"><input type="hidden" name="student_id" value="<?= appEscape($student['user_id']) ?>">
                    <button type="submit">Remove</button>
                </form></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </section>
</main>
<footer><p>&copy; 2026 Student Hub Portal</p></footer><script src="../script.js"></script>
</body>
</html>
