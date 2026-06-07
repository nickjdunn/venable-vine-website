<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Menu';
$tags = dietary_tags();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';

    if ($action === 'reorder_categories') {
        MenuRepository::reorderCategories(json_decode($_POST['order'] ?? '[]', true) ?: []);
        json_response(['success' => true]);
    }

    try {
        if ($action === 'save_category') {
            $data = [
                'name' => $_POST['name'],
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']),
            ];
            if (!empty($_POST['category_id'])) {
                MenuRepository::updateCategory((int) $_POST['category_id'], $data);
            } else {
                MenuRepository::createCategory($data['name']);
            }
            flash('success', 'Category saved.');
        } elseif ($action === 'delete_category') {
            MenuRepository::deleteCategory((int) $_POST['category_id']);
            flash('success', 'Category deleted.');
        } elseif ($action === 'save_item') {
            $photoPath = null;
            $itemId = (int) ($_POST['item_id'] ?? 0);
            if ($itemId) {
                $existing = MenuRepository::item($itemId);
                $photoPath = $existing['photo_path'] ?? null;
            }
            if (!empty($_POST['photo_path'])) {
                $photoPath = resolve_image_path(trim($_POST['photo_path']));
            } elseif (isset($_POST['photo_path']) && $_POST['photo_path'] === '') {
                $photoPath = null;
            }
            $selectedTags = [];
            foreach (array_keys($tags) as $key) {
                if (!empty($_POST['tag_' . $key])) $selectedTags[] = $key;
            }
            $data = [
                'category_id' => (int) $_POST['category_id'],
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? null,
                'price_note' => $_POST['price_note'] ?? null,
                'photo_path' => $photoPath,
                'dietary_tags' => $selectedTags,
                'is_featured' => isset($_POST['is_featured']),
                'is_active' => isset($_POST['is_active']),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            ];
            if ($itemId) {
                MenuRepository::updateItem($itemId, $data);
            } else {
                MenuRepository::createItem($data);
            }
            flash('success', 'Menu item saved.');
        } elseif ($action === 'delete_item') {
            $item = MenuRepository::item((int) $_POST['item_id']);
            if ($item) Upload::delete($item['photo_path']);
            MenuRepository::deleteItem((int) $_POST['item_id']);
            flash('success', 'Item deleted.');
        } elseif ($action === 'toggle_item') {
            $item = MenuRepository::item((int) $_POST['item_id']);
            if ($item) {
                MenuRepository::updateItem((int) $_POST['item_id'], array_merge($item, [
                    'is_active' => !$item['is_active'],
                    'dietary_tags' => $item['dietary_tags'],
                ]));
            }
            flash('success', 'Item updated.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/menu.php' . (!empty($_POST['item_id']) ? '?edit=' . (int)$_POST['item_id'] : ''));
}

$categories = MenuRepository::categories();
$items = MenuRepository::items();
$editItem = null;
if (!empty($_GET['edit'])) {
    $editItem = MenuRepository::item((int) $_GET['edit']);
}

require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Menu Management</h1>

<div data-tabs>
    <div class="tabs">
        <button type="button" class="tab-btn active" data-tab="tab-items">Menu Items</button>
        <button type="button" class="tab-btn" data-tab="tab-categories">Categories</button>
    </div>

    <div id="tab-items" class="tab-panel active">
        <div class="card">
            <h3><?= $editItem ? 'Edit Item' : 'Add Menu Item' ?></h3>
            <form method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="save_item">
                <?php if ($editItem): ?><input type="hidden" name="item_id" value="<?= (int)$editItem['id'] ?>"><?php endif; ?>
                <div class="form-row">
                    <div>
                        <label>Name *</label>
                        <input type="text" name="name" required value="<?= e($editItem['name'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Category *</label>
                        <select name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"<?= ($editItem['category_id'] ?? '') == $cat['id'] ? ' selected' : '' ?>><?= e($cat['name']) ?><?= !$cat['is_active'] ? ' (inactive)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <label>Description</label>
                <textarea name="description"><?= e($editItem['description'] ?? '') ?></textarea>
                <div class="form-row">
                    <div>
                        <label>Price ($)</label>
                        <input type="number" step="0.01" min="0" name="price" value="<?= e($editItem['price'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Price Note</label>
                        <input type="text" name="price_note" placeholder="e.g. Market price" value="<?= e($editItem['price_note'] ?? '') ?>">
                    </div>
                </div>
                <?php media_picker_field('photo_path', $editItem['photo_path'] ?? '', 'Photo'); ?>
                <div class="tag-checkboxes">
                    <?php foreach ($tags as $key => $label): ?>
                        <label><input type="checkbox" name="tag_<?= e($key) ?>"<?= in_array($key, $editItem['dietary_tags'] ?? [], true) ? ' checked' : '' ?>> <?= e($label) ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="checkbox-row"><label><input type="checkbox" name="is_featured"<?= !empty($editItem['is_featured']) ? ' checked' : '' ?>> Featured item</label></div>
                <div class="checkbox-row"><label><input type="checkbox" name="is_active"<?= !isset($editItem) || !empty($editItem['is_active']) ? ' checked' : '' ?>> Active</label></div>
                <div class="form-actions">
                    <button type="submit" class="btn">Save Item</button>
                    <?php if ($editItem): ?><a href="/admin/menu.php" class="btn btn-muted">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>

        <h3>All Items</h3>
        <?php if (empty($items)): ?>
            <p>No menu items yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Item</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['photo_path']): ?><img src="<?= e(upload_url($item['photo_path'])) ?>" class="item-thumb" style="vertical-align:middle;margin-right:8px;" alt=""><?php endif; ?>
                            <?= e($item['name']) ?><?= $item['is_featured'] ? ' ★' : '' ?>
                        </td>
                        <td><?= e($item['category_name']) ?></td>
                        <td><?= $item['price'] !== null ? '$' . number_format((float)$item['price'], 2) : '—' ?></td>
                        <td><span class="status-badge <?= $item['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $item['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td>
                            <a href="?edit=<?= (int)$item['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <form method="post" style="display:inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="toggle_item">
                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                <button class="btn btn-sm"><?= $item['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this item?')">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="tab-categories" class="tab-panel">
        <div class="card">
            <h3>Add Category</h3>
            <form method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="save_category">
                <label>Name</label>
                <input type="text" name="name" required>
                <button type="submit" class="btn">Add Category</button>
            </form>
        </div>
        <h3>Categories</h3>
        <ul class="sortable-list" id="category-sort">
            <?php foreach ($categories as $cat): ?>
                <li class="sortable-item" data-id="<?= (int)$cat['id'] ?>">
                    <span class="sortable-handle">☰</span>
                    <form method="post" style="flex:1;display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="save_category">
                        <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                        <input type="text" name="name" value="<?= e($cat['name']) ?>" style="flex:1;min-width:150px;">
                        <label style="margin:0;display:flex;align-items:center;gap:4px;"><input type="checkbox" name="is_active"<?= $cat['is_active'] ? ' checked' : '' ?>> Active</label>
                        <button class="btn btn-sm">Save</button>
                    </form>
                    <form method="post" onsubmit="return confirm('Delete category? Items in it will also be deleted.')">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const catList = document.getElementById('category-sort');
if (catList) {
    Sortable.create(catList, {
        handle: '.sortable-handle',
        onEnd: () => {
            const order = [...catList.querySelectorAll('[data-id]')].map(el => el.dataset.id);
            fetch('/admin/menu.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ action: 'reorder_categories', order: JSON.stringify(order), _csrf: window.CSRF_TOKEN })
            });
        }
    });
}
</script>
<?php
$extraAdminJs = [];
require ROOT . '/includes/templates/admin-footer.php';
