<div class="page-header">
    <h1 class="page-title">Donations</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/donors/campaigns" class="btn btn-secondary btn-sm">Campaigns</a>
        <a href="<?= APP_URL ?>/donors/lybunt" class="btn btn-secondary btn-sm">LYBUNT</a>
        <a href="<?= APP_URL ?>/donors/sybunt" class="btn btn-secondary btn-sm">SYBUNT</a>
        <a href="<?= APP_URL ?>/donors/batch-receipt" class="btn btn-secondary btn-sm">Batch Receipts</a>
        <a href="<?= APP_URL ?>/donors/receipt" class="btn btn-secondary btn-sm">Summary Receipt</a>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/donors/create" class="btn btn-primary btn-sm">+ Record</a>
        <?php endif; ?>
    </div>
</div>
<div class="card">
<div class="table-wrap">
<table>
<thead><tr><th>Donor</th><th>Amount</th><th>Date</th><th>Campaign</th><th>Method</th><th>Recurring</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($donations as $d): ?>
<tr>
    <td><?= $d['is_anonymous'] ? '<em>Anonymous</em>' : htmlspecialchars($d['first_name'] . ' ' . $d['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
    <td>$<?= number_format((float)$d['amount'], 2) ?></td>
    <td><?= htmlspecialchars($d['donated_on'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($d['campaign_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($d['method'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= $d['is_recurring'] ? '<span class="badge badge-info">Yes</span>' : '' ?></td>
    <td><a href="<?= APP_URL ?>/donors/<?= $d['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$donations): ?><tr><td colspan="7" class="text-muted" style="text-align:center">No donations yet.</td></tr><?php endif; ?>
</tbody>
<?php if ($donations): ?>
<tfoot>
    <tr style="background:#f8fafc">
        <td style="font-weight:600;padding-top:.5rem"><?= count($donations) ?> donation<?= count($donations) !== 1 ? 's' : '' ?></td>
        <td style="font-weight:700;padding-top:.5rem">$<?= number_format(array_sum(array_column($donations, 'amount')), 2) ?></td>
        <td colspan="5"></td>
    </tr>
</tfoot>
<?php endif; ?>
</table>
</div>
</div>
