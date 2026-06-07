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
        <p class="pb-subtitle">Drag sections to reorder your homepage. Each block matches a section on the live site. Use <strong>Desktop</strong> and <strong>Mobile</strong> tabs for separate layouts.</p>
    </div>
    <div class="pb-header-actions">
        <a href="/" target="_blank" class="btn btn-outline btn-sm">View Live Site</a>
        <button type="button" id="reset-mobile-btn" class="btn btn-outline btn-sm" title="Stack all desktop sections vertically for mobile">Reset Mobile from Desktop</button>
        <button type="button" id="save-layout-btn" class="btn btn-success">Save Page</button>
    </div>
</div>

<div class="pb-viewport-tabs">
    <button type="button" class="pb-viewport-tab active" data-viewport="desktop">Desktop</button>
    <button type="button" class="pb-viewport-tab" data-viewport="mobile">Mobile</button>
</div>
<div id="pb-viewport-notice" class="pb-viewport-notice" hidden></div>

<div class="pb-layout">
    <aside class="pb-palette">
        <h3>Page Sections</h3>
        <p class="pb-palette-hint">Click to add a full-width section</p>
        <div id="palette-modules" class="pb-palette-list"></div>
        <hr>
        <h3>Basic Blocks</h3>
        <div id="palette-basic" class="pb-palette-list"></div>
        <hr>
        <button type="button" id="add-column-row-btn" class="btn btn-sm btn-outline" style="width:100%">+ Add 3-Column Row</button>
    </aside>

    <main class="pb-canvas-wrap" id="pb-canvas-wrap">
        <div id="pb-canvas" class="pb-canvas"></div>
        <div id="pb-status"></div>
    </main>

    <aside class="pb-inspector" id="pb-inspector">
        <p class="pb-hint">Click a section or block to edit it here.</p>
    </aside>
</div>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
