<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = 'localhost';
$dbName = 'studenthub_db';
$dbUser = 'root';
$dbPassword = '';

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Start MySQL in XAMPP and import the Practical 9 and Practical 10 schema files.');
}
