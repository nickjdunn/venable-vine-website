<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Gallery';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    if ($action === 'reorder') {
        GalleryRepository::reorder(json_decode($_POST['order'] ?? '[]', true) ?: []);
        json_response(['success' => true]);
    }
    try {
        if ($action === 'upload') {
            $path = Upload::image($_FILES['photo'], 'gallery');
            GalleryRepository::create($path, $_POST['caption'] ?? null);
            flash('success', 'Photo uploaded.');
        } elseif ($action === 'delete') {
            GalleryRepository::delete((int) $_POST['id']);
            flash('success', 'Photo deleted.');
        } elseif ($action === 'toggle') {
            $img = GalleryRepository::find((int) $_POST['id']);
            if ($img) GalleryRepository::update((int) $_POST['id'], ['caption' => $img['caption'], 'sort_order' => $img['sort_order'], 'is_active' => !$img['is_active']]);
            flash('success', 'Updated.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    if ($action !== 'reorder') redirect('/admin/gallery.php');
}

$images = GalleryRepository::all();
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Gallery</h1>
<div class="card">
    <h3>Upload Photo</h3>
    <form method="post" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="upload">
        <label>Photo</label>
        <input type="file" name="photo" accept="image/*" required>
        <label>Caption (optional)</label>
        <input type="text" name="caption">
        <button type="submit" class="btn">Upload</button>
    </form>
</div>
<h3>Photos (drag to reorder)</h3>
<ul class="sortable-list" id="gallery-sort">
    <?php foreach ($images as $img): ?>
        <li class="sortable-item" data-id="<?= (int)$img['id'] ?>">
            <span class="sortable-handle">☰</span>
            <img src="<?= e(upload_url($img['file_path'])) ?>" class="item-thumb" alt="">
            <span style="flex:1;"><?= e($img['caption'] ?: 'No caption') ?> <?= !$img['is_active'] ? '(inactive)' : '' ?></span>
            <form method="post" style="display:inline"><?= Csrf::field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$img['id'] ?>"><button class="btn btn-sm"><?= $img['is_active'] ? 'Hide' : 'Show' ?></button></form>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$img['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
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
