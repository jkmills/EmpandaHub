<div class="page-header">
    <h1 class="page-title">Volunteers</h1>
    <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/volunteers/shifts" class="btn btn-secondary btn-sm">Shifts</a>
        <?php if (Auth::hasRole('super_admin','admin','staff')): ?>
        <a href="<?= APP_URL ?>/volunteers/create" class="btn btn-primary btn-sm">+ Add Volunteer</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($pending && Auth::hasRole('super_admin','admin','staff')): ?>
<div class="card mb-2">
    <h3 style="margin-top:0">Pending Approvals (<?= count($pending) ?>)</h3>
    <div class="table-wrap"><table>
    <thead><tr><th>Volunteer</th><th>Date</th><th>Hours</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($pending as $h): ?>
    <tr>
        <td><?= htmlspecialchars($h['first_name'].' '.$h['last_name'], ENT_QUOTES,'UTF-8') ?></td>
        <td><?= htmlspecialchars($h['activity_date'], ENT_QUOTES,'UTF-8') ?></td>
        <td><?= $h['hours'] ?></td>
        <td><?= htmlspecialchars($h['description'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
        <td class="d-flex gap-1">
            <form method="post" action="<?= APP_URL ?>/volunteers/hours/<?= $h['id'] ?>/approve"><?= Csrf::field() ?><button class="btn btn-primary btn-sm">Approve</button></form>
            <form method="post" action="<?= APP_URL ?>/volunteers/hours/<?= $h['id'] ?>/reject"><?= Csrf::field() ?><button class="btn btn-danger btn-sm">Reject</button></form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<div class="card">
<div class="table-wrap"><table>
<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Skills</th><th>Active</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($roster as $v): ?>
<tr>
    <td><a href="<?= APP_URL ?>/volunteers/<?= $v['id'] ?>"><?= htmlspecialchars($v['first_name'].' '.$v['last_name'], ENT_QUOTES,'UTF-8') ?></a></td>
    <td><?= htmlspecialchars($v['email'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($v['phone'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars(mb_strimwidth($v['skills'] ?? '', 0, 40, '…'), ENT_QUOTES,'UTF-8') ?></td>
    <td><span class="badge <?= $v['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= $v['is_active'] ? 'Yes' : 'No' ?></span></td>
    <td><a href="<?= APP_URL ?>/volunteers/<?= $v['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$roster): ?><tr><td colspan="6" class="text-muted" style="text-align:center">No volunteers yet.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
