<div class="page-header">
    <h1 class="page-title">Funders</h1>
    <div class="d-flex gap-1"><a href="<?= APP_URL ?>/grants" class="btn btn-secondary btn-sm">Grants</a><?php if (Auth::hasRole('super_admin','admin','staff')): ?><a href="<?= APP_URL ?>/grants/funders/create" class="btn btn-primary btn-sm">+ New Funder</a><?php endif; ?></div>
</div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Name</th><th>Contact</th><th>Email</th><th>Phone</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($funders as $f): ?>
<tr>
    <td><?= htmlspecialchars($f['name'], ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($f['contact_name'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($f['email'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?= htmlspecialchars($f['phone'] ?? '', ENT_QUOTES,'UTF-8') ?></td>
    <td><?php if (Auth::hasRole('super_admin','admin','staff')): ?><a href="<?= APP_URL ?>/grants/funders/<?= $f['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a><?php endif; ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$funders): ?><tr><td colspan="5" class="text-muted" style="text-align:center">No funders.</td></tr><?php endif; ?>
</tbody></table></div></div>
