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

function seed_gallery_from_assets(): void
{
    if (!Settings::get('logo_path')) {
        Settings::set('logo_path', 'assets/images/VenableandVineLogo.webp');
    }
    if (!Settings::get('favicon_path')) {
        Settings::set('favicon_path', 'assets/images/JamIcon.webp');
    }
    if (GalleryRepository::all()) {
        return;
    }
    $skip = ['Logo', 'JamIcon', 'BerriesInhand'];
    foreach (list_asset_images() as $path) {
        $base = basename($path);
        foreach ($skip as $s) {
            if (str_contains($base, $s)) {
                continue 2;
            }
        }
        GalleryRepository::create($path, null);
    }
}

try {
    if ($action === 'get_builder_data') {
        seed_gallery_from_assets();
        $categories = MenuRepository::categories(false);
        $gallery = array_map(fn($g) => [
            'id' => (int) $g['id'],
            'file_path' => $g['file_path'],
            'url' => upload_url($g['file_path']),
            'caption' => $g['caption'],
            'is_active' => (bool) $g['is_active'],
        ], GalleryRepository::all());
        json_response([
            'success' => true,
            'layout_desktop' => PageRepository::getLayout($pageId, 'desktop'),
            'layout_mobile' => PageRepository::getLayout($pageId, 'mobile'),
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
            PageRepository::saveLayout($pageId, $viewport, $payload['layout']);
            json_response(['success' => true, 'message' => ucfirst($viewport) . ' layout saved']);
        })(),
        'gallery_upload' => (function () {
            Csrf::requireValid();
            if (empty($_FILES['photo'])) {
                json_response(['success' => false, 'message' => 'No file uploaded']);
            }
            $path = Upload::image($_FILES['photo'], 'gallery');
            $id = GalleryRepository::create($path, $_POST['caption'] ?? null);
            $row = GalleryRepository::find($id);
            json_response(['success' => true, 'image' => [
                'id' => $id,
                'file_path' => $path,
                'url' => upload_url($path),
                'caption' => $row['caption'] ?? '',
                'is_active' => true,
            ]]);
        })(),
        'gallery_delete' => (function () {
            Csrf::requireValid();
            $id = (int) ($_POST['id'] ?? 0);
            GalleryRepository::delete($id);
            json_response(['success' => true]);
        })(),
        'gallery_toggle' => (function () {
            Csrf::requireValid();
            $id = (int) ($_POST['id'] ?? 0);
            $img = GalleryRepository::find($id);
            if ($img) {
                GalleryRepository::update($id, [
                    'caption' => $img['caption'],
                    'sort_order' => $img['sort_order'],
                    'is_active' => !$img['is_active'],
                ]);
            }
            json_response(['success' => true]);
        })(),
        default => json_response(['success' => false, 'message' => 'Unknown action'], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
