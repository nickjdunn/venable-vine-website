<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Contacts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $id = (int) ($_POST['id'] ?? 0);
    match ($_POST['action'] ?? '') {
        'read' => ContactRepository::setStatus($id, 'read'),
        'unread' => ContactRepository::setStatus($id, 'new'),
        'delete' => ContactRepository::delete($id),
        default => null,
    };
    flash('success', 'Updated.');
    redirect('/admin/contacts.php');
}

$new = ContactRepository::byStatus('new');
$read = ContactRepository::byStatus('read');
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Contact Messages</h1>
<h3>New</h3>
<?php if (empty($new)): ?><p>No new messages.</p><?php else: foreach ($new as $c): ?>
    <div class="card">
        <strong><?= e($c['name']) ?></strong> &lt;<a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>&gt;<br>
        <small><?= e($c['created_at']) ?></small>
        <p><?= nl2br(e($c['message'])) ?></p>
        <div class="form-actions">
            <form method="post"><?= Csrf::field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm">Mark Read</button></form>
            <form method="post" onsubmit="return confirm('Delete?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
        </div>
    </div>
<?php endforeach; endif; ?>
<h3 style="margin-top:2rem;">Read</h3>
<?php if (empty($read)): ?><p>No read messages.</p><?php else: foreach ($read as $c): ?>
    <div class="card" style="opacity:0.9;">
        <strong><?= e($c['name']) ?></strong> — <?= e($c['email']) ?><br>
        <p><?= nl2br(e($c['message'])) ?></p>
        <form method="post"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
    </div>
<?php endforeach; endif; ?>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
