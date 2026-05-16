<?php
$statuses = ['prospect' => 'Prospect', 'drafting' => 'Drafting', 'submitted' => 'Submitted', 'awarded' => 'Awarded', 'declined' => 'Declined'];
$colors   = ['prospect' => 'badge-muted', 'drafting' => 'badge-warning', 'submitted' => 'badge-info', 'awarded' => 'badge-success', 'declined' => 'badge-danger'];
?>
<div class="page-header">
    <h1 class="page-title">Grants Pipeline</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/grants/funders" class="btn btn-secondary btn-sm">Funders</a>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?><a href="<?= APP_URL ?>/grants/create" class="btn btn-primary btn-sm">+ New Grant</a><?php endif; ?>
    </div>
</div>

<?php if ($deadlines): ?>
<div class="card mb-2">
    <h3 style="margin-top:0">Upcoming Deadlines (30 days)</h3>
    <div class="table-wrap"><table>
    <thead><tr><th>Grant</th><th>Funder</th><th>Deadline</th><th>Days Left</th></tr></thead>
    <tbody>
    <?php foreach ($deadlines as $d): $urgency = $d['days_left'] <= 7 ? 'badge-danger' : ($d['days_left'] <= 14 ? 'badge-warning' : 'badge-info'); ?>
    <tr>
        <td><a href="<?= APP_URL ?>/grants/<?= $d['id'] ?>"><?= htmlspecialchars($d['title'], ENT_QUOTES,'UTF-8') ?></a></td>
        <td><?= htmlspecialchars($d['funder_name'], ENT_QUOTES,'UTF-8') ?></td>
        <td><?= htmlspecialchars($d['deadline_date'], ENT_QUOTES,'UTF-8') ?></td>
        <td><span class="badge <?= $urgency ?>"><?= $d['days_left'] ?> days</span></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem">
<?php foreach ($statuses as $key => $label): ?>
<div>
    <h3 style="margin:0 0 .6rem"><span class="badge <?= $colors[$key] ?>"><?= $label ?></span> <small class="text-muted">(<?= count($grouped[$key] ?? []) ?>)</small></h3>
    <?php foreach ($grouped[$key] ?? [] as $g): ?>
    <div class="card mb-1" style="padding:.9rem">
        <p style="margin:0 0 .3rem;font-weight:600"><a href="<?= APP_URL ?>/grants/<?= $g['id'] ?>"><?= htmlspecialchars($g['title'], ENT_QUOTES,'UTF-8') ?></a></p>
        <p class="text-muted" style="font-size:.82rem;margin:0"><?= htmlspecialchars($g['funder_name'], ENT_QUOTES,'UTF-8') ?></p>
        <?php if ($g['deadline_date']): ?><p class="text-muted" style="font-size:.78rem;margin:.2rem 0 0">Due: <?= htmlspecialchars($g['deadline_date'], ENT_QUOTES,'UTF-8') ?></p><?php endif; ?>
        <?php if ($g['amount_requested']): ?><p style="font-size:.82rem;margin:.2rem 0 0">$<?= number_format((float)$g['amount_requested'], 0) ?> requested</p><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if (empty($grouped[$key])): ?><p class="text-muted" style="font-size:.82rem">None</p><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
