<?php
require_once __DIR__ . '/db.php';

try {
    $department = 'CSE';

    $studentStmt = $pdo->prepare(
        'SELECT student_id, full_name, email, department
         FROM students
         WHERE department = :department
         ORDER BY student_id'
    );
    $studentStmt->execute(['department' => $department]);
    $students = $studentStmt->fetchAll();

    $eventStmt = $pdo->prepare(
        'SELECT event_id, event_name, event_date, venue
         FROM events
         ORDER BY event_id'
    );
    $eventStmt->execute();
    $events = $eventStmt->fetchAll();

    $registrationStmt = $pdo->prepare(
        'SELECT r.registration_id, s.full_name, e.event_name,
                r.registered_at
         FROM registrations r
         INNER JOIN students s ON r.student_id = s.student_id
         INNER JOIN events e ON r.event_id = e.event_id
         ORDER BY r.registration_id'
    );
    $registrationStmt->execute();
    $registrations = $registrationStmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Unable to fetch database records. Check the server error log.');
}

function escapeHtml($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Database Test</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f5fa;
            margin: 0;
            padding: 25px;
            color: #243047;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        h1 {
            color: #174ea6;
        }

        h2 {
            margin-top: 30px;
            color: #263b60;
        }

        .success {
            background: #d9fbe5;
            color: #166534;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
        }

        .table-box {
            background: white;
            padding: 15px;
            margin-top: 12px;
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 2px 8px #00000010;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            padding: 11px;
            text-align: left;
            border: 1px solid #dce3ed;
        }

        th {
            background: #174ea6;
            color: white;
        }

        tr:nth-child(even) {
            background: #f5f8fc;
        }

        .count {
            color: #52637c;
            margin-top: 8px;
        }
    </style>
</head>

<body>
<div class="container">

    <h1>StudentHub Database Connection</h1>

    <p class="success">
        ✓ Database connection successful!
    </p>

    <p>Database: studenthub_db</p>

    <h2>1. Students (CSE Department)</h2>
    <div class="table-box">
        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Department</th>
            </tr>

            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= escapeHtml($student['student_id']) ?></td>
                    <td><?= escapeHtml($student['full_name']) ?></td>
                    <td><?= escapeHtml($student['email']) ?></td>
                    <td><?= escapeHtml($student['department']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="count">
            Total records: <?= count($students) ?>
        </p>
    </div>

    <h2>2. Events</h2>
    <div class="table-box">
        <table>
            <tr>
                <th>ID</th>
                <th>Event Name</th>
                <th>Date</th>
                <th>Venue</th>
            </tr>

            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= escapeHtml($event['event_id']) ?></td>
                    <td><?= escapeHtml($event['event_name']) ?></td>
                    <td><?= escapeHtml($event['event_date']) ?></td>
                    <td><?= escapeHtml($event['venue']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="count">
            Total records: <?= count($events) ?>
        </p>
    </div>

    <h2>3. Event Registrations</h2>
    <div class="table-box">
        <table>
            <tr>
                <th>Registration ID</th>
                <th>Student</th>
                <th>Event</th>
                <th>Registered At</th>
            </tr>

            <?php foreach ($registrations as $registration): ?>
                <tr>
                    <td><?= escapeHtml($registration['registration_id']) ?></td>
                    <td><?= escapeHtml($registration['full_name']) ?></td>
                    <td><?= escapeHtml($registration['event_name']) ?></td>
                    <td><?= escapeHtml($registration['registered_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="count">
            Total records: <?= count($registrations) ?>
        </p>
    </div>

</div>
</body>
</html>