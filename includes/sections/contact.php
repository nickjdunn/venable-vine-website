<?php
$recaptchaKey = Settings::get('recaptcha_site_key');
$showContact = !isset($config['show_contact']) || $config['show_contact'];
$showReview = !isset($config['show_review']) || $config['show_review'];
?>
<section id="contact" class="section-contact">
    <div class="contact-container">
        <h2><?= e($config['title'] ?? 'Get In Touch') ?></h2>
        <?php if (!empty($config['subtitle'])): ?>
            <p><?= e($config['subtitle']) ?></p>
        <?php endif; ?>
        <?php if ($showContact && $showReview): ?>
            <div class="choice-buttons">
                <button type="button" class="choice-btn active" data-form="contact-form-panel">Contact Us</button>
                <button type="button" class="choice-btn" data-form="review-form-panel">Leave a Review</button>
            </div>
        <?php endif; ?>
        <?php if ($showReview): ?>
            <div id="review-form-panel" class="form-container<?= $showContact ? '' : ' active' ?>">
                <form class="ajax-form" data-endpoint="/api/submit-review.php">
                    <?= Csrf::field() ?>
                    <label>Your Name</label>
                    <input type="text" name="name" required>
                    <label>Your Rating</label>
                    <div class="rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>"<?= $i === 5 ? ' required' : '' ?>>
                            <label for="star<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <label>Your Review</label>
                    <textarea name="text" rows="5" required></textarea>
                    <?php if ($recaptchaKey): ?>
                        <div class="g-recaptcha" data-sitekey="<?= e($recaptchaKey) ?>"></div>
                    <?php endif; ?>
                    <div class="form-actions-centered">
                        <button type="submit" class="submit-btn">Submit Review</button>
                    </div>
                    <p class="form-status"></p>
                </form>
            </div>
        <?php endif; ?>
        <?php if ($showContact): ?>
            <div id="contact-form-panel" class="form-container<?= $showReview ? ' active' : '' ?>">
                <form class="ajax-form" data-endpoint="/api/submit-contact.php">
                    <?= Csrf::field() ?>
                    <label>Your Name</label>
                    <input type="text" name="name" required>
                    <label>Your Email</label>
                    <input type="email" name="email" required>
                    <label>Message</label>
                    <textarea name="message" rows="6" required></textarea>
                    <?php if ($recaptchaKey): ?>
                        <div class="g-recaptcha" data-sitekey="<?= e($recaptchaKey) ?>"></div>
                    <?php endif; ?>
                    <div class="form-actions-centered">
                        <button type="submit" class="submit-btn">Send Message</button>
                    </div>
                    <p class="form-status"></p>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
