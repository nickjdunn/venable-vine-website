<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

Csrf::requireValid();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$message || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Please fill in all fields with a valid email.']);
}

if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? null)) {
    json_response(['success' => false, 'message' => 'Please complete the captcha.']);
}

$id = ContactRepository::create(compact('name', 'email', 'message'));
Mailer::notifyContact(compact('name', 'email', 'message'));

json_response(['success' => true, 'message' => 'Thank you! Your message has been sent.']);

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
