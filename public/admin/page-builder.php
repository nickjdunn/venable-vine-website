<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Page Builder';
$page = PageRepository::getBySlug('home');
if (!$page) {
    flash('error', 'Home page not found. Import sql/schema.sql first.');
    redirect('/admin/dashboard.php');
}

$sections = PageRepository::getSections((int) $page['id']);
$formatted = array_map(function ($s) {
    return [
        'id' => (int) $s['id'],
        'section_type' => $s['section_type'],
        'sort_order' => (int) $s['sort_order'],
        'is_active' => (bool) $s['is_active'],
        'config' => parse_json_config($s['config'] ?? null),
        'label' => section_types()[$s['section_type']] ?? $s['section_type'],
    ];
}, $sections);

$extraAdminJs = [
    'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
    asset('js/page-builder.js'),
];
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Page Builder</h1>
<p>Drag sections to reorder. Click a section to edit its content. Toggle active to hide without deleting.</p>
<div class="builder-layout">
    <aside class="builder-library">
        <h3>Add Section</h3>
        <ul class="builder-section-types">
            <?php foreach (section_types() as $type => $label): ?>
                <li><button type="button" class="btn btn-sm btn-outline" data-add-section="<?= e($type) ?>">+ <?= e($label) ?></button></li>
            <?php endforeach; ?>
        </ul>
    </aside>
    <div class="builder-canvas-wrap">
        <div class="card-header">
            <strong>Homepage Sections</strong>
            <button type="button" id="save-page-btn" class="btn btn-success">Save Page</button>
        </div>
        <div id="builder-canvas" class="builder-canvas"></div>
        <div id="builder-status" style="margin-top:0.75rem;"></div>
    </div>
    <aside class="builder-editor" id="builder-editor">
        <p class="builder-preview-note">Select a section to edit</p>
    </aside>
</div>
<script>window.BUILDER_SECTIONS = <?= json_encode($formatted, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
