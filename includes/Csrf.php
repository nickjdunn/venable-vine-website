<?php

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        return isset($_SESSION[self::TOKEN_KEY])
            && is_string($token)
            && hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    public static function requireValid(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::validate($token)) {
            http_response_code(403);
            if (is_ajax()) {
                json_response(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
            }
            die('Invalid CSRF token.');
        }
    }
}
