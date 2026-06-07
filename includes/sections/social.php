<?php
$socials = array_filter([
    'Facebook' => Settings::get('facebook_url'),
    'Instagram' => Settings::get('instagram_url'),
    'TikTok' => Settings::get('tiktok_url'),
]);
?>
<?php if (editor_mode() || $socials): ?>
<section id="social" class="section-social">
    <div class="container text-center">
        <?php editable_text('title', $config['title'] ?? 'Follow Us', 'h2'); ?>
        <?php if (editor_mode()): ?>
            <?php editor_placeholder(
                'Social media links are set in Site Settings and appear here on the live site.',
                '/admin/settings.php',
                'Site Settings'
            ); ?>
            <?php if ($socials): ?>
                <div class="social-links se-static-preview">
                    <?php foreach ($socials as $label => $url): ?>
                        <span class="social-link"><?= e($label) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($socials): ?>
            <div class="social-links">
                <?php foreach ($socials as $label => $url): ?>
                    <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer" class="social-link"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
