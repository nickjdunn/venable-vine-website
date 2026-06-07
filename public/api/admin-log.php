<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

Auth::requireLogin();

try {
    Csrf::requireValid();
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $message = trim((string) ($payload['message'] ?? 'Client error'));
    Logger::error('JS: ' . $message, [
        'url' => $payload['url'] ?? '',
        'line' => $payload['line'] ?? '',
        'column' => $payload['column'] ?? '',
        'stack' => $payload['stack'] ?? '',
        'page' => $payload['page'] ?? '',
    ]);
    json_response(['success' => true]);
} catch (Throwable $e) {
    Logger::exception($e, ['context' => 'admin_log_api']);
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
