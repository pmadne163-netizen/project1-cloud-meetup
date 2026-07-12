<?php
require_once __DIR__ . '/s3.php';

$pageTitle = 'Meetups';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');
    $capacity = max(1, (int) ($_POST['capacity'] ?? 20));

    if ($title === '' || $date === '' || $location === '') {
        flash('error', 'Please provide a title, date, and location.');
        header('Location: /meetups.php');
        exit;
    }

    $id = date('Ymd', strtotime($date)) . '-' . bin2hex(random_bytes(4));

    $meetup = [
        'id' => $id,
        'title' => $title,
        'description' => $description,
        'date' => $date,
        'location' => $location,
        'organizer' => $organizer,
        'capacity' => $capacity,
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'rsvps' => [],
    ];

    s3_put_json(S3_PREFIX . $id . '.json', $meetup);

    flash('success', "Meetup \"{$title}\" created — stored at s3://" . S3_BUCKET . '/' . S3_PREFIX . $id . '.json');
    header('Location: /meetups.php');
    exit;
}

$meetups = [];
$s3Error = null;
try {
    foreach (s3_list_keys(S3_PREFIX) as $key) {
        $data = s3_get_json($key);
        if ($data) {
            $meetups[] = $data;
        }
    }
    usort($meetups, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
} catch (Throwable $e) {
    $s3Error = 'Could not reach S3. Check Step 1/7 in the deployment guide (IAM role / run api/setup_bucket.php).';
    error_log($e->getMessage());
}

require __DIR__ . '/includes/header.php';
$today = date('Y-m-d');
?>

<h1>Meetups</h1>
<p class="subtitle">Each meetup is a JSON object at <code><?= h(S3_PREFIX) ?>&lt;id&gt;.json</code> in S3.</p>

<div class="two-col">
    <div class="card">
        <h2>Create a meetup</h2>
        <form method="post" action="/meetups.php">
            <input type="hidden" name="action" value="create">

            <label for="title">Title</label>
            <input type="text" id="title" name="title" required maxlength="150">

            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="1000"></textarea>

            <label for="date">Date</label>
            <input type="date" id="date" name="date" required>

            <label for="location">Location</label>
            <input type="text" id="location" name="location" required maxlength="200" placeholder="e.g. Udaipur Tech Hub">

            <label for="organizer">Organizer</label>
            <input type="text" id="organizer" name="organizer" maxlength="150">

            <label for="capacity">Capacity</label>
            <input type="number" id="capacity" name="capacity" min="1" step="1" value="20" required>

            <div class="btn-row">
                <button type="submit" class="btn">Create meetup</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>All meetups (<?= count($meetups) ?>)</h2>
        <?php if ($s3Error): ?>
            <div class="alert alert-error"><?= h($s3Error) ?></div>
        <?php elseif (empty($meetups)): ?>
            <p class="empty">No meetups yet — create one on the left.</p>
        <?php else: ?>
            <?php foreach ($meetups as $m): ?>
                <div class="meetup-card">
                    <span class="badge <?= ($m['date'] ?? '') >= $today ? 'upcoming' : 'past' ?>">
                        <?= ($m['date'] ?? '') >= $today ? 'Upcoming' : 'Past' ?>
                    </span>
                    <h3><a href="/meetup_view.php?id=<?= urlencode($m['id']) ?>"><?= h($m['title']) ?></a></h3>
                    <div class="meta">
                        <?= h($m['date']) ?> &middot; <?= h($m['location']) ?> &middot;
                        <?= count($m['rsvps'] ?? []) ?>/<?= (int) $m['capacity'] ?> going
                    </div>
                    <div class="actions">
                        <a href="/meetup_view.php?id=<?= urlencode($m['id']) ?>">View / RSVP</a>
                        <a href="/meetup_edit.php?id=<?= urlencode($m['id']) ?>">Edit</a>
                        <form class="inline" method="post" action="/meetup_delete.php"
                              onsubmit="return confirm('Delete this meetup?');">
                            <input type="hidden" name="id" value="<?= h($m['id']) ?>">
                            <button type="submit" class="btn danger" style="padding:2px 8px;font-size:0.8rem;">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
