<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Reviews';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'approve') ReviewRepository::setStatus($id, 'approved');
    elseif ($action === 'unapprove') ReviewRepository::setStatus($id, 'pending');
    elseif ($action === 'delete') ReviewRepository::delete($id);
    elseif ($action === 'edit') {
        ReviewRepository::update($id, [
            'name' => $_POST['name'],
            'rating' => (int) $_POST['rating'],
            'text' => $_POST['text'],
            'status' => $_POST['status'],
        ]);
    }
    flash('success', 'Review updated.');
    redirect('/admin/reviews.php');
}

$pending = ReviewRepository::byStatus('pending');
$approved = ReviewRepository::byStatus('approved');
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Reviews</h1>
<h3>Pending</h3>
<?php if (empty($pending)): ?><p>No pending reviews.</p><?php else: foreach ($pending as $r): ?>
    <div class="card">
        <strong><?= e($r['name']) ?></strong> <?= str_repeat('★', (int)$r['rating']) ?><br>
        <em><?= e($r['text']) ?></em>
        <div class="form-actions" style="margin-top:0.75rem;">
            <form method="post"><?= Csrf::field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-success">Approve</button></form>
            <form method="post" onsubmit="return confirm('Delete?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
        </div>
    </div>
<?php endforeach; endif; ?>
<h3 style="margin-top:2rem;">Approved</h3>
<?php if (empty($approved)): ?><p>No approved reviews.</p><?php else: foreach ($approved as $r): ?>
    <div class="card">
        <strong><?= e($r['name']) ?></strong> <?= str_repeat('★', (int)$r['rating']) ?><br>
        <em><?= e($r['text']) ?></em>
        <div class="form-actions" style="margin-top:0.75rem;">
            <form method="post"><?= Csrf::field() ?><input type="hidden" name="action" value="unapprove"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm">Move to Pending</button></form>
            <form method="post" onsubmit="return confirm('Delete?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
        </div>
    </div>
<?php endforeach; endif; ?>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
