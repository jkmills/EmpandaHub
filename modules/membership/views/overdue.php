<?php $totalOwed = array_sum(array_column($list, 'dues_rate')); ?>
<div class="page-header">
    <h1 class="page-title">Overdue Memberships</h1>
    <a href="<?= APP_URL ?>/membership" class="btn btn-secondary btn-sm">Back</a>
</div>

<?php if ($list): ?>
<div class="stat-cards mb-2">
    <div class="stat-card">
        <div class="stat-label">Overdue Members</div>
        <div class="stat-value"><?= count($list) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Dues Rate Owed</div>
        <div class="stat-value">$<?= number_format($totalOwed, 0) ?></div>
        <div class="stat-sub">one period per member</div>
    </div>
</div>
<?php endif; ?>

<div class="card">
<div class="table-wrap"><table>
<thead>
    <tr>
        <th>Member</th>
        <th>Email</th>
        <th>Tier</th>
        <th>Dues Rate</th>
        <th>Expired</th>
        <th>Last Paid</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php foreach ($list as $m): ?>
<tr>
    <td><a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>"><?= htmlspecialchars($m['first_name'].' '.$m['last_name'], ENT_QUOTES,'UTF-8') ?></a></td>
    <td><?= htmlspecialchars($m['email'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($m['tier_name'], ENT_QUOTES,'UTF-8') ?></td>
    <td>$<?= number_format((float)$m['dues_rate'], 2) ?> / <?= $m['billing_cycle'] ?></td>
    <td style="color:#dc2626;font-weight:600"><?= fmt_date($m['end_date']) ?></td>
    <td><?= !empty($m['last_paid_on']) ? fmt_date($m['last_paid_on']) : '<span class="text-muted">Never</span>' ?></td>
    <td class="d-flex gap-1">
        <a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>" class="btn btn-secondary btn-sm">View</a>
        <a href="<?= APP_URL ?>/membership/<?= $m['id'] ?>#dues" class="btn btn-primary btn-sm">Record Payment</a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="7" class="text-muted" style="text-align:center">No overdue memberships.</td></tr><?php endif; ?>
</tbody>
</table></div>
</div>
