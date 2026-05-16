<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars($event['title'], ENT_QUOTES,'UTF-8') ?></h1>
    <div class="d-flex gap-1">
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/events/<?= $event['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
        <a href="<?= APP_URL ?>/events/<?= $event['id'] ?>/checkin" class="btn btn-secondary btn-sm">Mobile Check-In</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/events" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
<div class="card">
    <h3 style="margin-top:0">Details</h3>
    <table style="width:100%">
        <tr><td class="text-muted" style="width:35%">Date</td><td><?= htmlspecialchars($event['event_date'], ENT_QUOTES,'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Time</td><td><?= htmlspecialchars(($event['start_time'] ?? '') . ($event['end_time'] ? ' – '.$event['end_time'] : ''), ENT_QUOTES,'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Location</td><td><?= htmlspecialchars($event['location'] ?? '—', ENT_QUOTES,'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Registered</td><td><?= $event['reg_count'] ?><?= $event['capacity'] ? ' / '.$event['capacity'] : '' ?></td></tr>
        <tr><td class="text-muted">Price</td><td><?= $event['price'] > 0 ? '$'.number_format((float)$event['price'], 2) : 'Free' ?></td></tr>
        <tr><td class="text-muted">Status</td><td><?= $event['is_published'] ? 'Published' : 'Draft' ?></td></tr>
    </table>
    <?php if ($event['description']): ?><p class="text-muted mt-1"><?= nl2br(htmlspecialchars($event['description'], ENT_QUOTES,'UTF-8')) ?></p><?php endif; ?>
</div>
<div class="card">
    <h3 style="margin-top:0">Register Attendee</h3>
    <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
    <form method="post" action="<?= APP_URL ?>/events/<?= $event['id'] ?>/register">
        <?= Csrf::field() ?>
        <div class="form-group"><label>Contact</label><select name="contact_id"><option value="">Guest</option><?php foreach ($contacts as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['first_name'].' '.$c['last_name'], ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Name *</label><input name="name" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email"></div>
        <?php if ($event['price'] > 0): ?><div class="form-group"><label>Amount Paid</label><input type="number" step="0.01" name="amount_paid" value="<?= number_format((float)$event['price'], 2) ?>"></div><?php endif; ?>
        <button type="submit" class="btn btn-primary btn-sm">Register</button>
    </form>
    <?php endif; ?>
</div>
</div>
<div class="card mt-2">
    <h3 style="margin-top:0">Registrations</h3>
    <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Paid</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($regs as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['name'], ENT_QUOTES,'UTF-8') ?></td>
        <td><?= htmlspecialchars($r['email'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
        <td><span class="badge <?= $r['status'] === 'attended' ? 'badge-success' : ($r['status'] === 'waitlist' ? 'badge-warning' : ($r['status'] === 'cancelled' ? 'badge-muted' : 'badge-info')) ?>"><?= htmlspecialchars($r['status'], ENT_QUOTES,'UTF-8') ?></span></td>
        <td>$<?= number_format((float)$r['amount_paid'], 2) ?></td>
        <td class="d-flex gap-1">
            <?php if (Auth::hasRole('super_admin','admin','staff') && $r['status'] === 'registered'): ?>
            <form method="post" action="<?= APP_URL ?>/events/<?= $event['id'] ?>/registrations/<?= $r['id'] ?>/checkin"><?= Csrf::field() ?><button class="btn btn-primary btn-sm">Check In</button></form>
            <form method="post" action="<?= APP_URL ?>/events/<?= $event['id'] ?>/registrations/<?= $r['id'] ?>/cancel"><?= Csrf::field() ?><input name="refund_note" placeholder="Refund note…" style="width:120px"><button class="btn btn-danger btn-sm">Cancel</button></form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$regs): ?><tr><td colspan="5" class="text-muted" style="text-align:center">No registrations.</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
