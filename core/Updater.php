<?php
declare(strict_types=1);

class Updater
{
    private const GITHUB_REPO = 'jkmills/EmpandaHub';
    private const CACHE_FILE  = ROOT . '/config/update_cache.json';
    private const CACHE_TTL   = 86400; // 24 hours

    public static function currentVersion(): string
    {
        $vf = ROOT . '/VERSION';
        return file_exists($vf) ? trim((string)file_get_contents($vf)) : '1.0.0';
    }

    public static function installedDbVersion(): string
    {
        try {
            $db  = Database::getInstance();
            $row = $db->query('SELECT version FROM migrations ORDER BY applied_at DESC, id DESC LIMIT 1')->fetch();
            return $row ? $row['version'] : '0.0.0';
        } catch (\Throwable) {
            return '0.0.0';
        }
    }

    /** @return array<int, array{version: string, file: string}> */
    public static function pendingMigrations(): array
    {
        $installed = self::installedDbVersion();
        $dir       = ROOT . '/install/migrations';
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/v*.sql') ?: [];
        sort($files);

        $pending = [];
        foreach ($files as $file) {
            $ver = ltrim(substr(basename($file), 0, -4), 'v');
            if (version_compare($ver, $installed, '>')) {
                $pending[] = ['version' => $ver, 'file' => $file];
            }
        }
        return $pending;
    }

    /** @return array{version: string, tag_name: string, url: string, published_at: string, body: string, cached_at: int}|null */
    public static function latestRelease(bool $forceRefresh = false): ?array
    {
        if (!$forceRefresh && file_exists(self::CACHE_FILE)) {
            $cache = json_decode((string)file_get_contents(self::CACHE_FILE), true);
            if (is_array($cache) && (time() - ($cache['cached_at'] ?? 0)) < self::CACHE_TTL) {
                return $cache;
            }
        }

        $url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';
        $ctx = stream_context_create(['http' => [
            'header'  => "User-Agent: EmpandaHub-Updater/" . self::currentVersion() . "\r\n",
            'timeout' => 5,
        ]]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) return null;

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['tag_name'])) return null;

        $result = [
            'version'      => ltrim($data['tag_name'], 'v'),
            'tag_name'     => $data['tag_name'],
            'url'          => $data['html_url'] ?? '',
            'published_at' => $data['published_at'] ?? '',
            'body'         => $data['body'] ?? '',
            'cached_at'    => time(),
        ];

        @file_put_contents(self::CACHE_FILE, json_encode($result, JSON_PRETTY_PRINT));
        return $result;
    }

    public static function hasUpdate(): bool
    {
        $release = self::latestRelease();
        if (!$release) return false;
        return version_compare($release['version'], self::currentVersion(), '>');
    }

    /** @return array<int, array{version: string, status: string, message?: string}> */
    public static function runPendingMigrations(): array
    {
        $db      = Database::getInstance();
        $pending = self::pendingMigrations();
        $results = [];

        self::ensureMigrationsTable($db);

        foreach ($pending as $m) {
            try {
                $sql = (string)file_get_contents($m['file']);
                if (trim($sql) && !str_starts_with(trim($sql), '--')) {
                    $db->exec($sql);
                }
                $db->prepare('INSERT IGNORE INTO migrations (version) VALUES (?)')->execute([$m['version']]);
                $results[] = ['version' => $m['version'], 'status' => 'ok'];
            } catch (\Throwable $e) {
                $results[] = ['version' => $m['version'], 'status' => 'error', 'message' => $e->getMessage()];
                break;
            }
        }

        return $results;
    }

    public static function ensureMigrationsTable(\PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS migrations (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            version    VARCHAR(20) NOT NULL,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_migrations_version (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
}
