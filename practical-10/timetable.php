<?php
require_once __DIR__ . '/auth.php';
appRequireRole('admin');
require_once __DIR__ . '/db.php';

$message = '';
$error = '';
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('The request token is invalid. Refresh the page and try again.');
    }
    try {
        if ($_POST['action'] === 'add') {
            $day = (string)($_POST['day_of_week'] ?? '');
            $subject = trim((string)($_POST['subject'] ?? ''));
            $start = (string)($_POST['start_time'] ?? '');
            $end = (string)($_POST['end_time'] ?? '');
            $room = trim((string)($_POST['room'] ?? ''));
            if (!in_array($day, $days, true) || $subject === '' || $room === '' ||
                !preg_match('/\A\d{2}:\d{2}\z/', $start) || !preg_match('/\A\d{2}:\d{2}\z/', $end) ||
                $start >= $end) {
                $error = 'Enter a subject, room, valid times, and a valid day.';
            } else {
                $statement = $mysqli->prepare(
                    'INSERT INTO p10_timetable (day_of_week, subject, start_time, end_time, room)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $statement->bind_param('sssss', $day, $subject, $start, $end, $room);
                $statement->execute();
                $statement->close();
                $message = 'Timetable entry added.';
            }
        } elseif ($_POST['action'] === 'remove') {
            $entryId = filter_var($_POST['timetable_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$entryId) {
                $error = 'Select a valid timetable entry.';
            } else {
                $statement = $mysqli->prepare('DELETE FROM p10_timetable WHERE timetable_id = ?');
                $statement->bind_param('i', $entryId);
                $statement->execute();
                $statement->close();
                $message = 'Timetable entry removed.';
            }
        }
    } catch (mysqli_sql_exception $exception) {
        error_log($exception->getMessage());
        $error = 'The timetable could not be changed.';
    }
}

try {
    $result = $mysqli->query(
        'SELECT timetable_id, day_of_week, subject, start_time, end_time, room
         FROM p10_timetable
         ORDER BY FIELD(day_of_week, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"), start_time'
    );
    $entries = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load the timetable.');
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Time table | Student Hub</title><link rel="stylesheet" href="../style.css"></head>
<body><header class="header"><h1>Time table</h1><button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button></header>
<nav class="portal-nav"><a href="admin_dashboard.php">Admin Dashboard</a><a href="manage_students.php">Students</a><a href="attendance.php">Attendance</a><a href="../profile.php">My Profile</a><a href="approve_leave.php">Approve leave</a>
<form class="portal-logout" action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><button type="submit">Sign out</button></form></nav>
<main class="role-dashboard">
<?php if ($message !== ''): ?><p class="form-message success-message"><?= appEscape($message) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="form-message error-message"><?= appEscape($error) ?></p><?php endif; ?>
<section class="account-card"><h2>Add class</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="add">
<label for="day_of_week">Day</label><select id="day_of_week" name="day_of_week" required><?php foreach ($days as $day): ?><option><?= appEscape($day) ?></option><?php endforeach; ?></select>
<label for="subject">Subject</label><input id="subject" name="subject" required maxlength="100"><label for="start_time">Start time</label><input id="start_time" name="start_time" type="time" required>
<label for="end_time">End time</label><input id="end_time" name="end_time" type="time" required><label for="room">Room</label><input id="room" name="room" required maxlength="50">
<button type="submit" class="registration-submit">Add to timetable</button></form></section>
<section class="account-card admin-users"><h2>Current timetable</h2><div class="table-responsive"><table><thead><tr><th>Day</th><th>Subject</th><th>Time</th><th>Room</th><th>Action</th></tr></thead><tbody>
<?php foreach ($entries as $entry): ?><tr><td><?= appEscape($entry['day_of_week']) ?></td><td><?= appEscape($entry['subject']) ?></td><td><?= appEscape(substr($entry['start_time'], 0, 5) . ' - ' . substr($entry['end_time'], 0, 5)) ?></td><td><?= appEscape($entry['room']) ?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="timetable_id" value="<?= appEscape($entry['timetable_id']) ?>"><button type="submit">Remove</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section></main><footer><p>&copy; 2026 Student Hub Portal</p></footer><script src="../script.js"></script></body></html>
