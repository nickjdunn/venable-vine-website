<?php

class PageRepository
{
    public static function ensureLayoutColumns(): void
    {
        $pdo = Database::connection();
        $cols = $pdo->query('SHOW COLUMNS FROM pages')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('layout_desktop', $cols, true)) {
            $pdo->exec('ALTER TABLE pages ADD COLUMN layout_desktop JSON NULL, ADD COLUMN layout_mobile JSON NULL');
        }
    }

    public static function getBySlug(string $slug): ?array
    {
        self::ensureLayoutColumns();
        $stmt = Database::connection()->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $page = $stmt->fetch();
        return $page ?: null;
    }

    public static function getLayout(int $pageId, string $viewport = 'desktop'): array
    {
        self::ensureLayoutColumns();
        $col = $viewport === 'mobile' ? 'layout_mobile' : 'layout_desktop';
        $stmt = Database::connection()->prepare("SELECT {$col} AS layout FROM pages WHERE id = ?");
        $stmt->execute([$pageId]);
        $row = $stmt->fetch();
        $layout = parse_json_config($row['layout'] ?? null);
        if (!empty($layout['rows'])) {
            return $layout;
        }
        if ($viewport === 'desktop') {
            return default_layout_from_sections(self::getSections($pageId));
        }
        return empty_layout();
    }

    public static function saveLayout(int $pageId, string $viewport, array $layout): void
    {
        self::ensureLayoutColumns();
        $col = $viewport === 'mobile' ? 'layout_mobile' : 'layout_desktop';
        $json = json_encode($layout, JSON_UNESCAPED_UNICODE);
        $stmt = Database::connection()->prepare("UPDATE pages SET {$col} = ? WHERE id = ?");
        $stmt->execute([$json, $pageId]);
    }

    public static function getSections(int $pageId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM page_sections WHERE page_id = ?';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$pageId]);
        return $stmt->fetchAll();
    }

    public static function getSection(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM page_sections WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function saveSections(int $pageId, array $sections): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $existing = self::getSections($pageId);
            $existingIds = array_column($existing, 'id');
            $keptIds = [];
            foreach ($sections as $order => $section) {
                $config = json_encode($section['config'] ?? [], JSON_UNESCAPED_UNICODE);
                if (!empty($section['id'])) {
                    $stmt = $db->prepare(
                        'UPDATE page_sections SET section_type = ?, sort_order = ?, is_active = ?, config = ? WHERE id = ? AND page_id = ?'
                    );
                    $stmt->execute([
                        $section['section_type'],
                        $order,
                        !empty($section['is_active']) ? 1 : 0,
                        $config,
                        $section['id'],
                        $pageId,
                    ]);
                    $keptIds[] = (int) $section['id'];
                } else {
                    $stmt = $db->prepare(
                        'INSERT INTO page_sections (page_id, section_type, sort_order, is_active, config) VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([
                        $pageId,
                        $section['section_type'],
                        $order,
                        !empty($section['is_active']) ? 1 : 0,
                        $config,
                    ]);
                    $keptIds[] = (int) $db->lastInsertId();
                }
            }
            foreach ($existingIds as $id) {
                if (!in_array((int) $id, $keptIds, true)) {
                    $db->prepare('DELETE FROM page_sections WHERE id = ? AND page_id = ?')->execute([$id, $pageId]);
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function addSection(int $pageId, string $type): int
    {
        $config = json_encode(default_section_config($type), JSON_UNESCAPED_UNICODE);
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order FROM page_sections WHERE page_id = ?'
        );
        $stmt->execute([$pageId]);
        $order = (int) $stmt->fetchColumn();
        $insert = Database::connection()->prepare(
            'INSERT INTO page_sections (page_id, section_type, sort_order, is_active, config) VALUES (?, ?, ?, 1, ?)'
        );
        $insert->execute([$pageId, $type, $order, $config]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function updateSectionConfig(int $id, array $config): void
    {
        $stmt = Database::connection()->prepare('UPDATE page_sections SET config = ? WHERE id = ?');
        $stmt->execute([json_encode($config, JSON_UNESCAPED_UNICODE), $id]);
    }
}
