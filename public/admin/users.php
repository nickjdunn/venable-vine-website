<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireOwner();

$adminTitle = 'Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            UserRepository::create($_POST['email'], $_POST['name'], $_POST['password'], $_POST['role'] ?? 'editor');
            flash('success', 'User created.');
        } elseif ($action === 'update') {
            UserRepository::update((int) $_POST['user_id'], [
                'email' => $_POST['email'],
                'name' => $_POST['name'],
                'role' => $_POST['role'],
                'password' => $_POST['password'] ?? '',
            ]);
            flash('success', 'User updated.');
        } elseif ($action === 'delete') {
            $id = (int) $_POST['user_id'];
            $user = UserRepository::find($id);
            if ($user && $user['role'] === 'owner' && UserRepository::countOwners() <= 1) {
                throw new RuntimeException('Cannot delete the only owner account.');
            }
            if ($id === Auth::user()['id']) {
                throw new RuntimeException('Cannot delete your own account.');
            }
            UserRepository::delete($id);
            flash('success', 'User deleted.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/users.php');
}

$users = UserRepository::all();
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Admin Users</h1>
<div class="card">
    <h3>Add User</h3>
    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="create">
        <div class="form-row">
            <div><label>Name</label><input type="text" name="name" required></div>
            <div><label>Email</label><input type="email" name="email" required></div>
        </div>
        <label>Password</label>
        <input type="password" name="password" required minlength="8">
        <label>Role</label>
        <select name="role"><option value="editor">Editor</option><option value="owner">Owner</option></select>
        <button type="submit" class="btn">Add User</button>
    </form>
</div>
<h3>Existing Users</h3>
<?php foreach ($users as $u): ?>
    <div class="card">
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
            <div class="form-row">
                <div><label>Name</label><input type="text" name="name" value="<?= e($u['name']) ?>" required></div>
                <div><label>Email</label><input type="email" name="email" value="<?= e($u['email']) ?>" required></div>
                <div><label>Role</label><select name="role"><option value="editor"<?= $u['role']==='editor'?' selected':'' ?>>Editor</option><option value="owner"<?= $u['role']==='owner'?' selected':'' ?>>Owner</option></select></div>
            </div>
            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password" minlength="8">
            <div class="form-actions">
                <button type="submit" class="btn btn-sm">Save Changes</button>
                <?php if ($u['id'] !== Auth::user()['id']): ?>
                <button type="submit" class="btn btn-sm btn-danger" formaction="/admin/users.php" name="action" value="delete" onclick="return confirm('Delete this user?')">Delete</button>
                <?php endif; ?>
            </div>
            <small>Last login: <?= e($u['last_login'] ?? 'Never') ?></small>
        </form>
    </div>
<?php endforeach; ?>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
