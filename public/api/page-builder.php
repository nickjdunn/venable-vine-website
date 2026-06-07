<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

Auth::requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$page = PageRepository::getBySlug('home');
if (!$page) {
    json_response(['success' => false, 'message' => 'Home page not found'], 404);
}
$pageId = (int) $page['id'];

function fix_layout_image_paths(array $layout): array
{
    $imageKeys = ['src', 'background_image', 'logo_image', 'image'];
    foreach ($layout['rows'] ?? [] as &$row) {
        foreach ($row['columns'] ?? [] as &$col) {
            foreach ($col['blocks'] ?? [] as &$block) {
                foreach ($imageKeys as $key) {
                    if (!empty($block['config'][$key])) {
                        $block['config'][$key] = resolve_image_path($block['config'][$key]);
                    }
                }
            }
        }
    }
    unset($row, $col, $block);
    return $layout;
}

function seed_media_and_settings(): void
{
    $defaults = default_brand_images();
    MediaRepository::syncFromDisk();

    $logo = resolve_image_path(Settings::get('logo_path') ?: $defaults['logo']);
    $favicon = resolve_image_path(Settings::get('favicon_path') ?: $defaults['favicon']);
    Settings::set('logo_path', $logo);
    Settings::set('favicon_path', $favicon);

    if (!MediaRepository::all()) {
        foreach (list_asset_images() as $path) {
            MediaRepository::create($path, [
                'display_name' => pathinfo($path, PATHINFO_FILENAME),
                'alt_text' => 'Venable & Vine',
            ]);
        }
    }
}

try {
    if ($action === 'get_builder_data') {
        seed_media_and_settings();
        $layoutDesktop = fix_layout_image_paths(PageRepository::getLayout($pageId, 'desktop'));
        $layoutMobile = fix_layout_image_paths(PageRepository::getLayout($pageId, 'mobile'));
        $categories = MenuRepository::categories(false);
        $gallery = MediaRepository::all();
        json_response([
            'success' => true,
            'layout_desktop' => $layoutDesktop,
            'layout_mobile' => $layoutMobile,
            'block_types' => block_types(),
            'categories' => $categories,
            'gallery' => $gallery,
            'asset_images' => list_asset_images(),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    match ($action) {
        'save_layout' => (function () use ($pageId) {
            $payload = json_decode(file_get_contents('php://input'), true);
            $token = $payload['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!Csrf::validate($token)) {
                json_response(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            }
            $viewport = ($payload['viewport'] ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop';
            if (!isset($payload['layout']) || !is_array($payload['layout'])) {
                json_response(['success' => false, 'message' => 'Invalid layout']);
            }
            PageRepository::saveLayout($pageId, $viewport, fix_layout_image_paths($payload['layout']));
            json_response(['success' => true, 'message' => ucfirst($viewport) . ' layout saved']);
        })(),
        default => json_response(['success' => false, 'message' => 'Unknown action'], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
