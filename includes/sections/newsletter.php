<section id="newsletter" class="section-newsletter">
    <div class="container newsletter-inner">
        <h2><?= e($config['title'] ?? 'Newsletter') ?></h2>
        <?php if (!empty($config['subtitle'])): ?>
            <p class="text-center"><?= e($config['subtitle']) ?></p>
        <?php endif; ?>
        <form class="newsletter-form ajax-form" data-endpoint="/api/subscribe.php">
            <?= Csrf::field() ?>
            <div class="newsletter-row">
                <input type="email" name="email" placeholder="Your email address" required>
                <button type="submit" class="submit-btn">Subscribe</button>
            </div>
            <p class="form-status"></p>
        </form>
    </div>
</section>
