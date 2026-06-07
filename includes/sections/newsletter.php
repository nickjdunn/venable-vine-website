<section id="newsletter" class="section-newsletter">
    <div class="container newsletter-inner">
        <?php editable_text('title', $config['title'] ?? 'Newsletter', 'h2'); ?>
        <?php editable_multiline('subtitle', $config['subtitle'] ?? '', 'p', 'text-center'); ?>
        <?php if (editor_mode()): ?>
            <div class="se-form-preview se-newsletter-preview">
                <div class="newsletter-row se-fake-newsletter">
                    <span class="se-fake-input">Your email address</span>
                    <span class="submit-btn">Subscribe</span>
                </div>
                <p class="se-managed-placeholder-note">Newsletter signup works on the live site.</p>
            </div>
        <?php else: ?>
            <form class="newsletter-form ajax-form" data-endpoint="/api/subscribe.php">
                <?= Csrf::field() ?>
                <div class="newsletter-row">
                    <input type="email" name="email" placeholder="Your email address" required>
                    <button type="submit" class="submit-btn">Subscribe</button>
                </div>
                <p class="form-status"></p>
            </form>
        <?php endif; ?>
    </div>
</section>
