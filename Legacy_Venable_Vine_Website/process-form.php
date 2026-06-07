<?php
// =================================================================
// CONFIGURATION - EDIT THESE TWO LINES
// =================================================================
$your_email = "nickjdunn@gmail.com"; // The email address where you want to receive messages.
$recaptcha_secret_key = "6LcfarMrAAAAALZ0fj4nNIdxMQE9_qbJXHMG8CHF"; // Paste your SECRET Key from Google here.
// =================================================================

// Set the header to return JSON
header('Content-Type: application/json');

// A function to send a JSON response and exit
function send_json_response($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    send_json_response(false, 'Invalid request method.');
}

// Get the reCAPTCHA response token from the form
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
if (empty($recaptcha_response)) {
    send_json_response(false, 'Please complete the CAPTCHA.');
}

// Verify the reCAPTCHA token with Google
$url = 'https://www.google.com/recaptcha/api/siteverify';
$data = [
    'secret' => $recaptcha_secret_key,
    'response' => $recaptcha_response
];
$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$recaptcha_result = json_decode($result);

// Check if reCAPTCHA verification was successful
if (!$recaptcha_result->success) {
    send_json_response(false, 'reCAPTCHA verification failed. Please try again.');
}

// --- reCAPTCHA is valid, proceed to build and send the email ---

// Sanitize form inputs
$form_type = filter_var($_POST['_form'] ?? 'Unknown Form', FILTER_SANITIZE_STRING);
$name = filter_var($_POST['Name'] ?? 'N/A', FILTER_SANITIZE_STRING);
$email = filter_var($_POST['Email'] ?? 'N/A', FILTER_SANITIZE_EMAIL);
$rating = filter_var($_POST['Rating'] ?? 'N/A', FILTER_SANITIZE_STRING);
$message_content = filter_var($_POST['Message'] ?? ($_POST['Review'] ?? 'N/A'), FILTER_SANITIZE_STRING);

// Set the email subject and body
$subject = "New Submission: " . $form_type . " from " . $name;
$body = "You have received a new form submission from your website.\n\n"
      . "Form Type: " . $form_type . "\n"
      . "Name: " . $name . "\n";

if ($email !== 'N/A') $body .= "Email: " . $email . "\n";
if ($rating !== 'N/A') $body .= "Rating: " . $rating . " out of 5 stars\n";

$body .= "Message/Review:\n" . $message_content . "\n";
$headers = "From: " . $name . " <" . ($email !== 'N/A' ? $email : 'noreply@yourdomain.com') . ">";

// Send the email
if (mail($your_email, $subject, $body, $headers)) {
    if ($form_type === 'Review Submission') {
        send_json_response(true, 'Thank you! Your review has been submitted.');
    } else {
        send_json_response(true, 'Thank you! Your message has been sent.');
    }
} else {
    send_json_response(false, 'Sorry, there was an error sending your message.');
}
?>