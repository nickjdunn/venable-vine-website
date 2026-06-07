<?php
$max = (int) ($config['max_events'] ?? 5);
$events = array_slice(EventRepository::upcoming(true), 0, $max);
$fb = Settings::get('facebook_url');
?>
<section id="find-us" class="container section-find-us"<?= section_style_attr($config) ?>>
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
    <p class="text-center">
        <?php editable_cta('schedule_button_text', 'schedule_button_link', $config['schedule_button_text'] ?? 'Full Schedule & Map', $config['schedule_button_link'] ?? '/find-us.php'); ?>
    </p>
    <?php if (!empty($config['show_facebook_button']) && ($fb || editor_mode())): ?>
        <p class="text-center" style="margin-top:1rem;">
            <?php if (editor_mode()): ?>
                <span class="cta-button cta-outline se-editable se-editable-trigger" data-field="facebook_button_text" data-edit-mode="plain" role="button" tabindex="0"><?= e($config['facebook_button_text'] ?? 'Visit Facebook') ?></span>
            <?php elseif ($fb): ?>
                <a href="<?= e($fb) ?>" target="_blank" rel="noopener noreferrer" class="cta-button cta-outline"><?= e($config['facebook_button_text'] ?? 'Visit Facebook') ?></a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</section>
