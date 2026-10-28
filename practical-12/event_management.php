<?php
require_once __DIR__ . '/../practical-10/auth.php';
appRequireRole('admin', '../practical-10/login.php', '../practical-10/timeout.php');
require_once __DIR__ . '/../practical-10/db.php';

const P12_MAX_POSTER_BYTES = 2097152;
const P12_UPLOAD_DIR = __DIR__ . '/uploads';
const P12_UPLOAD_URL = 'uploads/';

$messageMap = [
    'added' => 'Event added successfully.',
    'updated' => 'Event updated successfully.',
    'deleted' => 'Event deleted successfully.',
];
$message = $messageMap[(string)($_GET['message'] ?? '')] ?? '';
$error = '';
$editEvent = null;
$allowedCategories = ['Workshop', 'Competition', 'Exhibition', 'Seminar', 'Other'];
$allowedStatuses = ['open', 'closed'];

function p12Redirect($query = '')
{
    header('Location: event_management.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

function p12UploadErrorMessage($uploadError)
{
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'The poster exceeds the server upload limit.',
        UPLOAD_ERR_FORM_SIZE => 'The poster exceeds the allowed form size.',
        UPLOAD_ERR_PARTIAL => 'The poster upload was incomplete.',
        UPLOAD_ERR_NO_FILE => 'No poster was selected.',
    ];
    return $messages[$uploadError] ?? 'The poster upload failed.';
}

function p12UploadPoster($file)
{
    if (!isset($file) || !is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(p12UploadErrorMessage((int)$file['error']));
    }
    if ($file['size'] > P12_MAX_POSTER_BYTES) {
        throw new RuntimeException('Poster size must not exceed 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($mimeToExtension[$mime])) {
        throw new RuntimeException('Invalid poster type. Upload JPG, PNG, GIF, or WEBP only.');
    }
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('The uploaded poster is not a valid image.');
    }
    if (!is_dir(P12_UPLOAD_DIR) && !mkdir(P12_UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException('The poster upload directory could not be created.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $mimeToExtension[$mime];
    $destination = P12_UPLOAD_DIR . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The poster could not be saved.');
    }
    return P12_UPLOAD_URL . $filename;
}

function p12DeletePoster($posterPath)
{
    if (!is_string($posterPath) || !preg_match('/\Auploads\/[a-f0-9]{32}\.(jpg|png|gif|webp)\z/', $posterPath)) {
        return;
    }
    $path = __DIR__ . '/' . $posterPath;
    if (is_file($path) && !unlink($path)) {
        error_log('Unable to remove event poster: ' . $path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('The request token is invalid. Refresh the page and try again.');
    }

    $action = (string)($_POST['action'] ?? '');
    $newPosterPath = null;
    try {
        if ($action === 'save') {
            $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string)($_POST['event_name'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $date = (string)($_POST['event_date'] ?? '');
            $venue = trim((string)($_POST['venue'] ?? ''));
            $category = (string)($_POST['category'] ?? '');
            $status = (string)($_POST['status'] ?? '');
            $dateObject = DateTime::createFromFormat('Y-m-d', $date);

            if ($name === '' || strlen($name) > 150 || $description === '' ||
                strlen($description) > 5000 || !$dateObject || $dateObject->format('Y-m-d') !== $date ||
                $venue === '' || strlen($venue) > 150 || !in_array($category, $allowedCategories, true) ||
                !in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Enter valid event details in every field.');
            }

            $newPosterPath = p12UploadPoster($_FILES['poster'] ?? null);
            if ($eventId) {
                $existing = $mysqli->prepare('SELECT poster_path FROM p12_events WHERE event_id = ? LIMIT 1');
                $existing->bind_param('i', $eventId);
                $existing->execute();
                $oldEvent = $existing->get_result()->fetch_assoc();
                $existing->close();
                if (!$oldEvent) {
                    throw new RuntimeException('The event record was not found.');
                }

                if ($newPosterPath !== null) {
                    $statement = $mysqli->prepare(
                        'UPDATE p12_events
                         SET event_name = ?, description = ?, event_date = ?, venue = ?,
                             category = ?, status = ?, poster_path = ?
                         WHERE event_id = ?'
                    );
                    $statement->bind_param(
                        'sssssssi',
                        $name, $description, $date, $venue, $category, $status, $newPosterPath, $eventId
                    );
                } else {
                    $statement = $mysqli->prepare(
                        'UPDATE p12_events
                         SET event_name = ?, description = ?, event_date = ?, venue = ?,
                             category = ?, status = ?
                         WHERE event_id = ?'
                    );
                    $statement->bind_param(
                        'ssssssi',
                        $name, $description, $date, $venue, $category, $status, $eventId
                    );
                }
                $statement->execute();
                $statement->close();
                if ($newPosterPath !== null) {
                    p12DeletePoster($oldEvent['poster_path']);
                }
                p12Redirect('message=updated');
            }

            $statement = $mysqli->prepare(
                'INSERT INTO p12_events
                 (event_name, description, event_date, venue, category, status, poster_path)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->bind_param(
                'sssssss',
                $name, $description, $date, $venue, $category, $status, $newPosterPath
            );
            $statement->execute();
            $statement->close();
            p12Redirect('message=added');
        } elseif ($action === 'delete') {
            $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$eventId) {
                throw new RuntimeException('Select a valid event.');
            }
            $statement = $mysqli->prepare('SELECT poster_path FROM p12_events WHERE event_id = ? LIMIT 1');
            $statement->bind_param('i', $eventId);
            $statement->execute();
            $event = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$event) {
                throw new RuntimeException('The event record was not found.');
            }
            $statement = $mysqli->prepare('DELETE FROM p12_events WHERE event_id = ?');
            $statement->bind_param('i', $eventId);
            $statement->execute();
            $statement->close();
            p12DeletePoster($event['poster_path']);
            p12Redirect('message=deleted');
        }
    } catch (RuntimeException $exception) {
        if ($newPosterPath !== null) {
            p12DeletePoster($newPosterPath);
        }
        $error = $exception->getMessage();
    } catch (mysqli_sql_exception $exception) {
        if ($newPosterPath !== null) {
            p12DeletePoster($newPosterPath);
        }
        error_log($exception->getMessage());
        $error = 'The event could not be saved because of a database error.';
    }
}

try {
    if (isset($_GET['edit'])) {
        $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
        if ($editId) {
            $statement = $mysqli->prepare(
                'SELECT event_id, event_name, description, event_date, venue, category, status, poster_path
                 FROM p12_events WHERE event_id = ? LIMIT 1'
            );
            $statement->bind_param('i', $editId);
            $statement->execute();
            $editEvent = $statement->get_result()->fetch_assoc();
            $statement->close();
        }
    }

    $categoryFilter = trim((string)($_GET['category'] ?? ''));
    $statusFilter = (string)($_GET['status'] ?? '');
    $where = [];
    $types = '';
    $params = [];
    if (in_array($categoryFilter, $allowedCategories, true)) {
        $where[] = 'category = ?';
        $types .= 's';
        $params[] = $categoryFilter;
    } else {
        $categoryFilter = '';
    }
    if (in_array($statusFilter, $allowedStatuses, true)) {
        $where[] = 'status = ?';
        $types .= 's';
        $params[] = $statusFilter;
    } else {
        $statusFilter = '';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $statement = $mysqli->prepare(
        'SELECT event_id, event_name, description, event_date, venue, category, status, poster_path
         FROM p12_events' . $whereSql . ' ORDER BY event_date, event_name'
    );
    if ($types !== '') {
        $references = [];
        foreach ($params as $index => &$param) {
            $references[$index] = &$param;
        }
        $statement->bind_param($types, ...$references);
    }
    $statement->execute();
    $events = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load events. Import practical-12/schema.sql first.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management | Student Hub</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="header"><h1>Event Management</h1><button type="button" id="themeBtn" class="theme-btn">🌙 Dark Mode</button></header>
<nav class="portal-nav" aria-label="Admin navigation">
    <a href="../practical-10/admin_dashboard.php">Admin Dashboard</a>
    <a href="event_management.php" aria-current="page">Event Management</a>
    <a href="../profile.php">My Profile</a>
    <form class="portal-logout" action="../practical-10/logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><button type="submit">Sign out</button>
    </form>
</nav>
<main class="role-dashboard">
    <?php if ($message !== ''): ?><p class="form-message success-message" role="status"><?= appEscape($message) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="form-message error-message" role="alert"><?= appEscape($error) ?></p><?php endif; ?>
    <section class="account-card">
        <h2><?= $editEvent ? 'Edit event' : 'Add event' ?></h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="save">
            <?php if ($editEvent): ?><input type="hidden" name="event_id" value="<?= appEscape($editEvent['event_id']) ?>"><?php endif; ?>
            <label for="event_name">Event name</label>
            <input id="event_name" name="event_name" maxlength="150" required value="<?= appEscape($editEvent['event_name'] ?? '') ?>">
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="5000" required><?= appEscape($editEvent['description'] ?? '') ?></textarea>
            <label for="event_date">Date</label>
            <input id="event_date" name="event_date" type="date" required value="<?= appEscape($editEvent['event_date'] ?? '') ?>">
            <label for="venue">Venue</label>
            <input id="venue" name="venue" maxlength="150" required value="<?= appEscape($editEvent['venue'] ?? '') ?>">
            <label for="category">Category</label>
            <select id="category" name="category" required><option value="">Choose category</option>
                <?php foreach ($allowedCategories as $category): ?><option value="<?= appEscape($category) ?>" <?= ($editEvent['category'] ?? '') === $category ? 'selected' : '' ?>><?= appEscape($category) ?></option><?php endforeach; ?>
            </select>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <?php foreach ($allowedStatuses as $status): ?><option value="<?= appEscape($status) ?>" <?= ($editEvent['status'] ?? 'open') === $status ? 'selected' : '' ?>><?= appEscape(ucfirst($status)) ?></option><?php endforeach; ?>
            </select>
            <label for="poster">Poster (JPG, PNG, GIF, or WEBP; maximum 2 MB)</label>
            <input id="poster" name="poster" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
            <?php if (!empty($editEvent['poster_path'])): ?><p>Current poster: <a href="<?= appEscape($editEvent['poster_path']) ?>" target="_blank" rel="noopener">View poster</a></p><?php endif; ?>
            <button type="submit" class="registration-submit"><?= $editEvent ? 'Update event' : 'Add event' ?></button>
            <?php if ($editEvent): ?><a class="account-dashboard-link" href="event_management.php">Cancel edit</a><?php endif; ?>
        </form>
    </section>
    <section class="account-card admin-users">
        <h2>Event list</h2>
        <form method="get" class="student-filters">
            <label for="category-filter">Category</label>
            <select id="category-filter" name="category"><option value="">All categories</option>
                <?php foreach ($allowedCategories as $category): ?><option value="<?= appEscape($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>><?= appEscape($category) ?></option><?php endforeach; ?>
            </select>
            <label for="status-filter">Status</label>
            <select id="status-filter" name="status"><option value="">All statuses</option>
                <?php foreach ($allowedStatuses as $status): ?><option value="<?= appEscape($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= appEscape(ucfirst($status)) ?></option><?php endforeach; ?>
            </select>
            <button type="submit">Filter</button>
        </form>
        <div class="table-responsive"><table>
            <thead><tr><th>Poster</th><th>Event</th><th>Date</th><th>Venue</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody><?php foreach ($events as $event): ?><tr>
                <td><?php if ($event['poster_path']): ?><img class="event-poster-thumb" src="<?= appEscape($event['poster_path']) ?>" alt="<?= appEscape($event['event_name']) ?> poster"><?php else: ?>No poster<?php endif; ?></td>
                <td><?= appEscape($event['event_name']) ?><br><small><?= appEscape($event['description']) ?></small></td>
                <td><?= appEscape($event['event_date']) ?></td><td><?= appEscape($event['venue']) ?></td><td><?= appEscape($event['category']) ?></td><td><?= appEscape(ucfirst($event['status'])) ?></td>
                <td><a href="?edit=<?= appEscape($event['event_id']) ?>">Edit</a>
                    <form class="inline-form" method="post" onsubmit="return confirm('Are you sure you want to delete this event?');">
                        <input type="hidden" name="csrf_token" value="<?= appEscape($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= appEscape($event['event_id']) ?>"><button type="submit">Delete</button>
                    </form>
                </td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </section>
</main>
<footer><p>&copy; 2026 Student Hub Portal</p></footer>
<script src="../script.js"></script>
</body>
</html>
