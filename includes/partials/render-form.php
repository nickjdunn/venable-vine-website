<?php
/**
 * Render a form definition. Expects $form array with fields, $panelId, $activeClass.
 */
$recaptchaKey = Settings::get('recaptcha_site_key');
$panelId = $panelId ?? ('form-panel-' . (int) ($form['id'] ?? 0));
$activeClass = $activeClass ?? '';
$endpoint = '/api/submit-form.php?slug=' . urlencode($form['slug'] ?? '');
?>
<div id="<?= e($panelId) ?>" class="form-container<?= $activeClass ? ' ' . e(trim($activeClass)) : '' ?>">
    <form class="ajax-form" data-endpoint="<?= e($endpoint) ?>">
        <?= Csrf::field() ?>
        <?php foreach ($form['fields'] ?? [] as $field): ?>
            <?php if (($field['field_type'] ?? '') === 'rating'): ?>
                <label><?= e($field['label']) ?></label>
                <div class="rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star-<?= e($panelId) ?>-<?= $i ?>" name="<?= e($field['name']) ?>" value="<?= $i ?>"<?= $i === 5 && !empty($field['required']) ? ' required' : '' ?>>
                        <label for="star-<?= e($panelId) ?>-<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            <?php else: ?>
                <label><?= e($field['label']) ?></label>
                <?php if (($field['field_type'] ?? '') === 'textarea'): ?>
                    <textarea name="<?= e($field['name']) ?>" rows="5"<?= !empty($field['required']) ? ' required' : '' ?> placeholder="<?= e($field['placeholder'] ?? '') ?>"></textarea>
                <?php else: ?>
                    <input type="<?= e($field['field_type'] === 'email' ? 'email' : 'text') ?>"
                           name="<?= e($field['name']) ?>"
                           <?= !empty($field['required']) ? 'required' : '' ?>
                           placeholder="<?= e($field['placeholder'] ?? '') ?>">
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($recaptchaKey): ?>
            <div class="g-recaptcha" data-sitekey="<?= e($recaptchaKey) ?>"></div>
        <?php endif; ?>
        <div class="form-actions-centered">
            <button type="submit" class="submit-btn"><?= e($form['button_text'] ?? 'Submit') ?></button>
        </div>
        <p class="form-status"></p>
    </form>
</div>
