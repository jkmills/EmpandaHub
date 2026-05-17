<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars($grant['title'], ENT_QUOTES,'UTF-8') ?></h1>
    <div class="d-flex gap-1">
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?><a href="<?= APP_URL ?>/grants/<?= $grant['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a><?php endif; ?>
        <a href="<?= APP_URL ?>/grants" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
<div class="card">
    <h3 style="margin-top:0">Grant Details</h3>
    <table style="width:100%">
        <tr><td class="text-muted" style="width:38%">Funder</td><td><?= htmlspecialchars($grant['funder_name'], ENT_QUOTES,'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Status</td><td><?php
        $gsc = ['prospect'=>'badge-muted','drafting'=>'badge-warning','submitted'=>'badge-info','awarded'=>'badge-success','declined'=>'badge-danger'][$grant['status']] ?? 'badge-muted';
        ?><span class="badge <?= $gsc ?>"><?= htmlspecialchars(ucfirst($grant['status']), ENT_QUOTES,'UTF-8') ?></span></td></tr>
        <tr><td class="text-muted">Requested</td><td><?= $grant['amount_requested'] ? '$'.number_format((float)$grant['amount_requested'], 2) : '—' ?></td></tr>
        <tr><td class="text-muted">Awarded</td><td><?= $grant['amount_awarded'] ? '$'.number_format((float)$grant['amount_awarded'], 2) : '—' ?></td></tr>
        <tr><td class="text-muted">Deadline</td><td><?= fmt_date($grant['deadline_date'] ?? null) ?></td></tr>
        <tr><td class="text-muted">Submitted</td><td><?= fmt_date($grant['submitted_date'] ?? null) ?></td></tr>
        <tr><td class="text-muted">Period</td><td><?= fmt_date($grant['period_start'] ?? null) ?> – <?= fmt_date($grant['period_end'] ?? null) ?></td></tr>
    </table>
    <?php if ($grant['notes']): ?><p class="text-muted mt-1"><?= nl2br(htmlspecialchars($grant['notes'], ENT_QUOTES,'UTF-8')) ?></p><?php endif; ?>
    <?php if (Auth::hasRole('super_admin','admin') && $grant['status'] !== 'awarded'): ?>
    <div class="mt-2" style="border-top:1px solid #e2e8f0;padding-top:.8rem">
        <h4 style="margin:0 0 .4rem">Mark as Awarded</h4>
        <form method="post" action="<?= APP_URL ?>/grants/<?= $grant['id'] ?>/award" class="d-flex gap-1 align-center">
            <?= Csrf::field() ?>
            <input type="number" step="0.01" name="amount_awarded" placeholder="Award amount" required>
            <input type="date" name="awarded_date" value="<?= date('Y-m-d') ?>">
            <button class="btn btn-primary btn-sm">Award</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<div class="card">
    <h3 style="margin-top:0">Report Due Dates</h3>
    <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
    <form method="post" action="<?= APP_URL ?>/grants/<?= $grant['id'] ?>/reports" class="mb-2">
        <?= Csrf::field() ?>
        <div class="form-group"><label style="font-size:.78rem">Report Title</label><input name="title" placeholder="Interim, Final…" required></div>
        <div class="form-group"><label style="font-size:.78rem">Due Date</label><input type="date" name="due_date" required></div>
        <button type="submit" class="btn btn-primary btn-sm">Add</button>
    </form>
    <?php endif; ?>
    <?php foreach ($reports as $r): ?>
    <div style="border-top:1px solid #e2e8f0;padding:.6rem 0">
        <div class="d-flex justify-between align-center">
            <div>
                <strong><?= htmlspecialchars($r['title'], ENT_QUOTES,'UTF-8') ?></strong>
                <p class="text-muted" style="margin:.1rem 0;font-size:.82rem">Due: <?= htmlspecialchars($r['due_date'], ENT_QUOTES,'UTF-8') ?> <?= $r['submitted_date'] ? '· Submitted: '.$r['submitted_date'] : '' ?></p>
            </div>
            <?php if (!$r['submitted_date'] && Auth::hasRole('super_admin','admin','staff')): ?>
            <form method="post" action="<?= APP_URL ?>/grants/<?= $grant['id'] ?>/reports/<?= $r['id'] ?>/submit">
                <?= Csrf::field() ?>
                <input type="hidden" name="submitted_date" value="<?= date('Y-m-d') ?>">
                <button class="btn btn-primary btn-sm">Mark Submitted</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$reports): ?><p class="text-muted">No reports scheduled.</p><?php endif; ?>
</div>
</div>
