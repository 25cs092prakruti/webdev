<?php
require_once __DIR__ . '/auth.php';
appRequireRole('student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Student Hub</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="header">
    <h1>Welcome, <?= appEscape($_SESSION['full_name']) ?></h1>
    <button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button>
</header>
<nav class="portal-nav" aria-label="Student navigation">
    <a href="../studentdashboard.php" aria-current="page">Dashboard</a>
    <a href="../profile.php">My Profile</a>
    <a href="../courses.html">Courses</a>
    <a href="../result.html">Results</a>
    <a href="../fees.html">Fees</a>
    <a href="../certificates.html">Certificates</a>
    <a href="../leave.html">Leave</a>
    <a href="../notifications.html">Notifications</a>
    <a href="../contactus.html">Contact</a>
    <form class="portal-logout" action="logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
        <button type="submit">Sign out</button>
    </form>
</nav>
<main class="role-dashboard">
    <section class="role-welcome">
        <p class="registration-eyebrow">STUDENT ACCOUNT</p>
        <h2>Your Student Hub</h2>
        <p>Signed in as <strong><?= appEscape($_SESSION['username']) ?></strong>
            &middot; <?= appEscape($_SESSION['email']) ?></p>
    </section>
    <div class="dashboard">
        <div class="dashboard-box"><h3>My Profile</h3><p>View your account details</p><a href="../profile.php">Open</a></div>
        <div class="dashboard-box"><h3>Courses</h3><p>Current semester courses</p><a href="../courses.html">Open</a></div>
        <div class="dashboard-box"><h3>Results</h3><p>SGPA and attendance</p><a href="../result.html">Open</a></div>
        <div class="dashboard-box"><h3>Fees</h3><p>Fees and scholarship details</p><a href="../fees.html">Open</a></div>
        <div class="dashboard-box"><h3>Certificates</h3><p>Manage certificates</p><a href="../certificates.html">Open</a></div>
        <div class="dashboard-box"><h3>Leave</h3><p>Apply for leave</p><a href="leave.php">Open</a></div>
        <div class="dashboard-box"><h3>Notifications</h3><p>Latest updates</p><a href="../notifications.html">Open</a></div>
        <div class="dashboard-box"><h3>Contact</h3><p>Contact Student Hub</p><a href="../contactus.html">Open</a></div>
    </div>
</main>
<footer><p>&copy; 2026 Student Hub Portal</p></footer>
<script src="../script.js"></script>
</body>
</html>
