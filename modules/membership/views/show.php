<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name'], ENT_QUOTES, 'UTF-8') ?> — Membership</h1>
    <div class="d-flex gap-1">
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/membership/<?= $membership['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/membership" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
<div class="card">
    <h3 style="margin-top:0">Details</h3>
    <table style="width:100%">
        <tr><td class="text-muted" style="width:40%">Tier</td><td><?= htmlspecialchars($membership['tier_name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Status</td><td><?= htmlspecialchars($membership['status'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Billing</td><td><?= htmlspecialchars($membership['billing_cycle'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Amount</td><td>$<?= number_format((float)$membership['amount'], 2) ?></td></tr>
        <tr><td class="text-muted">Start</td><td><?= htmlspecialchars($membership['start_date'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">End</td><td><?= htmlspecialchars($membership['end_date'] ?? 'Lifetime', ENT_QUOTES, 'UTF-8') ?></td></tr>
    </table>
    <?php if ($membership['notes']): ?>
    <p class="text-muted mt-1" style="font-size:.88rem"><?= nl2br(htmlspecialchars($membership['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-top:0">
        <?= $membership['billing_cycle'] === 'lifetime' ? 'Optional Contributions' : 'Dues Payments' ?>
    </h3>
    <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
    <form method="post" action="<?= APP_URL ?>/membership/<?= $membership['id'] ?>/dues" class="mb-2" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.4rem;align-items:end">
        <?= Csrf::field() ?>
        <div class="form-group" style="margin:0"><label style="font-size:.78rem">Amount</label><input type="number" step="0.01" name="amount" placeholder="0.00" required></div>
        <div class="form-group" style="margin:0"><label style="font-size:.78rem">Date</label><input type="date" name="paid_on" value="<?= date('Y-m-d') ?>" required></div>
        <div class="form-group" style="margin:0"><label style="font-size:.78rem">Method</label><input name="method" placeholder="Check, Card…"></div>
        <button type="submit" class="btn btn-primary btn-sm">Record</button>
    </form>
    <?php endif; ?>
    <div class="table-wrap">
    <table>
    <thead><tr><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
    <tbody>
    <?php foreach ($dues as $d): ?>
    <tr>
        <td><?= htmlspecialchars($d['paid_on'], ENT_QUOTES, 'UTF-8') ?></td>
        <td>$<?= number_format((float)$d['amount'], 2) ?></td>
        <td><?= htmlspecialchars($d['method'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$dues): ?><tr><td colspan="3" class="text-muted" style="text-align:center">No payments yet.</td></tr><?php endif; ?>
    </tbody>
    </table>
    </div>
</div>
</div>
