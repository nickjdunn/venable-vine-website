<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Media Library';
MediaRepository::syncFromDisk();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    if ($action === 'reorder') {
        MediaRepository::reorder(json_decode($_POST['order'] ?? '[]', true) ?: []);
        json_response(['success' => true]);
    }
    try {
        if ($action === 'upload') {
            MediaRepository::upload($_FILES['photo'], [
                'display_name' => trim($_POST['display_name'] ?? '') ?: null,
                'alt_text' => trim($_POST['alt_text'] ?? '') ?: null,
                'caption' => trim($_POST['caption'] ?? '') ?: null,
                'title' => trim($_POST['title'] ?? '') ?: null,
            ]);
            flash('success', 'Image uploaded to media library.');
        } elseif ($action === 'update') {
            MediaRepository::update((int) $_POST['id'], [
                'display_name' => trim($_POST['display_name'] ?? ''),
                'alt_text' => trim($_POST['alt_text'] ?? ''),
                'caption' => trim($_POST['caption'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']),
            ]);
            flash('success', 'Image details saved.');
        } elseif ($action === 'delete') {
            MediaRepository::delete((int) $_POST['id']);
            flash('success', 'Image deleted.');
        } elseif ($action === 'sync') {
            $count = MediaRepository::syncFromDisk();
            flash('success', $count ? "Imported {$count} image(s) from assets/images/." : 'All folder images are already in the library.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    if ($action !== 'reorder') {
        redirect('/admin/gallery.php' . (!empty($_POST['id']) ? '?edit=' . (int) $_POST['id'] : ''));
    }
}

$images = MediaRepository::all();
$editId = (int) ($_GET['edit'] ?? 0);
$editItem = $editId ? MediaRepository::find($editId) : null;

require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Media Library</h1>
<p class="pb-hint">All site images live in <code>assets/images/</code>. Use them in the page builder, settings, menu, and photo gallery.</p>

<div class="card">
    <h3>Upload Image</h3>
    <form method="post" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="upload">
        <label>Image file *</label>
        <input type="file" name="photo" accept="image/*" required>
        <label>Display name</label>
        <input type="text" name="display_name" placeholder="Friendly name shown in pickers">
        <label>Alt text (accessibility)</label>
        <input type="text" name="alt_text" placeholder="Describe the image">
        <label>Caption</label>
        <input type="text" name="caption" placeholder="Optional caption for gallery">
        <label>Title</label>
        <input type="text" name="title" placeholder="Optional title attribute">
        <button type="submit" class="btn">Upload to Media Library</button>
    </form>
    <form method="post" style="margin-top:1rem;">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="sync">
        <button type="submit" class="btn btn-outline btn-sm">Sync images from assets/images/ folder</button>
    </form>
</div>

<div class="card">
    <h3>All Images</h3>
    <div class="media-library-grid" id="media-library-grid">
        <?php foreach ($images as $img): ?>
            <a href="/admin/gallery.php?edit=<?= (int) $img['id'] ?>" class="media-library-card<?= !$img['is_active'] ? ' inactive' : '' ?><?= $editId === $img['id'] ? ' selected' : '' ?>">
                <img src="<?= e($img['url']) ?>" alt="<?= e($img['alt_text']) ?>">
                <div class="media-card-name"><?= e($img['display_name']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if (empty($images)): ?>
        <p>No images yet. Upload above or sync from the server folder.</p>
    <?php endif; ?>
</div>

<?php if ($editItem): ?>
<div class="card media-edit-panel">
    <h3>Edit Image</h3>
    <img src="<?= e($editItem['url']) ?>" class="item-thumb" alt="">
    <p><code><?= e($editItem['file_path']) ?></code></p>
    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= (int) $editItem['id'] ?>">
        <label>Display name</label>
        <input type="text" name="display_name" value="<?= e($editItem['display_name']) ?>">
        <label>Alt text</label>
        <input type="text" name="alt_text" value="<?= e($editItem['alt_text']) ?>">
        <label>Caption</label>
        <input type="text" name="caption" value="<?= e($editItem['caption']) ?>">
        <label>Title</label>
        <input type="text" name="title" value="<?= e($editItem['title']) ?>">
        <label>Sort order</label>
        <input type="number" name="sort_order" value="<?= (int) $editItem['sort_order'] ?>">
        <div class="checkbox-row"><label><input type="checkbox" name="is_active"<?= $editItem['is_active'] ? ' checked' : '' ?>> Show in site gallery block</label></div>
        <div class="form-actions">
            <button type="submit" class="btn">Save Details</button>
            <a href="/admin/gallery.php" class="btn btn-muted">Close</a>
        </div>
    </form>
    <form method="post" onsubmit="return confirm('Delete this image permanently? It may break pages that use it.');" style="margin-top:1rem;">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int) $editItem['id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm">Delete Image</button>
    </form>
</div>
<?php endif; ?>

<h3>Gallery order (drag to reorder — affects photo gallery on site)</h3>
<ul class="sortable-list" id="gallery-sort">
    <?php foreach ($images as $img): ?>
        <li class="sortable-item" data-id="<?= (int) $img['id'] ?>">
            <span class="sortable-handle">☰</span>
            <img src="<?= e($img['url']) ?>" class="item-thumb" alt="">
            <span style="flex:1;"><?= e($img['display_name']) ?><?= !$img['is_active'] ? ' (hidden from gallery)' : '' ?></span>
            <a href="/admin/gallery.php?edit=<?= (int) $img['id'] ?>" class="btn btn-sm">Edit</a>
        </li>
    <?php endforeach; ?>
</ul>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const gSort = document.getElementById('gallery-sort');
if (gSort) Sortable.create(gSort, { handle: '.sortable-handle', onEnd: () => {
    const order = [...gSort.querySelectorAll('[data-id]')].map(el => el.dataset.id);
    fetch('/admin/gallery.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ action: 'reorder', order: JSON.stringify(order), _csrf: window.CSRF_TOKEN }) });
}});
</script>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
