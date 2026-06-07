<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

FormRepository::seedDefaults();
$adminTitle = 'Contacts';
$view = $_GET['view'] ?? 'messages';
$editFormParam = $_GET['edit_form'] ?? '';
$editFormId = $editFormParam === 'new' ? 0 : (int) $editFormParam;
$isNewForm = ($view === 'forms' && $editFormParam === 'new');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'read' || $action === 'unread' || $action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            match ($action) {
                'read' => ContactRepository::setStatus($id, 'read'),
                'unread' => ContactRepository::setStatus($id, 'new'),
                'delete' => ContactRepository::delete($id),
                default => null,
            };
            flash('success', 'Updated.');
            redirect('/admin/contacts.php');
        }
        if ($action === 'create_message') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $status = ($_POST['status'] ?? 'new') === 'read' ? 'read' : 'new';
            if ($name === '' || $email === '' || $message === '') {
                throw new RuntimeException('Name, email, and message are required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email address.');
            }
            ContactRepository::create(compact('name', 'email', 'message', 'status'));
            flash('success', 'Contact message added.');
            redirect('/admin/contacts.php');
        }
        if ($action === 'save_form') {
            $id = (int) ($_POST['id'] ?? 0);
            $fields = [];
            $labels = $_POST['field_label'] ?? [];
            $names = $_POST['field_name'] ?? [];
            $types = $_POST['field_type'] ?? [];
            $required = array_map('strval', $_POST['field_required'] ?? []);
            $placeholders = $_POST['field_placeholder'] ?? [];
            foreach ($labels as $i => $label) {
                $label = trim((string) $label);
                $name = trim((string) ($names[$i] ?? ''));
                if ($label === '' || $name === '') {
                    continue;
                }
                $fields[] = [
                    'field_type' => $types[$i] ?? 'text',
                    'label' => $label,
                    'name' => preg_replace('/[^a-z0-9_]/i', '_', $name),
                    'placeholder' => trim((string) ($placeholders[$i] ?? '')),
                    'required' => in_array((string) $i, $required, true),
                ];
            }
            $payload = [
                'name' => trim($_POST['name'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'handler' => $_POST['handler'] ?? 'custom',
                'button_text' => trim($_POST['button_text'] ?? 'Submit'),
                'success_message' => trim($_POST['success_message'] ?? ''),
                'is_active' => isset($_POST['is_active']),
                'fields' => $fields,
            ];
            if ($payload['name'] === '' || $payload['slug'] === '') {
                throw new RuntimeException('Form name and slug are required.');
            }
            $payload['slug'] = strtolower(preg_replace('/[^a-z0-9-]+/i', '-', $payload['slug']));
            if ($id > 0) {
                FormRepository::update($id, $payload);
                flash('success', 'Form saved.');
            } else {
                $id = FormRepository::create($payload);
                flash('success', 'Form created.');
            }
            redirect('/admin/contacts.php?view=forms&edit_form=' . $id);
        }
        if ($action === 'delete_form') {
            FormRepository::delete((int) ($_POST['id'] ?? 0));
            flash('success', 'Form deleted.');
            redirect('/admin/contacts.php?view=forms');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
}

$new = ContactRepository::byStatus('new');
$read = ContactRepository::byStatus('read');
$forms = FormRepository::all();
$editForm = null;
if ($view === 'forms' && ($editFormId > 0 || $isNewForm)) {
    $editForm = $isNewForm ? [
        'id' => 0,
        'name' => '',
        'slug' => '',
        'handler' => 'custom',
        'button_text' => 'Submit',
        'success_message' => 'Thank you!',
        'is_active' => true,
        'fields' => [
            ['field_type' => 'text', 'label' => 'Name', 'name' => 'name', 'placeholder' => '', 'required' => true],
        ],
    ] : FormRepository::find($editFormId);
}

require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Contacts</h1>
<div class="admin-tabs" style="margin-bottom:1.5rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
    <a href="/admin/contacts.php" class="btn btn-sm<?= $view === 'messages' ? ' btn-success' : ' btn-outline' ?>">Messages</a>
    <a href="/admin/contacts.php?view=forms" class="btn btn-sm<?= $view === 'forms' ? ' btn-success' : ' btn-outline' ?>">Form Builder</a>
</div>

<?php if ($view === 'forms'): ?>
    <p class="pb-hint" style="text-align:left;">Create and edit forms here, then add them to your homepage in <a href="/admin/page-builder.php">Page Builder</a> → Contact section.</p>

    <?php if ($editForm): ?>
        <div class="card">
            <h3><?= $editFormId ? 'Edit Form' : 'New Form' ?></h3>
            <form method="post" id="form-builder">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="save_form">
                <input type="hidden" name="id" value="<?= (int) $editForm['id'] ?>">
                <label>Form name</label>
                <input type="text" name="name" value="<?= e($editForm['name']) ?>" required>
                <label>Slug (used in submission URL)</label>
                <input type="text" name="slug" value="<?= e($editForm['slug']) ?>" required pattern="[a-z0-9-]+">
                <label>Handler</label>
                <select name="handler">
                    <?php foreach (['contact' => 'Contact message', 'review' => 'Review', 'newsletter' => 'Newsletter', 'custom' => 'Custom (stored in form submissions)'] as $val => $label): ?>
                        <option value="<?= e($val) ?>"<?= ($editForm['handler'] ?? '') === $val ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Submit button text</label>
                <input type="text" name="button_text" value="<?= e($editForm['button_text']) ?>">
                <label>Success message</label>
                <input type="text" name="success_message" value="<?= e($editForm['success_message']) ?>">
                <div class="checkbox-row"><label><input type="checkbox" name="is_active"<?= !empty($editForm['is_active']) ? ' checked' : '' ?>> Active</label></div>

                <h4 style="margin-top:1.5rem;">Fields</h4>
                <div id="form-fields-list">
                    <?php foreach ($editForm['fields'] as $i => $field): ?>
                        <div class="form-field-row card" style="padding:0.75rem;margin-bottom:0.75rem;">
                            <label>Label</label>
                            <input type="text" name="field_label[]" value="<?= e($field['label']) ?>" required>
                            <label>Field name (no spaces)</label>
                            <input type="text" name="field_name[]" value="<?= e($field['name']) ?>" required>
                            <label>Type</label>
                            <select name="field_type[]">
                                <?php foreach (['text', 'email', 'textarea', 'rating'] as $type): ?>
                                    <option value="<?= $type ?>"<?= ($field['field_type'] ?? '') === $type ? ' selected' : '' ?>><?= ucfirst($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label>Placeholder</label>
                            <input type="text" name="field_placeholder[]" value="<?= e($field['placeholder'] ?? '') ?>">
                            <label><input type="checkbox" name="field_required[]" value="<?= (int) $i ?>"<?= !empty($field['required']) ? ' checked' : '' ?>> Required</label>
                            <button type="button" class="btn btn-sm btn-danger remove-field-row">Remove field</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline" id="add-form-field">+ Add Field</button>
                <div class="form-actions" style="margin-top:1rem;">
                    <button type="submit" class="btn btn-success">Save Form</button>
                    <a href="/admin/contacts.php?view=forms" class="btn btn-muted">Back to list</a>
                </div>
            </form>
            <?php if (!in_array($editForm['slug'], ['contact', 'review'], true)): ?>
                <form method="post" onsubmit="return confirm('Delete this form?');" style="margin-top:1rem;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="delete_form">
                    <input type="hidden" name="id" value="<?= (int) $editForm['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete Form</button>
                </form>
            <?php endif; ?>
        </div>
        <template id="form-field-template">
            <div class="form-field-row card" style="padding:0.75rem;margin-bottom:0.75rem;">
                <label>Label</label>
                <input type="text" name="field_label[]" required>
                <label>Field name (no spaces)</label>
                <input type="text" name="field_name[]" required>
                <label>Type</label>
                <select name="field_type[]">
                    <option value="text">Text</option>
                    <option value="email">Email</option>
                    <option value="textarea">Textarea</option>
                    <option value="rating">Rating</option>
                </select>
                <label>Placeholder</label>
                <input type="text" name="field_placeholder[]">
                <label><input type="checkbox" name="field_required[]" value="__INDEX__"> Required</label>
                <button type="button" class="btn btn-sm btn-danger remove-field-row">Remove field</button>
            </div>
        </template>
        <script>
        document.getElementById('add-form-field')?.addEventListener('click', () => {
            const list = document.getElementById('form-fields-list');
            const tpl = document.getElementById('form-field-template');
            if (!list || !tpl) return;
            const row = tpl.content.cloneNode(true);
            const idx = list.children.length;
            row.querySelector('[name="field_required[]"]').value = String(idx);
            list.appendChild(row);
        });
        document.getElementById('form-fields-list')?.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-field-row')) {
                e.target.closest('.form-field-row')?.remove();
            }
        });
        </script>
    <?php else: ?>
        <div class="card">
            <div class="form-actions" style="margin-bottom:1rem;">
                <a href="/admin/contacts.php?view=forms&edit_form=new" class="btn btn-success">+ New Form</a>
            </div>
            <?php if (empty($forms)): ?>
                <p>No forms yet.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>Name</th><th>Slug</th><th>Handler</th><th>Fields</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td><?= e($form['name']) ?></td>
                                <td><code><?= e($form['slug']) ?></code></td>
                                <td><?= e($form['handler']) ?></td>
                                <td><?= count(FormRepository::fieldsFor((int) $form['id'])) ?></td>
                                <td><a href="/admin/contacts.php?view=forms&edit_form=<?= (int) $form['id'] ?>" class="btn btn-sm">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="card">
        <h3>Add Contact Manually</h3>
        <p class="pb-hint" style="text-align:left;margin-bottom:1rem;">Record a phone call, in-person note, or other message that did not come through the website form.</p>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create_message">
            <label>Name</label>
            <input type="text" name="name" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Message</label>
            <textarea name="message" rows="5" required></textarea>
            <label>Status</label>
            <select name="status">
                <option value="new">New</option>
                <option value="read">Read</option>
            </select>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Add Message</button>
            </div>
        </form>
    </div>

    <h3>New Messages</h3>
    <?php if (empty($new)): ?><p>No new messages.</p><?php else: foreach ($new as $c): ?>
        <div class="card">
            <strong><?= e($c['name']) ?></strong> &lt;<a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>&gt;<br>
            <small><?= e($c['created_at']) ?></small>
            <p><?= nl2br(e($c['message'])) ?></p>
            <div class="form-actions">
                <form method="post"><?= Csrf::field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm">Mark Read</button></form>
                <form method="post" onsubmit="return confirm('Delete?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
            </div>
        </div>
    <?php endforeach; endif; ?>
    <h3 style="margin-top:2rem;">Read</h3>
    <?php if (empty($read)): ?><p>No read messages.</p><?php else: foreach ($read as $c): ?>
        <div class="card" style="opacity:0.9;">
            <strong><?= e($c['name']) ?></strong> — <?= e($c['email']) ?><br>
            <p><?= nl2br(e($c['message'])) ?></p>
            <form method="post"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
        </div>
    <?php endforeach; endif; ?>
<?php endif; ?>

<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
