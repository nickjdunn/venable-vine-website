<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Newsletter';

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers.csv"');
    echo NewsletterRepository::exportCsv();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !NewsletterRepository::subscribe($email)) {
            flash('error', 'Please enter a valid email address.');
        } else {
            flash('success', 'Subscriber added.');
        }
        redirect('/admin/newsletter.php');
    }
    if ($action === 'delete') {
        NewsletterRepository::delete((int) $_POST['id']);
        flash('success', 'Subscriber removed.');
    }
    redirect('/admin/newsletter.php');
}

$search = trim($_GET['q'] ?? '');
$subscribers = $search ? NewsletterRepository::search($search) : NewsletterRepository::all('active');
$mailchimpReady = NewsletterService::isConfigured();
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Newsletter Subscribers</h1>
<?php if (!$mailchimpReady): ?>
    <div class="alert alert-success">Collecting emails locally. Add Mailchimp keys in <a href="/admin/settings.php">Settings</a> to enable sync later.</div>
<?php endif; ?>

<div class="card">
    <h3>Add Subscriber Manually</h3>
    <p class="pb-hint" style="text-align:left;margin-bottom:1rem;">Add someone to your newsletter list who signed up in person or over the phone.</p>
    <form method="post" class="form-actions" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;margin-top:0;">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="add">
        <div style="flex:1;min-width:220px;">
            <label>Email address</label>
            <input type="email" name="email" required placeholder="name@example.com">
        </div>
        <button type="submit" class="btn btn-success">Add to List</button>
    </form>
</div>

<div class="form-actions" style="margin-bottom:1rem;">
    <a href="?export=csv" class="btn">Export CSV</a>
    <form method="get" style="display:flex;gap:0.5rem;">
        <input type="text" name="q" placeholder="Search email..." value="<?= e($search) ?>">
        <button class="btn btn-sm">Search</button>
    </form>
</div>
<table class="admin-table">
    <thead><tr><th>Email</th><th>Subscribed</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($subscribers)): ?>
        <tr><td colspan="3">No subscribers yet.</td></tr>
    <?php else: foreach ($subscribers as $s): ?>
        <tr>
            <td><?= e($s['email']) ?></td>
            <td><?= e($s['subscribed_at']) ?></td>
            <td>
                <form method="post" onsubmit="return confirm('Remove this email?')">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
