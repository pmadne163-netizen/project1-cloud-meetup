<?php
require_once __DIR__ . '/../config.php';
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?>KuCL Meetup Project</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="wrap">
        <a class="brand" href="/index.php">KuCL Meetup Project</a>
        <nav>
            <a href="/index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="/meetups.php" class="<?= in_array($current, ['meetups.php', 'meetup_view.php', 'meetup_edit.php']) ? 'active' : '' ?>">Meetups</a>
        </nav>
    </div>
</header>
<main class="wrap">
    <?php
    $successMsg = flash('success');
    $errorMsg = flash('error');
    ?>
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?= h($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-error"><?= h($errorMsg) ?></div>
    <?php endif; ?>
