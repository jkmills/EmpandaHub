<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars($volunteer['first_name'].' '.$volunteer['last_name'], ENT_QUOTES,'UTF-8') ?></h1>
    <div class="d-flex gap-1">
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?><a href="<?= APP_URL ?>/volunteers/<?= $volunteer['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a><?php endif; ?>
        <a href="<?= APP_URL ?>/volunteers" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
<div class="card">
    <h3 style="margin-top:0">Details</h3>
    <table style="width:100%">
        <tr><td class="text-muted" style="width:35%">Email</td><td><?= htmlspecialchars($volunteer['email'] ?? '—', ENT_QUOTES,'UTF-8') ?></td></tr>
        <tr><td class="text-muted">Skills</td><td><?= nl2br(htmlspecialchars($volunteer['skills'] ?? '', ENT_QUOTES,'UTF-8')) ?></td></tr>
        <tr><td class="text-muted">Availability</td><td><?= nl2br(htmlspecialchars($volunteer['availability'] ?? '', ENT_QUOTES,'UTF-8')) ?></td></tr>
        <tr><td class="text-muted">Active</td><td><?= $volunteer['is_active'] ? 'Yes' : 'No' ?></td></tr>
    </table>
</div>
<div class="card">
    <h3 style="margin-top:0">Log Hours</h3>
    <?php if (Auth::hasRole('super_admin','admin','staff','volunteer')): ?>
    <form method="post" action="<?= APP_URL ?>/volunteers/<?= $volunteer['id'] ?>/hours">
        <?= Csrf::field() ?>
        <div class="form-group"><label>Date</label><input type="date" name="activity_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group"><label>Hours</label><input type="number" step="0.25" name="hours" placeholder="2.0" required></div>
        <div class="form-group"><label>Shift</label><select name="shift_id"><option value="">None</option><?php foreach ($shifts as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['title'].' ('.$s['shift_date'].')', ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Description</label><input name="description"></div>
        <button type="submit" class="btn btn-primary btn-sm">Log Hours</button>
    </form>
    <?php endif; ?>
</div>
</div>
<div class="card mt-2">
    <h3 style="margin-top:0">Hours Log</h3>
    <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Hours</th><th>Shift</th><th>Description</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($hours as $h): ?>
    <tr>
        <td><?= htmlspecialchars($h['activity_date'], ENT_QUOTES,'UTF-8') ?></td>
        <td><?= $h['hours'] ?></td>
        <td><?= htmlspecialchars($h['shift_title'] ?? '—', ENT_QUOTES,'UTF-8') ?></td>
        <td><?= htmlspecialchars($h['description'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
        <td><span class="badge <?= $h['status'] === 'approved' ? 'badge-success' : ($h['status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>"><?= htmlspecialchars($h['status'], ENT_QUOTES,'UTF-8') ?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$hours): ?><tr><td colspan="5" class="text-muted" style="text-align:center">No hours logged.</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
