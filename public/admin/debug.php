<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

Auth::requireLogin();
Auth::requireOwner();

$adminTitle = 'Debug';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    if ($action === 'clear_log') {
        Logger::clear();
        flash('success', 'Log cleared.');
        redirect('/admin/debug.php');
    }
}

$logLines = Logger::recent(300);
$tests = [];

try {
    Database::connection()->query('SELECT 1');
    $tests['database'] = ['ok' => true, 'message' => 'Connected'];
} catch (Throwable $e) {
    $tests['database'] = ['ok' => false, 'message' => $e->getMessage()];
    Logger::exception($e, ['context' => 'debug_page_db_test']);
}

try {
    PageRepository::ensureLayoutColumns();
    $page = PageRepository::getBySlug('home');
    $tests['home_page'] = ['ok' => (bool) $page, 'message' => $page ? 'Home page id ' . $page['id'] : 'Not found'];
    if ($page) {
        $desktop = PageRepository::getStoredLayout((int) $page['id'], 'desktop');
        $mobile = PageRepository::getStoredLayout((int) $page['id'], 'mobile');
        $tests['layout_desktop'] = ['ok' => true, 'message' => count($desktop['rows'] ?? []) . ' rows stored'];
        $tests['layout_mobile'] = ['ok' => true, 'message' => count($mobile['rows'] ?? []) . ' rows stored'];
    }
} catch (Throwable $e) {
    $tests['page_builder'] = ['ok' => false, 'message' => $e->getMessage()];
    Logger::exception($e, ['context' => 'debug_page_layout_test']);
}

$logWritable = is_writable(ROOT . '/logs') || (is_dir(ROOT . '/logs') === false && is_writable(ROOT));

require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Debug</h1>
<p class="pb-hint" style="text-align:left;">Errors from admin pages and API requests are logged here. Use this if something like the Page Builder appears frozen or blank.</p>

<div class="card">
    <h3>Environment</h3>
    <ul class="debug-list">
        <li><strong>PHP</strong> <?= e(PHP_VERSION) ?></li>
        <li><strong>ROOT</strong> <code><?= e(ROOT) ?></code></li>
        <li><strong>PUBLIC_ROOT</strong> <code><?= e(PUBLIC_ROOT) ?></code></li>
        <li><strong>Log file</strong> <code><?= e(ROOT . '/logs/app.log') ?></code></li>
        <li><strong>Log writable</strong> <?= $logWritable ? 'Yes' : '<span style="color:red">No — create ~/logs and chmod 755</span>' ?></li>
    </ul>
</div>

<div class="card">
    <h3>System checks</h3>
    <ul class="debug-list">
        <?php foreach ($tests as $name => $t): ?>
            <li class="<?= !empty($t['ok']) ? 'debug-ok' : 'debug-fail' ?>">
                <strong><?= e(str_replace('_', ' ', $name)) ?>:</strong> <?= e($t['message']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn btn-sm" id="test-page-builder-api">Test Page Builder API</button>
    <pre id="api-test-result" class="debug-pre" hidden></pre>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
        <h3 style="margin:0;">Recent log (last <?= count($logLines) ?> lines)</h3>
        <form method="post" onsubmit="return confirm('Clear the entire log?');">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="clear_log">
            <button type="submit" class="btn btn-sm btn-danger">Clear Log</button>
        </form>
    </div>
    <?php if (empty($logLines)): ?>
        <p>No log entries yet. Errors will appear here automatically.</p>
    <?php else: ?>
        <pre class="debug-log"><?php foreach ($logLines as $line): ?><?= e($line) . "\n" ?><?php endforeach; ?></pre>
    <?php endif; ?>
</div>

<script>
document.getElementById('test-page-builder-api')?.addEventListener('click', async () => {
    const out = document.getElementById('api-test-result');
    out.hidden = false;
    out.textContent = 'Loading…';
    try {
        const res = await fetch('/api/page-builder.php?action=get_builder_data', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const text = await res.text();
        out.textContent = 'HTTP ' + res.status + '\n\n' + text.slice(0, 4000);
    } catch (e) {
        out.textContent = 'Fetch failed: ' + e.message;
    }
});
</script>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
