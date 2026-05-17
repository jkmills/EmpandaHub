<div class="page-header">
    <h1 class="page-title">Memberships</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/membership/dues" class="btn btn-secondary btn-sm">Dues</a>
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
<thead>
    <tr>
        <th>Member</th>
        <th>Tier</th>
        <th>Status</th>
        <th>Dues Rate</th>
        <th>Next Due</th>
        <th>Last Paid</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php foreach ($memberships as $m): ?>
<?php
$badge = match($m['status']) {
    'active','lifetime' => 'badge-success',
    'grace'   => 'badge-warning',
    'expired' => 'badge-danger',
    default   => 'badge-muted',
};
$isOverdue = !empty($m['end_date']) && $m['end_date'] < date('Y-m-d') && $m['status'] === 'expired';
?>
<tr>
    <td><a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name'], ENT_QUOTES, 'UTF-8') ?></a></td>
    <td><?= htmlspecialchars($m['tier_name'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($m['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
    <td>
        <?php if ($m['billing_cycle'] === 'lifetime'): ?>
        <span class="text-muted">—</span>
        <?php else: ?>
        $<?= number_format((float)$m['dues_rate'], 2) ?> / <?= $m['billing_cycle'] ?>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($m['billing_cycle'] === 'lifetime'): ?>
        <span class="text-muted">—</span>
        <?php elseif (!empty($m['end_date'])): ?>
        <span <?= $isOverdue ? 'style="color:#dc2626;font-weight:600"' : '' ?>>
            <?= fmt_date($m['end_date']) ?>
        </span>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($m['last_paid_on'])): ?>
        <?= fmt_date($m['last_paid_on']) ?>
        <span class="text-muted" style="font-size:.78rem">($<?= number_format((float)$m['last_paid_amount'], 2) ?>)</span>
        <?php else: ?>
        <span class="text-muted">Never</span>
        <?php endif; ?>
    </td>
    <td class="d-flex gap-1">
        <a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>" class="btn btn-secondary btn-sm">View</a>
        <?php if (Auth::hasRole('super_admin','admin','staff') && $m['billing_cycle'] !== 'lifetime'): ?>
        <a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>#dues" class="btn btn-primary btn-sm">Pay</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$memberships): ?>
<tr><td colspan="7" class="text-muted" style="text-align:center">No memberships yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
