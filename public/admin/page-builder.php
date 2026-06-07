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
$pbLayout = normalize_homepage_layout(PageRepository::getLayout($pageId, 'desktop'));

$sectionSettings = [];
foreach (homepage_module_types() as $type) {
    $settings = section_editor_settings($type);
    if ($settings) {
        $sectionSettings[$type] = $settings;
    }
}

$editorBootstrap = [
    'success' => true,
    'layout' => $pbLayout,
    'sectionSettings' => $sectionSettings,
    'sectionLabels' => section_editor_labels(),
];

agent_debug_log('C', 'page-builder.php:embed', 'EDITOR_INITIAL prepared', [
    'rows' => count($pbLayout['rows'] ?? []),
]);

$adminBodyClass = 'se-editor-page';
$extraAdminCss = [
    asset('css/style.css'),
    asset('css/section-editor.css'),
];
$extraAdminJs = [
    'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
    asset('js/section-editor.js'),
];
require ROOT . '/includes/templates/admin-header.php';
?>
<div class="se-header">
    <div>
        <h1>Page Builder</h1>
        <p class="se-subtitle">Edit your homepage directly — click text or photos to change them. Drag section handles to reorder. Hidden sections stay saved but won't appear on the live site.</p>
    </div>
    <div class="se-header-actions">
        <a href="/" target="_blank" class="btn btn-outline btn-sm">View Live Site</a>
        <button type="button" id="reset-layout-btn" class="btn btn-outline btn-sm">Reset to Defaults</button>
        <button type="button" id="save-layout-btn" class="btn btn-success">Save Page</button>
    </div>
</div>

<div class="se-editor-wrap">
    <?php render_homepage_editor($pbLayout); ?>
</div>
<div id="se-status"></div>

<script>window.EDITOR_INITIAL = <?= json_encode($editorBootstrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;</script>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
