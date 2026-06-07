<?php
/** @var array $event */
$gmaps = maps_link($event['address'], $event['lat'] ?? null, $event['lng'] ?? null);
$apple = apple_maps_link($event['address'], $event['lat'] ?? null, $event['lng'] ?? null);
?>
<div class="event-card">
    <h3><?= e($event['title'] ?: format_event_datetime($event['start_at'])) ?></h3>
    <?php if ($event['title']): ?>
        <p><strong>Date:</strong> <?= e(format_event_datetime($event['start_at'])) ?></p>
    <?php endif; ?>
    <p><strong>Time:</strong> <?= e(format_event_time($event['start_at'])) ?> – <?= e(format_event_time($event['end_at'])) ?></p>
    <p><strong>Location:</strong> <a href="<?= e($gmaps) ?>" target="_blank" rel="noopener noreferrer"><?= e($event['address']) ?></a></p>
    <p class="map-links">
        <a href="<?= e($gmaps) ?>" target="_blank" rel="noopener noreferrer">Google Maps</a>
        ·
        <a href="<?= e($apple) ?>" target="_blank" rel="noopener noreferrer">Apple Maps</a>
    </p>
    <?php if (!empty($event['details'])): ?>
        <div class="event-details"><?= nl2br(e($event['details'])) ?></div>
    <?php endif; ?>
</div>
