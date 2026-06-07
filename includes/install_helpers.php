<?php

function install_is_locked(): bool
{
    return file_exists(ROOT . '/config/.installed');
}

function install_has_database_config(): bool
{
    return file_exists(ROOT . '/config/database.php');
}

function install_test_connection(array $config): void
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['dbname'],
        $config['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->query('SELECT 1');
}

function install_write_database_config(array $config): void
{
    $path = ROOT . '/config/database.php';
    $content = "<?php\n\nreturn " . var_export([
        'host' => $config['host'],
        'dbname' => $config['dbname'],
        'username' => $config['username'],
        'password' => $config['password'],
        'charset' => $config['charset'] ?? 'utf8mb4',
    ], true) . ";\n";
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Could not write config/database.php — check folder permissions.');
    }
}

function install_tables_exist(): bool
{
    try {
        $pdo = Database::connection();
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        return (bool) $stmt->fetch();
    } catch (Throwable) {
        return false;
    }
}

function install_run_schema(): void
{
    $schemaPath = ROOT . '/sql/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new RuntimeException('Missing sql/schema.sql');
    }
    $sql = file_get_contents($schemaPath);
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $pdo = Database::connection();
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

function install_admin_exists(): bool
{
    try {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function install_current_step(): string
{
    if (install_is_locked()) {
        return 'done';
    }
    if (!install_has_database_config()) {
        return 'database';
    }
    if (!install_tables_exist()) {
        return 'schema';
    }
    if (!install_admin_exists()) {
        return 'admin';
    }
    return 'finish';
}

function install_uploads_writable(): bool
{
    $dir = PUBLIC_ROOT . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return is_writable($dir);
}

function install_render_layout(string $title, string $body, int $stepNum, int $totalSteps): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> | Venable & Vine Setup</title>
        <link rel="stylesheet" href="/assets/css/admin.css">
        <style>
            .install-wrap { max-width: 620px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
            .install-steps { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
            .install-step { flex: 1; min-width: 100px; text-align: center; padding: 0.5rem; border-radius: 6px; background: #f3f4f6; font-size: 0.85rem; }
            .install-step.active { background: #fdf6e8; color: #D97706; font-weight: 700; }
            .install-step.done { background: #dcfce7; color: #166534; }
            .help-box { background: #fdf6e8; padding: 1rem; border-radius: 6px; margin: 1rem 0; font-size: 0.9rem; }
            code { background: #f3f4f6; padding: 0.1rem 0.35rem; border-radius: 3px; }
        </style>
    </head>
    <body class="admin-body">
    <div class="install-wrap">
        <h1>Venable & Vine Setup</h1>
        <div class="install-steps">
            <?php
            $labels = ['Database', 'Tables', 'Admin', 'Done'];
            for ($i = 1; $i <= $totalSteps; $i++):
                $cls = $i < $stepNum ? 'done' : ($i === $stepNum ? 'active' : '');
            ?>
                <div class="install-step <?= $cls ?>">Step <?= $i ?>: <?= $labels[$i - 1] ?? '' ?></div>
            <?php endfor; ?>
        </div>
        <?= $body ?>
    </div>
    </body>
    </html>
    <?php
}
