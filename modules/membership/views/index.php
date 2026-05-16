<div class="page-header">
    <h1 class="page-title">Memberships</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/membership/expiring" class="btn btn-secondary btn-sm">Expiring (30d)</a>
        <a href="<?= APP_URL ?>/membership/overdue" class="btn btn-secondary btn-sm">Overdue</a>
        <a href="<?= APP_URL ?>/membership/tiers" class="btn btn-secondary btn-sm">Tiers</a>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/membership/create" class="btn btn-primary btn-sm">+ New</a>
        <?php endif; ?>
    </div>
</div>
<div class="card">
<div class="table-wrap">
<table>
<thead><tr><th>Member</th><th>Tier</th><th>Status</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($memberships as $m): ?>
<tr>
    <td><a href="<?= APP_URL ?>/crm/<?= $m['contact_id'] ?>"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name'], ENT_QUOTES, 'UTF-8') ?></a></td>
    <td><?= htmlspecialchars($m['tier_name'], ENT_QUOTES, 'UTF-8') ?></td>
    <td>
        <?php
        $badge = match($m['status']) {
            'active','lifetime' => 'badge-success',
            'grace'   => 'badge-warning',
            'expired' => 'badge-danger',
            default   => 'badge-muted',
        };
        ?>
        <span class="badge <?= $badge ?>"><?= htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8') ?></span>
    </td>
    <td><?= htmlspecialchars($m['start_date'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($m['end_date'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
    <td><a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$memberships): ?><tr><td colspan="6" class="text-muted" style="text-align:center">No memberships yet.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</div>
