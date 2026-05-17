<div class="page-header">
    <h1 class="page-title">Restore Complete</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/settings" class="btn btn-primary btn-sm">Go to Settings</a>
        <a href="<?= APP_URL ?>/settings/import" class="btn btn-secondary btn-sm">Import Another</a>
    </div>
</div>

<div class="card" style="max-width:600px">
    <h3 style="margin-top:0">What was restored</h3>
    <?php if ($log): ?>
    <ul style="margin:.25rem 0 0;padding-left:1.2rem">
        <?php foreach ($log as $line): ?>
        <li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="text-muted">Nothing was selected to restore.</p>
    <?php endif; ?>
</div>

<?php if ($newUsers): ?>
<div class="card mt-2" style="max-width:600px">
    <h3 style="margin-top:0">Temporary Passwords for Imported Users</h3>
    <p class="text-muted" style="font-size:.88rem">
        Save these now — they are shown only once. Each user should change their password after first login.
    </p>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Temp Password</th></tr></thead>
        <tbody>
        <?php foreach ($newUsers as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['name'],  ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($u['role'],  ENT_QUOTES, 'UTF-8') ?></td>
            <td><code><?= htmlspecialchars($u['temp_password'], ENT_QUOTES, 'UTF-8') ?></code></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p style="font-size:.8rem;color:var(--danger,#dc2626);margin-top:.5rem">
        This page will not show these passwords again. Copy them before navigating away.
    </p>
</div>
<?php endif; ?>
