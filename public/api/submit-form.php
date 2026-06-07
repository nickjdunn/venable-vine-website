<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

Csrf::requireValid();

$slug = trim($_GET['slug'] ?? $_POST['form_slug'] ?? '');
$form = $slug ? FormRepository::findBySlug($slug) : null;
if (!$form || empty($form['is_active'])) {
    json_response(['success' => false, 'message' => 'Form not found.'], 404);
}

if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? null)) {
    json_response(['success' => false, 'message' => 'Please complete the captcha.']);
}

$data = [];
foreach ($form['fields'] as $field) {
    $name = $field['name'];
    $value = trim((string) ($_POST[$name] ?? ''));
    if (!empty($field['required']) && $value === '') {
        json_response(['success' => false, 'message' => 'Please fill in all required fields.']);
    }
    if ($field['field_type'] === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Please enter a valid email address.']);
    }
    if ($field['field_type'] === 'rating') {
        $value = (int) $value;
        if (!empty($field['required']) && ($value < 1 || $value > 5)) {
            json_response(['success' => false, 'message' => 'Please select a rating.']);
        }
    }
    $data[$name] = $value;
}

match ($form['handler']) {
    'contact' => (function () use ($data) {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $message = $data['message'] ?? '';
        if (!$name || !$email || !$message) {
            json_response(['success' => false, 'message' => 'Please fill in all fields.']);
        }
        ContactRepository::create(compact('name', 'email', 'message'));
        Mailer::notifyContact(compact('name', 'email', 'message'));
    })(),
    'review' => (function () use ($data) {
        $name = $data['name'] ?? '';
        $rating = (int) ($data['rating'] ?? 0);
        $text = $data['text'] ?? '';
        if (!$name || $rating < 1 || $rating > 5 || !$text) {
            json_response(['success' => false, 'message' => 'Please complete all review fields.']);
        }
        ReviewRepository::create(compact('name', 'rating', 'text'));
    })(),
    'newsletter' => (function () use ($data) {
        $email = $data['email'] ?? '';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Please enter a valid email.']);
        }
        NewsletterRepository::subscribe($email);
    })(),
    default => FormRepository::createSubmission((int) ($form['id'] ?? 0), $data),
};

$message = $form['success_message'] ?: 'Thank you! Your submission was received.';
json_response(['success' => true, 'message' => $message]);

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
