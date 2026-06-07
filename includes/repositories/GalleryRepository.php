<?php

class GalleryRepository
{
    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM gallery_images';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM gallery_images WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $filePath, ?string $caption = null): int
    {
        $order = (int) Database::connection()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM gallery_images')->fetchColumn();
        $stmt = Database::connection()->prepare(
            'INSERT INTO gallery_images (file_path, caption, sort_order, is_active) VALUES (?, ?, ?, 1)'
        );
        $stmt->execute([$filePath, $caption, $order]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE gallery_images SET caption = ?, sort_order = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['caption'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $row = self::find($id);
        if ($row) {
            Upload::delete($row['file_path']);
        }
        Database::connection()->prepare('DELETE FROM gallery_images WHERE id = ?')->execute([$id]);
    }

    public static function reorder(array $ids): void
    {
        $db = Database::connection();
        foreach ($ids as $order => $id) {
            $db->prepare('UPDATE gallery_images SET sort_order = ? WHERE id = ?')->execute([$order, $id]);
        }
    }
}
