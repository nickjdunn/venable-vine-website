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

    public static function getStoredLayout(int $pageId, string $viewport = 'desktop'): array
    {
        self::ensureLayoutColumns();
        $col = $viewport === 'mobile' ? 'layout_mobile' : 'layout_desktop';
        $stmt = Database::connection()->prepare("SELECT {$col} AS layout FROM pages WHERE id = ?");
        $stmt->execute([$pageId]);
        $row = $stmt->fetch();
        return parse_json_config($row['layout'] ?? null);
    }

    public static function hasStoredLayout(int $pageId, string $viewport = 'desktop'): bool
    {
        return !empty(self::getStoredLayout($pageId, $viewport)['rows']);
    }

    /**
     * Migrate legacy page_sections into layout JSON and generate mobile when missing.
     * Returns true if anything was written to the database.
     */
    public static function ensureLayoutsPersisted(int $pageId): bool
    {
        self::ensureLayoutColumns();
        $saved = false;
        $desktop = self::getStoredLayout($pageId, 'desktop');
        if (empty($desktop['rows'])) {
            $sections = self::getSections($pageId);
            if ($sections) {
                $desktop = normalize_layout(default_layout_from_sections($sections));
                self::saveLayout($pageId, 'desktop', $desktop);
                $saved = true;
            }
        } else {
            if (layout_needs_homepage_normalize($desktop)) {
                $homepage = normalize_homepage_layout($desktop);
                self::saveLayout($pageId, 'desktop', $homepage);
                self::saveLayout($pageId, 'mobile', mobile_layout_from_layout($homepage));
                $saved = true;
                $desktop = $homepage;
            }
        }
        $mobile = self::getStoredLayout($pageId, 'mobile');
        if (empty($mobile['rows'])) {
            $source = !empty($desktop['rows'])
                ? $desktop
                : normalize_layout(default_layout_from_sections(self::getSections($pageId)));
            if (!empty($source['rows'])) {
                self::saveLayout($pageId, 'mobile', mobile_layout_from_layout($source));
                $saved = true;
            }
        }
        return $saved;
    }

    public static function getLayout(int $pageId, string $viewport = 'desktop'): array
    {
        self::ensureLayoutsPersisted($pageId);
        $stored = self::getStoredLayout($pageId, $viewport);
        if (!empty($stored['rows'])) {
            return normalize_layout($stored);
        }
        if ($viewport === 'desktop') {
            $sections = self::getSections($pageId);
            if ($sections) {
                return normalize_layout(default_layout_from_sections($sections));
            }
            return empty_layout();
        }
        $desktop = self::getStoredLayout($pageId, 'desktop');
        if (empty($desktop['rows'])) {
            $sections = self::getSections($pageId);
            if ($sections) {
                $desktop = normalize_layout(default_layout_from_sections($sections));
            }
        }
        if (!empty($desktop['rows'])) {
            return mobile_layout_from_layout($desktop);
        }
        return empty_layout();
    }

    public static function saveLayout(int $pageId, string $viewport, array $layout): void
    {
        self::ensureLayoutColumns();
        $col = $viewport === 'mobile' ? 'layout_mobile' : 'layout_desktop';
        $json = json_encode(normalize_layout($layout), JSON_UNESCAPED_UNICODE);
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
