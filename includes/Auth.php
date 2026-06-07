<?php

class Auth
{
    private const SESSION_USER_KEY = 'admin_user';
    private const SESSION_LAST_ACTIVITY = 'admin_last_activity';

    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM admin_users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        $_SESSION[self::SESSION_USER_KEY] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
        ];
        $_SESSION[self::SESSION_LAST_ACTIVITY] = time();
        $update = Database::connection()->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?');
        $update->execute([$user['id']]);
        return true;
    }

    public static function check(): bool
    {
        if (!isset($_SESSION[self::SESSION_USER_KEY])) {
            return false;
        }
        $lifetime = app_config('session_lifetime', 7200);
        $last = $_SESSION[self::SESSION_LAST_ACTIVITY] ?? 0;
        if (time() - $last > $lifetime) {
            self::logout();
            return false;
        }
        $_SESSION[self::SESSION_LAST_ACTIVITY] = time();
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_USER_KEY] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/admin/login.php');
        }
    }

    public static function requireOwner(): void
    {
        self::requireLogin();
        if ((self::user()['role'] ?? '') !== 'owner') {
            flash('error', 'You do not have permission to access that page.');
            redirect('/admin/dashboard.php');
        }
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_USER_KEY], $_SESSION[self::SESSION_LAST_ACTIVITY]);
    }

    public static function isOwner(): bool
    {
        return (self::user()['role'] ?? '') === 'owner';
    }
}
