<?php
require_once __DIR__ . '/s3.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /meetups.php');
    exit;
}

$id = $_POST['id'] ?? '';
$rsvpId = $_POST['rsvp_id'] ?? '';

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

$rsvps = $meetup['rsvps'] ?? [];
$meetup['rsvps'] = array_values(array_filter($rsvps, fn($r) => ($r['rsvp_id'] ?? '') !== $rsvpId));
$meetup['updated_at'] = date('c');

s3_put_json($key, $meetup);

flash('success', 'RSVP removed.');
header('Location: /meetup_view.php?id=' . urlencode($id));
exit;
