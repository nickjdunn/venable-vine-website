<?php
$config = ensure_contact_config_forms($config ?? []);
$formSlots = normalize_contact_forms($config['forms'] ?? []);
$formsById = [];
foreach (FormRepository::all(true) as $f) {
    $formsById[(int) $f['id']] = FormRepository::find((int) $f['id']);
}
?>
<section id="contact" class="section-contact"<?= section_style_attr($config) ?>>
    <div class="contact-container">
        <?php editable_text('title', $config['title'] ?? 'Get In Touch', 'h2'); ?>
        <?php editable_multiline('subtitle', $config['subtitle'] ?? '', 'p'); ?>
        <?php if (editor_mode()): ?>
            <div class="se-forms-editor" data-forms-editor>
                <?php if (empty($formSlots)): ?>
                    <p class="se-forms-empty-hint">No forms yet. Click + Add Form to choose a form from your library.</p>
                <?php endif; ?>
                <div class="se-forms-list" data-forms-list>
                    <?php foreach ($formSlots as $i => $slot): ?>
                        <?php
                        $form = $formsById[(int) $slot['form_id']] ?? null;
                        if (!$form) {
                            continue;
                        }
                        ?>
                        <div class="se-form-slot" data-form-index="<?= (int) $i ?>" data-form-id="<?= (int) $slot['form_id'] ?>">
                            <div class="se-form-slot-header">
                                <span class="se-editable se-editable-trigger se-form-tab-label" role="button" tabindex="0"><?= e($slot['tab_label'] ?: $form['name']) ?></span>
                                <button type="button" class="se-form-remove" title="Remove form">×</button>
                            </div>
                            <div class="se-form-preview-panel">
                                <strong><?= e($form['name']) ?></strong>
                                <div class="se-fake-form">
                                    <?php foreach ($form['fields'] as $field): ?>
                                        <span><?= e($field['label']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <span class="se-form-preview-btn"><?= e($form['button_text']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline se-forms-add">+ Add Form</button>
                <p class="se-managed-placeholder-note">Build and edit forms in <a href="/admin/contacts.php?view=forms" target="_blank">Contacts → Form Builder</a>.</p>
            </div>
        <?php elseif (empty($formSlots)): ?>
            <p class="text-center">Forms coming soon!</p>
        <?php else: ?>
            <?php if (count($formSlots) > 1): ?>
                <div class="choice-buttons">
                    <?php foreach ($formSlots as $i => $slot): ?>
                        <?php
                        $form = $formsById[(int) $slot['form_id']] ?? null;
                        if (!$form) {
                            continue;
                        }
                        $panelId = 'form-panel-' . (int) $slot['form_id'] . '-' . (int) $i;
                        ?>
                        <button type="button" class="choice-btn<?= $i === 0 ? ' active' : '' ?>" data-form="<?= e($panelId) ?>"><?= e($slot['tab_label'] ?: $form['name']) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php foreach ($formSlots as $i => $slot): ?>
                <?php
                $form = $formsById[(int) $slot['form_id']] ?? null;
                if (!$form) {
                    continue;
                }
                $panelId = 'form-panel-' . (int) $slot['form_id'] . '-' . (int) $i;
                $activeClass = $i === 0 ? 'active' : '';
                include ROOT . '/includes/partials/render-form.php';
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
