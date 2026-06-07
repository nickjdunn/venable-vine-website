<?php

class Settings
{
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key] ?? $default;
        }
        $stmt = Database::connection()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $value = $row ? $row['setting_value'] : $default;
        self::$cache[$key] = $value;
        return $value;
    }

    public static function set(string $key, ?string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
        self::$cache[$key] = $value;
    }

    public static function all(): array
    {
        $stmt = Database::connection()->query('SELECT setting_key, setting_value FROM site_settings');
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
        return $out;
    }

    public static function getMany(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = self::get($key);
        }
        return $out;
    }
}
