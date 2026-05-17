<div class="page-header">
    <h1 class="page-title">Donation #<?= $donation['id'] ?></h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/donors/<?= $donation['id'] ?>/receipt" class="btn btn-primary btn-sm" target="_blank">Print Receipt</a>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?><a href="<?= APP_URL ?>/donors/<?= $donation['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a><?php endif; ?>
        <a href="<?= APP_URL ?>/donors" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>
<div class="card" style="max-width:480px">
<table style="width:100%">
    <tr><td class="text-muted" style="width:35%">Donor</td><td><?= $donation['is_anonymous'] ? '<em>Anonymous</em>' : htmlspecialchars($donation['first_name'].' '.$donation['last_name'], ENT_QUOTES,'UTF-8') ?></td></tr>
    <tr><td class="text-muted">Amount</td><td><strong>$<?= number_format((float)$donation['amount'], 2) ?></strong></td></tr>
    <tr><td class="text-muted">Date</td><td><?= fmt_date($donation['donated_on']) ?></td></tr>
    <tr><td class="text-muted">Campaign</td><td><?= htmlspecialchars($donation['campaign_name'] ?? '—', ENT_QUOTES,'UTF-8') ?></td></tr>
    <tr><td class="text-muted">Method</td><td><?= htmlspecialchars($donation['method'] ?? '—', ENT_QUOTES,'UTF-8') ?></td></tr>
    <tr><td class="text-muted">Recurring</td><td><?= $donation['is_recurring'] ? ucfirst($donation['recur_interval'] ?? 'Yes') : 'No' ?></td></tr>
    <tr><td class="text-muted">Note</td><td><?= htmlspecialchars($donation['note'] ?? '', ENT_QUOTES,'UTF-8') ?></td></tr>
    <tr><td class="text-muted">Fiscal Year</td><td><?= htmlspecialchars($donation['fiscal_year'] ?? '', ENT_QUOTES,'UTF-8') ?></td></tr>
    <tr><td class="text-muted">Receipt</td><td>
        <?php if ($donation['receipt_sent']): ?>
        <span class="badge badge-success">Sent</span>
        <?php else: ?>
        <span class="badge badge-muted">Not sent</span>
        <?php endif; ?>
    </td></tr>
</table>
</div>
