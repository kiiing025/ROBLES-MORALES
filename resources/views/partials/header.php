<?php $app = app_config(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? $app['name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
    <script defer src="<?= e(asset('app.js')) ?>"></script>
</head>
<body class="app-shell">
