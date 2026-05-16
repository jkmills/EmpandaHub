<div class="page-header">
    <h1 class="page-title">Volunteer Shifts</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/volunteers" class="btn btn-secondary btn-sm">Roster</a>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?><a href="<?= APP_URL ?>/volunteers/shifts/create" class="btn btn-primary btn-sm">+ New Shift</a><?php endif; ?>
    </div>
</div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Max</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($shifts as $s): ?>
<tr>
    <td><?= htmlspecialchars($s['title'], ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($s['shift_date'], ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars(($s['start_time'] ?? '') . ($s['end_time'] ? ' – '.$s['end_time'] : ''), ENT_QUOTES,'UTF-8') ?></td>
    <td><?= $s['max_volunteers'] ?? '—' ?></td>
    <td class="d-flex gap-1">
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <form method="post" action="<?= APP_URL ?>/volunteers/shifts/<?= $s['id'] ?>/complete"><?= Csrf::field() ?><button class="btn btn-primary btn-sm" data-confirm="Mark shift complete and log hours?">Complete</button></form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$shifts): ?><tr><td colspan="5" class="text-muted" style="text-align:center">No shifts.</td></tr><?php endif; ?>
</tbody></table></div></div>
