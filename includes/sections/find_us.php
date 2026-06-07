<?php
$max = (int) ($config['max_events'] ?? 5);
$events = array_slice(EventRepository::upcoming(true), 0, $max);
$fb = Settings::get('facebook_url');
?>
<section id="find-us" class="container section-find-us">
    <h2><?= e($config['title'] ?? 'Where to Find Us') ?></h2>
    <div id="events-list">
        <?php if (empty($events)): ?>
            <p class="no-events">No events scheduled right now. Check our social pages for updates!</p>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <?php include ROOT . '/includes/partials/event-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($config['text'])): ?>
        <p class="find-us-text"><?= e($config['text']) ?></p>
    <?php endif; ?>
    <p class="text-center">
        <a href="/find-us.php" class="cta-button">Full Schedule & Map</a>
    </p>
    <?php if (!empty($config['show_facebook_button']) && $fb): ?>
        <p class="text-center" style="margin-top:1rem;">
            <a href="<?= e($fb) ?>" target="_blank" rel="noopener noreferrer" class="cta-button cta-outline">Visit Facebook</a>
        </p>
    <?php endif; ?>
</section>
