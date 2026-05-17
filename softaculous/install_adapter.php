<?php
declare(strict_types=1);

/**
 * Softaculous Install Adapter
 *
 * Softaculous calls this script with environment variables or CLI arguments.
 * This adapter maps those into EmpandaHub's install process.
 *
 * CLI usage (for Softaculous internal use):
 *   php softaculous/install_adapter.php \
 *     --db-host=localhost \
 *     --db-name=mydb \
 *     --db-user=myuser \
 *     --db-pass=secret \
 *     --org-name="My Nonprofit" \
 *     --admin-name="Admin" \
 *     --admin-email=admin@example.com \
 *     --admin-pass=changeme123 \
 *     --app-url=https://example.com
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$opts = getopt('', [
    'db-host:', 'db-name:', 'db-user:', 'db-pass:',
    'org-name:', 'admin-name:', 'admin-email:', 'admin-pass:',
    'app-url:',
]);

$required = ['db-name', 'db-user', 'org-name', 'admin-name', 'admin-email', 'admin-pass', 'app-url'];
foreach ($required as $key) {
    if (empty($opts[$key])) {
        fwrite(STDERR, "Missing required argument: --$key\n");
        exit(1);
    }
}

$dbHost     = $opts['db-host']     ?? 'localhost';
$dbName     = $opts['db-name'];
$dbUser     = $opts['db-user'];
$dbPass     = $opts['db-pass']     ?? '';
$orgName    = $opts['org-name'];
$adminName  = $opts['admin-name'];
$adminEmail = $opts['admin-email'];
$adminPass  = $opts['admin-pass'];
$appUrl     = rtrim($opts['app-url'], '/');

$root       = dirname(__DIR__);
$configPath = $root . '/config/config.php';
$schemaPath = $root . '/install/schema.sql';

if (file_exists($configPath)) {
    fwrite(STDERR, "Already installed (config/config.php exists).\n");
    exit(1);
}

// Connect and create database
try {
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
} catch (PDOException $e) {
    fwrite(STDERR, 'DB connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

// Run schema
try {
    $pdo->exec((string)file_get_contents($schemaPath));
} catch (PDOException $e) {
    fwrite(STDERR, 'Schema error: ' . $e->getMessage() . "\n");
    exit(1);
}

// Seed migration baseline
$appVersion = file_exists($root . '/VERSION') ? trim((string)file_get_contents($root . '/VERSION')) : '1.0.0';
$pdo->prepare('INSERT IGNORE INTO migrations (version) VALUES (?)')->execute([$appVersion]);

// Create org and super_admin
$pdo->prepare('INSERT INTO organizations (name, primary_color) VALUES (?, ?)')->execute([$orgName, '#2563eb']);
$orgId = (int)$pdo->lastInsertId();

$hash = password_hash($adminPass, PASSWORD_BCRYPT);
$pdo->prepare('INSERT INTO users (org_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)')->execute([$orgId, $adminName, $adminEmail, $hash, 'super_admin']);

// Write config.php
$config = <<<PHP
<?php
define('DB_HOST',    '$dbHost');
define('DB_NAME',    '$dbName');
define('DB_USER',    '$dbUser');
define('DB_PASS',    '$dbPass');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',   '$orgName');
define('APP_URL',    '$appUrl');
define('APP_ENV',    'production');

define('SESSION_LIFETIME', 7200);
define('UPLOAD_DIR',  __DIR__ . '/../public/uploads/');
define('UPLOAD_URL',  APP_URL . '/uploads/');

define('MAIL_HOST',      '');
define('MAIL_USER',      '');
define('MAIL_PASS',      '');
define('MAIL_FROM',      '');
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_PORT',      587);
PHP;

if (file_put_contents($configPath, $config) === false) {
    fwrite(STDERR, "Could not write config/config.php — check directory permissions.\n");
    exit(1);
}

echo "EmpandaHub installed successfully.\n";
echo "Login: $appUrl\n";
echo "Admin: $adminEmail\n";
