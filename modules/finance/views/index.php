<div class="page-header">
    <h1 class="page-title">Finance Reports</h1>
    <a href="<?= APP_URL ?>/finance/export?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="btn btn-secondary btn-sm">Export CSV</a>
</div>

<form method="get" action="<?= APP_URL ?>/finance" class="card mb-2" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
    <div class="form-group" style="margin:0"><label>From</label><input type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES,'UTF-8') ?>"></div>
    <div class="form-group" style="margin:0"><label>To</label><input type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES,'UTF-8') ?>"></div>
    <div class="form-group" style="margin:0"><label>Fiscal Year</label><input type="number" name="fy" value="<?= $fy ?>" style="width:80px"></div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
</form>

<div class="stat-cards">
    <div class="stat-card"><div class="stat-label">Total Revenue</div><div class="stat-value">$<?= number_format($total_revenue, 0) ?></div><div class="stat-sub"><?= $from ?> – <?= $to ?></div></div>
    <div class="stat-card"><div class="stat-label">Donations</div><div class="stat-value">$<?= number_format($donations, 0) ?></div></div>
    <div class="stat-card"><div class="stat-label">Dues</div><div class="stat-value">$<?= number_format($dues, 0) ?></div></div>
    <div class="stat-card"><div class="stat-label">Event Revenue</div><div class="stat-value">$<?= number_format($events, 0) ?></div></div>
    <div class="stat-card"><div class="stat-label">Grant Income</div><div class="stat-value">$<?= number_format($grants, 0) ?></div></div>
    <div class="stat-card"><div class="stat-label">FY<?= $fy ?> Total</div><div class="stat-value">$<?= number_format($fy_total, 0) ?></div></div>
</div>

<div class="card">
<h3 style="margin-top:0">Transaction Ledger</h3>
<div class="table-wrap"><table>
<thead><tr><th>Date</th><th>Category</th><th>Direction</th><th>Amount</th><th>Contact</th><th>Description</th><th>FY</th></tr></thead>
<tbody>
<?php foreach ($ledger as $t): ?>
<tr>
    <td><?= htmlspecialchars($t['transaction_date'], ENT_QUOTES,'UTF-8') ?></td>
    <td><span class="badge badge-info"><?= htmlspecialchars($t['category'] ?? '', ENT_QUOTES,'UTF-8') ?></span></td>
    <td><?= htmlspecialchars($t['direction'], ENT_QUOTES,'UTF-8') ?></td>
    <td>$<?= number_format((float)$t['amount'], 2) ?></td>
    <td><?= htmlspecialchars(trim(($t['first_name'] ?? '').' '.($t['last_name'] ?? '')), ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($t['description'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= $t['fiscal_year'] ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$ledger): ?><tr><td colspan="7" class="text-muted" style="text-align:center">No transactions in range.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
