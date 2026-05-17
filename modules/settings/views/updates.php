<?php
/** @var array|null $release */
/** @var string $currentVersion */
/** @var string $dbVersion */
/** @var array $pending */
/** @var array $migrationResults */
/** @var array $upgradeResults */
/** @var array $preflightErrors */
/** @var bool $hasUpdate */
$hasUpdate       = isset($release) && $release && version_compare($release['version'] ?? '0', $currentVersion, '>');
$upgradeResults  = $upgradeResults ?? [];
$preflightErrors = $preflightErrors ?? [];
$backupFile      = $backupFile ?? null;
$canUpgrade      = $hasUpdate && !empty($release['download_url']);
$upgradeError    = (bool)array_filter($upgradeResults, fn($r) => $r['status'] === 'error');
$upgradeOk       = !empty($upgradeResults) && !$upgradeError;
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Updates &amp; Migrations</h2>
        <p class="page-subtitle">Manage software version and database migrations</p>
    </div>
</div>

<?php if (!empty($upgradeResults)): ?>
<?php if ($upgradeError): ?>
<div class="alert alert-error" style="margin-bottom:1.5rem">
    <strong>Upgrade failed.</strong> See details below. Your files may be in a partial state — restore from backup if needed.
</div>
<?php else: ?>
<div class="alert alert-success" style="margin-bottom:1.5rem">
    <strong>Upgrade to v<?= htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8') ?> complete!</strong>
</div>
<?php endif; ?>
<div class="card" style="margin-bottom:1.5rem">
    <div class="card-header"><h3>Upgrade Results</h3></div>
    <div class="card-body">
        <ul style="list-style:none;padding:0;margin:0">
        <?php foreach ($upgradeResults as $r): ?>
            <?php
            $bg   = match($r['status']) { 'ok' => '#dcfce7', 'warn' => '#fef9c3', default => '#fee2e2' };
            $fg   = match($r['status']) { 'ok' => '#166534', 'warn' => '#713f12', default => '#991b1b' };
            $icon = match($r['status']) { 'ok' => '&#10003;', 'warn' => '&#9888;', default => '&#10007;' };
            $step = match($r['step'] ?? '') { 'backup' => '[backup]', 'files' => '[files]', 'migration' => '[db]', default => '' };
            ?>
            <li style="padding:.4rem .5rem;border-radius:.3rem;margin:.2rem 0;font-size:.875rem;background:<?= $bg ?>;color:<?= $fg ?>">
                <?= $icon ?> <span style="font-family:monospace;opacity:.7"><?= $step ?></span> <?= htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8') ?>
                <?php if (($r['step'] ?? '') === 'backup' && $r['status'] === 'ok' && $backupFile): ?>
                &nbsp;<a href="<?= APP_URL ?>/settings/updates/upgrade/backup?file=<?= urlencode($backupFile) ?>" style="color:<?= $fg ?>;font-weight:600">Download backup &darr;</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($migrationResults)): ?>
<?php $anyError = array_filter($migrationResults, fn($r) => $r['status'] === 'error'); ?>
<?php if ($anyError): ?>
<div class="alert alert-error" style="margin-bottom:1.5rem">
    <strong>Migration error.</strong> One or more migrations failed. Your database may be in a partial state — restore from backup if needed.
</div>
<?php else: ?>
<div class="alert alert-success" style="margin-bottom:1.5rem">
    <strong>Migrations applied successfully.</strong>
</div>
<?php endif; ?>
<div class="card" style="margin-bottom:1.5rem">
    <div class="card-header"><h3>Migration Results</h3></div>
    <div class="card-body">
        <ul style="list-style:none;padding:0;margin:0">
        <?php foreach ($migrationResults as $r): ?>
            <li style="padding:.4rem .5rem;border-radius:.3rem;margin:.2rem 0;font-family:monospace;font-size:.875rem;background:<?= $r['status']==='ok'?'#dcfce7':'#fee2e2' ?>;color:<?= $r['status']==='ok'?'#166534':'#991b1b' ?>">
                v<?= htmlspecialchars($r['version'], ENT_QUOTES, 'UTF-8') ?> — <?= $r['status'] === 'ok' ? 'Applied' : 'Error: ' . htmlspecialchars($r['message'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

    <div class="card">
        <div class="card-header"><h3>Installed Version</h3></div>
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem">
                <span style="font-size:1.75rem;font-weight:700;color:var(--brand)"><?= htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($hasUpdate): ?>
                <span style="background:#fef3c7;color:#92400e;padding:.2rem .6rem;border-radius:.25rem;font-size:.75rem;font-weight:600">UPDATE AVAILABLE</span>
                <?php else: ?>
                <span style="background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:.25rem;font-size:.75rem;font-weight:600">UP TO DATE</span>
                <?php endif; ?>
            </div>
            <div style="font-size:.8rem;color:#64748b">
                <div>DB schema: v<?= htmlspecialchars($dbVersion, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if (!empty($pending)): ?>
                <div style="color:#b45309;margin-top:.3rem"><?= count($pending) ?> pending migration(s)</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($hasUpdate && $release): ?>
    <div class="card" style="border-color:#f59e0b">
        <div class="card-header" style="background:#fffbeb"><h3 style="color:#92400e">New Release: v<?= htmlspecialchars($release['version'], ENT_QUOTES, 'UTF-8') ?></h3></div>
        <div class="card-body">
            <div style="font-size:.8rem;color:#64748b;margin-bottom:.75rem">
                Released <?= htmlspecialchars(date('M j, Y', strtotime($release['published_at'])), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <a href="<?= htmlspecialchars($release['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm">View Release &rarr;</a>
        </div>
    </div>
    <?php elseif ($release): ?>
    <div class="card">
        <div class="card-header"><h3>Latest Release</h3></div>
        <div class="card-body">
            <div style="font-size:1.1rem;font-weight:600;color:#166534">v<?= htmlspecialchars($release['version'], ENT_QUOTES, 'UTF-8') ?></div>
            <div style="font-size:.8rem;color:#64748b;margin:.3rem 0">Released <?= htmlspecialchars(date('M j, Y', strtotime($release['published_at'])), ENT_QUOTES, 'UTF-8') ?></div>
            <a href="<?= htmlspecialchars($release['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="font-size:.8rem;color:var(--brand)">View on GitHub</a>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header"><h3>Release Check</h3></div>
        <div class="card-body">
            <p style="font-size:.875rem;color:#64748b">Could not reach GitHub API. Check your server's outbound connectivity.</p>
            <form method="post" action="<?= APP_URL ?>/settings/updates/check">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-sm" style="margin-top:.5rem">Retry Check</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php if (!empty($preflightErrors)): ?>
<div class="card" style="margin-bottom:1.5rem;border-color:#dc2626">
    <div class="card-header" style="background:#fee2e2"><h3 style="color:#991b1b">Upgrade Blocked</h3></div>
    <div class="card-body">
        <p style="font-size:.875rem;color:#64748b;margin-bottom:.75rem">The following issues must be resolved before the in-app upgrade can run. Fix them and try again, or upgrade manually using the instructions below.</p>
        <ul style="margin:0;padding-left:1.25rem;font-size:.875rem;color:#991b1b">
            <?php foreach ($preflightErrors as $err): ?>
            <li style="margin:.25rem 0"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if (Updater::upgradeLockExists()): ?>
        <form method="post" action="<?= APP_URL ?>/settings/updates/upgrade/clear-lock" style="margin-top:1rem">
            <?= Csrf::field() ?>
            <button type="submit" class="btn" style="background:#dc2626;font-size:.8rem">Clear Upgrade Lock</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($canUpgrade && empty($upgradeResults)): ?>
<div class="card" style="margin-bottom:1.5rem;border-color:#2563eb">
    <div class="card-header" style="background:#eff6ff">
        <h3 style="color:#1e40af">Upgrade to v<?= htmlspecialchars($release['version'], ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body">
        <p style="font-size:.875rem;color:#475569;margin-bottom:1rem">
            The in-app upgrader will download the release ZIP from GitHub, replace application files (preserving <code>config/config.php</code> and <code>public/uploads/</code>), and apply any pending database migrations automatically.
        </p>
        <div style="background:#f8fafc;border-radius:.375rem;padding:.75rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#475569">
            <strong>What will happen:</strong>
            <ul style="margin:.5rem 0 0;padding-left:1.25rem;line-height:1.8">
                <li>A full data backup is created automatically and available to download from the results page.</li>
                <li>Application files are replaced from the release ZIP (<code>config/config.php</code> and <code>public/uploads/</code> are preserved).</li>
                <li>Any pending database migrations are applied.</li>
            </ul>
            <p style="margin:.75rem 0 0;color:#b45309"><strong>Note:</strong> Ensure your server can reach GitHub over HTTPS. Do not close this tab during the upgrade.</p>
        </div>
        <form method="post" action="<?= APP_URL ?>/settings/updates/upgrade"
              onsubmit="this.querySelector('button[type=submit]').disabled=true;this.querySelector('button[type=submit]').textContent='Upgrading…';return true;">
            <?= Csrf::field() ?>
            <button type="submit" class="btn" style="background:#2563eb">
                Upgrade to v<?= htmlspecialchars($release['version'], ENT_QUOTES, 'UTF-8') ?> Now
            </button>
            <span style="font-size:.8rem;color:#64748b;margin-left:.75rem">This may take 30–60 seconds.</span>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($pending)): ?>
<div class="card" style="margin-bottom:1.5rem;border-color:#f59e0b">
    <div class="card-header" style="background:#fffbeb">
        <h3 style="color:#92400e">Pending Database Migrations</h3>
    </div>
    <div class="card-body">
        <p style="font-size:.875rem;color:#64748b;margin-bottom:1rem">These migrations must be applied to bring the database schema up to date with the current codebase. Back up your database before running.</p>
        <ul style="list-style:none;padding:0;margin:0 0 1rem">
        <?php foreach ($pending as $m): ?>
            <li style="padding:.4rem .6rem;background:#f1f5f9;border-radius:.3rem;margin:.2rem 0;font-family:monospace;font-size:.875rem;color:#475569">
                v<?= htmlspecialchars($m['version'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(basename($m['file']), ENT_QUOTES, 'UTF-8') ?>
            </li>
        <?php endforeach; ?>
        </ul>
        <form method="post" action="<?= APP_URL ?>/settings/updates/migrate" onsubmit="return confirm('Run <?= count($pending) ?> migration(s)? Back up your database first.')">
            <?= Csrf::field() ?>
            <button type="submit" class="btn" style="background:#d97706">Run <?= count($pending) ?> Migration(s)</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body" style="font-size:.875rem;color:#475569">
        <div style="margin-bottom:1rem;padding:.75rem;background:#f8fafc;border-radius:.375rem">
            <strong>Force update check:</strong>
            <form method="post" action="<?= APP_URL ?>/settings/updates/check" style="display:inline;margin-left:.5rem">
                <?= Csrf::field() ?>
                <button type="submit" style="background:none;border:none;color:var(--brand);cursor:pointer;font-size:.875rem;padding:0;text-decoration:underline">Check for updates now</button>
            </form>
        </div>

        <details>
            <summary style="cursor:pointer;font-weight:600;color:#475569;user-select:none">Manual upgrade (use if in-app upgrade fails)</summary>
            <ol style="margin:.75rem 0 0;padding-left:1.25rem;line-height:1.8">
                <li>Download the release ZIP from <a href="https://github.com/jkmills/EmpandaHub/releases" target="_blank" rel="noopener">GitHub Releases</a>.</li>
                <li>Back up your database: <code>mysqldump -u USER -p DBNAME &gt; backup.sql</code></li>
                <li>Replace all application files (do <strong>not</strong> overwrite <code>config/config.php</code> or <code>public/uploads/</code>).</li>
                <li>Composer dependencies are bundled in the ZIP — no separate install needed.</li>
                <li>Return here and click <strong>Run Migrations</strong> if pending migrations appear above.</li>
            </ol>
        </details>
    </div>
</div>
