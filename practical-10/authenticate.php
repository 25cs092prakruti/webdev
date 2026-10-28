<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

if (!appVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
    header('Location: login.php?status=login_required');
    exit;
}

$identifier = strtolower(trim((string)($_POST['identifier'] ?? '')));
$password = (string)($_POST['password'] ?? '');
if ($identifier === '' || $password === '') {
    header('Location: login.php?status=invalid');
    exit;
}

try {
    $statement = $mysqli->prepare(
        'SELECT user_id, full_name, username, email, password_hash, role
         FROM user_accounts
         WHERE username = ? OR email = ?
         LIMIT 1'
    );
    $statement->bind_param('ss', $identifier, $identifier);
    $statement->execute();
    $statement->bind_result($userId, $fullName, $username, $email, $passwordHash, $role);
    $authenticated = $statement->fetch() &&
        password_verify($password, $passwordHash) &&
        in_array($role, ['admin', 'student'], true);
    $statement->close();

    if (!$authenticated) {
        header('Location: login.php?status=invalid');
        exit;
    }

    appEstablishSession($userId, $fullName, $username, $email, $role);

    $lastLogin = $mysqli->prepare(
        'UPDATE user_accounts SET last_login_at = CURRENT_TIMESTAMP WHERE user_id = ?'
    );
    $lastLogin->bind_param('i', $userId);
    $lastLogin->execute();
    $lastLogin->close();

    appRevokePresentedRememberToken($mysqli);
    if (isset($_POST['remember_me']) && $_POST['remember_me'] === '1') {
        appStoreRememberToken($mysqli, $userId);
    }

    header('Location: ' . appDashboardPath($role));
    exit;
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Login could not be completed because of a database error.');
}
