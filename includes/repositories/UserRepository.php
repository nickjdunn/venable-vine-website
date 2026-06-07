<?php

class UserRepository
{
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT id, email, name, role, last_login, created_at FROM admin_users ORDER BY name ASC'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM admin_users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $email, string $name, string $password, string $role = 'editor'): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO admin_users (email, name, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            strtolower(trim($email)),
            trim($name),
            password_hash($password, PASSWORD_DEFAULT),
            $role === 'owner' ? 'owner' : 'editor',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        if (!empty($data['password'])) {
            $stmt = Database::connection()->prepare(
                'UPDATE admin_users SET email = ?, name = ?, role = ?, password_hash = ? WHERE id = ?'
            );
            $stmt->execute([
                strtolower(trim($data['email'])),
                trim($data['name']),
                $data['role'] === 'owner' ? 'owner' : 'editor',
                password_hash($data['password'], PASSWORD_DEFAULT),
                $id,
            ]);
        } else {
            $stmt = Database::connection()->prepare(
                'UPDATE admin_users SET email = ?, name = ?, role = ? WHERE id = ?'
            );
            $stmt->execute([
                strtolower(trim($data['email'])),
                trim($data['name']),
                $data['role'] === 'owner' ? 'owner' : 'editor',
                $id,
            ]);
        }
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
    }

    public static function countOwners(): int
    {
        return (int) Database::connection()->query("SELECT COUNT(*) FROM admin_users WHERE role = 'owner'")->fetchColumn();
    }
}
