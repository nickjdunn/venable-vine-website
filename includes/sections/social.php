<?php
$socials = array_filter([
    'Facebook' => Settings::get('facebook_url'),
    'Instagram' => Settings::get('instagram_url'),
    'TikTok' => Settings::get('tiktok_url'),
]);
?>
<?php if ($socials): ?>
<section id="social" class="section-social">
    <div class="container text-center">
        <?php if (!empty($config['title'])): ?>
            <h2><?= e($config['title']) ?></h2>
        <?php endif; ?>
        <div class="social-links">
            <?php foreach ($socials as $label => $url): ?>
                <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer" class="social-link"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
