<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Media Library';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'upload') {
            $uploaded = 0;
            if (!empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
                $items = MediaRepository::uploadMultiple($_FILES['photos']);
                $uploaded = count($items);
            } elseif (!empty($_FILES['photo'])) {
                MediaRepository::upload($_FILES['photo'], [
                    'display_name' => trim($_POST['display_name'] ?? '') ?: null,
                    'alt_text' => trim($_POST['alt_text'] ?? '') ?: null,
                    'caption' => trim($_POST['caption'] ?? '') ?: null,
                    'title' => trim($_POST['title'] ?? '') ?: null,
                ]);
                $uploaded = 1;
            }
            if ($uploaded === 0) {
                throw new RuntimeException('No files were uploaded.');
            }
            flash('success', $uploaded === 1 ? 'Image uploaded.' : "{$uploaded} images uploaded.");
        } elseif ($action === 'update') {
            MediaRepository::update((int) $_POST['id'], [
                'display_name' => trim($_POST['display_name'] ?? ''),
                'alt_text' => trim($_POST['alt_text'] ?? ''),
                'caption' => trim($_POST['caption'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => true,
            ]);
            flash('success', 'Image details saved.');
        } elseif ($action === 'delete') {
            MediaRepository::delete((int) $_POST['id']);
            flash('success', 'Image deleted.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/gallery.php' . (!empty($_POST['id']) ? '?edit=' . (int) $_POST['id'] : ''));
}

$images = MediaRepository::all();
$editId = (int) ($_GET['edit'] ?? 0);
$editItem = $editId ? MediaRepository::find($editId) : null;

require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Media Library</h1>
<p class="pb-hint" style="text-align:left;">Upload and manage image files for use across the site (logo, hero, menu items, page builder, etc.). Homepage gallery photos are chosen separately in <a href="/admin/page-builder.php">Page Builder</a>.</p>

<div class="card">
    <h3>Upload Photos</h3>
    <form method="post" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="upload">
        <label>Image files (select one or many)</label>
        <input type="file" name="photos[]" accept="image/*" multiple required>
        <p class="pb-hint" style="text-align:left;margin:0.5rem 0 1rem;">Optional details apply when uploading a single file only.</p>
        <label>Display name</label>
        <input type="text" name="display_name" placeholder="Friendly name shown in pickers">
        <label>Alt text (accessibility)</label>
        <input type="text" name="alt_text" placeholder="Describe the image">
        <label>Caption</label>
        <input type="text" name="caption" placeholder="Optional caption">
        <label>Title</label>
        <input type="text" name="title" placeholder="Optional title attribute">
        <button type="submit" class="btn">Upload to Media Library</button>
    </form>
</div>

<div class="card">
    <h3>All Images</h3>
    <div class="media-library-grid" id="media-library-grid">
        <?php foreach ($images as $img): ?>
            <a href="/admin/gallery.php?edit=<?= (int) $img['id'] ?>" class="media-library-card<?= $editId === $img['id'] ? ' selected' : '' ?>">
                <img src="<?= e($img['url']) ?>" alt="<?= e($img['alt_text']) ?>">
                <div class="media-card-name"><?= e($img['display_name']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if (empty($images)): ?>
        <p>No images yet. Upload above to add photos.</p>
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

<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
