<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();

if (empty($_SESSION['registration_csrf'])) {
    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$old = [
    'full_name' => '',
    'username' => '',
    'email' => '',
];

if (isset($_SESSION['registration_flash'])) {
    $successMessage = $_SESSION['registration_flash'];
    unset($_SESSION['registration_flash']);
} else {
    $successMessage = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $value) {
        $old[$field] = trim((string)($_POST[$field] ?? ''));
    }

    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    $passwordLength = preg_match_all('/./us', $password, $passwordCharacters);

    if (!hash_equals($_SESSION['registration_csrf'], $csrfToken)) {
        $errors[] = 'Your form session expired. Refresh the page and submit again.';
    }

    if ($old['full_name'] === '' || $old['username'] === '' || $old['email'] === '' ||
        $password === '' || $confirmPassword === '') {
        $errors[] = 'Please complete every field.';
    }

    if ($old['full_name'] !== '' &&
        !preg_match('/\A[\p{L}\p{M}][\p{L}\p{M} .\'-]{1,99}\z/u', $old['full_name'])) {
        $errors[] = 'Enter a name between 2 and 100 characters using letters, spaces, apostrophes, or hyphens.';
    }

    $old['username'] = strtolower($old['username']);
    if ($old['username'] !== '' &&
        !preg_match('/\A[a-z0-9_]{3,30}\z/', $old['username'])) {
        $errors[] = 'Username must be 3–30 characters and contain only letters, numbers, or underscores.';
    }

    $old['email'] = strtolower($old['email']);
    if ($old['email'] !== '' &&
        (strlen($old['email']) > 254 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL))) {
        $errors[] = 'Enter a valid email address (maximum 254 characters).';
    }

    if ($password !== '' &&
        ($passwordLength === false || $passwordLength < 8 || strlen($password) > 72)) {
        $errors[] = 'Password must be at least 8 characters and no more than 72 bytes.';
    }

    if ($password !== '' && $confirmPassword !== '' && !hash_equals($password, $confirmPassword)) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        require_once __DIR__ . '/db.php';

        try {
            $duplicate = $mysqli->prepare(
                'SELECT username, email
                 FROM user_accounts
                 WHERE username = ? OR email = ?
                 LIMIT 1'
            );
            $duplicate->bind_param('ss', $old['username'], $old['email']);
            $duplicate->execute();
            $duplicate->bind_result($existingUsername, $existingEmail);

            if ($duplicate->fetch()) {
                if ($existingUsername === $old['username'] && $existingEmail === $old['email']) {
                    $errors[] = 'That username and email are already registered.';
                } elseif ($existingUsername === $old['username']) {
                    $errors[] = 'That username is already registered.';
                } else {
                    $errors[] = 'That email address is already registered.';
                }
                $duplicate->close();
            } else {
                $duplicate->close();
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $mysqli->begin_transaction();

                try {
                    $insert = $mysqli->prepare(
                        'INSERT INTO user_accounts (full_name, username, email, password_hash)
                         VALUES (?, ?, ?, ?)'
                    );
                    $insert->bind_param(
                        'ssss',
                        $old['full_name'],
                        $old['username'],
                        $old['email'],
                        $passwordHash
                    );
                    $insert->execute();
                    $accountId = $mysqli->insert_id;
                    $insert->close();

                    $audit = $mysqli->prepare(
                        'INSERT INTO registration_audit (user_id, event_type)
                         VALUES (?, ?)'
                    );
                    $eventType = 'user_registered';
                    $audit->bind_param('is', $accountId, $eventType);
                    $audit->execute();
                    $audit->close();

                    $mysqli->commit();
                    session_regenerate_id(true);
                    $_SESSION['registration_flash'] = 'Registration successful. Your account was created.';
                    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
                    header('Location: register.php');
                    exit;
                } catch (mysqli_sql_exception $exception) {
                    $mysqli->rollback();
                    if ($exception->getCode() === 1062) {
                        $errors[] = 'That username or email is already registered.';
                    } else {
                        error_log($exception->getMessage());
                        $errors[] = 'Registration could not be completed. Please try again later.';
                    }
                }
            }
        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            http_response_code(500);
            $errors[] = 'Registration could not be completed because of a database error.';
        }
    }
}

function escapeHtml($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Student Hub</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="header">
    <h1>Student Hub Portal</h1>
    <button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button>
</header>

<nav class="portal-nav" aria-label="Main navigation">
    <a href="../practical-10/dashboard.php">Dashboard</a>
    <a href="../profile.php">Profile</a>
    <a href="../courses.html">Courses</a>
    <a href="../result.html">Results</a>
    <a href="../fees.html">Fees</a>
    <a href="../certificates.html">Certificates</a>
    <a href="../leave.html">Leave</a>
    <a href="../notifications.html">Notifications</a>
    <a href="../contactus.html">Contact</a>
    <a href="../practical-10/login.php">Login</a>
    <a href="register.php" aria-current="page">Register</a>
</nav>

<main class="registration-main">
    <section class="registration-card" aria-labelledby="registrationTitle">
        <p class="registration-eyebrow">STUDENT HUB ACCOUNT</p>
        <h2 id="registrationTitle">Create your account</h2>
        <p class="registration-intro">Register to get started with the Student Hub Portal.</p>

        <?php if ($successMessage !== ''): ?>
            <p class="form-message success-message" role="status"><?= escapeHtml($successMessage) ?></p>
            <p class="registration-signin">Already registered? <a href="../practical-10/login.php">Go to login</a>.</p>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="form-message error-message" role="alert">
                <p>Please review the following:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escapeHtml($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" id="registrationForm">
            <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['registration_csrf']) ?>">

            <label for="full_name">Full name</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                value="<?= escapeHtml($old['full_name']) ?>"
                minlength="2"
                maxlength="100"
                autocomplete="name"
                required
            >

            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?= escapeHtml($old['username']) ?>"
                pattern="[A-Za-z0-9_]{3,30}"
                minlength="3"
                maxlength="30"
                autocomplete="username"
                aria-describedby="usernameHint"
                required
            >
            <small id="usernameHint">3–30 letters, numbers, or underscores.</small>

            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= escapeHtml($old['email']) ?>"
                maxlength="254"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                maxlength="72"
                autocomplete="new-password"
                aria-describedby="passwordHint"
                required
            >
            <small id="passwordHint">Use at least 8 characters and no more than 72 bytes.</small>

            <label for="confirm_password">Confirm password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                maxlength="72"
                autocomplete="new-password"
                required
            >

            <button type="submit" class="registration-submit">Create account</button>
        </form>

        <p class="registration-signin">Already have an account? <a href="../practical-10/login.php">Sign in</a>.</p>
    </section>
</main>

<footer><p>&copy; 2026 Student Hub Portal</p></footer>
<script src="../script.js"></script>
<script>
const registrationForm = document.getElementById("registrationForm");
const passwordInput = document.getElementById("password");
const confirmPasswordInput = document.getElementById("confirm_password");
const fullNameInput = document.getElementById("full_name");

function validateRegistrationForm() {
    const namePattern = /^[\p{L}\p{M}][\p{L}\p{M} .'-]{1,99}$/u;
    const passwordBytes = new TextEncoder().encode(passwordInput.value).length;
    const passwordCharacters = Array.from(passwordInput.value).length;

    fullNameInput.setCustomValidity(
        namePattern.test(fullNameInput.value.trim())
            ? ""
            : "Enter a name between 2 and 100 letters, spaces, apostrophes, or hyphens."
    );
    passwordInput.setCustomValidity(
        passwordCharacters >= 8 && passwordBytes <= 72
            ? ""
            : "Password must be at least 8 characters and no more than 72 bytes."
    );
    confirmPasswordInput.setCustomValidity(
        confirmPasswordInput.value === passwordInput.value
            ? ""
            : "Passwords do not match."
    );
}

for (const input of [fullNameInput, passwordInput, confirmPasswordInput]) {
    input.addEventListener("input", validateRegistrationForm);
}
registrationForm.addEventListener("submit", function (event) {
    validateRegistrationForm();
    if (!registrationForm.checkValidity()) {
        event.preventDefault();
        registrationForm.reportValidity();
    }
});
</script>
</body>
</html>
