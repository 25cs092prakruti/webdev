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
    $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
    $date = (string)($_POST['attendance_date'] ?? '');
    $status = (string)($_POST['status'] ?? '');
    $dateValid = DateTime::createFromFormat('Y-m-d', $date);
    if (!$studentId || !$dateValid || $dateValid->format('Y-m-d') !== $date ||
        !in_array($status, ['present', 'absent'], true)) {
        $error = 'Choose a student, valid date, and attendance status.';
    } else {
        try {
            $adminId = (int)$_SESSION['user_id'];
            $statement = $mysqli->prepare(
                'INSERT INTO p10_attendance (student_id, attendance_date, status, marked_by)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)'
            );
            $statement->bind_param('issi', $studentId, $date, $status, $adminId);
            $statement->execute();
            $statement->close();
            $message = 'Attendance saved.';
        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $error = 'Attendance could not be saved.';
        }
    }
}

try {
    $studentsResult = $mysqli->query(
        'SELECT user_id, full_name, username FROM user_accounts WHERE role = "student" ORDER BY full_name'
    );
    $students = $studentsResult->fetch_all(MYSQLI_ASSOC);
    $studentsResult->free();
    $recordsResult = $mysqli->query(
        'SELECT a.attendance_date, a.status, u.full_name
         FROM p10_attendance a INNER JOIN user_accounts u ON u.user_id = a.student_id
         ORDER BY a.attendance_date DESC, u.full_name LIMIT 100'
    );
    $records = $recordsResult->fetch_all(MYSQLI_ASSOC);
    $recordsResult->free();
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load attendance.');
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance | Student Hub</title><link rel="stylesheet" href="../style.css"></head>
<body><header class="header"><h1>Attendance</h1><button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button></header>
<nav class="portal-nav"><a href="admin_dashboard.php">Admin Dashboard</a><a href="manage_students.php">Students</a><a href="../profile.php">My Profile</a><a href="timetable.php">Time table</a><a href="approve_leave.php">Approve leave</a>
<form class="portal-logout" action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><button type="submit">Sign out</button></form></nav>
<main class="role-dashboard">
<?php if ($message !== ''): ?><p class="form-message success-message"><?= appEscape($message) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="form-message error-message"><?= appEscape($error) ?></p><?php endif; ?>
<section class="account-card"><h2>Mark attendance</h2><form method="post">
<input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
<label for="student_id">Student</label><select id="student_id" name="student_id" required><option value="">Select student</option>
<?php foreach ($students as $student): ?><option value="<?= appEscape($student['user_id']) ?>"><?= appEscape($student['full_name'] . ' (' . $student['username'] . ')') ?></option><?php endforeach; ?></select>
<label for="attendance_date">Date</label><input id="attendance_date" name="attendance_date" type="date" value="<?= appEscape(date('Y-m-d')) ?>" required>
<label for="status">Status</label><select id="status" name="status" required><option value="present">Present</option><option value="absent">Absent</option></select>
<button type="submit" class="registration-submit">Save attendance</button></form></section>
<section class="account-card admin-users"><h2>Recent attendance</h2><div class="table-responsive"><table>
<thead><tr><th>Date</th><th>Student</th><th>Status</th></tr></thead><tbody>
<?php foreach ($records as $record): ?><tr><td><?= appEscape($record['attendance_date']) ?></td><td><?= appEscape($record['full_name']) ?></td><td><?= appEscape(ucfirst($record['status'])) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section></main><footer><p>&copy; 2026 Student Hub Portal</p></footer><script src="../script.js"></script></body></html>
