<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

Auth::requireLogin();
Csrf::requireValid();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$page = PageRepository::getBySlug('home');
if (!$page) {
    json_response(['success' => false, 'message' => 'Home page not found'], 404);
}
$pageId = (int) $page['id'];

try {
    match ($action) {
        'add_section' => (function () use ($pageId) {
            $type = $_POST['section_type'] ?? '';
            if (!isset(section_types()[$type])) {
                json_response(['success' => false, 'message' => 'Invalid section type']);
            }
            $id = PageRepository::addSection($pageId, $type);
            $section = PageRepository::getSection($id);
            json_response(['success' => true, 'section' => format_section($section)]);
        })(),
        'save_sections' => (function () use ($pageId) {
            $payload = json_decode(file_get_contents('php://input'), true);
            $token = $payload['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!Csrf::validate($token)) {
                json_response(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            }
            if (!is_array($payload) || !isset($payload['sections'])) {
                json_response(['success' => false, 'message' => 'Invalid payload']);
            }
            PageRepository::saveSections($pageId, $payload['sections']);
            json_response(['success' => true, 'message' => 'Page saved']);
        })(),
        'update_config' => (function () {
            $id = (int) ($_POST['section_id'] ?? 0);
            $config = json_decode($_POST['config'] ?? '{}', true);
            if (!$id || !is_array($config)) {
                json_response(['success' => false, 'message' => 'Invalid data']);
            }
            PageRepository::updateSectionConfig($id, $config);
            json_response(['success' => true, 'message' => 'Section updated']);
        })(),
        'get_sections' => (function () use ($pageId) {
            $sections = PageRepository::getSections($pageId);
            json_response(['success' => true, 'sections' => array_map('format_section', $sections)]);
        })(),
        default => json_response(['success' => false, 'message' => 'Unknown action'], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

function format_section(array $section): array
{
    return [
        'id' => (int) $section['id'],
        'section_type' => $section['section_type'],
        'sort_order' => (int) $section['sort_order'],
        'is_active' => (bool) $section['is_active'],
        'config' => parse_json_config($section['config'] ?? null),
        'label' => section_types()[$section['section_type']] ?? $section['section_type'],
    ];
}
