<?php
$siteName = Settings::get('site_name', 'Venable & Vine');
$tagline = Settings::get('site_tagline', 'Freshly Squeezed. Family Made.');
$metaDesc = Settings::get('meta_description', '');
$logo = upload_url(Settings::get('logo_path'));
$favicon = upload_url(Settings::get('favicon_path'));
$pageTitle = ($pageTitle ?? $siteName) . ' | ' . $tagline;
$recaptchaKey = Settings::get('recaptcha_site_key');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <?php if ($metaDesc): ?><meta name="description" content="<?= e($metaDesc) ?>"><?php endif; ?>
    <?php if ($favicon): ?><link rel="icon" href="<?= e($favicon) ?>"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <?php if (!empty($extraCss)): foreach ((array)$extraCss as $css): ?>
        <link rel="stylesheet" href="<?= e($css) ?>">
    <?php endforeach; endif; ?>
    <?php if ($recaptchaKey): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body>
<header class="header">
    <a href="/" class="logo-link">
        <?php if ($logo): ?>
            <img src="<?= e($logo) ?>" alt="<?= e($siteName) ?> Logo" class="logo-img">
        <?php else: ?>
            <span class="logo-text"><?= e($siteName) ?></span>
        <?php endif; ?>
    </a>
    <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">☰</button>
    <nav class="nav-links">
        <a href="/menu.php">Menu</a>
        <a href="/find-us.php">Find Us</a>
        <a href="/#story">Our Story</a>
        <a href="/#gallery">Gallery</a>
        <a href="/#reviews">Reviews</a>
        <a href="/#contact">Contact</a>
    </nav>
</header>
<main>
