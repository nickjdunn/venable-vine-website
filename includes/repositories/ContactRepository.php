<?php

class ContactRepository
{
    public static function byStatus(string $status): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM contacts WHERE status = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM contacts WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO contacts (name, email, message, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($data['name']),
            trim($data['email']),
            trim($data['message']),
            'new',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::connection()->prepare('UPDATE contacts SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM contacts WHERE id = ?')->execute([$id]);
    }
}
