<?php
require_once __DIR__ . '/s3.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /meetups.php');
    exit;
}

$id = $_POST['id'] ?? '';

if ($id !== '') {
    s3_delete(S3_PREFIX . basename($id) . '.json');
    flash('success', 'Meetup deleted.');
}

header('Location: /meetups.php');
exit;
