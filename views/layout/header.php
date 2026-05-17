<!DOCTYPE html>
<html lang="en" style="--brand: <?= htmlspecialchars($orgColor ?? '#2563eb', ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($pageTitle ?? '') ? $pageTitle . ' — ' . ($orgName ?? APP_NAME) : ($orgName ?? APP_NAME), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body class="<?= htmlspecialchars($body_class ?? '', ENT_QUOTES, 'UTF-8') ?>">
