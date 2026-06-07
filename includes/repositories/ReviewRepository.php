<?php

class ReviewRepository
{
    public static function approved(int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM reviews WHERE status = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, 'approved');
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function byStatus(string $status): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM reviews WHERE status = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM reviews WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $status = in_array($data['status'] ?? '', ['pending', 'approved'], true)
            ? $data['status']
            : 'pending';
        $stmt = Database::connection()->prepare(
            'INSERT INTO reviews (name, rating, text, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($data['name']),
            max(1, min(5, (int) $data['rating'])),
            trim($data['text']),
            $status,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE reviews SET name = ?, rating = ?, text = ?, status = ? WHERE id = ?'
        );
        $stmt->execute([
            trim($data['name']),
            (int) $data['rating'],
            trim($data['text']),
            $data['status'],
            $id,
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::connection()->prepare('UPDATE reviews SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
    }
}
