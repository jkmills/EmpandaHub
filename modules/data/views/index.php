<?php
$modules = [
    'crm'        => 'CRM / Contacts',
    'membership' => 'Membership',
    'donors'     => 'Donors',
    'volunteers' => 'Volunteers',
    'events'     => 'Events',
    'grants'     => 'Grants',
];
$tableLabels = [
    'contacts'           => 'Contacts',
    'contact_tags'       => 'Contact Tags',
    'contact_notes'      => 'Contact Notes',
    'board_positions'    => 'Board Positions',
    'membership_tiers'   => 'Membership Tiers',
    'memberships'        => 'Memberships',
    'dues_payments'      => 'Dues Payments',
    'membership_history' => 'Membership History',
    'campaigns'          => 'Campaigns',
    'donations'          => 'Donations',
    'volunteer_shifts'   => 'Volunteer Shifts',
    'volunteers'         => 'Volunteers',
    'volunteer_hours'    => 'Volunteer Hours',
    'events'             => 'Events',
    'event_registrations'=> 'Registrations',
    'funders'            => 'Funders',
    'grants'             => 'Grants',
    'grant_reports'      => 'Grant Reports',
    'transactions'       => 'Transactions',
];
?>
<div class="page-header">
    <h1 class="page-title">Data Management</h1>
</div>

<p class="text-muted" style="margin-bottom:1.5rem">
    Back up, wipe, and restore your organization's data by module. Transactions are automatically included whenever a financial module (Donors, Membership, Events, or Grants) is selected.
</p>

<!-- Current record counts -->
<div class="card" style="margin-bottom:1.5rem">
    <h3 style="margin-top:0">Current Data Summary</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.5rem">
        <?php foreach ($tableLabels as $tbl => $label): ?>
        <?php if (!isset($counts[$tbl])) continue; ?>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:.5rem .75rem;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:.83rem;color:#475569"><?= $label ?></span>
            <strong style="font-size:1rem"><?= number_format($counts[$tbl]) ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- BACKUP -->
<div class="card" style="margin-bottom:1.5rem">
    <h3 style="margin-top:0">Backup</h3>
    <p class="text-muted" style="font-size:.88rem;margin-top:0">Download a JSON file containing all selected module data. Store it securely — it contains contact information.</p>
    <form method="post" action="<?= APP_URL ?>/data/backup">
        <?= Csrf::field() ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem;margin-bottom:1rem">
            <?php foreach ($modules as $key => $label): ?>
            <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;cursor:pointer;padding:.35rem .5rem;border:1px solid #e2e8f0;border-radius:5px">
                <input type="checkbox" name="modules[]" value="<?= $key ?>" checked style="margin:0;width:15px;height:15px;flex-shrink:0">
                <?= $label ?>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Download Backup</button>
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="this.closest('form').querySelectorAll('input[type=checkbox]').forEach(c=>c.checked=true)">All</button>
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="this.closest('form').querySelectorAll('input[type=checkbox]').forEach(c=>c.checked=false)">None</button>
        </div>
    </form>
</div>

<!-- RESTORE -->
<div class="card" style="margin-bottom:1.5rem">
    <h3 style="margin-top:0">Restore</h3>
    <p class="text-muted" style="font-size:.88rem;margin-top:0">
        Upload a backup file exported from EmpandaHub. Records are inserted with new IDs; foreign key references are remapped automatically.
    </p>
    <div style="background:#fffbeb;border:1px solid #fbbf24;border-radius:6px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.85rem">
        <strong>Cross-module dependencies:</strong> Membership, Donors, Volunteers, and Events all reference CRM contacts.
        If you restore those modules without CRM, rows that reference contacts will fail to insert.
        Always back up and restore dependent modules together.
    </div>
    <form method="post" action="<?= APP_URL ?>/data/restore" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label>Backup File (.json) *</label>
            <input type="file" name="backup" accept=".json,application/json" required>
        </div>

        <div class="form-group">
            <label style="margin-bottom:.4rem;display:block">Modules to Restore</label>
            <p class="text-muted" style="font-size:.82rem;margin:.1rem 0 .6rem">
                Only modules present in the backup file will be restored regardless of selection.
            </p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem">
                <?php foreach ($modules as $key => $label): ?>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;cursor:pointer;padding:.35rem .5rem;border:1px solid #e2e8f0;border-radius:5px">
                    <input type="checkbox" name="modules[]" value="<?= $key ?>" checked style="margin:0;width:15px;height:15px;flex-shrink:0">
                    <?= $label ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem">
                <input type="checkbox" name="wipe_first" value="1" style="margin:0;width:15px;height:15px;flex-shrink:0">
                <span><strong>Wipe selected modules before restoring</strong> <span class="text-muted">(recommended for a clean restore)</span></span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Restore from Backup</button>
    </form>
</div>

<!-- DANGER ZONE: WIPE -->
<?php if (Auth::hasRole('super_admin')): ?>
<div class="card" style="border:2px solid #dc2626;margin-bottom:1.5rem">
    <h3 style="margin-top:0;color:#dc2626">Danger Zone — Wipe Data</h3>
    <p style="font-size:.88rem;color:#64748b;margin-top:0">
        Permanently deletes all records for the selected modules. <strong>This cannot be undone.</strong>
        Take a backup first. Only the Super Admin role can perform this action.
    </p>

    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.85rem">
        <strong>Dependency warning:</strong> Wiping CRM will orphan foreign key references in Membership, Donors, Volunteers, and Events.
        For a full reset, select all modules and wipe at once.
    </div>

    <form method="post" action="<?= APP_URL ?>/data/wipe">
        <?= Csrf::field() ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem;margin-bottom:1rem">
            <?php foreach ($modules as $key => $label): ?>
            <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;cursor:pointer;padding:.35rem .5rem;border:1px solid #fecaca;border-radius:5px;background:#fff">
                <input type="checkbox" name="modules[]" value="<?= $key ?>" style="margin:0;width:15px;height:15px;flex-shrink:0;accent-color:#dc2626">
                <?= $label ?>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-1" style="margin-bottom:1rem">
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="this.closest('form').querySelectorAll('input[type=checkbox]').forEach(c=>c.checked=true)">Select All</button>
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="this.closest('form').querySelectorAll('input[type=checkbox]').forEach(c=>c.checked=false)">Clear</button>
        </div>

        <div class="form-group">
            <label style="font-weight:600;color:#dc2626">
                Type your organization name to confirm: <code><?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8') ?></code>
            </label>
            <input type="text" name="confirm_name"
                   placeholder="<?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8') ?>"
                   data-match="<?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8') ?>"
                   autocomplete="off" style="border-color:#fca5a5;max-width:400px">
        </div>

        <button type="submit" class="btn btn-danger" style="background:#dc2626;color:#fff;border-color:#dc2626">
            Wipe Selected Data
        </button>
    </form>
</div>
<?php endif; ?>
