<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $keys = [
        'site_name', 'site_tagline', 'meta_description', 'footer_text',
        'facebook_url', 'instagram_url', 'tiktok_url', 'contact_email',
        'google_maps_api_key', 'recaptcha_site_key', 'recaptcha_secret_key',
        'mailchimp_api_key', 'mailchimp_list_id',
    ];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            Settings::set($key, trim($_POST[$key]));
        }
    }
    if (isset($_POST['logo_path'])) {
        Settings::set('logo_path', resolve_image_path(trim($_POST['logo_path'])));
    }
    if (isset($_POST['favicon_path'])) {
        Settings::set('favicon_path', resolve_image_path(trim($_POST['favicon_path'])));
    }
    flash('success', 'Settings saved.');
    redirect('/admin/settings.php');
}

$s = Settings::all();
$s['logo_path'] = resolve_image_path($s['logo_path'] ?? default_brand_images()['logo']);
$s['favicon_path'] = resolve_image_path($s['favicon_path'] ?? default_brand_images()['favicon']);
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Site Settings</h1>
<form method="post">
    <?= Csrf::field() ?>
    <div class="card">
        <h3>General</h3>
        <label>Site Name</label>
        <input type="text" name="site_name" value="<?= e($s['site_name'] ?? '') ?>">
        <label>Tagline</label>
        <input type="text" name="site_tagline" value="<?= e($s['site_tagline'] ?? '') ?>">
        <label>Meta Description</label>
        <textarea name="meta_description"><?= e($s['meta_description'] ?? '') ?></textarea>
        <label>Footer Text</label>
        <input type="text" name="footer_text" value="<?= e($s['footer_text'] ?? '') ?>">
    </div>
    <div class="card">
        <h3>Branding</h3>
        <?php media_picker_field('logo_path', $s['logo_path'] ?? '', 'Logo'); ?>
        <?php media_picker_field('favicon_path', $s['favicon_path'] ?? '', 'Favicon'); ?>
    </div>
    <div class="card">
        <h3>Social & Contact</h3>
        <label>Facebook URL</label>
        <input type="url" name="facebook_url" value="<?= e($s['facebook_url'] ?? '') ?>">
        <label>Instagram URL</label>
        <input type="url" name="instagram_url" value="<?= e($s['instagram_url'] ?? '') ?>">
        <label>TikTok URL</label>
        <input type="url" name="tiktok_url" value="<?= e($s['tiktok_url'] ?? '') ?>">
        <label>Contact Notification Email</label>
        <input type="email" name="contact_email" value="<?= e($s['contact_email'] ?? '') ?>">
    </div>
    <div class="card">
        <h3>Integrations</h3>
        <label>Google Maps API Key</label>
        <input type="text" name="google_maps_api_key" value="<?= e($s['google_maps_api_key'] ?? '') ?>">
        <label>reCAPTCHA Site Key</label>
        <input type="text" name="recaptcha_site_key" value="<?= e($s['recaptcha_site_key'] ?? '') ?>">
        <label>reCAPTCHA Secret Key</label>
        <input type="text" name="recaptcha_secret_key" value="<?= e($s['recaptcha_secret_key'] ?? '') ?>">
        <hr>
        <h4>Mailchimp (optional — Phase 1.5)</h4>
        <label>Mailchimp API Key</label>
        <input type="text" name="mailchimp_api_key" value="<?= e($s['mailchimp_api_key'] ?? '') ?>" placeholder="Leave blank to collect locally only">
        <label>Mailchimp List ID</label>
        <input type="text" name="mailchimp_list_id" value="<?= e($s['mailchimp_list_id'] ?? '') ?>">
    </div>
    <button type="submit" class="btn">Save Settings</button>
</form>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
