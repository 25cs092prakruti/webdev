<?php
require_once __DIR__ . '/auth.php';
appRequireRole('student', 'login.php', 'timeout.php');
require_once __DIR__ . '/db.php';

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('The request token is invalid. Refresh the page and try again.');
    }
    $type = trim((string)($_POST['leave_type'] ?? ''));
    $start = (string)($_POST['start_date'] ?? '');
    $end = (string)($_POST['end_date'] ?? '');
    $reason = trim((string)($_POST['reason'] ?? ''));
    $startDate = DateTime::createFromFormat('Y-m-d', $start);
    $endDate = DateTime::createFromFormat('Y-m-d', $end);
    if (!in_array($type, ['Home Leave', 'Medical Leave', 'Other'], true) ||
        !$startDate || !$endDate || $startDate->format('Y-m-d') !== $start ||
        $endDate->format('Y-m-d') !== $end || $start > $end || $reason === '') {
        $error = 'Choose a leave type, valid dates, and enter a reason.';
    } else {
        try {
            $studentId = (int)$_SESSION['user_id'];
            $statement = $mysqli->prepare(
                'INSERT INTO p10_leave_requests
                 (student_id, leave_type, start_date, end_date, reason)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $statement->bind_param('issss', $studentId, $type, $start, $end, $reason);
            $statement->execute();
            $statement->close();
            $message = 'Leave request submitted for administrator approval.';
        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $error = 'The leave request could not be submitted.';
        }
    }
}

$statement = $mysqli->prepare(
    'SELECT leave_type, start_date, end_date, reason, status
     FROM p10_leave_requests WHERE student_id = ? ORDER BY created_at DESC'
);
$studentId = (int)$_SESSION['user_id'];
$statement->bind_param('i', $studentId);
$statement->execute();
$result = $statement->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$statement->close();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave | Student Hub</title><link rel="stylesheet" href="../style.css"></head>
<body><header class="header"><h1>Leave application</h1><button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button></header>
<nav class="portal-nav"><a href="student_dashboard.php">Dashboard</a><a href="../profile.php">My Profile</a><a href="../courses.html">Courses</a><a href="leave.php" aria-current="page">Leave</a>
<form class="portal-logout" action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><button type="submit">Sign out</button></form></nav>
<main class="role-dashboard"><?php if ($message !== ''): ?><p class="form-message success-message"><?= appEscape($message) ?></p><?php endif; ?><?php if ($error !== ''): ?><p class="form-message error-message"><?= appEscape($error) ?></p><?php endif; ?>
<section class="account-card"><h2>Apply for leave</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
<label for="leave_type">Type</label><select id="leave_type" name="leave_type" required><option>Home Leave</option><option>Medical Leave</option><option>Other</option></select>
<label for="start_date">From</label><input id="start_date" name="start_date" type="date" required><label for="end_date">To</label><input id="end_date" name="end_date" type="date" required>
<label for="reason">Reason</label><textarea id="reason" name="reason" maxlength="1000" required></textarea><button type="submit" class="registration-submit">Submit leave</button></form></section>
<section class="account-card admin-users"><h2>My requests</h2><div class="table-responsive"><table><thead><tr><th>Type</th><th>Dates</th><th>Reason</th><th>Status</th></tr></thead><tbody>
<?php foreach ($requests as $request): ?><tr><td><?= appEscape($request['leave_type']) ?></td><td><?= appEscape($request['start_date'] . ' to ' . $request['end_date']) ?></td><td><?= appEscape($request['reason']) ?></td><td><?= appEscape(ucfirst($request['status'])) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section></main><footer><p>&copy; 2026 Student Hub Portal</p></footer><script src="../script.js"></script></body></html>
