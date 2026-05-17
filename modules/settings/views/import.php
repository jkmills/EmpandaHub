<div class="page-header">
    <h1 class="page-title">Import / Restore Backup</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/settings/export" class="btn btn-secondary btn-sm">Export / Backup</a>
        <a href="<?= APP_URL ?>/settings" class="btn btn-secondary btn-sm">Back to Settings</a>
    </div>
</div>

<div class="card" style="max-width:560px">
    <p class="text-muted" style="margin-top:0;font-size:.9rem">
        Upload a <strong>.json</strong> file previously exported from EmpandaHub. Select which sections to restore below.
        Existing data is overwritten for profile and modules; for users, only <em>new</em> accounts are created — existing emails are skipped.
    </p>

    <form method="post" action="<?= APP_URL ?>/settings/import" enctype="multipart/form-data">
        <?= Csrf::field() ?>

        <div class="form-group">
            <label>Backup File *</label>
            <input type="file" name="backup" accept=".json,application/json" required>
        </div>

        <h4 style="margin:.8rem 0 .4rem">Restore Options</h4>
        <div style="display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                <input type="checkbox" name="restore_profile" value="1" checked style="width:15px;height:15px;margin:0;flex-shrink:0">
                <span><strong>Organization Profile</strong> — name, brand color, timezone, fiscal year start</span>
            </label>
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                <input type="checkbox" name="restore_modules" value="1" checked style="width:15px;height:15px;margin:0;flex-shrink:0">
                <span><strong>Module Visibility</strong> — which modules are enabled</span>
            </label>
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                <input type="checkbox" name="restore_users" value="1" style="width:15px;height:15px;margin:0;flex-shrink:0">
                <span><strong>Users</strong> — new accounts only; a temporary password will be generated for each</span>
            </label>
        </div>

        <div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:.375rem;padding:.6rem .8rem;font-size:.85rem;margin-bottom:1rem">
            <strong>Note:</strong> Logo files are not included in the backup and will not be restored. Re-upload the logo manually if needed.
        </div>

        <button type="submit" class="btn btn-primary" data-confirm="This will overwrite current settings for the selected sections. Continue?">Restore Backup</button>
    </form>
</div>
