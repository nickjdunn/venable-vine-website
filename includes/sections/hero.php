<?php
$bg = upload_url($config['background_image'] ?? '');
$logo = upload_url($config['logo_image'] ?? Settings::get('logo_path'));
$heroStyle = $bg ? "background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('" . e($bg) . "');" : '';
?>
<section class="hero" id="hero"<?= $heroStyle ? ' style="' . $heroStyle . '"' : '' ?>>
    <?php if ($logo): ?>
        <img src="<?= e($logo) ?>" alt="<?= e(Settings::get('site_name', 'Venable & Vine')) ?> Logo" class="logo-img-display">
    <?php endif; ?>
    <h1><?= $config['title'] ?? '' ?></h1>
    <div class="hero-subtitle"><?= $config['subtitle'] ?? '' ?></div>
    <?php if (!empty($config['cta_text']) && !empty($config['cta_link'])): ?>
        <a href="<?= e($config['cta_link']) ?>" class="cta-button"><?= e($config['cta_text']) ?></a>
    <?php endif; ?>
</section>
