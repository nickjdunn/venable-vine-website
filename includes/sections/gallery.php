<?php
$config = ensure_gallery_config_photos($config ?? []);
$photos = normalize_gallery_photos($config['photos'] ?? []);
?>
<section id="gallery" class="container section-gallery"<?= section_style_attr($config) ?>>
    <?php editable_text('title', $config['title'] ?? 'Gallery', 'h2'); ?>
    <?php if (editor_mode()): ?>
        <div class="se-gallery-editor" data-gallery-editor>
            <?php if (empty($photos)): ?>
                <p class="se-gallery-hint" data-gallery-hint>No photos yet — click Add Photos to choose images from your Media Library.</p>
            <?php else: ?>
                <p class="se-gallery-hint" data-gallery-hint hidden>No photos yet — click Add Photos to choose images from your Media Library.</p>
            <?php endif; ?>
            <div class="se-gallery-grid" data-gallery-grid>
                <?php foreach ($photos as $i => $photo): ?>
                    <?php $url = upload_url($photo['src']); ?>
                    <div class="se-gallery-item" data-index="<?= (int) $i ?>">
                        <img src="<?= e($url) ?>" alt="<?= e($photo['alt']) ?>">
                        <button type="button" class="se-gallery-remove" title="Remove">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline se-gallery-add">Add Photos</button>
        </div>
    <?php elseif (empty($photos)): ?>
        <p class="text-center">Photos coming soon!</p>
    <?php else: ?>
        <div class="gallery-grid" data-lightbox-gallery>
            <?php foreach ($photos as $photo): ?>
                <?php $url = upload_url($photo['src']); ?>
                <img src="<?= e($url) ?>"
                     alt="<?= e($photo['alt'] ?: $photo['caption'] ?: 'Gallery photo') ?>"
                     title="<?= e($photo['title']) ?>"
                     class="gallery-image"
                     loading="lazy">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
