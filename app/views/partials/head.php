<?php
$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'LUMINA - Shop mắt kính trực tuyến.';
$pageStyles = $pageStyles ?? [];

if (!is_array($pageStyles)) {
    $pageStyles = [$pageStyles];
}

$styleVersion = @filemtime(PUBLIC_PATH . '/assets/css/style.css') ?: time();
$headerVersion = @filemtime(PUBLIC_PATH . '/assets/css/header-v2.css') ?: time();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/vendor/flaticon-uicons/css/uicons-regular-rounded.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css?v=<?= e((string) $styleVersion) ?>">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/header-v2.css?v=<?= e((string) $headerVersion) ?>">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/footer-v2.css?v=1.0.0">

    <?php foreach ($pageStyles as $styleHref): ?>
        <link rel="stylesheet" href="<?= e($styleHref) ?>">
    <?php endforeach; ?>
</head>
<body>
