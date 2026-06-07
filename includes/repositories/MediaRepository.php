<?php

class MediaRepository
{
    public static function ensureSchema(): void
    {
        $pdo = Database::connection();
        $cols = $pdo->query('SHOW COLUMNS FROM gallery_images')->fetchAll(PDO::FETCH_COLUMN);
        $add = [];
        if (!in_array('display_name', $cols, true)) {
            $add[] = 'ADD COLUMN display_name VARCHAR(255) NULL';
        }
        if (!in_array('alt_text', $cols, true)) {
            $add[] = 'ADD COLUMN alt_text VARCHAR(255) NULL';
        }
        if (!in_array('title', $cols, true)) {
            $add[] = 'ADD COLUMN title VARCHAR(255) NULL';
        }
        if ($add) {
            $pdo->exec('ALTER TABLE gallery_images ' . implode(', ', $add));
        }
    }

    public static function all(bool $activeOnly = false): array
    {
        self::ensureSchema();
        $sql = 'SELECT * FROM gallery_images';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return array_map([self::class, 'format'], Database::connection()->query($sql)->fetchAll());
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM gallery_images WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function findByPath(string $path): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM gallery_images WHERE file_path = ? LIMIT 1');
        $stmt->execute([$path]);
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function create(string $filePath, array $meta = []): int
    {
        self::ensureSchema();
        $order = (int) Database::connection()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM gallery_images')->fetchColumn();
        $displayName = $meta['display_name'] ?? basename($filePath);
        $stmt = Database::connection()->prepare(
            'INSERT INTO gallery_images (file_path, caption, display_name, alt_text, title, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $filePath,
            $meta['caption'] ?? null,
            $displayName,
            $meta['alt_text'] ?? null,
            $meta['title'] ?? null,
            $order,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'UPDATE gallery_images SET caption = ?, display_name = ?, alt_text = ?, title = ?, sort_order = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['caption'] ?? null,
            $data['display_name'] ?? null,
            $data['alt_text'] ?? null,
            $data['title'] ?? null,
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

    public static function upload(array $file, array $meta = []): int
    {
        $path = Upload::mediaImage($file);
        return self::create($path, $meta);
    }

    /** Upload multiple files; returns formatted item arrays. */
    public static function uploadMultiple(array $files): array
    {
        $items = [];
        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i] ?? 0,
            ];
            $id = self::upload($file);
            $item = self::find($id);
            if ($item) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /** Import files from assets/images/ that are not yet in the database. */
    public static function syncFromDisk(): int
    {
        self::ensureSchema();
        $count = 0;
        foreach (list_asset_images() as $path) {
            if (!self::findByPath($path)) {
                self::create($path, [
                    'display_name' => pathinfo($path, PATHINFO_FILENAME),
                    'alt_text' => 'Venable & Vine',
                ]);
                $count++;
            }
        }
        return $count;
    }

    public static function format(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'file_path' => $row['file_path'],
            'url' => upload_url($row['file_path']),
            'caption' => $row['caption'] ?? '',
            'display_name' => $row['display_name'] ?? basename($row['file_path']),
            'alt_text' => $row['alt_text'] ?? '',
            'title' => $row['title'] ?? '',
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_active' => (bool) ($row['is_active'] ?? true),
        ];
    }
}

if (!class_exists('GalleryRepository', false)) {
    class_alias(MediaRepository::class, 'GalleryRepository');
}
