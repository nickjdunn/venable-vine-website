<?php

class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $smtp = app_config('smtp', []);
        if (!empty($smtp['enabled'])) {
            return self::sendSmtp($to, $subject, $body, $smtp);
        }
        $from = $smtp['from_email'] ?? 'noreply@localhost';
        $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        return @mail($to, $subject, $body, $headers);
    }

    private static function sendSmtp(string $to, string $subject, string $body, array $smtp): bool
    {
        // Basic mail() fallback; PHPMailer can be added for production SMTP
        $from = $smtp['from_email'] ?? 'noreply@localhost';
        $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        return @mail($to, $subject, $body, $headers);
    }

    public static function notifyContact(array $contact): void
    {
        $to = Settings::get('contact_email');
        if (!$to) {
            return;
        }
        $subject = 'New contact message from ' . ($contact['name'] ?? 'Website');
        $body = "Name: {$contact['name']}\nEmail: {$contact['email']}\n\n{$contact['message']}";
        self::send($to, $subject, $body);
    }
}
