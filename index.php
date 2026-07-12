<?php
require_once __DIR__ . '/s3.php';

$pageTitle = 'Home';

$meetups = [];
$s3Error = null;

try {
    $keys = s3_list_keys(S3_PREFIX);
    foreach ($keys as $key) {
        $data = s3_get_json($key);
        if ($data) {
            $meetups[] = $data;
        }
    }
} catch (Throwable $e) {
    $s3Error = 'Could not reach S3. Check Step 1/7 in the deployment guide.';
    error_log($e->getMessage());
}

$today = date('Y-m-d');
usort($meetups, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
$upcoming = array_values(array_filter($meetups, fn($m) => ($m['date'] ?? '') >= $today));
$totalRsvps = array_sum(array_map(fn($m) => count($m['rsvps'] ?? []), $meetups));

require __DIR__ . '/includes/header.php';
?>

<h1>KuCL Meetup Project</h1>
<p class="subtitle">PHP on EC2 — every meetup (and its RSVPs) is one JSON object in S3.</p>

<div class="grid">
    <div class="stat">
        <div class="num"><?= $s3Error ? '—' : count($meetups) ?></div>
        <div class="label">Meetups</div>
    </div>
    <div class="stat">
        <div class="num"><?= $s3Error ? '—' : count($upcoming) ?></div>
        <div class="label">Upcoming</div>
    </div>
    <div class="stat">
        <div class="num"><?= $s3Error ? '—' : $totalRsvps ?></div>
        <div class="label">RSVPs</div>
    </div>
</div>

<?php if ($s3Error): ?>
    <div class="alert alert-error"><?= h($s3Error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Next up</h2>
    <?php if (empty($upcoming)): ?>
        <p class="empty">No upcoming meetups yet. <a href="/meetups.php">Create one</a>.</p>
    <?php else: ?>
        <?php foreach (array_slice($upcoming, 0, 3) as $m): ?>
            <div class="meetup-card">
                <h3><a href="/meetup_view.php?id=<?= urlencode($m['id']) ?>"><?= h($m['title']) ?></a></h3>
                <div class="meta"><?= h($m['date']) ?> &middot; <?= h($m['location']) ?> &middot; <?= count($m['rsvps'] ?? []) ?>/<?= (int) $m['capacity'] ?> going</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <p><a href="/meetups.php">See all meetups &rarr;</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
