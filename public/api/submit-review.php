<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

Csrf::requireValid();

$name = trim($_POST['name'] ?? '');
$rating = (int) ($_POST['rating'] ?? 0);
$text = trim($_POST['text'] ?? '');

if (!$name || $rating < 1 || $rating > 5 || !$text) {
    json_response(['success' => false, 'message' => 'Please complete all review fields.']);
}

if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? null)) {
    json_response(['success' => false, 'message' => 'Please complete the captcha.']);
}

ReviewRepository::create(compact('name', 'rating', 'text'));
json_response(['success' => true, 'message' => 'Thank you! Your review will appear after approval.']);

function verify_recaptcha(?string $token): bool
{
    $secret = Settings::get('recaptcha_secret_key');
    if (!$secret) {
        return true;
    }
    if (!$token) {
        return false;
    }
    $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
        'secret' => $secret,
        'response' => $token,
    ]));
    $data = json_decode($response, true);
    return !empty($data['success']);
}
