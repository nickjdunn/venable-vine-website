<?php

class EventRepository
{
    public static function upcoming(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM events WHERE end_at >= NOW()';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY start_at ASC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function past(int $limit = 50): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM events WHERE end_at < NOW() ORDER BY end_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function currentOrNext(): ?array
    {
        $stmt = Database::connection()->query(
            'SELECT * FROM events WHERE is_active = 1 AND end_at >= NOW() ORDER BY start_at ASC LIMIT 1'
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM events WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO events (title, details, start_at, end_at, address, lat, lng, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'] ?? null,
            $data['details'] ?? null,
            $data['start_at'],
            $data['end_at'],
            $data['address'],
            $data['lat'] ?? null,
            $data['lng'] ?? null,
            !empty($data['is_active']) ? 1 : 0,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE events SET title = ?, details = ?, start_at = ?, end_at = ?, address = ?, lat = ?, lng = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['title'] ?? null,
            $data['details'] ?? null,
            $data['start_at'],
            $data['end_at'],
            $data['address'],
            $data['lat'] ?? null,
            $data['lng'] ?? null,
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
    }

    public static function forCalendar(): array
    {
        $rows = Database::connection()->query(
            'SELECT id, title, start_at, end_at, address, is_active FROM events ORDER BY start_at ASC'
        )->fetchAll();
        $events = [];
        foreach ($rows as $row) {
            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'] ?: $row['address'],
                'start' => $row['start_at'],
                'end' => $row['end_at'],
                'extendedProps' => [
                    'address' => $row['address'],
                    'is_active' => (bool) $row['is_active'],
                ],
            ];
        }
        return $events;
    }
}
