<?php

class MenuRepository
{
    public static function categories(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM menu_categories';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function category(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM menu_categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function createCategory(string $name): int
    {
        $order = self::nextCategoryOrder();
        $stmt = Database::connection()->prepare(
            'INSERT INTO menu_categories (name, sort_order, is_active) VALUES (?, ?, 1)'
        );
        $stmt->execute([trim($name), $order]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function updateCategory(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE menu_categories SET name = ?, sort_order = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            trim($data['name']),
            (int) $data['sort_order'],
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    public static function deleteCategory(int $id): void
    {
        Database::connection()->prepare('DELETE FROM menu_categories WHERE id = ?')->execute([$id]);
    }

    public static function reorderCategories(array $ids): void
    {
        $db = Database::connection();
        foreach ($ids as $order => $id) {
            $db->prepare('UPDATE menu_categories SET sort_order = ? WHERE id = ?')->execute([$order, $id]);
        }
    }

    public static function items(?int $categoryId = null, bool $activeOnly = false): array
    {
        $sql = 'SELECT mi.*, mc.name AS category_name, mc.is_active AS category_active
                FROM menu_items mi
                JOIN menu_categories mc ON mc.id = mi.category_id';
        $conds = [];
        $params = [];
        if ($categoryId) {
            $conds[] = 'mi.category_id = ?';
            $params[] = $categoryId;
        }
        if ($activeOnly) {
            $conds[] = 'mi.is_active = 1 AND mc.is_active = 1';
        }
        if ($conds) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }
        $sql .= ' ORDER BY mc.sort_order ASC, mi.sort_order ASC, mi.name ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['dietary_tags'] = parse_json_config($row['dietary_tags'] ?? '[]');
        }
        return $rows;
    }

    public static function item(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['dietary_tags'] = parse_json_config($row['dietary_tags'] ?? '[]');
        }
        return $row ?: null;
    }

    public static function createItem(array $data): int
    {
        $order = self::nextItemOrder((int) $data['category_id']);
        $stmt = Database::connection()->prepare(
            'INSERT INTO menu_items (category_id, name, description, price, price_note, photo_path, dietary_tags, is_featured, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int) $data['category_id'],
            trim($data['name']),
            $data['description'] ?? '',
            $data['price'] !== '' && $data['price'] !== null ? $data['price'] : null,
            $data['price_note'] ?? null,
            $data['photo_path'] ?? null,
            json_encode($data['dietary_tags'] ?? []),
            !empty($data['is_featured']) ? 1 : 0,
            !empty($data['is_active']) ? 1 : 0,
            $order,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function updateItem(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, price_note = ?,
             photo_path = ?, dietary_tags = ?, is_featured = ?, is_active = ?, sort_order = ? WHERE id = ?'
        );
        $stmt->execute([
            (int) $data['category_id'],
            trim($data['name']),
            $data['description'] ?? '',
            $data['price'] !== '' && $data['price'] !== null ? $data['price'] : null,
            $data['price_note'] ?? null,
            $data['photo_path'] ?? null,
            json_encode($data['dietary_tags'] ?? []),
            !empty($data['is_featured']) ? 1 : 0,
            !empty($data['is_active']) ? 1 : 0,
            (int) ($data['sort_order'] ?? 0),
            $id,
        ]);
    }

    public static function deleteItem(int $id): void
    {
        Database::connection()->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
    }

    public static function reorderItems(array $ids): void
    {
        $db = Database::connection();
        foreach ($ids as $order => $id) {
            $db->prepare('UPDATE menu_items SET sort_order = ? WHERE id = ?')->execute([$order, $id]);
        }
    }

    public static function itemsGrouped(bool $activeOnly = true): array
    {
        $categories = self::categories($activeOnly);
        $items = self::items(null, $activeOnly);
        $grouped = [];
        foreach ($categories as $cat) {
            $grouped[$cat['id']] = ['category' => $cat, 'items' => []];
        }
        foreach ($items as $item) {
            if (isset($grouped[$item['category_id']])) {
                $grouped[$item['category_id']]['items'][] = $item;
            }
        }
        return array_values(array_filter($grouped, fn($g) => !$activeOnly || count($g['items']) > 0));
    }

    private static function nextCategoryOrder(): int
    {
        return (int) Database::connection()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM menu_categories')->fetchColumn();
    }

    private static function nextItemOrder(int $categoryId): int
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM menu_items WHERE category_id = ?');
        $stmt->execute([$categoryId]);
        return (int) $stmt->fetchColumn();
    }
}
