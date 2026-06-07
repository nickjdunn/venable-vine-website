<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Dashboard';
$stats = [
    'events' => count(EventRepository::upcoming(false)),
    'menu_items' => count(MenuRepository::items()),
    'reviews_pending' => count(ReviewRepository::byStatus('pending')),
    'contacts_new' => count(ContactRepository::byStatus('new')),
    'subscribers' => count(NewsletterRepository::all('active')),
];
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Dashboard</h1>
<p>Welcome back, <?= e(Auth::user()['name']) ?>!</p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-top:1.5rem;">
    <div class="card"><strong><?= $stats['events'] ?></strong><br>Upcoming Events</div>
    <div class="card"><strong><?= $stats['menu_items'] ?></strong><br>Menu Items</div>
    <div class="card"><strong><?= $stats['reviews_pending'] ?></strong><br>Pending Reviews</div>
    <div class="card"><strong><?= $stats['contacts_new'] ?></strong><br>New Messages</div>
    <div class="card"><strong><?= $stats['subscribers'] ?></strong><br>Newsletter Subscribers</div>
</div>
<div class="card" style="margin-top:1.5rem;">
    <h3>Quick Links</h3>
    <div class="form-actions">
        <a href="/admin/events.php" class="btn">Add Event</a>
        <a href="/admin/menu.php" class="btn">Manage Menu</a>
        <a href="/admin/page-builder.php" class="btn">Edit Homepage</a>
        <a href="/" class="btn btn-outline" target="_blank">View Site</a>
    </div>
</div>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
