<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (Auth::check()) {
    redirect('/admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    if (Auth::attempt($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        redirect('/admin/dashboard.php');
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Venable & Vine</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <style>
        .login-wrap { max-width: 420px; margin: 4rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .login-wrap h1 { text-align: center; margin-bottom: 1.5rem; }
    </style>
</head>
<body class="admin-body">
<div class="login-wrap">
    <h1>V&V Admin</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <label>Email</label>
        <input type="email" name="email" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <div class="form-actions">
            <button type="submit" class="btn">Log In</button>
        </div>
    </form>
</div>
</body>
</html>
