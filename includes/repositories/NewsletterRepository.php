<?php

class NewsletterRepository
{
    public static function all(string $status = 'active'): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM newsletter_subscribers WHERE status = ? ORDER BY subscribed_at DESC'
        );
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public static function search(string $query): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM newsletter_subscribers WHERE email LIKE ? ORDER BY subscribed_at DESC'
        );
        $stmt->execute(['%' . $query . '%']);
        return $stmt->fetchAll();
    }

    public static function subscribe(string $email): bool
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $stmt = Database::connection()->prepare(
            'INSERT INTO newsletter_subscribers (email, status) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE status = ?, subscribed_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$email, 'active', 'active']);
        NewsletterService::syncToMailchimp($email);
        return true;
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM newsletter_subscribers WHERE id = ?')->execute([$id]);
    }

    public static function exportCsv(): string
    {
        $rows = Database::connection()->query(
            'SELECT email, subscribed_at, status FROM newsletter_subscribers ORDER BY subscribed_at DESC'
        )->fetchAll();
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['email', 'subscribed_at', 'status']);
        foreach ($rows as $row) {
            fputcsv($out, [$row['email'], $row['subscribed_at'], $row['status']]);
        }
        rewind($out);
        return stream_get_contents($out);
    }
}
