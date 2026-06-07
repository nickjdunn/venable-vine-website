<section id="newsletter" class="section-newsletter"<?= section_style_attr($config) ?>>
    <div class="container newsletter-inner">
        <?php editable_text('title', $config['title'] ?? 'Newsletter', 'h2'); ?>
        <?php editable_multiline('subtitle', $config['subtitle'] ?? '', 'p', 'text-center'); ?>
        <?php if (editor_mode()): ?>
            <div class="se-form-preview se-newsletter-preview">
                <div class="newsletter-row se-fake-newsletter">
                    <span class="se-editable se-editable-trigger se-fake-input" data-field="email_placeholder" data-edit-mode="plain" role="button" tabindex="0"><?= e($config['email_placeholder'] ?? 'Your email address') ?></span>
                    <span class="se-editable se-editable-trigger submit-btn" data-field="submit_text" data-edit-mode="plain" role="button" tabindex="0"><?= e($config['submit_text'] ?? 'Subscribe') ?></span>
                </div>
            </div>
        <?php else: ?>
            <form class="newsletter-form ajax-form" data-endpoint="/api/subscribe.php">
                <?= Csrf::field() ?>
                <div class="newsletter-row">
                    <input type="email" name="email" placeholder="<?= e($config['email_placeholder'] ?? 'Your email address') ?>" required>
                    <button type="submit" class="submit-btn"><?= e($config['submit_text'] ?? 'Subscribe') ?></button>
                </div>
                <p class="form-status"></p>
            </form>
        <?php endif; ?>
    </div>
</section>
