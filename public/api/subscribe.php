<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

Csrf::requireValid();

$email = trim($_POST['email'] ?? '');
if (!NewsletterRepository::subscribe($email)) {
    json_response(['success' => false, 'message' => 'Please enter a valid email address.']);
}

json_response(['success' => true, 'message' => 'You\'re subscribed! We\'ll keep you in the loop.']);
