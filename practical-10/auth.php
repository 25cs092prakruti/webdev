<?php
const SESSION_IDLE_TIMEOUT = 900;
const REMEMBER_COOKIE_NAME = 'studenthub_remember';
const REMEMBER_COOKIE_LIFETIME = 2592000;

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
$sessionCookie = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $sessionCookie['path'],
    'domain' => $sessionCookie['domain'],
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function appEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function appDashboardPath($role)
{
    return $role === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php';
}

function appClearRememberCookie()
{
    $cookieParameters = session_get_cookie_params();
    setcookie(REMEMBER_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => $cookieParameters['path'],
        'domain' => $cookieParameters['domain'],
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function appSetRememberCookie($selector, $validator, $expiresAt)
{
    $cookieParameters = session_get_cookie_params();
    setcookie(REMEMBER_COOKIE_NAME, $selector . '.' . $validator, [
        'expires' => $expiresAt,
        'path' => $cookieParameters['path'],
        'domain' => $cookieParameters['domain'],
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function appDestroySession()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookieParameters = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookieParameters['path'],
            'domain' => $cookieParameters['domain'],
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function appEstablishSession($userId, $fullName, $username, $email, $role)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$userId;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function appStoreRememberToken($mysqli, $userId)
{
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expiresAt = time() + REMEMBER_COOKIE_LIFETIME;
    $expiresAtSql = date('Y-m-d H:i:s', $expiresAt);

    $statement = $mysqli->prepare(
        'INSERT INTO p10_remember_tokens (user_id, selector, validator_hash, expires_at)
         VALUES (?, ?, ?, ?)'
    );
    $statement->bind_param('isss', $userId, $selector, $validatorHash, $expiresAtSql);
    $statement->execute();
    $statement->close();

    $_SESSION['remember_selector'] = $selector;
    appSetRememberCookie($selector, $validator, $expiresAt);
}

function appRevokePresentedRememberToken($mysqli)
{
    $cookie = (string)($_COOKIE[REMEMBER_COOKIE_NAME] ?? '');
    if (preg_match('/\A([a-f0-9]{18})\.[a-f0-9]{64}\z/', $cookie, $matches)) {
        $selector = $matches[1];
        $statement = $mysqli->prepare('DELETE FROM p10_remember_tokens WHERE selector = ?');
        $statement->bind_param('s', $selector);
        $statement->execute();
        $statement->close();
    }

    appClearRememberCookie();
}

function appRestoreRememberedSession($mysqli)
{
    $cookie = (string)($_COOKIE[REMEMBER_COOKIE_NAME] ?? '');
    if (!preg_match('/\A([a-f0-9]{18})\.([a-f0-9]{64})\z/', $cookie, $matches)) {
        return false;
    }

    $selector = $matches[1];
    $validator = $matches[2];
    $mysqli->begin_transaction();

    try {
        $statement = $mysqli->prepare(
            'SELECT u.user_id, u.full_name, u.username, u.email, u.role,
                    t.validator_hash, t.expires_at
             FROM p10_remember_tokens t
             INNER JOIN user_accounts u ON u.user_id = t.user_id
             WHERE t.selector = ?
             LIMIT 1
             FOR UPDATE'
        );
        $statement->bind_param('s', $selector);
        $statement->execute();
        $statement->bind_result(
            $userId,
            $fullName,
            $username,
            $email,
            $role,
            $validatorHash,
            $expiresAt
        );

        if (!$statement->fetch()) {
            $statement->close();
            $mysqli->commit();
            appClearRememberCookie();
            return false;
        }
        $statement->close();

        if (strtotime($expiresAt) <= time() ||
            !hash_equals($validatorHash, hash('sha256', $validator)) ||
            !in_array($role, ['admin', 'student'], true)) {
            $delete = $mysqli->prepare('DELETE FROM p10_remember_tokens WHERE selector = ?');
            $delete->bind_param('s', $selector);
            $delete->execute();
            $delete->close();
            $mysqli->commit();
            appClearRememberCookie();
            return false;
        }

        appEstablishSession($userId, $fullName, $username, $email, $role);
        $delete = $mysqli->prepare('DELETE FROM p10_remember_tokens WHERE selector = ?');
        $delete->bind_param('s', $selector);
        $delete->execute();
        $delete->close();
        appStoreRememberToken($mysqli, $userId);

        $update = $mysqli->prepare(
            'UPDATE user_accounts SET last_login_at = CURRENT_TIMESTAMP WHERE user_id = ?'
        );
        $update->bind_param('i', $userId);
        $update->execute();
        $update->close();

        $mysqli->commit();
        return true;
    } catch (mysqli_sql_exception $exception) {
        $mysqli->rollback();
        throw $exception;
    }
}

function appRequireLogin($redirect = 'login.php', $timeoutRedirect = 'timeout.php')
{
    if (empty($_SESSION['user_id']) ||
        !in_array($_SESSION['role'] ?? '', ['admin', 'student'], true)) {
        header('Location: ' . $redirect . '?status=login_required');
        exit;
    }

    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
    if ($lastActivity === 0 || time() - $lastActivity > SESSION_IDLE_TIMEOUT) {
        if (!empty($_SESSION['remember_selector'])) {
            require_once __DIR__ . '/db.php';
            $selector = $_SESSION['remember_selector'];
            $statement = $mysqli->prepare('DELETE FROM p10_remember_tokens WHERE selector = ?');
            $statement->bind_param('s', $selector);
            $statement->execute();
            $statement->close();
        }
        appClearRememberCookie();
        appDestroySession();
        header('Location: ' . $timeoutRedirect);
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function appRequireRole($requiredRole, $redirect = 'login.php', $timeoutRedirect = 'timeout.php')
{
    appRequireLogin($redirect, $timeoutRedirect);

    if ($_SESSION['role'] !== $requiredRole) {
        http_response_code(403);
        $actualRole = appEscape($_SESSION['role']);
        $requiredLabel = appEscape(ucfirst($requiredRole));
        $dashboardPath = appDashboardPath($_SESSION['role']);
        if (strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/practical-10/') === false) {
            $dashboardPath = 'practical-10/' . $dashboardPath;
        }
        $destination = appEscape($dashboardPath);
        $stylesheetPath = strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/practical-10/') === false
            ? 'style.css'
            : '../style.css';
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Unauthorized Access | Student Hub</title>';
        echo '<link rel="stylesheet" href="' . $stylesheetPath . '"></head><body>';
        echo '<main class="registration-main"><section class="registration-card">';
        echo '<h1>Unauthorized Access</h1>';
        echo '<p>This page is for ' . $requiredLabel . ' accounts. You are signed in as ' .
            $actualRole . '.</p>';
        echo '<a href="' . $destination . '">Return to your dashboard</a>';
        echo '</section></main></body></html>';
        exit;
    }
}

function appVerifyCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) &&
        is_string($token) &&
        hash_equals($_SESSION['csrf_token'], $token);
}
