<?php
/** @var array $release — from Updater::latestRelease() or null */
/** @var string $currentVersion */
/** @var string $dbVersion */
/** @var array $pending */
/** @var array $migrationResults */
/** @var bool $hasUpdate */
$hasUpdate = isset($release) && $release && version_compare($release['version'] ?? '0', $currentVersion, '>');
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Updates &amp; Migrations</h2>
        <p class="page-subtitle">Manage software version and database migrations</p>
    </div>
</div>

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
    <div class="card-header"><h3>Upgrade Instructions</h3></div>
    <div class="card-body" style="font-size:.875rem;color:#475569">
        <ol style="margin:0;padding-left:1.25rem;line-height:1.8">
            <li>Download the latest release ZIP from <a href="https://github.com/jkmills/EmpandaHub/releases" target="_blank" rel="noopener">GitHub Releases</a>.</li>
            <li>Back up your database: <code>mysqldump -u USER -p DBNAME &gt; backup.sql</code></li>
            <li>Replace all application files (do <strong>not</strong> overwrite <code>config/config.php</code> or <code>public/uploads/</code>).</li>
            <li>Run <code>composer install --no-dev</code> in the app root.</li>
            <li>Return here and click <strong>Run Migrations</strong> if pending migrations appear above.</li>
            <li>Alternatively: <code>php install/migrate.php</code> from the command line.</li>
        </ol>

        <div style="margin-top:1rem;padding:.75rem;background:#f8fafc;border-radius:.375rem">
            <strong>Force update check:</strong>
            <form method="post" action="<?= APP_URL ?>/settings/updates/check" style="display:inline;margin-left:.5rem">
                <?= Csrf::field() ?>
                <button type="submit" style="background:none;border:none;color:var(--brand);cursor:pointer;font-size:.875rem;padding:0;text-decoration:underline">Check for updates now</button>
            </form>
        </div>
    </div>
</div>
