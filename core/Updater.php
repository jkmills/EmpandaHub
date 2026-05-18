<?php
declare(strict_types=1);

class Updater
{
    private const GITHUB_REPO    = 'jkmills/EmpandaHub';
    private const CACHE_FILE     = ROOT . '/config/update_cache.json';
    private const CACHE_TTL      = 86400; // 24 hours
    private const UPGRADE_LOCK   = ROOT . '/config/.upgrade_lock';

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

        // Find the named ZIP release asset; fall back to zipball
        $downloadUrl = null;
        foreach ($data['assets'] ?? [] as $asset) {
            if (str_ends_with($asset['name'] ?? '', '.zip')) {
                $downloadUrl = $asset['browser_download_url'] ?? null;
                break;
            }
        }
        $downloadUrl ??= $data['zipball_url'] ?? null;

        $result = [
            'version'      => ltrim($data['tag_name'], 'v'),
            'tag_name'     => $data['tag_name'],
            'url'          => $data['html_url'] ?? '',
            'published_at' => $data['published_at'] ?? '',
            'body'         => $data['body'] ?? '',
            'download_url' => $downloadUrl,
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
                foreach (self::splitSql($sql) as $stmt) {
                    try {
                        $db->exec($stmt);
                    } catch (\PDOException $e) {
                        $code = (int)($e->errorInfo[1] ?? 0);
                        // Swallow: duplicate column (1060), table already exists (1050), duplicate key name (1061)
                        // These mean the schema change was already applied — safe to continue.
                        if (!in_array($code, [1060, 1050, 1061], true)) {
                            throw $e;
                        }
                    }
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

    /** Split a SQL file into individual executable statements, stripping comment-only lines. */
    private static function splitSql(string $sql): array
    {
        $statements = [];
        foreach (explode(';', $sql) as $chunk) {
            $lines = array_filter(
                explode("\n", $chunk),
                fn($l) => !str_starts_with(trim($l), '--')
            );
            $stmt = trim(implode("\n", $lines));
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
        }
        return $statements;
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

    /** @return array<int, string> List of error strings; empty = all clear */
    public static function preflightCheck(): array
    {
        $errors = [];

        if (!class_exists('ZipArchive')) {
            $errors[] = 'PHP ZipArchive extension is not available on this server.';
        }
        if (!is_writable(sys_get_temp_dir())) {
            $errors[] = 'System temp directory is not writable.';
        }
        foreach (['core', 'modules', 'views', 'install', 'public'] as $dir) {
            $path = ROOT . '/' . $dir;
            if (is_dir($path) && !is_writable($path)) {
                $errors[] = "Directory not writable: $dir/";
            }
        }
        if (file_exists(self::UPGRADE_LOCK)) {
            $age = time() - (int)file_get_contents(self::UPGRADE_LOCK);
            $errors[] = 'An upgrade lock file exists' . ($age > 300 ? ' (stale — may be safe to clear)' : ' — upgrade in progress') . '.';
        }

        return $errors;
    }

    public static function upgradeLockExists(): bool
    {
        return file_exists(self::UPGRADE_LOCK);
    }

    public static function clearUpgradeLock(): void
    {
        @unlink(self::UPGRADE_LOCK);
    }

    /**
     * Download the release ZIP, extract it over ROOT, and run migrations.
     *
     * @return array<int, array{status: string, step: string, message: string}>
     */
    public static function performUpgrade(string $downloadUrl, string $newVersion): array
    {
        if (file_exists(self::UPGRADE_LOCK)) {
            return [['status' => 'error', 'step' => 'lock', 'message' => 'Upgrade already in progress.']];
        }

        @file_put_contents(self::UPGRADE_LOCK, (string)time());
        $tmpZip = null;
        $tmpDir = null;

        try {
            // Download
            $tmpZip = tempnam(sys_get_temp_dir(), 'empanda_upgrade_');
            if ($tmpZip === false) {
                throw new \RuntimeException('Could not create temp file.');
            }

            $ctx  = stream_context_create(['http' => [
                'header'          => "User-Agent: EmpandaHub-Updater/" . self::currentVersion() . "\r\n",
                'timeout'         => 120,
                'follow_location' => 1,
                'max_redirects'   => 5,
            ]]);
            $raw = @file_get_contents($downloadUrl, false, $ctx);
            if ($raw === false || strlen($raw) < 1024) {
                throw new \RuntimeException('Download failed or returned an empty file. Check server outbound connectivity.');
            }
            file_put_contents($tmpZip, $raw);

            // Extract
            $tmpDir = sys_get_temp_dir() . '/empanda_upgrade_' . time();
            mkdir($tmpDir, 0755, true);

            $zip = new \ZipArchive();
            $opened = $zip->open($tmpZip);
            if ($opened !== true) {
                throw new \RuntimeException("Could not open ZIP archive (ZipArchive error $opened).");
            }
            $zip->extractTo($tmpDir);
            $zip->close();

            // Detect single-subdirectory wrapping (zipball_url produces this)
            $extractRoot = self::findExtractRoot($tmpDir);

            // Copy files, preserving config and uploads
            self::copyTree($extractRoot, ROOT, ['config/config.php', 'public/uploads']);

            // Write the new version number
            @file_put_contents(ROOT . '/VERSION', $newVersion . "\n");

            // Bust the update cache so next check sees the new version
            @unlink(self::CACHE_FILE);

            $results = [['status' => 'ok', 'step' => 'files', 'message' => 'Application files updated.']];

            // Run any pending migrations
            $db = Database::getInstance();
            self::ensureMigrationsTable($db);
            $migrations = self::runPendingMigrations();
            foreach ($migrations as $m) {
                $results[] = [
                    'status'  => $m['status'],
                    'step'    => 'migration',
                    'message' => 'Migration v' . $m['version'] . ($m['status'] === 'ok' ? ' applied.' : ': ' . ($m['message'] ?? 'error')),
                ];
            }
            if (!$migrations) {
                $results[] = ['status' => 'ok', 'step' => 'migration', 'message' => 'No pending database migrations.'];
            }

            return $results;

        } catch (\Throwable $e) {
            return [['status' => 'error', 'step' => 'upgrade', 'message' => $e->getMessage()]];
        } finally {
            if ($tmpZip && file_exists($tmpZip)) @unlink($tmpZip);
            if ($tmpDir && is_dir($tmpDir)) self::rmdirRecursive($tmpDir);
            @unlink(self::UPGRADE_LOCK);
        }
    }

    private static function findExtractRoot(string $tmpDir): string
    {
        $items = array_values(array_diff(scandir($tmpDir) ?: [], ['.', '..']));
        if (count($items) === 1 && is_dir($tmpDir . '/' . $items[0])) {
            return $tmpDir . '/' . $items[0];
        }
        return $tmpDir;
    }

    /** @param array<int, string> $skip Relative paths to skip (e.g. 'config/config.php') */
    private static function copyTree(string $src, string $dst, array $skip = []): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($src) + 1));
            foreach ($skip as $protected) {
                $protected = str_replace('\\', '/', $protected);
                if ($rel === $protected || str_starts_with($rel, $protected . '/')) {
                    continue 2;
                }
            }
            $target = $dst . DIRECTORY_SEPARATOR . $rel;
            if ($item->isDir()) {
                @mkdir($target, 0755, true);
            } else {
                if (!@copy($item->getPathname(), $target)) {
                    throw new \RuntimeException("Could not write file: $rel — check file permissions.");
                }
            }
        }
    }

    private static function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
