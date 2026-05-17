<?php
declare(strict_types=1);

class Auth
{
    private const ROLES = ['super_admin', 'admin', 'staff', 'volunteer', 'readonly'];

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['org_id']  = $user['org_id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['name'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::require();
        if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'     => $_SESSION['user_id'],
            'org_id' => $_SESSION['org_id'],
            'role'   => $_SESSION['role'],
            'name'   => $_SESSION['name'],
        ];
    }

    public static function orgId(): int
    {
        return (int)($_SESSION['org_id'] ?? 0);
    }

    public static function role(): string
    {
        return $_SESSION['role'] ?? '';
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function isAtLeast(string $minRole): bool
    {
        $hierarchy = array_flip(self::ROLES);
        $current   = $hierarchy[self::role()] ?? PHP_INT_MAX;
        $min       = $hierarchy[$minRole] ?? PHP_INT_MAX;
        return $current <= $min;
    }

    public static function orgModules(): array
    {
        if (!self::check()) return [];
        if (isset($_SESSION['org_modules'])) return $_SESSION['org_modules'];
        $stmt = Database::getInstance()->prepare(
            'SELECT config_json FROM organizations WHERE id = ?'
        );
        $stmt->execute([self::orgId()]);
        $row = $stmt->fetch();
        $cfg = json_decode($row['config_json'] ?? '{}', true) ?: [];
        return $_SESSION['org_modules'] = $cfg['modules'] ?? [];
    }

    public static function clearOrgCache(): void
    {
        unset($_SESSION['org_modules']);
    }
}
