<?php
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
$totalInserted = array_sum($result['counts']);
$hasErrors     = !empty($result['errors']);
?>
<div class="page-header">
    <h1 class="page-title">Restore <?= $hasErrors ? 'Completed with Warnings' : 'Complete' ?></h1>
    <a href="<?= APP_URL ?>/data" class="btn btn-secondary btn-sm">Back to Data Management</a>
</div>

<?php if (!$hasErrors): ?>
<div class="flash flash-success">
    Restore completed successfully. <?= number_format($totalInserted) ?> records imported from backup
    <em><?= htmlspecialchars($meta['org_name'] ?? '?', ENT_QUOTES, 'UTF-8') ?></em>
    (exported <?= htmlspecialchars($meta['exported_at'] ?? '?', ENT_QUOTES, 'UTF-8') ?>).
    <?= $wipedFirst ? ' Existing data was wiped first.' : '' ?>
</div>
<?php else: ?>
<div class="flash flash-warning">
    Restore finished with <?= count($result['errors']) ?> warning(s). <?= number_format($totalInserted) ?> records were imported.
    Rows that could not be inserted (e.g. unresolvable foreign keys) are listed below.
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1.5rem">

<div class="card">
    <h3 style="margin-top:0">Records Imported</h3>
    <table style="width:100%">
    <thead><tr><th>Table</th><th style="text-align:right">Rows</th></tr></thead>
    <tbody>
    <?php foreach ($result['counts'] as $table => $n): ?>
    <tr>
        <td><?= htmlspecialchars($tableLabels[$table] ?? $table, ENT_QUOTES, 'UTF-8') ?></td>
        <td style="text-align:right;font-weight:<?= $n > 0 ? '600' : '400' ?>;color:<?= $n > 0 ? 'inherit' : '#94a3b8' ?>">
            <?= number_format($n) ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="border-top:2px solid #e2e8f0">
            <td><strong>Total</strong></td>
            <td style="text-align:right"><strong><?= number_format($totalInserted) ?></strong></td>
        </tr>
    </tfoot>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0">Backup Info</h3>
    <table style="width:100%">
        <tr><td class="text-muted" style="width:40%">Source Org</td><td><?= htmlspecialchars($meta['org_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Exported</td><td><?= htmlspecialchars($meta['exported_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">App Version</td><td><?= htmlspecialchars($meta['version'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Modules in File</td><td><?= htmlspecialchars(implode(', ', $meta['modules'] ?? []), ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Modules Restored</td><td><?= htmlspecialchars(implode(', ', $modules), ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Wiped First</td><td><?= $wipedFirst ? 'Yes' : 'No (append mode)' ?></td></tr>
    </table>
</div>

</div>

<?php if ($hasErrors): ?>
<div class="card" style="margin-top:1.5rem;border-left:4px solid #f59e0b">
    <h3 style="margin-top:0;color:#b45309">Warnings (<?= count($result['errors']) ?>)</h3>
    <p class="text-muted" style="font-size:.85rem;margin-top:0">
        These rows were skipped. Usually caused by missing foreign-key references — e.g. restoring Membership without CRM contacts.
    </p>
    <div style="max-height:300px;overflow-y:auto;font-family:monospace;font-size:.8rem;background:#fffbeb;border:1px solid #fde68a;border-radius:4px;padding:.75rem">
        <?php foreach ($result['errors'] as $err): ?>
        <div><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
