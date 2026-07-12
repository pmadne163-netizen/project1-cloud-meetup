<?php
require_once __DIR__ . '/s3.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /meetups.php');
    exit;
}

$id = $_POST['id'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = trim(strtolower($_POST['email'] ?? ''));

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

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Please provide a valid name and email.');
    header('Location: /meetup_view.php?id=' . urlencode($id));
    exit;
}

$rsvps = $meetup['rsvps'] ?? [];

if (count($rsvps) >= (int) $meetup['capacity']) {
    flash('error', 'Sorry, this meetup is fully booked.');
    header('Location: /meetup_view.php?id=' . urlencode($id));
    exit;
}

foreach ($rsvps as $r) {
    if (strtolower($r['email'] ?? '') === $email) {
        flash('error', 'That email has already RSVP\'d.');
        header('Location: /meetup_view.php?id=' . urlencode($id));
        exit;
    }
}

$rsvps[] = [
    'rsvp_id' => bin2hex(random_bytes(6)),
    'name' => $name,
    'email' => $email,
    'created_at' => date('c'),
];

$meetup['rsvps'] = $rsvps;
$meetup['updated_at'] = date('c');

s3_put_json($key, $meetup);

flash('success', "Thanks, {$name} — you're on the list!");
header('Location: /meetup_view.php?id=' . urlencode($id));
exit;
