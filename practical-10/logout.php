<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

if (!appVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Unable to sign out: the request token is invalid. Return to your dashboard and try again.');
}

if (!empty($_SESSION['remember_selector'])) {
    $selector = $_SESSION['remember_selector'];
    $statement = $mysqli->prepare('DELETE FROM p10_remember_tokens WHERE selector = ?');
    $statement->bind_param('s', $selector);
    $statement->execute();
    $statement->close();
}

appClearRememberCookie();
appDestroySession();
header('Location: login.php?status=signed_out');
exit;
