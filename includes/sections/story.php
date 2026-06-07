<?php
$image = upload_url(resolve_image_path($config['image'] ?? default_brand_images()['story']));
?>
<section id="story" class="section-story">
    <div class="container">
        <h2><?= e($config['title'] ?? 'Our Story') ?></h2>
        <div class="story-content">
            <div class="story-text">
                <?php if (!empty($config['paragraph1'])): ?>
                    <p><?= nl2br(e($config['paragraph1'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($config['paragraph2'])): ?>
                    <p><?= nl2br(e($config['paragraph2'])) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($image): ?>
                <div class="story-image">
                    <img src="<?= e($image) ?>" alt="Our story">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
