<?php
require_once __DIR__ . '/s3.php';

$pageTitle = 'Meetup details';

$id = $_GET['id'] ?? '';
if ($id === '') {
    header('Location: /meetups.php');
    exit;
}

$key = S3_PREFIX . basename($id) . '.json';
$meetup = s3_get_json($key);

if (!$meetup) {
    flash('error', 'Meetup not found.');
    header('Location: /meetups.php');
    exit;
}

require __DIR__ . '/includes/header.php';

$rsvps = $meetup['rsvps'] ?? [];
$spotsLeft = max(0, (int) $meetup['capacity'] - count($rsvps));
?>

<p><a href="/meetups.php">&larr; All meetups</a></p>

<h1><?= h($meetup['title']) ?></h1>
<p class="subtitle"><?= h($meetup['date']) ?> &middot; <?= h($meetup['location']) ?></p>

<div class="two-col">
    <div>
        <div class="card">
            <h2>Details</h2>
            <p><?= nl2br(h($meetup['description'])) ?></p>
            <p><strong>Organizer:</strong> <?= h($meetup['organizer'] ?: '—') ?></p>
            <p><strong>Capacity:</strong> <?= count($rsvps) ?> / <?= (int) $meetup['capacity'] ?> going
                (<?= $spotsLeft ?> spot<?= $spotsLeft === 1 ? '' : 's' ?> left)</p>
            <p style="color:var(--muted);font-size:0.85rem;">
                Stored at <code>s3://<?= h(S3_BUCKET) ?>/<?= h($key) ?></code>
            </p>
        </div>

        <div class="card">
            <h2>RSVP</h2>
            <?php if ($spotsLeft <= 0): ?>
                <p class="empty">This meetup is fully booked.</p>
            <?php else: ?>
                <form method="post" action="/rsvp_add.php">
                    <input type="hidden" name="id" value="<?= h($meetup['id']) ?>">

                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required maxlength="150">

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required maxlength="150">

                    <div class="btn-row">
                        <button type="submit" class="btn">I'm going</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>Attendees (<?= count($rsvps) ?>)</h2>
        <?php if (empty($rsvps)): ?>
            <p class="empty">No RSVPs yet.</p>
        <?php else: ?>
            <ul class="attendee-list">
                <?php foreach ($rsvps as $r): ?>
                    <li>
                        <span><?= h($r['name']) ?> <span style="color:var(--muted);">(<?= h($r['email']) ?>)</span></span>
                        <form class="inline" method="post" action="/rsvp_delete.php"
                              onsubmit="return confirm('Remove this RSVP?');">
                            <input type="hidden" name="id" value="<?= h($meetup['id']) ?>">
                            <input type="hidden" name="rsvp_id" value="<?= h($r['rsvp_id']) ?>">
                            <button type="submit" class="btn danger" style="padding:2px 8px;font-size:0.78rem;">Remove</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
