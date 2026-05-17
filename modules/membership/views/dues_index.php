<?php
$total   = array_sum(array_column($payments, 'amount'));
$count   = count($payments);
$methods = array_count_values(array_filter(array_column($payments, 'method')));
arsort($methods);
?>
<div class="page-header">
    <h1 class="page-title">Dues Management</h1>
    <a href="<?= APP_URL ?>/membership" class="btn btn-secondary btn-sm">Back to Memberships</a>
</div>

<form method="get" action="<?= APP_URL ?>/membership/dues" class="card mb-2"
      style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
    <div class="form-group" style="margin:0"><label>From</label><input type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="form-group" style="margin:0"><label>To</label><input type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>"></div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= APP_URL ?>/membership/dues" class="btn btn-secondary btn-sm">Reset</a>
</form>

<div class="stat-cards" style="margin-bottom:1.5rem">
    <div class="stat-card">
        <div class="stat-label">Payments</div>
        <div class="stat-value"><?= $count ?></div>
        <div class="stat-sub"><?= fmt_date($from) ?> – <?= fmt_date($to) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Collected</div>
        <div class="stat-value">$<?= number_format($total, 0) ?></div>
        <div class="stat-sub">Avg $<?= $count ? number_format($total / $count, 2) : '0.00' ?> / payment</div>
    </div>
    <?php if ($methods): $topMethod = array_key_first($methods); ?>
    <div class="stat-card">
        <div class="stat-label">Top Payment Method</div>
        <div class="stat-value" style="font-size:1.3rem"><?= htmlspecialchars($topMethod, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="stat-sub"><?= $methods[$topMethod] ?> of <?= $count ?> payments</div>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
        <h3 style="margin:0">Payment History</h3>
        <a href="<?= APP_URL ?>/membership/dues/export?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="btn btn-secondary btn-sm">Export CSV</a>
    </div>
    <div class="table-wrap">
    <table>
    <thead>
        <tr>
            <th>Member</th>
            <th>Tier</th>
            <th>Paid On</th>
            <th>Due Date</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Reference</th>
            <th>Note</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
    <tr>
        <td><a href="<?= APP_URL ?>/membership/<?= $p['membership_id'] ?>"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name'], ENT_QUOTES, 'UTF-8') ?></a></td>
        <td><?= htmlspecialchars($p['tier_name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= fmt_date($p['paid_on']) ?></td>
        <td><?= fmt_date($p['due_date'] ?? null) ?></td>
        <td style="font-variant-numeric:tabular-nums">$<?= number_format((float)$p['amount'], 2) ?></td>
        <td><?= htmlspecialchars($p['method'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($p['reference'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($p['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td><a href="<?= APP_URL ?>/membership/<?= $p['membership_id'] ?>/dues/<?= $p['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$payments): ?>
    <tr><td colspan="9" class="text-muted" style="text-align:center">No dues payments in this date range.</td></tr>
    <?php endif; ?>
    </tbody>
    <?php if ($payments): ?>
    <tfoot>
        <tr style="background:#f8fafc">
            <td colspan="4" style="font-weight:600"><?= $count ?> payment<?= $count !== 1 ? 's' : '' ?></td>
            <td style="font-weight:700;font-variant-numeric:tabular-nums">$<?= number_format($total, 2) ?></td>
            <td colspan="4"></td>
        </tr>
    </tfoot>
    <?php endif; ?>
    </table>
    </div>
</div>
