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
    $leaveId = filter_var($_POST['leave_id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string)($_POST['status'] ?? '');
    if (!$leaveId || !in_array($status, ['approved', 'rejected'], true)) {
        $error = 'Choose a valid leave request and decision.';
    } else {
        try {
            $adminId = (int)$_SESSION['user_id'];
            $statement = $mysqli->prepare(
                'UPDATE p10_leave_requests
                 SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
                 WHERE leave_id = ? AND status = "pending"'
            );
            $statement->bind_param('sii', $status, $adminId, $leaveId);
            $statement->execute();
            $message = $statement->affected_rows === 1
                ? 'Leave request updated.'
                : 'That leave request is no longer pending.';
            $statement->close();
        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $error = 'The leave request could not be updated.';
        }
    }
}

try {
    $result = $mysqli->query(
        'SELECT l.leave_id, l.leave_type, l.start_date, l.end_date, l.reason, l.status,
                u.full_name, u.username
         FROM p10_leave_requests l
         INNER JOIN user_accounts u ON u.user_id = l.student_id
         ORDER BY FIELD(l.status, "pending", "approved", "rejected"), l.created_at DESC'
    );
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load leave requests.');
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Approve Leave | Student Hub</title><link rel="stylesheet" href="../style.css"></head>
<body><header class="header"><h1>Approve Leave</h1><button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button></header>
<nav class="portal-nav"><a href="admin_dashboard.php">Admin Dashboard</a><a href="manage_students.php">Students</a><a href="attendance.php">Attendance</a><a href="timetable.php">Time table</a><a href="../profile.php">My Profile</a>
<form class="portal-logout" action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><button type="submit">Sign out</button></form></nav>
<main class="role-dashboard">
<?php if ($message !== ''): ?><p class="form-message success-message"><?= appEscape($message) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="form-message error-message"><?= appEscape($error) ?></p><?php endif; ?>
<section class="account-card admin-users"><h2>Student leave requests</h2><div class="table-responsive"><table>
<thead><tr><th>Student</th><th>Type</th><th>Dates</th><th>Reason</th><th>Status</th><th>Decision</th></tr></thead><tbody>
<?php foreach ($requests as $request): ?><tr><td><?= appEscape($request['full_name'] . ' (' . $request['username'] . ')') ?></td><td><?= appEscape($request['leave_type']) ?></td>
<td><?= appEscape($request['start_date'] . ' to ' . $request['end_date']) ?></td><td><?= appEscape($request['reason']) ?></td><td><?= appEscape(ucfirst($request['status'])) ?></td><td>
<?php if ($request['status'] === 'pending'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><input type="hidden" name="leave_id" value="<?= appEscape($request['leave_id']) ?>"><button name="status" value="approved" type="submit">Approve</button> <button name="status" value="rejected" type="submit">Reject</button></form><?php else: ?>Completed<?php endif; ?>
</td></tr><?php endforeach; ?></tbody></table></div></section></main><footer><p>&copy; 2026 Student Hub Portal</p></footer><script src="../script.js"></script></body></html>
