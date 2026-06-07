<?php $images = MediaRepository::all(true); ?>
<section id="gallery" class="container section-gallery">
    <h2><?= e($config['title'] ?? 'Gallery') ?></h2>
    <?php if (empty($images)): ?>
        <p class="text-center">Photos coming soon!</p>
    <?php else: ?>
        <div class="gallery-grid" data-lightbox-gallery>
            <?php foreach ($images as $img): ?>
                <img src="<?= e($img['url']) ?>"
                     alt="<?= e($img['alt_text'] ?: $img['caption'] ?: 'Gallery photo') ?>"
                     title="<?= e($img['title']) ?>"
                     class="gallery-image"
                     loading="lazy">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
