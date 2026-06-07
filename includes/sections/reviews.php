<?php $reviews = ReviewRepository::approved(12); ?>
<section id="reviews" class="section-reviews">
    <div class="container">
        <h2><?= e($config['title'] ?? 'Reviews') ?></h2>
        <?php if (empty($reviews)): ?>
            <p class="text-center">No reviews yet. <a href="#contact">Be the first!</a></p>
        <?php else: ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="stars"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></div>
                        <p class="text">"<?= e($review['text']) ?>"</p>
                        <p class="author">— <?= e($review['name']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
