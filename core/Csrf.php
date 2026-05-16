<?php
declare(strict_types=1);

class Csrf
{
    private const KEY = '__csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(): void
    {
        $submitted = $_POST['csrf_token'] ?? '';
        if (!hash_equals(self::token(), $submitted)) {
            http_response_code(403);
            die('Invalid CSRF token.');
        }
    }
}
