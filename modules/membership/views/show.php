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
        <tr><td class="text-muted">Status</td><td>
            <?php
            $statusColors = ['active'=>'badge-success','lifetime'=>'badge-info','grace'=>'badge-warning','expired'=>'badge-danger','cancelled'=>'badge-muted'];
            $sc = $statusColors[$membership['status']] ?? 'badge-muted';
            ?>
            <span class="badge <?= $sc ?>"><?= htmlspecialchars(ucfirst($membership['status']), ENT_QUOTES, 'UTF-8') ?></span>
        </td></tr>
        <tr><td class="text-muted">Billing</td><td><?= htmlspecialchars(ucfirst($membership['billing_cycle']), ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Dues Rate</td><td><strong>$<?= number_format((float)$membership['amount'], 2) ?></strong><?= $membership['billing_cycle'] !== 'lifetime' ? ' / ' . $membership['billing_cycle'] : ' (optional)' ?></td></tr>
        <tr><td class="text-muted">Start Date</td><td><?= fmt_date($membership['start_date']) ?></td></tr>
        <?php if ($membership['billing_cycle'] !== 'lifetime'): ?>
        <tr><td class="text-muted">Next Due Date</td><td>
            <?php if (!empty($membership['end_date'])): ?>
                <?php
                $today = date('Y-m-d');
                $isOverdue = $membership['end_date'] < $today;
                ?>
                <span style="<?= $isOverdue ? 'color:var(--danger,#dc2626);font-weight:600' : '' ?>">
                    <?= fmt_date($membership['end_date']) ?>
                    <?= $isOverdue ? ' <span class="badge badge-danger">Overdue</span>' : '' ?>
                </span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td></tr>
        <tr><td class="text-muted">Grace Period</td><td><?= (int)$membership['grace_period_days'] ?> days</td></tr>
        <?php endif; ?>
    </table>
    <?php if ($membership['notes']): ?>
    <p class="text-muted mt-1" style="font-size:.88rem"><?= nl2br(htmlspecialchars($membership['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>
</div>

<div class="card" id="dues">
    <h3 style="margin-top:0">
        <?= $membership['billing_cycle'] === 'lifetime' ? 'Optional Contributions' : 'Dues Payments' ?>
    </h3>

    <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
    <form method="post" action="<?= APP_URL ?>/membership/<?= $membership['id'] ?>/dues" class="mb-2">
        <?= Csrf::field() ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem">
            <div class="form-group" style="margin:0">
                <label style="font-size:.78rem">Amount *</label>
                <input type="number" step="0.01" min="0.01" name="amount"
                       value="<?= number_format((float)$membership['amount'], 2) ?>" required>
            </div>
            <div class="form-group" style="margin:0">
                <label style="font-size:.78rem">Date Paid *</label>
                <input type="date" name="paid_on" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group" style="margin:0">
                <label style="font-size:.78rem">Due Date (period)</label>
                <input type="date" name="due_date" value="<?= htmlspecialchars($membership['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label style="font-size:.78rem">Method</label>
                <select name="method">
                    <option value="">— select —</option>
                    <?php foreach (['Check','Cash','Credit Card','ACH / Bank Transfer','Online','Other'] as $m): ?>
                    <option><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label style="font-size:.78rem">Reference / Check #</label>
                <input name="reference" placeholder="e.g. #1042">
            </div>
            <div class="form-group" style="margin:0">
                <label style="font-size:.78rem">Note</label>
                <input name="note" placeholder="Optional note">
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm mt-1">Record Payment</button>
    </form>
    <?php endif; ?>

    <div class="table-wrap">
    <table>
    <thead>
        <tr>
            <th>Paid On</th>
            <th>Due Date</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Reference</th>
            <th>Note</th>
            <?php if (Auth::hasRole('super_admin','admin','staff')): ?><th></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($dues as $d): ?>
    <tr>
        <td><?= htmlspecialchars($d['paid_on'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($d['due_date'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td>$<?= number_format((float)$d['amount'], 2) ?></td>
        <td><?= htmlspecialchars($d['method'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($d['reference'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($d['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <td><a href="<?= APP_URL ?>/membership/<?= $membership['id'] ?>/dues/<?= $d['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a></td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (!$dues): ?>
    <tr><td colspan="7" class="text-muted" style="text-align:center">No payments recorded yet.</td></tr>
    <?php endif; ?>
    </tbody>
    </table>
    </div>
</div>

</div>

<?php if (!empty($history)): ?>
<div class="card" style="margin-top:1.5rem">
    <h3 style="margin-top:0">Membership History</h3>
    <div class="table-wrap">
    <table style="font-size:.88rem">
    <thead>
        <tr><th>Date</th><th>Event</th><th>From</th><th>To</th><th>Notes</th></tr>
    </thead>
    <tbody>
    <?php foreach ($history as $h): ?>
    <tr>
        <td><?= fmt_date($h['created_at']) ?></td>
        <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $h['event_type'])), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($h['old_value'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($h['new_value'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($h['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
