<?php
$imagePath = resolve_image_path($config['image'] ?? default_brand_images()['story']);
?>
<section id="story" class="section-story"<?= section_style_attr($config) ?>>
    <div class="container">
        <?php editable_text('title', $config['title'] ?? 'Our Story', 'h2'); ?>
        <div class="story-content">
            <div class="story-text">
                <?php editable_multiline('paragraph1', $config['paragraph1'] ?? ''); ?>
                <?php editable_multiline('paragraph2', $config['paragraph2'] ?? ''); ?>
            </div>
            <div class="story-image">
                <?php editable_image('image', $imagePath, 'Our story'); ?>
            </div>
        </div>
    </div>
</section>
