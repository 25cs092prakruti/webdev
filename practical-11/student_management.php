<?php
require_once __DIR__ . '/../practical-10/auth.php';
appRequireRole('admin', '../practical-10/login.php', '../practical-10/timeout.php');
require_once __DIR__ . '/../practical-10/db.php';

$message = '';
$error = '';
$editStudent = null;
$courses = [];

function p11BindParams($statement, $types, &$params)
{
    $references = [];
    foreach ($params as $index => &$param) {
        $references[$index] = &$param;
    }
    $statement->bind_param($types, ...$references);
}

function p11Redirect($query = '')
{
    header('Location: student_management.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('The request token is invalid. Refresh the page and try again.');
    }

    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save') {
            $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string)($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $course = trim((string)($_POST['course'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));

            if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                strlen($email) > 254 || $course === '' || strlen($course) > 100 ||
                !preg_match('/\A[0-9+() -]{7,20}\z/', $phone)) {
                $error = 'Enter a valid name, email, course, and phone number.';
            } elseif ($studentId) {
                $statement = $mysqli->prepare(
                    'UPDATE p11_students SET full_name = ?, email = ?, course = ?, phone = ?
                     WHERE student_id = ?'
                );
                $statement->bind_param('ssssi', $name, $email, $course, $phone, $studentId);
                $statement->execute();
                $statement->close();
                p11Redirect('message=updated');
            } else {
                $statement = $mysqli->prepare(
                    'INSERT INTO p11_students (full_name, email, course, phone)
                     VALUES (?, ?, ?, ?)'
                );
                $statement->bind_param('ssss', $name, $email, $course, $phone);
                $statement->execute();
                $statement->close();
                p11Redirect('message=added');
            }
        } elseif ($action === 'delete') {
            $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$studentId) {
                $error = 'Select a valid student record.';
            } else {
                $statement = $mysqli->prepare(
                    'UPDATE p11_students SET status = "inactive" WHERE student_id = ?'
                );
                $statement->bind_param('i', $studentId);
                $statement->execute();
                $statement->close();
                p11Redirect('message=deleted');
            }
        } elseif ($action === 'restore') {
            $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$studentId) {
                $error = 'Select a valid student record.';
            } else {
                $statement = $mysqli->prepare(
                    'UPDATE p11_students SET status = "active" WHERE student_id = ?'
                );
                $statement->bind_param('i', $studentId);
                $statement->execute();
                $statement->close();
                p11Redirect('message=restored');
            }
        }
    } catch (mysqli_sql_exception $exception) {
        error_log($exception->getMessage());
        $error = $exception->getCode() === 1062
            ? 'That email address is already assigned to another student.'
            : 'The student record could not be saved.';
    }
}

$messageMap = [
    'added' => 'Student added successfully.',
    'updated' => 'Student details updated successfully.',
    'deleted' => 'Student soft-deleted successfully. The status is now inactive.',
    'restored' => 'Student record restored successfully.',
];
$message = $messageMap[(string)($_GET['message'] ?? '')] ?? '';
$search = trim((string)($_GET['search'] ?? ''));
$courseFilter = trim((string)($_GET['course'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? 'active');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

try {
    $courseResult = $mysqli->query('SELECT DISTINCT course FROM p11_students ORDER BY course');
    while ($courseRow = $courseResult->fetch_assoc()) {
        $courses[] = $courseRow['course'];
    }
    $courseResult->free();

    if (isset($_GET['edit'])) {
        $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
        if ($editId) {
            $statement = $mysqli->prepare(
                'SELECT student_id, full_name, email, course, phone
                 FROM p11_students WHERE student_id = ? LIMIT 1'
            );
            $statement->bind_param('i', $editId);
            $statement->execute();
            $editStudent = $statement->get_result()->fetch_assoc();
            $statement->close();
        }
    }

    $where = [];
    $types = '';
    $params = [];
    if ($search !== '') {
        $where[] = '(full_name LIKE ? OR email LIKE ? OR course LIKE ?)';
        $searchLike = '%' . $search . '%';
        $types .= 'sss';
        $params[] = $searchLike;
        $params[] = $searchLike;
        $params[] = $searchLike;
    }
    if ($courseFilter !== '') {
        $where[] = 'course = ?';
        $types .= 's';
        $params[] = $courseFilter;
    }
    if (in_array($statusFilter, ['active', 'inactive'], true)) {
        $where[] = 'status = ?';
        $types .= 's';
        $params[] = $statusFilter;
    } else {
        $statusFilter = 'all';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $countStatement = $mysqli->prepare('SELECT COUNT(*) AS total FROM p11_students' . $whereSql);
    if ($types !== '') {
        p11BindParams($countStatement, $types, $params);
    }
    $countStatement->execute();
    $total = (int)$countStatement->get_result()->fetch_assoc()['total'];
    $countStatement->close();

    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $listStatement = $mysqli->prepare(
        'SELECT student_id, full_name, email, course, phone, status
         FROM p11_students' . $whereSql . ' ORDER BY full_name LIMIT ? OFFSET ?'
    );
    $listTypes = $types . 'ii';
    $listParams = $params;
    $listParams[] = $perPage;
    $listParams[] = $offset;
    p11BindParams($listStatement, $listTypes, $listParams);
    $listStatement->execute();
    $students = $listStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $listStatement->close();
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load student records. Import practical-11/schema.sql first.');
}

$queryBase = http_build_query([
    'search' => $search,
    'course' => $courseFilter,
    'status' => $statusFilter,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | Student Hub</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="header"><h1>Student Management</h1><button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button></header>
<nav class="portal-nav" aria-label="Admin navigation">
    <a href="../practical-10/admin_dashboard.php">Admin Dashboard</a>
    <a href="student_management.php" aria-current="page">Student Management</a>
    <a href="../profile.php">My Profile</a>
    <form class="portal-logout" action="../practical-10/logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><button type="submit">Sign out</button>
    </form>
</nav>
<main class="role-dashboard">
    <?php if ($message !== ''): ?><p class="form-message success-message" role="status"><?= appEscape($message) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="form-message error-message" role="alert"><?= appEscape($error) ?></p><?php endif; ?>

    <section class="account-card">
        <h2><?= $editStudent ? 'Edit student details' : 'Add new student' ?></h2>
        <form method="post" action="student_management.php">
            <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="save">
            <?php if ($editStudent): ?><input type="hidden" name="student_id" value="<?= appEscape($editStudent['student_id']) ?>"><?php endif; ?>
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" maxlength="100" required value="<?= appEscape($editStudent['full_name'] ?? '') ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" maxlength="254" required value="<?= appEscape($editStudent['email'] ?? '') ?>">
            <label for="course">Course</label>
            <select id="course" name="course" required>
                <option value="">Choose a course</option>
                <?php foreach ($courses as $courseOption): ?>
                    <option value="<?= appEscape($courseOption) ?>" <?= ($editStudent['course'] ?? '') === $courseOption ? 'selected' : '' ?>>
                        <?= appEscape($courseOption) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" maxlength="20" required value="<?= appEscape($editStudent['phone'] ?? '') ?>">
            <button type="submit" class="registration-submit"><?= $editStudent ? 'Update student' : 'Add student' ?></button>
            <?php if ($editStudent): ?><a class="account-dashboard-link" href="student_management.php">Cancel edit</a><?php endif; ?>
        </form>
    </section>

    <section class="account-card admin-users">
        <h2>Student records</h2>
        <form method="get" class="student-filters">
            <label for="search">Search name, email, or course</label>
            <input id="search" name="search" value="<?= appEscape($search) ?>" placeholder="Search...">
            <label for="course-filter">Course</label>
            <select id="course-filter" name="course"><option value="">All courses</option>
                <?php foreach ($courses as $course): ?><option value="<?= appEscape($course) ?>" <?= $course === $courseFilter ? 'selected' : '' ?>><?= appEscape($course) ?></option><?php endforeach; ?>
            </select>
            <label for="status-filter">Status</label>
            <select id="status-filter" name="status"><option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option><option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option></select>
            <button type="submit">Search / filter</button>
            <a class="account-dashboard-link" href="export_students.php?<?= appEscape($queryBase) ?>">Export CSV</a>
        </form>
        <p>Showing <?= count($students) ?> of <?= $total ?> matching record(s).</p>
        <div class="table-responsive"><table>
            <thead><tr><th>Name</th><th>Email</th><th>Course</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody><?php foreach ($students as $student): ?><tr>
                <td><?= appEscape($student['full_name']) ?></td><td><?= appEscape($student['email']) ?></td><td><?= appEscape($student['course']) ?></td><td><?= appEscape($student['phone']) ?></td><td><?= appEscape(ucfirst($student['status'])) ?></td>
                <td><a href="?<?= appEscape($queryBase) ?>&edit=<?= appEscape($student['student_id']) ?>">Edit</a>
                    <form class="inline-form" method="post" action="student_management.php" onsubmit="return confirm('Are you sure you want to change this student status?');">
                        <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><input type="hidden" name="student_id" value="<?= appEscape($student['student_id']) ?>">
                        <?php if ($student['status'] === 'active'): ?><input type="hidden" name="action" value="delete"><button type="submit">Delete</button><?php else: ?><input type="hidden" name="action" value="restore"><button type="submit">Restore</button><?php endif; ?>
                    </form>
                </td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php if ($totalPages > 1): ?><p class="pagination"><?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?><a href="?<?= appEscape($queryBase) ?>&page=<?= $pageNumber ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a><?php endfor; ?></p><?php endif; ?>
    </section>
</main>
<footer><p>&copy; 2026 Student Hub Portal</p></footer>
<script src="../script.js"></script>
</body>
</html>
