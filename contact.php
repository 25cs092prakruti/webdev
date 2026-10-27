<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contactus.html");
    exit;
}

$name = trim($_POST["name"] ?? "");
$studentId = trim($_POST["student_id"] ?? "");
$email = trim($_POST["email"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$message = trim($_POST["message"] ?? "");

$valid =
    strlen($name) >= 2 &&
    preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $studentId) &&
    filter_var($email, FILTER_VALIDATE_EMAIL) &&
    strlen($subject) >= 3 &&
    strlen($message) >= 10;

if (!$valid) {
    header("Location: contactus.html?status=error");
    exit;
}

$storageDir = __DIR__ . "/storage";
$csvFile = $storageDir . "/contact-submissions.csv";

if (!is_dir($storageDir)) {
    if (!mkdir($storageDir, 0755, true)) {
        http_response_code(500);
        exit("Could not create the storage directory.");
    }
}

$file = fopen($csvFile, "a+");

if ($file === false) {
    http_response_code(500);
    exit("Could not open the CSV file.");
}

if (flock($file, LOCK_EX)) {
    // Add column headings when the file is empty.
    fseek($file, 0, SEEK_END);
    $isEmpty = ftell($file) === 0;

    if ($isEmpty) {
        fputcsv($file, [
            "Name",
            "Student ID",
            "Email",
            "Subject",
            "Message",
            "Submitted At"
        ]);
    }

    // Reduce the risk of spreadsheet formula injection.
    $safeValues = [$name, $studentId, $email, $subject, $message];

    foreach ($safeValues as &$value) {
        if (preg_match('/^[\s]*[=+\-@\t\r]/', $value)) {
            $value = "'" . $value;
        }
    }
    unset($value);

    $safeValues[] = date("Y-m-d H:i:s");

    $saved = fputcsv($file, $safeValues);
    fflush($file);
    flock($file, LOCK_UN);
} else {
    $saved = false;
}

fclose($file);

if ($saved !== false) {
    header("Location: contactus.html?status=success");
    exit;
}

http_response_code(500);
echo "Unable to save your inquiry. Please try again.";
?>
