<?php
declare(strict_types=1);

$configPath = __DIR__ . '/../config/config.php';

// Block re-install
if (file_exists($configPath)) {
    die('<h2>Already installed.</h2><p>Delete <code>config/config.php</code> to re-run the installer.</p>');
}

$step   = 'form';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = runInstall();
}

function runInstall(): string
{
    global $errors, $configPath;

    $dbHost   = trim($_POST['db_host']   ?? 'localhost');
    $dbName   = trim($_POST['db_name']   ?? '');
    $dbUser   = trim($_POST['db_user']   ?? '');
    $dbPass   = $_POST['db_pass']   ?? '';
    $orgName  = trim($_POST['org_name']  ?? '');
    $adminName  = trim($_POST['admin_name']  ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass  = $_POST['admin_pass']  ?? '';
    $appUrl     = rtrim(trim($_POST['app_url'] ?? ''), '/');

    // Validate
    if (!$dbName)    $errors['db_name']    = 'Database name required.';
    if (!$dbUser)    $errors['db_user']    = 'DB username required.';
    if (!$orgName)   $errors['org_name']   = 'Organization name required.';
    if (!$adminName) $errors['admin_name'] = 'Admin name required.';
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors['admin_email'] = 'Valid email required.';
    if (strlen($adminPass) < 8) $errors['admin_pass'] = 'Password must be 8+ characters.';
    if (!$appUrl)    $errors['app_url']    = 'App URL required.';

    if ($errors) return 'form';

    // Check PHP extensions
    $required = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $errors['_global'] = "Required PHP extension missing: $ext";
            return 'form';
        }
    }

    // Test DB connection
    try {
        $dsn = "mysql:host=$dbHost;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");
    } catch (PDOException $e) {
        $errors['_global'] = 'DB connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        return 'form';
    }

    // Run schema
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        $errors['_global'] = 'Schema error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        return 'form';
    }

    // Seed baseline migration marker
    $appVersion = file_exists(__DIR__ . '/../VERSION') ? trim(file_get_contents(__DIR__ . '/../VERSION')) : '1.0.0';
    $pdo->prepare('INSERT IGNORE INTO migrations (version) VALUES (?)')->execute([$appVersion]);

    // Create org
    $pdo->prepare('INSERT INTO organizations (name, primary_color) VALUES (?, ?)')->execute([$orgName, '#2563eb']);
    $orgId = (int)$pdo->lastInsertId();

    // Create super_admin
    $hash = password_hash($adminPass, PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO users (org_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)')->execute([$orgId, $adminName, $adminEmail, $hash, 'super_admin']);

    // Write config.php
    $configContent = <<<PHP
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

    if (file_put_contents($configPath, $configContent) === false) {
        $errors['_global'] = 'Could not write config.php — check directory permissions.';
        return 'form';
    }

    return 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EmpandaHub — Installer</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,sans-serif;background:#f8fafc;margin:0;padding:2rem;color:#1e293b}
.box{max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:.6rem;padding:2rem;box-shadow:0 1px 4px rgba(0,0,0,.07)}
h1{margin:0 0 1.5rem;font-size:1.4rem;color:#2563eb}
.form-group{margin-bottom:1rem}label{display:block;font-size:.85rem;font-weight:600;margin-bottom:.3rem}
input{width:100%;padding:.5rem .75rem;border:1px solid #e2e8f0;border-radius:.375rem;font-size:.9rem}
input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.err{color:#dc2626;font-size:.8rem;margin-top:.2rem}
.alert{padding:.8rem 1rem;border-radius:.375rem;margin-bottom:1rem;font-size:.88rem}
.alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}
.alert-success{background:#dcfce7;border:1px solid #bbf7d0;color:#166534}
.btn{display:inline-block;padding:.5rem 1.2rem;background:#2563eb;color:#fff;border:none;border-radius:.375rem;font-size:.9rem;font-weight:600;cursor:pointer}
.btn:hover{opacity:.9}
section-label{display:block;font-weight:700;margin:1.5rem 0 .5rem;border-bottom:1px solid #e2e8f0;padding-bottom:.3rem}
</style>
</head>
<body>
<div class="box">
<h1>EmpandaHub Installer</h1>

<?php if ($step === 'success'): ?>
<div class="alert alert-success">
    <strong>Installation complete!</strong><br>
    Your site is ready. <a href="<?= htmlspecialchars($_POST['app_url'] ?? '/', ENT_QUOTES, 'UTF-8') ?>">Log in now &rarr;</a>
</div>
<p><strong>Security:</strong> Delete or rename <code>install/install.php</code> before going live.</p>

<?php else: ?>

<?php if (!empty($errors['_global'])): ?>
<div class="alert alert-error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post">
<span class="section-label" style="display:block;font-weight:700;margin:0 0 .8rem;border-bottom:1px solid #e2e8f0;padding-bottom:.3rem">Database</span>

<div class="form-group">
    <label>DB Host</label>
    <input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost', ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label>Database Name</label>
    <input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($errors['db_name'])): ?><p class="err"><?= htmlspecialchars($errors['db_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</div>
<div class="form-group">
    <label>DB Username</label>
    <input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($errors['db_user'])): ?><p class="err"><?= htmlspecialchars($errors['db_user'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</div>
<div class="form-group">
    <label>DB Password</label>
    <input type="password" name="db_pass">
</div>

<span style="display:block;font-weight:700;margin:1.5rem 0 .8rem;border-bottom:1px solid #e2e8f0;padding-bottom:.3rem">Organization</span>

<div class="form-group">
    <label>Organization Name</label>
    <input name="org_name" value="<?= htmlspecialchars($_POST['org_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($errors['org_name'])): ?><p class="err"><?= htmlspecialchars($errors['org_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</div>
<div class="form-group">
    <label>App URL <small style="font-weight:normal;color:#64748b">(no trailing slash)</small></label>
    <input name="app_url" value="<?= htmlspecialchars($_POST['app_url'] ?? 'http://localhost:8080', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($errors['app_url'])): ?><p class="err"><?= htmlspecialchars($errors['app_url'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</div>

<span style="display:block;font-weight:700;margin:1.5rem 0 .8rem;border-bottom:1px solid #e2e8f0;padding-bottom:.3rem">Admin Account</span>

<div class="form-group">
    <label>Name</label>
    <input name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($errors['admin_name'])): ?><p class="err"><?= htmlspecialchars($errors['admin_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</div>
<div class="form-group">
    <label>Email</label>
    <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($errors['admin_email'])): ?><p class="err"><?= htmlspecialchars($errors['admin_email'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</div>
<div class="form-group">
    <label>Password <small style="font-weight:normal;color:#64748b">(8+ chars)</small></label>
    <input type="password" name="admin_pass">
    <?php if (!empty($errors['admin_pass'])): ?><p class="err"><?= htmlspecialchars($errors['admin_pass'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</div>

<div style="margin-top:1.5rem">
    <button type="submit" class="btn">Install EmpandaHub</button>
</div>
</form>
<?php endif; ?>
</div>
</body>
</html>
