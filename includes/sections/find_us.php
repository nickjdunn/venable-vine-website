<?php
$max = (int) ($config['max_events'] ?? 5);
$events = array_slice(EventRepository::upcoming(true), 0, $max);
$fb = Settings::get('facebook_url');
?>
<section id="find-us" class="container section-find-us">
    <?php editable_text('title', $config['title'] ?? 'Where to Find Us', 'h2'); ?>
    <?php if (editor_mode()): ?>
        <?php editor_placeholder(
            'Upcoming events are managed in the Events admin and listed here on the live site.',
            '/admin/events.php',
            'Manage Events'
        ); ?>
    <?php else: ?>
        <div id="events-list">
            <?php if (empty($events)): ?>
                <p class="no-events">No events scheduled right now. Check our social pages for updates!</p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <?php include ROOT . '/includes/partials/event-card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php editable_multiline('text', $config['text'] ?? '', 'p', 'find-us-text'); ?>
    <?php if (!editor_mode()): ?>
        <p class="text-center">
            <a href="/find-us.php" class="cta-button">Full Schedule & Map</a>
        </p>
        <?php if (!empty($config['show_facebook_button']) && $fb): ?>
            <p class="text-center" style="margin-top:1rem;">
                <a href="<?= e($fb) ?>" target="_blank" rel="noopener noreferrer" class="cta-button cta-outline">Visit Facebook</a>
            </p>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-center se-static-preview"><span class="cta-button">Full Schedule & Map</span></p>
    <?php endif; ?>
</section>
