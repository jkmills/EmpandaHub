<div class="page-header">
    <h1 class="page-title">Membership Tiers</h1>
    <a href="<?= APP_URL ?>/membership/tiers/create" class="btn btn-primary btn-sm">+ New Tier</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
<thead><tr><th>Name</th><th>Parent</th><th>Billing</th><th>Amount</th><th>Grace Days</th><th>Active</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($tiers as $t): ?>
<tr>
    <td><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($t['parent_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($t['billing_cycle'], ENT_QUOTES, 'UTF-8') ?></td>
    <td>$<?= number_format((float)$t['amount'], 2) ?></td>
    <td><?= $t['billing_cycle'] === 'lifetime' ? '—' : $t['grace_period_days'] ?></td>
    <td><span class="badge <?= $t['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= $t['is_active'] ? 'Yes' : 'No' ?></span></td>
    <td><a href="<?= APP_URL ?>/membership/tiers/<?= $t['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
