<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

Auth::requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'list') {
        json_response(['success' => true, 'items' => MediaRepository::all()]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    match ($action) {
        'upload' => (function () {
            Csrf::requireValid();
            if (empty($_FILES['photo'])) {
                json_response(['success' => false, 'message' => 'No file uploaded']);
            }
            $meta = [
                'display_name' => trim($_POST['display_name'] ?? '') ?: null,
                'alt_text' => trim($_POST['alt_text'] ?? '') ?: null,
                'caption' => trim($_POST['caption'] ?? '') ?: null,
                'title' => trim($_POST['title'] ?? '') ?: null,
            ];
            $id = MediaRepository::upload($_FILES['photo'], array_filter($meta, fn($v) => $v !== null));
            json_response(['success' => true, 'item' => MediaRepository::find($id)]);
        })(),
        'sync' => (function () {
            Csrf::requireValid();
            $count = MediaRepository::syncFromDisk();
            json_response(['success' => true, 'imported' => $count, 'items' => MediaRepository::all()]);
        })(),
        default => json_response(['success' => false, 'message' => 'Unknown action'], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
