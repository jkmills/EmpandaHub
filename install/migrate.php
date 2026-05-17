#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * EmpandaHub CLI Migration Runner
 *
 * Usage:
 *   php install/migrate.php           — apply all pending migrations
 *   php install/migrate.php --status  — show pending migrations without running
 *   php install/migrate.php --check   — exit 0 if up to date, 1 if pending
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only.');
}

define('ROOT', dirname(__DIR__));

$configPath = ROOT . '/config/config.php';
if (!file_exists($configPath)) {
    fwrite(STDERR, "Error: config/config.php not found. Run the installer first.\n");
    exit(1);
}

require $configPath;

if (file_exists(ROOT . '/vendor/autoload.php')) {
    require ROOT . '/vendor/autoload.php';
}

foreach (['Database', 'Updater'] as $cls) {
    require ROOT . '/core/' . $cls . '.php';
}

$args        = array_slice($argv ?? [], 1);
$statusOnly  = in_array('--status', $args, true);
$checkOnly   = in_array('--check', $args, true);

$db = Database::getInstance();
Updater::ensureMigrationsTable($db);

$current  = Updater::currentVersion();
$dbVer    = Updater::installedDbVersion();
$pending  = Updater::pendingMigrations();

echo "EmpandaHub Migration Runner\n";
echo "  Code version : $current\n";
echo "  DB version   : $dbVer\n";
echo "  Pending      : " . count($pending) . " migration(s)\n\n";

if (empty($pending)) {
    echo "Database is up to date.\n";
    exit(0);
}

if ($checkOnly) {
    exit(1);
}

foreach ($pending as $m) {
    echo "  Pending: v{$m['version']} — " . basename($m['file']) . "\n";
}

if ($statusOnly) {
    exit(count($pending) > 0 ? 1 : 0);
}

echo "\nRunning migrations...\n";

$results = Updater::runPendingMigrations();
$exitCode = 0;

foreach ($results as $r) {
    if ($r['status'] === 'ok') {
        echo "  [OK]    v{$r['version']}\n";
    } else {
        echo "  [FAIL]  v{$r['version']}: " . ($r['message'] ?? 'unknown error') . "\n";
        $exitCode = 1;
    }
}

if ($exitCode === 0) {
    echo "\nAll migrations applied successfully.\n";
} else {
    echo "\nMigration failed. Database may be in a partial state — restore from backup if needed.\n";
}

exit($exitCode);
