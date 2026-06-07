<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

Auth::requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$page = PageRepository::getBySlug('home');
if (!$page) {
    Logger::error('Page builder: home page not found');
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
    return normalize_layout($layout);
}

function seed_media_and_settings(): void
{
    try {
        $defaults = default_brand_images();
        $logo = resolve_image_path(Settings::get('logo_path') ?: $defaults['logo']);
        $favicon = resolve_image_path(Settings::get('favicon_path') ?: $defaults['favicon']);
        Settings::set('logo_path', $logo);
        Settings::set('favicon_path', $favicon);
    } catch (Throwable $e) {
        Logger::exception($e, ['context' => 'seed_media_and_settings']);
    }
}

try {
    if ($action === 'get_builder_data') {
        seed_media_and_settings();
        PageRepository::ensureLayoutsPersisted($pageId);
        $layout = fix_layout_image_paths(PageRepository::getLayout($pageId, 'desktop'));
        // #region agent log
        agent_debug_log('C', 'page-builder.php:get_builder_data', 'layout loaded', [
            'rows' => count($layout['rows'] ?? []),
            'PUBLIC_ROOT' => PUBLIC_ROOT,
        ]);
        // #endregion
        $categories = MenuRepository::categories(false);
        $gallery = MediaRepository::all();
        json_response([
            'success' => true,
            'layout' => $layout,
            'layout_desktop' => $layout,
            'block_types' => block_types(),
            'categories' => $categories,
            'gallery' => $gallery,
        ]);
    }

    if ($action === 'preview_block' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        Csrf::requireValid();
        $payload = json_decode(file_get_contents('php://input'), true);
        $block = is_array($payload) ? ($payload['block'] ?? $payload) : null;
        if (!is_array($block) || empty($block['type'])) {
            json_response(['success' => false, 'message' => 'Invalid block'], 400);
        }
        ob_start();
        render_block($block);
        $html = ob_get_clean();
        json_response(['success' => true, 'html' => $html]);
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
            if ($viewport === 'desktop') {
                PageRepository::saveLayout($pageId, 'mobile', fix_layout_image_paths($payload['layout']));
            }
            json_response(['success' => true, 'message' => 'Page layout saved']);
        })(),
        'reset_mobile' => (function () use ($pageId) {
            Csrf::requireValid();
            $desktop = fix_layout_image_paths(PageRepository::getLayout($pageId, 'desktop'));
            $mobile = mobile_layout_from_layout($desktop);
            PageRepository::saveLayout($pageId, 'mobile', $mobile);
            json_response(['success' => true, 'layout_mobile' => $mobile]);
        })(),
        'reset_layout' => (function () use ($pageId) {
            Csrf::requireValid();
            $layout = fix_layout_image_paths(default_homepage_layout());
            PageRepository::saveLayout($pageId, 'desktop', $layout);
            PageRepository::saveLayout($pageId, 'mobile', mobile_layout_from_layout($layout));
            json_response(['success' => true, 'layout' => $layout, 'message' => 'Homepage reset to default layout']);
        })(),
        default => json_response(['success' => false, 'message' => 'Unknown action'], 400),
    };
} catch (Throwable $e) {
    Logger::exception($e, ['context' => 'page_builder_api', 'action' => $action]);
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
