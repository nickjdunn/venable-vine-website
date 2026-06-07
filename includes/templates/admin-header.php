<?php
$adminTitle = ($adminTitle ?? 'Admin') . ' | Venable & Vine';
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/media-picker.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin-tutorial.css') ?>">
    <?php if (!empty($extraAdminCss)): foreach ((array)$extraAdminCss as $css): ?>
        <link rel="stylesheet" href="<?= e($css) ?>">
    <?php endforeach; endif; ?>
</head>
<body class="admin-body<?= !empty($adminBodyClass) ? ' ' . e($adminBodyClass) : '' ?>">
<header class="admin-topbar">
    <a href="/admin/dashboard.php" class="admin-brand">V&V Admin</a>
    <button class="admin-nav-toggle" aria-label="Menu">☰</button>
    <div class="admin-user">
        <button type="button" id="admin-tutorial-btn" class="btn btn-sm btn-tutorial">Tutorial</button>
        <span><?= e($user['name'] ?? '') ?></span>
        <a href="/admin/logout.php" class="btn btn-sm btn-muted">Logout</a>
    </div>
</header>
<nav class="admin-sidebar">
    <?php
    $nav = [
        '/admin/dashboard.php' => 'Dashboard',
        '/admin/page-builder.php' => 'Page Builder',
        '/admin/menu.php' => 'Menu',
        '/admin/events.php' => 'Events',
        '/admin/gallery.php' => 'Media Library',
        '/admin/reviews.php' => 'Reviews',
        '/admin/contacts.php' => 'Contacts',
        '/admin/newsletter.php' => 'Newsletter',
        '/admin/settings.php' => 'Settings',
    ];
    if (Auth::isOwner()) {
        $nav['/admin/users.php'] = 'Users';
        $nav['/admin/debug.php'] = 'Debug';
    }
    $nav['/admin/ordering.php'] = 'Ordering (Soon)';
    $current = $_SERVER['SCRIPT_NAME'] ?? '';
    foreach ($nav as $href => $label):
        $active = str_ends_with($current, basename($href)) ? ' active' : '';
    ?>
        <a href="<?= e($href) ?>" class="admin-nav-link<?= $active ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<div class="admin-content">
    <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>
