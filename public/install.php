<?php
/**
 * Web installer — run once, then DELETE this file from production.
 * Steps: 1) Database credentials  2) Create tables  3) Admin account
 */
define('ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', ROOT . '/public');

require_once ROOT . '/includes/helpers.php';
require_once ROOT . '/includes/install_helpers.php';

session_start();

$lockFile = ROOT . '/config/.installed';
$error = '';

// Load app classes only when database config exists
if (install_has_database_config()) {
    require_once ROOT . '/includes/Database.php';
    require_once ROOT . '/includes/Auth.php';
    require_once ROOT . '/includes/Csrf.php';
    require_once ROOT . '/includes/repositories/UserRepository.php';
}

$step = $_GET['step'] ?? install_current_step();

if (install_is_locked() && $step !== 'done') {
    install_render_layout('Already Installed', '
        <div class="alert alert-success">This site is already installed.</div>
        <p><a href="/admin/login.php" class="btn">Go to Admin Login</a></p>
        <p style="margin-top:1rem;font-size:0.9rem;">To re-run setup, delete <code>config/.installed</code> on the server.</p>
    ', 4, 4);
    exit;
}

// --- POST handlers ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($step === 'database') {
            $config = [
                'host' => trim($_POST['host'] ?? 'localhost'),
                'dbname' => trim($_POST['dbname'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'charset' => 'utf8mb4',
            ];
            if (!$config['dbname'] || !$config['username']) {
                throw new RuntimeException('Database name and username are required.');
            }
            install_test_connection($config);
            install_write_database_config($config);

            // Load Database class now that config exists
            require_once ROOT . '/includes/Database.php';
            header('Location: /install.php?step=schema');
            exit;
        }

        if ($step === 'schema') {
            require_once ROOT . '/includes/Database.php';
            install_run_schema();
            if (!install_uploads_writable()) {
                throw new RuntimeException('Could not write to public/uploads/ — set folder permissions to 755 or 775.');
            }
            header('Location: /install.php?step=admin');
            exit;
        }

        if ($step === 'admin') {
            require_once ROOT . '/includes/Database.php';
            require_once ROOT . '/includes/repositories/UserRepository.php';

            $email = trim($_POST['email'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $password = $_POST['password'] ?? '';
            if (strlen($password) < 8) {
                throw new RuntimeException('Password must be at least 8 characters.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email.');
            }
            UserRepository::create($email, $name, $password, 'owner');
            file_put_contents($lockFile, date('c'));
            header('Location: /install.php?step=done');
            exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Auto-skip steps that are already complete
if ($step === 'database' && install_has_database_config()) {
    header('Location: /install.php?step=schema');
    exit;
}
if ($step === 'schema' && install_tables_exist()) {
    header('Location: /install.php?step=admin');
    exit;
}
if ($step === 'admin' && install_admin_exists() && !install_is_locked()) {
    file_put_contents($lockFile, date('c'));
    header('Location: /install.php?step=done');
    exit;
}

// --- Render steps ---

ob_start();

if ($step === 'database') {
    ?>
    <h2>Step 1: Connect to MySQL</h2>
    <div class="help-box">
        <strong>In cPanel:</strong> Go to <em>MySQL Databases</em> → create a database and user → add user to database with ALL PRIVILEGES.
        Use the full names cPanel shows (often like <code>cpaneluser_venable</code>).
    </div>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <label>Database Host</label>
        <input type="text" name="host" value="<?= htmlspecialchars($_POST['host'] ?? 'localhost') ?>" required>
        <label>Database Name</label>
        <input type="text" name="dbname" value="<?= htmlspecialchars($_POST['dbname'] ?? '') ?>" placeholder="cpaneluser_venable_vine" required>
        <label>Database Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="cpaneluser_dbuser" required>
        <label>Database Password</label>
        <input type="password" name="password" value="">
        <p style="font-size:0.85rem;color:#666;margin-top:0.5rem;">This writes <code>config/database.php</code> on your server (never commit that file to Git).</p>
        <div class="form-actions"><button type="submit" class="btn">Test Connection &amp; Save</button></div>
    </form>
    <?php
    $body = ob_get_clean();
    install_render_layout('Database', $body, 1, 4);
    exit;
}

if ($step === 'schema') {
    ?>
    <h2>Step 2: Create Database Tables</h2>
    <p>This will import the site structure (menu, events, pages, admin, etc.) into your database.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if (install_tables_exist()): ?>
        <div class="alert alert-success">Tables already exist — you can continue.</div>
        <a href="/install.php?step=admin" class="btn">Continue to Admin Setup</a>
    <?php else: ?>
        <form method="post">
            <div class="form-actions"><button type="submit" class="btn">Create Tables Now</button></div>
        </form>
    <?php endif; ?>
    <?php
    $body = ob_get_clean();
    install_render_layout('Tables', $body, 2, 4);
    exit;
}

if ($step === 'admin') {
    require_once ROOT . '/includes/Database.php';
    ?>
    <h2>Step 3: Create Admin Account</h2>
    <p>This is the login your sister will use to manage the website.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <label>Admin Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="e.g. Sarah" required>
        <label>Admin Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <label>Password (min 8 characters)</label>
        <input type="password" name="password" required minlength="8">
        <div class="form-actions"><button type="submit" class="btn">Create Account &amp; Finish</button></div>
    </form>
    <?php
    $body = ob_get_clean();
    install_render_layout('Admin', $body, 3, 4);
    exit;
}

if ($step === 'done') {
    ?>
    <h2>Setup Complete!</h2>
    <div class="alert alert-success">Your site is ready.</div>
    <ol>
        <li><strong>Delete</strong> <code>public/install.php</code> from your server now (security).</li>
        <li>Log in to the admin panel and upload your logo in <strong>Settings</strong>.</li>
        <li>Add your first event in <strong>Events</strong> so customers know where to find the truck.</li>
    </ol>
    <div class="form-actions">
        <a href="/admin/login.php" class="btn">Go to Admin Login</a>
        <a href="/" class="btn btn-outline" target="_blank">View Website</a>
    </div>
    <?php
    $body = ob_get_clean();
    install_render_layout('Done', $body, 4, 4);
    exit;
}

// Fallback
header('Location: /install.php?step=database');
