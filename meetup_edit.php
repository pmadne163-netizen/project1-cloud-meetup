<?php
require_once __DIR__ . '/s3.php';

$pageTitle = 'Edit meetup';

$id = $_GET['id'] ?? $_POST['id'] ?? '';
if ($id === '') {
    header('Location: /meetups.php');
    exit;
}

$key = S3_PREFIX . basename($id) . '.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meetup = s3_get_json($key);
    if (!$meetup) {
        flash('error', 'Meetup not found.');
        header('Location: /meetups.php');
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');
    $capacity = max(1, (int) ($_POST['capacity'] ?? 20));

    if ($title === '' || $date === '' || $location === '') {
        flash('error', 'Please provide a title, date, and location.');
        header('Location: /meetup_edit.php?id=' . urlencode($id));
        exit;
    }

    $meetup['title'] = $title;
    $meetup['description'] = $description;
    $meetup['date'] = $date;
    $meetup['location'] = $location;
    $meetup['organizer'] = $organizer;
    $meetup['capacity'] = $capacity;
    $meetup['updated_at'] = date('c');

    s3_put_json($key, $meetup);

    flash('success', 'Meetup updated.');
    header('Location: /meetup_view.php?id=' . urlencode($id));
    exit;
}

$meetup = s3_get_json($key);
if (!$meetup) {
    flash('error', 'Meetup not found.');
    header('Location: /meetups.php');
    exit;
}

require __DIR__ . '/includes/header.php';
?>

<h1>Edit meetup</h1>

<div class="card" style="max-width:520px;">
    <form method="post" action="/meetup_edit.php">
        <input type="hidden" name="id" value="<?= h($meetup['id']) ?>">

        <label for="title">Title</label>
        <input type="text" id="title" name="title" required maxlength="150" value="<?= h($meetup['title']) ?>">

        <label for="description">Description</label>
        <textarea id="description" name="description" maxlength="1000"><?= h($meetup['description']) ?></textarea>

        <label for="date">Date</label>
        <input type="date" id="date" name="date" required value="<?= h($meetup['date']) ?>">

        <label for="location">Location</label>
        <input type="text" id="location" name="location" required maxlength="200" value="<?= h($meetup['location']) ?>">

        <label for="organizer">Organizer</label>
        <input type="text" id="organizer" name="organizer" maxlength="150" value="<?= h($meetup['organizer']) ?>">

        <label for="capacity">Capacity</label>
        <input type="number" id="capacity" name="capacity" min="1" step="1" required value="<?= h((string) $meetup['capacity']) ?>">

        <div class="btn-row">
            <button type="submit" class="btn">Save changes</button>
            <a class="btn secondary" href="/meetup_view.php?id=<?= urlencode($meetup['id']) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
