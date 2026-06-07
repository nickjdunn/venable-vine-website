<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Reviews';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $rating = (int) ($_POST['rating'] ?? 5);
        $status = ($_POST['status'] ?? 'approved') === 'pending' ? 'pending' : 'approved';
        if ($name === '' || $text === '') {
            flash('error', 'Name and review text are required.');
        } else {
            ReviewRepository::create(compact('name', 'rating', 'text', 'status'));
            flash('success', 'Review added.');
        }
        redirect('/admin/reviews.php');
    }
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
    if ($action !== 'create') {
        flash('success', 'Review updated.');
        redirect('/admin/reviews.php');
    }
}

$pending = ReviewRepository::byStatus('pending');
$approved = ReviewRepository::byStatus('approved');
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Reviews</h1>

<div class="card">
    <h3>Add Review Manually</h3>
    <p class="pb-hint" style="text-align:left;margin-bottom:1rem;">Create a review to display on your homepage without waiting for a customer submission.</p>
    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="create">
        <label>Customer name</label>
        <input type="text" name="name" required placeholder="Jane D.">
        <label>Rating</label>
        <select name="rating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"<?= $i === 5 ? ' selected' : '' ?>><?= $i ?> star<?= $i === 1 ? '' : 's' ?></option>
            <?php endfor; ?>
        </select>
        <label>Review text</label>
        <textarea name="text" rows="4" required placeholder="What did they say about your food?"></textarea>
        <label>Status</label>
        <select name="status">
            <option value="approved" selected>Show on website (approved)</option>
            <option value="pending">Save as pending</option>
        </select>
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Add Review</button>
        </div>
    </form>
</div>

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
