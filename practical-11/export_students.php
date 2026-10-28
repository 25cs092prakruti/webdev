<?php
require_once __DIR__ . '/../practical-10/auth.php';
appRequireRole('admin', '../practical-10/login.php', '../practical-10/timeout.php');
require_once __DIR__ . '/../practical-10/db.php';

$search = trim((string)($_GET['search'] ?? ''));
$course = trim((string)($_GET['course'] ?? ''));
$status = (string)($_GET['status'] ?? 'active');
$where = [];
$types = '';
$params = [];

function p11ExportBindParams($statement, $types, &$params)
{
    $references = [];
    foreach ($params as $index => &$param) {
        $references[$index] = &$param;
    }
    $statement->bind_param($types, ...$references);
}

if ($search !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR course LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($course !== '') {
    $where[] = 'course = ?';
    $types .= 's';
    $params[] = $course;
}
if (in_array($status, ['active', 'inactive'], true)) {
    $where[] = 'status = ?';
    $types .= 's';
    $params[] = $status;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

try {
    $statement = $mysqli->prepare(
        'SELECT student_id, full_name, email, course, phone, status, created_at
         FROM p11_students' . $whereSql . ' ORDER BY full_name'
    );
    if ($types !== '') {
        p11ExportBindParams($statement, $types, $params);
    }
    $statement->execute();
    $students = $statement->get_result();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="student_list.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student ID', 'Full name', 'Email', 'Course', 'Phone', 'Status', 'Created']);
    while ($student = $students->fetch_assoc()) {
        fputcsv($output, $student);
    }
    fclose($output);
    $statement->close();
    exit;
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('The CSV export could not be generated.');
}
