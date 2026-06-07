<?php
$bgPath = resolve_image_path($config['background_image'] ?? default_brand_images()['hero_bg']);
$logoPath = resolve_image_path($config['logo_image'] ?? Settings::get('logo_path') ?? default_brand_images()['logo']);
$bg = upload_url($bgPath);
$heroStyle = '';
if (!editor_mode() && $bg) {
    $heroStyle = "background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('" . e($bg) . "');";
}
$heroAttrs = ' class="hero" id="hero"';
if (editor_mode()) {
    $heroAttrs .= ' data-bg-field="background_image" data-bg-path="' . e($bgPath) . '"';
    if ($bg) {
        $heroStyle = "background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('" . e($bg) . "');";
    }
}
?>
<section<?= $heroAttrs ?><?= $heroStyle ? ' style="' . $heroStyle . '"' : '' ?>>
    <?php if (editor_mode()): ?>
        <div class="se-hero-bg-btn-wrap">
            <button type="button" class="se-image-btn se-hero-bg-btn" data-field="background_image">Change Background</button>
        </div>
    <?php endif; ?>
    <?php editable_image('logo_image', $logoPath, Settings::get('site_name', 'Venable & Vine') . ' Logo', 'logo-img-display'); ?>
    <?php editable_text('title', $config['title'] ?? '', 'h1'); ?>
    <?php editable_multiline('subtitle', $config['subtitle'] ?? '', 'div', 'hero-subtitle'); ?>
    <?php editable_cta('cta_text', 'cta_link', $config['cta_text'] ?? '', $config['cta_link'] ?? ''); ?>
</section>
