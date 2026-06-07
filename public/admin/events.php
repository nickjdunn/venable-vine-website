<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Events';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $date = $_POST['event_date'] ?? '';
            $start = $date . ' ' . ($_POST['time_start'] ?? '09:00') . ':00';
            $end = $date . ' ' . ($_POST['time_end'] ?? '17:00') . ':00';
            $data = [
                'title' => $_POST['title'] ?? null,
                'details' => $_POST['details'] ?? null,
                'start_at' => $start,
                'end_at' => $end,
                'address' => $_POST['address'],
                'lat' => $_POST['lat'] !== '' ? $_POST['lat'] : null,
                'lng' => $_POST['lng'] !== '' ? $_POST['lng'] : null,
                'is_active' => isset($_POST['is_active']),
            ];
            if (!empty($_POST['event_id'])) {
                EventRepository::update((int) $_POST['event_id'], $data);
            } else {
                EventRepository::create($data);
            }
            flash('success', 'Event saved.');
        } elseif ($action === 'delete') {
            EventRepository::delete((int) $_POST['event_id']);
            flash('success', 'Event deleted.');
        } elseif ($action === 'duplicate') {
            $ev = EventRepository::find((int) $_POST['event_id']);
            if ($ev) {
                unset($ev['id'], $ev['created_at']);
                EventRepository::create($ev);
                flash('success', 'Event duplicated.');
            }
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/events.php' . (!empty($_POST['event_id']) ? '?edit=' . (int)$_POST['event_id'] : ''));
}

$upcoming = EventRepository::upcoming(false);
$past = EventRepository::past(30);
$edit = !empty($_GET['edit']) ? EventRepository::find((int) $_GET['edit']) : null;

require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Events & Locations</h1>

<div class="card">
    <h3><?= $edit ? 'Edit Event' : 'Add Event' ?></h3>
    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($edit): ?><input type="hidden" name="event_id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <label>Event Title (optional)</label>
        <input type="text" name="title" placeholder="e.g. Downtown Farmer's Market" value="<?= e($edit['title'] ?? '') ?>">
        <label>Details (optional)</label>
        <textarea name="details" placeholder="e.g. Parked near the main stage"><?= e($edit['details'] ?? '') ?></textarea>
        <div class="form-row">
            <div>
                <label>Date *</label>
                <input type="date" name="event_date" required value="<?= $edit ? e(substr($edit['start_at'], 0, 10)) : '' ?>">
            </div>
            <div>
                <label>Start Time *</label>
                <input type="time" name="time_start" required value="<?= $edit ? e(substr($edit['start_at'], 11, 5)) : '09:00' ?>">
            </div>
            <div>
                <label>End Time *</label>
                <input type="time" name="time_end" required value="<?= $edit ? e(substr($edit['end_at'], 11, 5)) : '17:00' ?>">
            </div>
        </div>
        <label>Location / Address *</label>
        <div class="location-input-group">
            <input type="text" name="address" id="event-address" required placeholder="123 Main St" value="<?= e($edit['address'] ?? '') ?>">
            <button type="button" id="get-location-btn" class="btn btn-success">Use Current Location</button>
        </div>
        <input type="hidden" name="lat" id="event-lat" value="<?= e($edit['lat'] ?? '') ?>">
        <input type="hidden" name="lng" id="event-lng" value="<?= e($edit['lng'] ?? '') ?>">
        <div class="checkbox-row"><label><input type="checkbox" name="is_active"<?= !isset($edit) || !empty($edit['is_active']) ? ' checked' : '' ?>> Active (visible on site)</label></div>
        <div class="form-actions">
            <button type="submit" class="btn">Save Event</button>
            <?php if ($edit): ?><a href="/admin/events.php" class="btn btn-muted">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<h3>Upcoming Events</h3>
<?php if (empty($upcoming)): ?><p>No upcoming events.</p><?php else: ?>
    <?php foreach ($upcoming as $ev): ?>
        <div class="card">
            <div class="card-header">
                <div>
                    <strong><?= e($ev['title'] ?: format_event_datetime($ev['start_at'])) ?></strong><br>
                    <small><?= e(format_event_datetime($ev['start_at'])) ?> · <?= e(format_event_time($ev['start_at'])) ?>–<?= e(format_event_time($ev['end_at'])) ?></small><br>
                    <?= e($ev['address']) ?>
                    <?php if (!$ev['is_active']): ?> <span class="status-badge status-inactive">Inactive</span><?php endif; ?>
                </div>
                <div class="form-actions">
                    <a href="?edit=<?= (int)$ev['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <form method="post" style="display:inline"><?= Csrf::field() ?><input type="hidden" name="action" value="duplicate"><input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>"><button class="btn btn-sm">Duplicate</button></form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h3 style="margin-top:2rem;">Past Events (Archive)</h3>
<?php if (empty($past)): ?><p>No past events.</p><?php else: ?>
    <?php foreach ($past as $ev): ?>
        <div class="card" style="opacity:0.85;">
            <strong><?= e($ev['title'] ?: format_event_datetime($ev['start_at'])) ?></strong> — <?= e($ev['address']) ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
