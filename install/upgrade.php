<?php
declare(strict_types=1);

/**
 * EmpandaHub Web Upgrade Runner
 *
 * Use this script to apply pending database migrations after updating the
 * codebase files. Requires super_admin credentials for authentication.
 *
 * Security: Delete or rename this file after completing an upgrade.
 */

$configPath = __DIR__ . '/../config/config.php';

if (!file_exists($configPath)) {
    die('<h2>Not installed.</h2><p>Run <code>install/install.php</code> first.</p>');
}

define('ROOT', dirname(__DIR__));
require $configPath;

if (file_exists(ROOT . '/vendor/autoload.php')) {
    require ROOT . '/vendor/autoload.php';
}

foreach (['Database', 'Updater'] as $cls) {
    require ROOT . '/core/' . $cls . '.php';
}

$step   = 'auth';
$errors = [];
$results = [];
$pending = [];

// Already authenticated via POST token stored in session
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'authenticate') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        $db  = Database::getInstance();
        $row = $db->prepare('SELECT id, password, role FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $row->execute([$email]);
        $user = $row->fetch();

        if ($user && in_array($user['role'], ['super_admin']) && password_verify($pass, $user['password'])) {
            $_SESSION['upgrade_auth'] = true;
            $step = 'confirm';
        } else {
            $errors[] = 'Invalid credentials or insufficient role. Super admin required.';
            $step = 'auth';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'run' && !empty($_SESSION['upgrade_auth'])) {
        $db = Database::getInstance();
        Updater::ensureMigrationsTable($db);

        $pending = Updater::pendingMigrations();
        if ($pending) {
            $results = Updater::runPendingMigrations();
        }
        $step = 'done';
        unset($_SESSION['upgrade_auth']);
    }
} elseif (!empty($_SESSION['upgrade_auth'])) {
    $step = 'confirm';
}

$currentVersion = Updater::currentVersion();
try {
    $pending = Updater::pendingMigrations();
} catch (\Throwable) {
    $pending = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EmpandaHub — Upgrade</title>
<style>
*{box-sizing:border-box}
body{font-family:system-ui,sans-serif;background:#f8fafc;margin:0;padding:2rem;color:#1e293b}
.box{max-width:600px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:.6rem;padding:2rem;box-shadow:0 1px 4px rgba(0,0,0,.07)}
h1{margin:0 0 .4rem;font-size:1.4rem;color:#2563eb}
.sub{color:#64748b;font-size:.875rem;margin:0 0 1.5rem}
.form-group{margin-bottom:1rem}
label{display:block;font-size:.85rem;font-weight:600;margin-bottom:.3rem}
input{width:100%;padding:.5rem .75rem;border:1px solid #e2e8f0;border-radius:.375rem;font-size:.9rem}
input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.err{color:#dc2626;font-size:.8rem;margin-top:.2rem}
.alert{padding:.8rem 1rem;border-radius:.375rem;margin-bottom:1rem;font-size:.88rem}
.alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}
.alert-success{background:#dcfce7;border:1px solid #bbf7d0;color:#166534}
.alert-info{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}
.btn{display:inline-block;padding:.5rem 1.2rem;background:#2563eb;color:#fff;border:none;border-radius:.375rem;font-size:.9rem;font-weight:600;cursor:pointer}
.btn:hover{opacity:.9}
.btn-warn{background:#dc2626}
.mig-list{list-style:none;padding:0;margin:.5rem 0}
.mig-list li{padding:.4rem .6rem;border-radius:.3rem;margin:.2rem 0;font-family:monospace;font-size:.875rem}
.mig-ok{background:#dcfce7;color:#166534}
.mig-error{background:#fee2e2;color:#991b1b}
.mig-pending{background:#f1f5f9;color:#475569}
.version-badge{display:inline-block;background:#eff6ff;color:#1e40af;padding:.15rem .5rem;border-radius:.25rem;font-size:.8rem;font-weight:600}
</style>
</head>
<body>
<div class="box">
<h1>EmpandaHub Upgrade</h1>
<p class="sub">Database migration runner — version <span class="version-badge"><?= htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8') ?></span></p>

<?php if ($step === 'auth'): ?>

<?php foreach ($errors as $e): ?>
<div class="alert alert-error"><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
<?php endforeach; ?>

<div class="alert alert-info">Sign in with a <strong>super_admin</strong> account to run database migrations.</div>

<form method="post">
<input type="hidden" name="action" value="authenticate">
<div class="form-group">
    <label>Email</label>
    <input type="email" name="email" autocomplete="username" required>
</div>
<div class="form-group">
    <label>Password</label>
    <input type="password" name="password" autocomplete="current-password" required>
</div>
<button type="submit" class="btn">Authenticate</button>
</form>

<?php elseif ($step === 'confirm'): ?>

<?php if (empty($pending)): ?>
<div class="alert alert-success">
    <strong>Up to date!</strong> No pending migrations.
</div>
<p><a href="<?= defined('APP_URL') ? htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') : '/' ?>">Return to app &rarr;</a></p>
<?php else: ?>
<div class="alert alert-info">
    <strong><?= count($pending) ?> pending migration(s)</strong> will be applied in order:
</div>
<ul class="mig-list">
<?php foreach ($pending as $m): ?>
    <li class="mig-pending">v<?= htmlspecialchars($m['version'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(basename($m['file']), ENT_QUOTES, 'UTF-8') ?></li>
<?php endforeach; ?>
</ul>
<p style="font-size:.85rem;color:#64748b">Back up your database before proceeding.</p>
<form method="post">
<input type="hidden" name="action" value="run">
<button type="submit" class="btn btn-warn">Run Migrations</button>
</form>
<?php endif; ?>

<?php elseif ($step === 'done'): ?>

<?php
$hasError = false;
foreach ($results as $r) { if ($r['status'] === 'error') { $hasError = true; break; } }
?>

<?php if ($hasError): ?>
<div class="alert alert-error"><strong>Migration failed.</strong> See details below. Your database may be in a partial state — restore from backup if needed.</div>
<?php else: ?>
<div class="alert alert-success"><strong>Upgrade complete!</strong> All migrations applied successfully.</div>
<?php endif; ?>

<?php if ($results): ?>
<ul class="mig-list">
<?php foreach ($results as $r): ?>
    <li class="<?= $r['status'] === 'ok' ? 'mig-ok' : 'mig-error' ?>">
        v<?= htmlspecialchars($r['version'], ENT_QUOTES, 'UTF-8') ?> — <?= $r['status'] === 'ok' ? 'Applied' : 'Error: ' . htmlspecialchars($r['message'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>No migrations were pending.</p>
<?php endif; ?>

<?php if (!$hasError): ?>
<div class="alert alert-info" style="margin-top:1rem"><strong>Security reminder:</strong> Delete <code>install/upgrade.php</code> now that the upgrade is complete.</div>
<p><a href="<?= defined('APP_URL') ? htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') : '/' ?>">Return to app &rarr;</a></p>
<?php endif; ?>

<?php endif; ?>

</div>
</body>
</html>
