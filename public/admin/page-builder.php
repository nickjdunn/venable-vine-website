<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Page Builder';
$page = PageRepository::getBySlug('home');
if (!$page) {
    flash('error', 'Home page not found.');
    redirect('/admin/dashboard.php');
}

$pageId = (int) $page['id'];
PageRepository::ensureLayoutsPersisted($pageId);
$pbLayout = normalize_layout(PageRepository::getLayout($pageId, 'desktop'));
$pbBootstrap = [
    'success' => true,
    'layout' => $pbLayout,
    'layout_desktop' => $pbLayout,
    'block_types' => block_types(),
    'categories' => MenuRepository::categories(false),
    'gallery' => MediaRepository::all(),
];
// #region agent log
agent_debug_log('C', 'page-builder.php:embed', 'PB_INITIAL prepared', [
    'rows' => count($pbLayout['rows'] ?? []),
    'modules' => count(block_types()['modules'] ?? []),
    'basic' => count(block_types()['basic'] ?? []),
]);
// #endregion

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
        <p class="pb-subtitle">Build your single-page homepage. Drag sections to reorder — Hero, Menu, Story, Gallery, and more. The live site uses one scrollable page that adapts to mobile automatically.</p>
    </div>
    <div class="pb-header-actions">
        <a href="/" target="_blank" class="btn btn-outline btn-sm">View Live Site</a>
        <button type="button" id="reset-layout-btn" class="btn btn-outline btn-sm">Reset to Defaults</button>
        <button type="button" id="save-layout-btn" class="btn btn-success">Save Page</button>
    </div>
</div>

<div class="pb-layout">
    <aside class="pb-palette">
        <h3>Page Sections</h3>
        <p class="pb-palette-hint">Click to add a section to your homepage</p>
        <div id="palette-modules" class="pb-palette-list"></div>
        <hr>
        <h3>Basic Blocks</h3>
        <div id="palette-basic" class="pb-palette-list"></div>
        <hr>
        <button type="button" id="add-column-row-2-btn" class="btn btn-sm btn-outline" style="width:100%;margin-bottom:0.35rem">+ Add 2-Column Row</button>
        <button type="button" id="add-column-row-btn" class="btn btn-sm btn-outline" style="width:100%">+ Add 3-Column Row</button>
    </aside>

    <main class="pb-canvas-wrap" id="pb-canvas-wrap">
        <div id="pb-canvas" class="pb-canvas"><p class="pb-hint">Loading page builder…</p></div>
        <div id="pb-status"></div>
    </main>

    <aside class="pb-inspector" id="pb-inspector">
        <p class="pb-hint">Click a section or block to edit it here.</p>
    </aside>
</div>
<script>window.PB_INITIAL = <?= json_encode($pbBootstrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;</script>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
