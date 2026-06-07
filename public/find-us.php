<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Find Us';
$events = EventRepository::upcoming(true);
$current = EventRepository::currentOrNext();
$mapsKey = Settings::get('google_maps_api_key');
$calendarEvents = EventRepository::forCalendar();
$extraCss = ['https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css'];
$extraJs = ['https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js', asset('js/find-us.js')];
require ROOT . '/includes/templates/public-header.php';
?>
<section class="page-header">
    <div class="container">
        <h1>Where to Find Us</h1>
        <p>We move from location to location — here's where we'll be next.</p>
    </div>
</section>

<section class="container find-us-page">
    <div class="view-tabs">
        <button class="view-tab active" data-view="list">List</button>
        <button class="view-tab" data-view="calendar">Calendar</button>
        <button class="view-tab" data-view="map">Map</button>
    </div>

    <div id="view-list" class="view-panel active">
        <?php if (empty($events)): ?>
            <p class="no-events">No upcoming events scheduled. Follow us on social media for updates!</p>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <?php include ROOT . '/includes/partials/event-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="view-calendar" class="view-panel">
        <div id="events-calendar"></div>
    </div>

    <div id="view-map" class="view-panel">
        <?php if ($current): ?>
            <div class="map-embed-wrap">
                <?php if ($mapsKey && $current['lat'] && $current['lng']): ?>
                    <iframe
                        title="Map to Venable & Vine"
                        width="100%" height="450" style="border:0;border-radius:8px;"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps/embed/v1/place?key=<?= e($mapsKey) ?>&q=<?= e($current['lat']) ?>,<?= e($current['lng']) ?>">
                    </iframe>
                <?php else: ?>
                    <iframe
                        title="Map to Venable & Vine"
                        width="100%" height="450" style="border:0;border-radius:8px;"
                        loading="lazy"
                        src="https://maps.google.com/maps?q=<?= urlencode($current['address']) ?>&output=embed">
                    </iframe>
                <?php endif; ?>
                <div class="map-event-info">
                    <h3><?= e($current['title'] ?: 'Next Stop') ?></h3>
                    <p><?= e(format_event_datetime($current['start_at'])) ?> · <?= e(format_event_time($current['start_at'])) ?> – <?= e(format_event_time($current['end_at'])) ?></p>
                    <p><?= e($current['address']) ?></p>
                    <p>
                        <a href="<?= e(maps_link($current['address'], $current['lat'], $current['lng'])) ?>" target="_blank" rel="noopener">Google Maps</a>
                        ·
                        <a href="<?= e(apple_maps_link($current['address'], $current['lat'], $current['lng'])) ?>" target="_blank" rel="noopener">Apple Maps</a>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <p class="no-events">No location to show on the map yet.</p>
        <?php endif; ?>
    </div>
</section>
<script>window.VV_EVENTS = <?= json_encode($calendarEvents) ?>;</script>
<?php require ROOT . '/includes/templates/public-footer.php'; ?>
