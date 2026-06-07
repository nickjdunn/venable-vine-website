<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Page Builder';
$page = PageRepository::getBySlug('home');
if (!$page) {
    flash('error', 'Home page not found.');
    redirect('/admin/dashboard.php');
}

$extraAdminCss = [asset('css/page-builder.css')];
$extraAdminJs = [
    'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
    asset('js/page-builder.js'),
];
require ROOT . '/includes/templates/admin-header.php';
?>
<div class="pb-header">
    <div>
        <h1>Page Builder</h1>
        <p class="pb-subtitle">Build your homepage with drag-and-drop blocks. Use <strong>Desktop</strong> and <strong>Mobile</strong> tabs for different layouts.</p>
    </div>
    <button type="button" id="save-layout-btn" class="btn btn-success">Save Page</button>
</div>

<div class="pb-viewport-tabs">
    <button type="button" class="pb-viewport-tab active" data-viewport="desktop">Desktop</button>
    <button type="button" class="pb-viewport-tab" data-viewport="mobile">Mobile</button>
</div>

<div class="pb-layout">
    <aside class="pb-palette">
        <h3>Basic Blocks</h3>
        <div id="palette-basic" class="pb-palette-list"></div>
        <h3>Modules</h3>
        <div id="palette-modules" class="pb-palette-list"></div>
        <hr>
        <button type="button" id="add-row-btn" class="btn btn-sm btn-outline" style="width:100%">+ Add Row (3 columns)</button>
    </aside>

    <main class="pb-canvas-wrap">
        <div id="pb-canvas" class="pb-canvas"></div>
        <div id="pb-status"></div>
    </main>

    <aside class="pb-inspector" id="pb-inspector">
        <p class="pb-hint">Click a block to edit it here.</p>
    </aside>
</div>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
